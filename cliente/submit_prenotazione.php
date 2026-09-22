<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';

if (!app_is_post()) {
    http_response_code(405);
    exit('Metodo non consentito');
}

app_require_csrf();

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . '/../includes/eventi_functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/email_templates/evento_template.php';

$rlKey = 'prenotazione_evento_' . app_request_ip();
if (app_rate_limit_is_blocked($rlKey, 8, 3600)) {
    http_response_code(429);
    exit('Troppe richieste. Riprova più tardi.');
}
app_rate_limit_increment($rlKey, 3600);

$eventoId = intval($_POST['evento_id'] ?? 0);
$referenteNome = trim($_POST['referente_nome'] ?? '');
$referenteCognome = trim($_POST['referente_cognome'] ?? '');
$referenteTelefono = trim($_POST['referente_telefono'] ?? '');
$referenteEmail = trim($_POST['referente_email'] ?? '');
$note = trim($_POST['note'] ?? '');
$posti = json_decode($_POST['posti_json'] ?? '[]', true);
$tipoPartecipazione = trim($_POST['tipo_partecipazione'] ?? 'noleggio');

$stmtEvento = $conn->prepare("SELECT * FROM eventi WHERE id = ? AND stato = 'pubblicato'");
$stmtEvento->bind_param("i", $eventoId);
$stmtEvento->execute();
$evento = $stmtEvento->get_result()->fetch_assoc();

if (!$evento) {
    http_response_code(404);
    exit('Evento non disponibile.');
}

$modalitaTariffeEvento = $evento['modalita_tariffe'] ?? 'dettagliata';
if ($modalitaTariffeEvento === 'semplice' && $tipoPartecipazione === 'propria') {
    http_response_code(422);
    exit('Per questo evento la quota è unica e non è prevista la tavola propria.');
}

// ─── METODO DI PAGAMENTO (checkbox: solo informativo per lo staff) ───
$metodiPagamentoValidi = ['bonifico', 'contanti_carta'];
$metodiPagamentoInviati = $_POST['metodo_pagamento'] ?? [];
if (!is_array($metodiPagamentoInviati)) {
    $metodiPagamentoInviati = [$metodiPagamentoInviati];
}
$metodiPagamentoInviati = array_values(array_intersect($metodiPagamentoInviati, $metodiPagamentoValidi));

if (count($metodiPagamentoInviati) !== 1) {
    http_response_code(422);
    exit('Seleziona un metodo di pagamento (bonifico oppure contanti/carta).');
}
$metodoPagamento = $metodiPagamentoInviati[0];

if ($referenteNome === '' || $referenteCognome === '' || $referenteTelefono === '') {
    http_response_code(422);
    exit('Compila tutti i campi obbligatori.');
}

