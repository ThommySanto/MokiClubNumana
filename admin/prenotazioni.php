<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . '/../includes/eventi_functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/email_templates/evento_template.php';

$successo = "";
$errore = "";

function linkGestionePrenotazione(string $token): string
{
    return 'https://mokiclub.infinityfreeapp.com/cliente/gestisci_prenotazione.php?token=' . $token;
}

// ─── CAMBIO STATO PRENOTAZIONE ───
if (isset($_POST['cambia_stato'])) {
    app_require_csrf();

    $id = intval($_POST['prenotazione_id'] ?? 0);
    $nuovoStato = $_POST['nuovo_stato'] ?? '';

    if (!in_array($nuovoStato, ['confermata', 'rifiutata', 'annullata', 'in_attesa'], true)) {
        $errore = "Stato non valido.";
    } else {
        $stmt = $conn->prepare("SELECT p.*, e.titolo, e.data_evento FROM prenotazioni p INNER JOIN eventi e ON e.id = p.evento_id WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $pren = $stmt->get_result()->fetch_assoc();

        if ($pren) {
            $upd = $conn->prepare("UPDATE prenotazioni SET stato = ?, letta_admin = 1 WHERE id = ?");
            $upd->bind_param("si", $nuovoStato, $id);
            $upd->execute();

            if ($nuovoStato === 'confermata' && !$pren['email_confermata_inviata'] && filter_var($pren['referente_email'], FILTER_VALIDATE_EMAIL)) {
                $html = templateEventoConfermata($pren['referente_nome'], $pren, linkGestionePrenotazione($pren['token']));
                inviaEmail('evento', $pren['referente_email'], 'Prenotazione confermata - ' . $pren['titolo'] . ' - MOKI CLUB NUMANA', $html);
                $flag = $conn->prepare("UPDATE prenotazioni SET email_confermata_inviata = 1 WHERE id = ?");
                $flag->bind_param("i", $id);
                $flag->execute();
            }

            if ($nuovoStato === 'rifiutata' && filter_var($pren['referente_email'], FILTER_VALIDATE_EMAIL)) {
                $html = templateEventoRifiutata($pren['referente_nome'], $pren);
                inviaEmail('evento', $pren['referente_email'], 'Aggiornamento prenotazione - ' . $pren['titolo'] . ' - MOKI CLUB NUMANA', $html);
            }

            $successo = "Stato prenotazione aggiornato.";
        } else {
            $errore = "Prenotazione non trovata.";
        }
    }
}

// ─── TOGGLE FLAG PAGATO ───
if (isset($_POST['toggla_pagato'])) {
    app_require_csrf();

    $id = intval($_POST['prenotazione_id'] ?? 0);
    $pagato = intval($_POST['pagato_valore'] ?? 0) === 1 ? 1 : 0;

    $upd = $conn->prepare("UPDATE prenotazioni SET pagato = ? WHERE id = ?");
    $upd->bind_param("ii", $pagato, $id);
    $upd->execute();

    $successo = $pagato ? "Prenotazione segnata come pagata." : "Prenotazione segnata come non pagata.";
}

// ─── SEGNA COME LETTA (apertura dettaglio) ───
if (isset($_GET['view'])) {
    $id = intval($_GET['view']);
    $conn->query("UPDATE prenotazioni SET letta_admin = 1 WHERE id = " . $id);
}

$eventoFiltro = intval($_GET['evento_id'] ?? 0);
$statoFiltro = $_GET['stato'] ?? '';

$sql = "SELECT p.*, e.titolo AS evento_titolo, e.data_evento
        FROM prenotazioni p
        INNER JOIN eventi e ON e.id = p.evento_id
        WHERE 1=1";
$params = [];
$types = "";

if ($eventoFiltro > 0) {
    $sql .= " AND p.evento_id = ?";
    $params[] = $eventoFiltro;
    $types .= "i";
}
if (in_array($statoFiltro, ['in_attesa', 'confermata', 'rifiutata', 'annullata'], true)) {
    $sql .= " AND p.stato = ?";
    $params[] = $statoFiltro;
    $types .= "s";
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$prenotazioni = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// tavole per ciascuna prenotazione (con tariffa e importo)
$tavolePerPrenotazione = [];
$totalePerPrenotazione = [];
if (!empty($prenotazioni)) {
    $ids = array_map(fn($p) => (int) $p['id'], $prenotazioni);
    $idsStr = implode(',', $ids);
    $res = $conn->query(
        "SELECT pp.prenotazione_id, pp.nome, pp.cognome, pp.tipo_tariffa, pp.importo_tariffa,
                COALESCE(et.nome, 'Tavola propria') AS nome_tavola
         FROM prenotazione_partecipanti pp
         LEFT JOIN prenotazione_tavole pt ON pt.id = pp.prenotazione_tavola_id
         LEFT JOIN evento_tavole et ON et.id = pt.evento_tavola_id
         WHERE pp.prenotazione_id IN ($idsStr)
         ORDER BY nome_tavola ASC, pp.cognome ASC"
    );
    while ($row = $res->fetch_assoc()) {
        $tavolePerPrenotazione[$row['prenotazione_id']][] = $row;
        $totalePerPrenotazione[$row['prenotazione_id']] = ($totalePerPrenotazione[$row['prenotazione_id']] ?? 0) + (float) $row['importo_tariffa'];
    }
}

$labelsTariffaAdmin = ['tesserato' => 'Tesserato', 'non_tesserato' => 'Non tesserato', 'tavola_propria' => 'Tavola propria'];
$labelsPagamentoAdmin = ['bonifico' => '🏦 Bonifico (IBAN IT44K0538737471000004261922)', 'contanti_carta' => '💶 Contanti/Carta al Moki Club'];

$eventiList = $conn->query("SELECT id, titolo FROM eventi ORDER BY data_evento DESC")->fetch_all(MYSQLI_ASSOC);

$extraCssFiles = ['/assets/css/admin-eventi.css'];
$pageTitle = "Prenotazioni Eventi - Moki Club Numana";
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-container admin-eventi-container">
    <div class="admin-eventi-header">
        <h1>Prenotazioni Eventi</h1>
        <div class="admin-eventi-header-actions">
            <?php if ($eventoFiltro > 0): ?>
                <a href="export_prenotazioni_excel.php?evento_id=<?php echo $eventoFiltro; ?>" class="btn-primary admin-eventi-btn-export" target="_blank" rel="noopener" onclick="this.href='export_prenotazioni_excel.php?evento_id=<?php echo $eventoFiltro; ?>&nocache=' + Date.now();">📊 Esporta Excel</a>
            <?php endif; ?>
            <a href="eventi.php" class="btn-primary admin-eventi-btn-secondary">Torna agli Eventi</a>
        </div>
    </div>

    <?php if ($eventoFiltro === 0): ?>
        <p class="admin-eventi-hint">Seleziona un evento dal filtro qui sotto per poter esportare l'elenco prenotazioni in Excel.</p>
    <?php endif; ?>

    <?php if ($successo): ?><p class="admin-msg admin-msg-success"><?php echo htmlspecialchars($successo); ?></p><?php endif; ?>
    <?php if ($errore): ?><p class="admin-msg admin-msg-error"><?php echo htmlspecialchars($errore); ?></p><?php endif; ?>

    <form method="GET" class="admin-eventi-filtri">
        <select name="evento_id" onchange="this.form.submit()">
            <option value="0">Tutti gli eventi</option>
            <?php foreach ($eventiList as $e): ?>
                <option value="<?php echo (int) $e['id']; ?>" <?php echo $eventoFiltro === (int) $e['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['titolo']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="stato" onchange="this.form.submit()">
            <option value="">Tutti gli stati</option>
            <option value="in_attesa" <?php echo $statoFiltro === 'in_attesa' ? 'selected' : ''; ?>>In attesa</option>
            <option value="confermata" <?php echo $statoFiltro === 'confermata' ? 'selected' : ''; ?>>Confermata</option>
            <option value="rifiutata" <?php echo $statoFiltro === 'rifiutata' ? 'selected' : ''; ?>>Rifiutata</option>
            <option value="annullata" <?php echo $statoFiltro === 'annullata' ? 'selected' : ''; ?>>Annullata</option>
        </select>
    </form>

    <div class="admin-eventi-prenotazioni-list">
        <?php foreach ($prenotazioni as $p): ?>
        <div class="admin-prenotazione-card <?php echo !$p['letta_admin'] ? 'admin-prenotazione-nuova' : ''; ?>">
            <div class="admin-prenotazione-head">
                <div>
                    <strong><?php echo htmlspecialchars($p['referente_nome'] . ' ' . $p['referente_cognome']); ?></strong>
                    <?php if (!$p['letta_admin']): ?><span class="admin-badge-nuove">Nuova</span><?php endif; ?>
                    <br><small><?php echo htmlspecialchars($p['evento_titolo']); ?> — <?php echo htmlspecialchars(formattaDataIt($p['data_evento'])) ?: '—'; ?></small>
                </div>
                <span class="admin-badge-stato admin-badge-stato-<?php echo htmlspecialchars($p['stato'] === 'in_attesa' ? 'bozza' : ($p['stato'] === 'confermata' ? 'pubblicato' : 'archiviato')); ?>">
                    <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($p['stato']))); ?>
                </span>
            </div>

            <div class="admin-prenotazione-body">
                <p>📞 <?php echo htmlspecialchars($p['referente_telefono']); ?> &nbsp; ✉️ <?php echo htmlspecialchars($p['referente_email']); ?></p>
                <p><strong>Modalità:</strong> <?php echo htmlspecialchars(ucfirst($p['modalita'])); ?> — <?php echo (int) $p['numero_persone']; ?> persone</p>
                <p><strong>Pagamento:</strong> <?php echo htmlspecialchars($labelsPagamentoAdmin[$p['metodo_pagamento'] ?? ''] ?? 'Non specificato'); ?></p>
                <?php if (!empty($p['note'])): ?><p><strong>Note:</strong> <?php echo htmlspecialchars($p['note']); ?></p><?php endif; ?>

                <form method="POST" class="admin-prenotazione-pagato-form">
                    <?= app_csrf_input() ?>
                    <input type="hidden" name="prenotazione_id" value="<?php echo (int) $p['id']; ?>">
                    <input type="hidden" name="toggla_pagato" value="1">
                    <input type="hidden" name="pagato_valore" value="<?php echo !empty($p['pagato']) ? 0 : 1; ?>">
                    <label class="admin-prenotazione-pagato-label <?php echo !empty($p['pagato']) ? 'is-pagato' : ''; ?>">
                        <input type="checkbox" <?php echo !empty($p['pagato']) ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <?php echo !empty($p['pagato']) ? '✅ Pagato' : '☐ Da pagare — spunta quando il cliente paga'; ?>
                    </label>
                </form>
                <?php
                    $partecipanti = $tavolePerPrenotazione[$p['id']] ?? [];
                    $perTavola = [];
                    foreach ($partecipanti as $pt) {
                        $perTavola[$pt['nome_tavola']][] = $pt;
                    }
                ?>
                <div class="admin-prenotazione-tavole">
                    <?php foreach ($perTavola as $nomeTavola => $persone): ?>
                        <p class="admin-prenotazione-tavola-titolo"><strong>🏄 <?php echo htmlspecialchars($nomeTavola); ?></strong></p>
                        <ul>
                            <?php foreach ($persone as $pers): ?>
                                <li>
                                    <?php echo htmlspecialchars($pers['nome'] . ' ' . $pers['cognome']); ?>
                                    — <?php echo htmlspecialchars($labelsTariffaAdmin[$pers['tipo_tariffa']] ?? $pers['tipo_tariffa']); ?>
                                    — <strong><?php echo number_format((float) $pers['importo_tariffa'], 2, ',', ''); ?>€</strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                    <?php if (!empty($partecipanti)): ?>
                        <p class="admin-prenotazione-totale"><strong>Totale prenotazione: <?php echo number_format($totalePerPrenotazione[$p['id']] ?? 0, 2, ',', ''); ?>€</strong></p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" class="admin-prenotazione-actions">
                <?= app_csrf_input() ?>
                <input type="hidden" name="prenotazione_id" value="<?php echo (int) $p['id']; ?>">
                <?php if ($p['stato'] !== 'confermata'): ?>
                    <button type="submit" name="cambia_stato" value="1" onclick="this.form.nuovo_stato.value='confermata'" class="admin-action-link admin-action-link-success">Approva</button>
                <?php endif; ?>
                <?php if ($p['stato'] !== 'rifiutata'): ?>
                    <button type="submit" name="cambia_stato" value="1" onclick="this.form.nuovo_stato.value='rifiutata'" class="admin-action-link admin-action-link-danger">Rifiuta</button>
                <?php endif; ?>
                <?php if ($p['stato'] !== 'in_attesa'): ?>
                    <button type="submit" name="cambia_stato" value="1" onclick="this.form.nuovo_stato.value='in_attesa'" class="admin-action-link">Rimetti in attesa</button>
                <?php endif; ?>
                <input type="hidden" name="nuovo_stato" value="">
            </form>
        </div>
        <?php endforeach; ?>
        <?php if (empty($prenotazioni)): ?><p class="admin-table-empty">Nessuna prenotazione trovata.</p><?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>