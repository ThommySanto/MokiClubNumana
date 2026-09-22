<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . '/../includes/eventi_functions.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit('Prenotazione non trovata.');
}

$stmt = $conn->prepare(
    "SELECT p.*, e.titolo, e.data_evento, e.ora_evento, e.luogo, e.giorni_limite_modifica, e.immagine_copertina
     FROM prenotazioni p INNER JOIN eventi e ON e.id = p.evento_id
     WHERE p.token = ? LIMIT 1"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$prenotazione = $stmt->get_result()->fetch_assoc();

if (!$prenotazione) {
    http_response_code(404);
    exit('Prenotazione non trovata.');
}

$evento = [
    'titolo' => $prenotazione['titolo'],
    'data_evento' => $prenotazione['data_evento'],
    'giorni_limite_modifica' => $prenotazione['giorni_limite_modifica'],
];

$successo = "";
$errore = "";
$modificabile = prenotazioneModificabileDalCliente($prenotazione, $evento);

if (isset($_POST['annulla']) && app_is_post()) {
    app_require_csrf();

    if (!$modificabile) {
        $errore = "Il termine per annullare questa prenotazione è scaduto. Contattaci direttamente per assistenza.";
    } else {
        $upd = $conn->prepare("UPDATE prenotazioni SET stato = 'annullata' WHERE id = ?");
        $upd->bind_param("i", $prenotazione['id']);
        $upd->execute();
        $prenotazione['stato'] = 'annullata';
        $successo = "La tua prenotazione è stata annullata.";
        $modificabile = false;
    }
}

$tavole = $conn->query(
    "SELECT pt.*, et.nome AS nome_tavola FROM prenotazione_tavole pt
     INNER JOIN evento_tavole et ON et.id = pt.evento_tavola_id
     WHERE pt.prenotazione_id = " . (int) $prenotazione['id']
)->fetch_all(MYSQLI_ASSOC);

$etichetteStato = [
    'in_attesa' => 'In attesa di approvazione',
    'confermata' => 'Confermata',
    'rifiutata' => 'Rifiutata',
    'annullata' => 'Annullata',
];

$extraCssFiles = ['/assets/css/eventi.css'];
$pageTitle = "Gestisci la tua prenotazione — Moki Club Numana";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="evento-page-wrapper">
    <section class="evento-gestione-section">
        <h1>La tua prenotazione</h1>
        <h2 class="evento-gestione-titolo-evento"><?php echo htmlspecialchars($prenotazione['titolo']); ?></h2>
        <?php if (!empty($prenotazione['data_evento'])): ?>
            <p>📅 <?php echo htmlspecialchars(formattaDataIt($prenotazione['data_evento'])); ?></p>
        <?php endif; ?>

        <?php if ($successo): ?><p class="admin-msg admin-msg-success"><?php echo htmlspecialchars($successo); ?></p><?php endif; ?>
        <?php if ($errore): ?><p class="admin-msg admin-msg-error"><?php echo htmlspecialchars($errore); ?></p><?php endif; ?>

        <p class="evento-gestione-stato">
            Stato: <span class="admin-badge-stato admin-badge-stato-<?php echo $prenotazione['stato'] === 'confermata' ? 'pubblicato' : ($prenotazione['stato'] === 'in_attesa' ? 'bozza' : 'archiviato'); ?>">
                <?php echo htmlspecialchars($etichetteStato[$prenotazione['stato']] ?? $prenotazione['stato']); ?>
            </span>
        </p>

        <div class="evento-gestione-dettagli">
            <p><strong>Referente:</strong> <?php echo htmlspecialchars($prenotazione['referente_nome'] . ' ' . $prenotazione['referente_cognome']); ?></p>
            <p><strong>Telefono:</strong> <?php echo htmlspecialchars($prenotazione['referente_telefono']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($prenotazione['referente_email']); ?></p>
            <?php
                $etichettePagamento = ['bonifico' => 'Bonifico bancario (IBAN IT44K0538737471000004261922)', 'contanti_carta' => 'Contanti/Carta al Moki Club Numana, prima dell\'evento'];
            ?>
            <?php if (!empty($prenotazione['metodo_pagamento'])): ?>
                <p><strong>Metodo di pagamento:</strong> <?php echo htmlspecialchars($etichettePagamento[$prenotazione['metodo_pagamento']] ?? $prenotazione['metodo_pagamento']); ?></p>
            <?php endif; ?>
            <?php if (!empty($prenotazione['note'])): ?><p><strong>Note:</strong> <?php echo htmlspecialchars($prenotazione['note']); ?></p><?php endif; ?>

            <p><strong>Tavole/posti prenotati:</strong></p>
            <ul>
                <?php foreach ($tavole as $t): ?>
                    <li><?php echo htmlspecialchars($t['nome_tavola']); ?> — <?php echo htmlspecialchars($t['nome'] . ' ' . $t['cognome']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ($prenotazione['stato'] !== 'annullata'): ?>
            <?php if ($modificabile): ?>
                <form method="POST" onsubmit="return confirm('Sei sicuro di voler annullare questa prenotazione?');">
                    <?= app_csrf_input() ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <button type="submit" name="annulla" class="btn-primary evento-gestione-annulla-btn">Annulla prenotazione</button>
                </form>
            <?php else: ?>
                <p class="evento-gestione-non-modificabile">Il termine per annullare autonomamente questa prenotazione è scaduto. Per qualsiasi variazione contattaci direttamente.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