if (!filter_var($referenteEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    exit('Indirizzo email non valido.');
}

if (empty($_POST['privacy'])) {
    http_response_code(422);
    exit('Devi accettare la privacy policy.');
}

$tariffe = eventoOttieniTariffe($conn, $eventoId);

// ─── GESTIONE TAVOLA PROPRIA ───
if ($tipoPartecipazione === 'propria') {
    $numeroPersone = 1;
    $modalita = 'singola';
    $posti = [];
} else {
    if (!is_array($posti) || empty($posti)) {
        http_response_code(422);
        exit('Seleziona almeno una tavola prima di inviare la prenotazione.');
    }

    $disponibilita = [];
    foreach (eventoElencoTavoleConDisponibilita($conn, $eventoId) as $t) {
        $disponibilita[(int) $t['id']] = $t['posti_disponibili'];
    }

    $richiestePerTavola = [];
    foreach ($posti as $p) {
        $tId = intval($p['evento_tavola_id'] ?? 0);
        $nome = trim($p['nome'] ?? '');
        $cognome = trim($p['cognome'] ?? '');

        if ($tId <= 0 || $nome === '' || $cognome === '') {
            http_response_code(422);
            exit('Dati mancanti per una delle persone/tavole selezionate.');
        }

        $richiestePerTavola[$tId] = ($richiestePerTavola[$tId] ?? 0) + 1;
    }

    foreach ($richiestePerTavola as $tId => $richiesti) {
        if (!isset($disponibilita[$tId]) || $disponibilita[$tId] < $richiesti) {
            http_response_code(409);
            exit('Una delle tavole selezionate non è più disponibile nella quantità richiesta. Ricarica la pagina e riprova.');
        }
    }

    $numeroPersone = count($posti);
    $modalita = $numeroPersone > 1 ? 'gruppo' : 'singola';
}

$conn->begin_transaction();
try {
    $token = prenotazioneGeneraToken($conn);
    $ip = app_request_ip();

    $stmtIns = $conn->prepare(
        "INSERT INTO prenotazioni (evento_id, modalita, referente_nome, referente_cognome, referente_telefono, referente_email, numero_persone, note, token, ip_richiesta, metodo_pagamento)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmtIns->bind_param(
        "isssssissss",
        $eventoId, $modalita, $referenteNome, $referenteCognome, $referenteTelefono, $referenteEmail, $numeroPersone, $note, $token, $ip, $metodoPagamento
    );
    $stmtIns->execute();
    $prenotazioneId = $stmtIns->insert_id;

    $tavoleRiepilogo = [];
    $nomiTavole = [];

    if ($tipoPartecipazione === 'propria') {
        // Con tavola propria: solo referente - verifica se è tesserato (email/telefono in DB), come nelle altre modalità
        $tipoInfo = determinaTipoTariffa($conn, $eventoId, $referenteNome, $referenteCognome, $referenteEmail, $referenteTelefono, true);
        $tipoTariffa = $tipoInfo['tipo'];
        $iscritto_id = $tipoInfo['iscritto_id'];
        $importoTariffa = $tariffe[$tipoTariffa];

        $stmtPart = $conn->prepare(
            "INSERT INTO prenotazione_partecipanti (prenotazione_id, nome, cognome, email, telefono, tipo_tariffa, importo_tariffa, verificato_db, iscritto_id)
             VALUES (?,?,?,?,?,?,?,1,?)"
        );
        $stmtPart->bind_param(
            "isssssdi",
            $prenotazioneId, $referenteNome, $referenteCognome, $referenteEmail, $referenteTelefono, $tipoTariffa, $importoTariffa, $iscritto_id
        );
        $stmtPart->execute();

        $tavoleRiepilogo[] = [
            'nome' => $referenteNome,
            'cognome' => $referenteCognome,
            'nome_tavola' => 'Tavola propria',
            'tipo_tariffa' => $tipoTariffa,
            'importo_tariffa' => $importoTariffa
        ];
    } else {
        // Con noleggio: verifica e salva ogni partecipante
        $res = $conn->query("SELECT id, nome FROM evento_tavole WHERE evento_id = " . $eventoId);
        while ($row = $res->fetch_assoc()) {
            $nomiTavole[(int) $row['id']] = $row['nome'];
        }

        $stmtTavole = $conn->prepare(
            "INSERT INTO prenotazione_tavole (prenotazione_id, evento_tavola_id, nome, cognome, posti_occupati) VALUES (?,?,?,?,1)"
        );

        $stmtPart = $conn->prepare(
            "INSERT INTO prenotazione_partecipanti (prenotazione_id, prenotazione_tavola_id, nome, cognome, email, telefono, tipo_tariffa, importo_tariffa, verificato_db, iscritto_id)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        );

        foreach ($posti as $p) {
            $tId = intval($p['evento_tavola_id']);
            $nome = trim($p['nome']);
            $cognome = trim($p['cognome']);
            $email = trim($p['email'] ?? '');
            $telefono = trim($p['telefono'] ?? '');

            $stmtTavole->bind_param("iiss", $prenotazioneId, $tId, $nome, $cognome);
            $stmtTavole->execute();
            $prenotazioneTavolaId = $stmtTavole->insert_id;

            $tipoInfo = determinaTipoTariffa($conn, $eventoId, $nome, $cognome, $email, $telefono, false);
            $tipoTariffa = $tipoInfo['tipo'];
            $iscritto = $tipoInfo['iscritto'];
            $iscritto_id = $tipoInfo['iscritto_id'];
            $importoTariffa = $tariffe[$tipoTariffa];

            $verificatoDb = 1;
            $stmtPart->bind_param(
                "iisssssdii",
                $prenotazioneId, $prenotazioneTavolaId, $nome, $cognome, $email, $telefono, $tipoTariffa, $importoTariffa, $verificatoDb, $iscritto_id
            );
            $stmtPart->execute();

            $tavoleRiepilogo[] = [
                'nome' => $nome,
                'cognome' => $cognome,
                'nome_tavola' => $nomiTavole[$tId] ?? '',
                'tipo_tariffa' => $tipoTariffa,
                'importo_tariffa' => $importoTariffa
            ];
        }
    }

    $conn->commit();
} catch (Throwable $e) {
    $erroreMysqli = $conn->error; // va letto PRIMA del rollback, altrimenti ROLLBACK lo azzera
    $conn->rollback();
    $dettaglioErrore = $e->getMessage() . ' | mysqli_error: ' . $erroreMysqli;
    error_log('Errore submit_prenotazione: ' . $dettaglioErrore);
    http_response_code(500);
    // --- DEBUG TEMPORANEO: rimuovere dopo la diagnosi ---
    if (($_COOKIE['moki_debug'] ?? '') === 'moki2026debug') {
        exit('Errore durante il salvataggio della prenotazione (DEBUG): ' . htmlspecialchars($dettaglioErrore));
    }
    // --- FINE DEBUG TEMPORANEO ---
    exit('Errore durante il salvataggio della prenotazione. Riprova più tardi.');
}

$linkGestione = 'https://mokiclub.infinityfreeapp.com/cliente/gestisci_prenotazione.php?token=' . $token;

$htmlCliente = templateEventoInAttesa($referenteNome, $evento, $tavoleRiepilogo, $linkGestione, $metodoPagamento);
inviaEmail('evento', $referenteEmail, 'Richiesta ricevuta - ' . $evento['titolo'] . ' - MOKI CLUB NUMANA', $htmlCliente);

$flag = $conn->prepare("UPDATE prenotazioni SET email_bloccata_inviata = 1 WHERE id = ?");
$flag->bind_param("i", $prenotazioneId);
$flag->execute();

$prenotazioneArr = [
    'referente_nome' => $referenteNome,
    'referente_cognome' => $referenteCognome,
    'referente_telefono' => $referenteTelefono,
    'referente_email' => $referenteEmail,
    'metodo_pagamento' => $metodoPagamento,
];
$staffEmail = app_mail_secret('SMTP_USER', null);
if ($staffEmail) {
    $htmlAdmin = templateEventoNotificaAdmin($evento, $prenotazioneArr, $tavoleRiepilogo);
    inviaEmail('evento', $staffEmail, 'Nuova prenotazione - ' . $evento['titolo'], $htmlAdmin);
}

$pageTitle = "Richiesta Inviata";
$extraCssFiles = ['/assets/css/cliente-submit-result.css'];
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-container cliente-submit-main">
    <div class="success-card cliente-submit-card">
        <div class="success-icon cliente-submit-icon">✅</div>
        <h1 class="cliente-submit-title">Richiesta Inviata!</h1>
        <p class="cliente-submit-message">
            Grazie <strong><?php echo htmlspecialchars($referenteNome); ?></strong>, la tua richiesta di prenotazione per
            <strong><?php echo htmlspecialchars($evento['titolo']); ?></strong> è stata registrata ed è ora
            <strong>in attesa di approvazione</strong>.<br>
            Ti abbiamo inviato un'email di riepilogo a <?php echo htmlspecialchars($referenteEmail); ?> con un link
            personale per gestire la tua prenotazione.
        </p>

        <?php $labelPagamento = ['bonifico' => 'Bonifico bancario (IBAN IT44K0538737471000004261922)', 'contanti_carta' => 'Contanti/Carta al Moki Club Numana, prima dell\'evento']; ?>
        <p class="cliente-submit-message"><strong>Metodo di pagamento scelto:</strong> <?php echo htmlspecialchars($labelPagamento[$metodoPagamento] ?? $metodoPagamento); ?></p>

        <?php $totale = 0; foreach ($tavoleRiepilogo as $t) { $totale += (float) $t['importo_tariffa']; } ?>
        <div class="cliente-submit-riepilogo">
            <h3>Riepilogo importi:</h3>
            <ul>
                <?php foreach ($tavoleRiepilogo as $t): ?>
                <li>
                    <?php echo htmlspecialchars($t['nome'] . ' ' . $t['cognome']); ?>
                    — <?php echo htmlspecialchars($t['nome_tavola']); ?>
                    — <?php 
                        $labels = ['tesserato' => 'Tesserato', 'non_tesserato' => 'Non tesserato', 'tavola_propria' => 'Tavola propria'];
                        echo htmlspecialchars($labels[$t['tipo_tariffa']] ?? $t['tipo_tariffa']);
                    ?>
                    — <strong><?php echo number_format($t['importo_tariffa'], 2, ',', ''); ?>€</strong>
                </li>
                <?php endforeach; ?>
            </ul>
            <p class="cliente-submit-totale"><strong>Totale: <?php echo number_format($totale, 2, ',', ''); ?>€</strong></p>
        </div>

        <a href="/index.php" class="btn-primary cliente-submit-link">Torna alla Home</a>
    </div>
</main>

<?php
require_once __DIR__ . "/../includes/footer.php";
$conn->close();
exit();