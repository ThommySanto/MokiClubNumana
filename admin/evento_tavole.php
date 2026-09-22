<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . '/../includes/eventi_functions.php';

$eventoId = intval($_GET['evento_id'] ?? 0);
$stmtEv = $conn->prepare("SELECT * FROM eventi WHERE id = ?");
$stmtEv->bind_param("i", $eventoId);
$stmtEv->execute();
$evento = $stmtEv->get_result()->fetch_assoc();

if (!$evento) {
    http_response_code(404);
    exit('Evento non trovato.');
}

$successo = isset($_GET['creato']) ? "Evento creato. Aggiungi qui le tavole disponibili per questa uscita." : "";
$errore = "";

// ─── ATTIVA TAVOLA DA MODELLO ───
if (isset($_POST['attiva_da_modello'])) {
    app_require_csrf();
    $modelloId = intval($_POST['tavola_modello_id'] ?? 0);
    $stmtM = $conn->prepare("SELECT * FROM tavole_modello WHERE id = ?");
    $stmtM->bind_param("i", $modelloId);
    $stmtM->execute();
    $modello = $stmtM->get_result()->fetch_assoc();

    if ($modello) {
        $stmt = $conn->prepare(
            "INSERT INTO evento_tavole (evento_id, tavola_modello_id, nome, tipo, capacita, descrizione, foto1, foto2, foto3) VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            "iississss",
            $eventoId, $modelloId, $modello['nome'], $modello['tipo'], $modello['capacita'], $modello['descrizione'], $modello['foto1'], $modello['foto2'], $modello['foto3']
        );
        if ($stmt->execute()) {
            $successo = "Tavola \"" . $modello['nome'] . "\" attivata per questo evento.";
        } else {
            $errore = "Errore nell'attivazione della tavola.";
        }
    }
}

// ─── CREA / MODIFICA TAVOLA CUSTOM PER QUESTO EVENTO ───
if (isset($_POST['salva_tavola_evento'])) {
    app_require_csrf();

    $id = intval($_POST['evento_tavola_id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $tipo = ($_POST['tipo'] ?? '') === 'gruppo' ? 'gruppo' : 'singola';
    $capacita = max(1, intval($_POST['capacita'] ?? 1));
    $descrizione = trim($_POST['descrizione'] ?? '');
    $gestione = ($_POST['gestione_posti_liberi'] ?? '') === 'bloccato' ? 'bloccato' : 'libero';
    $ordine = intval($_POST['ordine'] ?? 0);

    if ($nome === '') {
        $errore = "Il nome della tavola è obbligatorio.";
    } else {
        $esistenti = [];
        if ($id > 0) {
            $stmtCur = $conn->prepare("SELECT foto1, foto2, foto3 FROM evento_tavole WHERE id = ? AND evento_id = ?");
            $stmtCur->bind_param("ii", $id, $eventoId);
            $stmtCur->execute();
            $esistenti = $stmtCur->get_result()->fetch_assoc() ?: [];
        }

        $foto = tavolaGestisciUploadFoto($_FILES, $esistenti);

        if ($id > 0) {
            $stmt = $conn->prepare(
                "UPDATE evento_tavole SET nome=?, tipo=?, capacita=?, descrizione=?, foto1=?, foto2=?, foto3=?, gestione_posti_liberi=?, ordine=? WHERE id=? AND evento_id=?"
            );
            $stmt->bind_param(
                "ssisssssiii",
                $nome, $tipo, $capacita, $descrizione, $foto['foto1'], $foto['foto2'], $foto['foto3'], $gestione, $ordine, $id, $eventoId
            );
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO evento_tavole (evento_id, nome, tipo, capacita, descrizione, foto1, foto2, foto3, gestione_posti_liberi, ordine) VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param(
                "ississsssi",
                $eventoId, $nome, $tipo, $capacita, $descrizione, $foto['foto1'], $foto['foto2'], $foto['foto3'], $gestione, $ordine
            );
        }

        if ($stmt->execute()) {
            $successo = "Tavola salvata con successo.";
        } else {
            $errore = "Errore durante il salvataggio della tavola.";
        }
    }
}

// ─── ATTIVA / DISATTIVA / ELIMINA TAVOLA EVENTO ───
if (isset($_GET['toggle'])) {
    if (app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $id = intval($_GET['toggle']);
        $conn->query("UPDATE evento_tavole SET attiva = 1 - attiva WHERE id = " . $id . " AND evento_id = " . $eventoId);
    }
}
if (isset($_GET['delete'])) {
    if (app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM evento_tavole WHERE id = ? AND evento_id = ?");
        $stmt->bind_param("ii", $id, $eventoId);
        $stmt->execute();
        $successo = "Tavola rimossa dall'evento.";
    }
}

$tavolaModifica = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM evento_tavole WHERE id = ? AND evento_id = ?");
    $stmt->bind_param("ii", $id, $eventoId);
    $stmt->execute();
    $tavolaModifica = $stmt->get_result()->fetch_assoc();
}

$tavoleEvento = eventoElencoTavoleConDisponibilita($conn, $eventoId);
$tutteTavoleEvento = $conn->query("SELECT * FROM evento_tavole WHERE evento_id = " . $eventoId . " ORDER BY ordine ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
$occupatiMap = eventoPostiOccupatiPerTavola($conn, $eventoId);

$modelli = $conn->query("SELECT * FROM tavole_modello ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

$extraCssFiles = ['/assets/css/admin-eventi.css'];
$pageTitle = "Tavole evento: " . $evento['titolo'];
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-container admin-eventi-container">
    <div class="admin-eventi-header">
        <h1>Tavole per: <?php echo htmlspecialchars($evento['titolo']); ?></h1>
        <div class="admin-eventi-header-actions">
            <a href="eventi.php?edit=<?php echo $eventoId; ?>" class="btn-primary admin-eventi-btn-secondary">Modifica evento e tariffe</a>
            <a href="eventi.php" class="btn-primary admin-eventi-btn-secondary">Torna agli Eventi</a>
        </div>
    </div>
    <p class="admin-eventi-hint">Qui gestisci solo le tavole disponibili. Prezzi e tariffe si impostano nella pagina di modifica evento.</p>

    <?php if ($successo): ?><p class="admin-msg admin-msg-success"><?php echo htmlspecialchars($successo); ?></p><?php endif; ?>
    <?php if ($errore): ?><p class="admin-msg admin-msg-error"><?php echo htmlspecialchars($errore); ?></p><?php endif; ?>

    <!-- ATTIVA DA MAGAZZINO -->
    <?php if (!empty($modelli)): ?>
    <div class="admin-eventi-form-card">
        <h2>Attiva tavola dal magazzino</h2>
        <form method="POST" class="admin-eventi-form-inline">
            <?= app_csrf_input() ?>
            <select name="tavola_modello_id" required>
                <option value="" disabled selected>Scegli una tavola modello…</option>
                <?php foreach ($modelli as $m): ?>
                    <option value="<?php echo (int) $m['id']; ?>"><?php echo htmlspecialchars($m['nome']); ?> (<?php echo htmlspecialchars($m['tipo']); ?>, max <?php echo (int) $m['capacita']; ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="attiva_da_modello" class="btn-primary">Attiva per questo evento</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- CREA / MODIFICA TAVOLA CUSTOM -->
    <div class="admin-eventi-form-card">
        <h2><?php echo $tavolaModifica ? 'Modifica tavola' : 'Aggiungi tavola personalizzata per questo evento'; ?></h2>
        <form method="POST" enctype="multipart/form-data" class="modern-form admin-eventi-form">
            <?= app_csrf_input() ?>
            <input type="hidden" name="evento_tavola_id" value="<?php echo (int) ($tavolaModifica['id'] ?? 0); ?>">

            <div class="form-row">
                <input type="text" name="nome" placeholder="Nome/numero tavola" required
                    value="<?php echo htmlspecialchars($tavolaModifica['nome'] ?? ''); ?>">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="singola" <?php echo (($tavolaModifica['tipo'] ?? 'singola') === 'singola') ? 'selected' : ''; ?>>Singola</option>
                        <option value="gruppo" <?php echo (($tavolaModifica['tipo'] ?? '') === 'gruppo') ? 'selected' : ''; ?>>Gruppo</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Capacità massima (persone)</label>
                    <input type="number" min="1" name="capacita" value="<?php echo (int) ($tavolaModifica['capacita'] ?? 1); ?>">
                </div>
                <div class="form-group">
                    <label>Posti residui su tavola di gruppo</label>
                    <select name="gestione_posti_liberi">
                        <option value="libero" <?php echo (($tavolaModifica['gestione_posti_liberi'] ?? 'libero') === 'libero') ? 'selected' : ''; ?>>Restano prenotabili da altri</option>
                        <option value="bloccato" <?php echo (($tavolaModifica['gestione_posti_liberi'] ?? '') === 'bloccato') ? 'selected' : ''; ?>>Tavola bloccata per il gruppo</option>
                    </select>
                </div>
            </div>

            <textarea name="descrizione" placeholder="Mini descrizione" rows="3"><?php echo htmlspecialchars($tavolaModifica['descrizione'] ?? ''); ?></textarea>

            <div class="form-group">
                <label>Ordine visualizzazione</label>
                <input type="number" name="ordine" value="<?php echo (int) ($tavolaModifica['ordine'] ?? 0); ?>">
            </div>

            <div class="form-group">
                <label>Foto (fino a 3)</label>
                <div class="form-row admin-eventi-foto-row">
                    <input type="file" name="foto1" accept=".jpg,.jpeg,.png,.webp">
                    <input type="file" name="foto2" accept=".jpg,.jpeg,.png,.webp">
                    <input type="file" name="foto3" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <div class="admin-eventi-foto-preview-row">
                    <?php foreach (['foto1', 'foto2', 'foto3'] as $f): if (!empty($tavolaModifica[$f])): ?>
                        <img src="/<?php echo htmlspecialchars($tavolaModifica[$f]); ?>" class="admin-eventi-foto-preview">
                    <?php endif; endforeach; ?>
                </div>
            </div>

            <button type="submit" name="salva_tavola_evento" class="btn-primary"><?php echo $tavolaModifica ? 'Salva modifiche' : 'Aggiungi tavola'; ?></button>
            <?php if ($tavolaModifica): ?><a href="evento_tavole.php?evento_id=<?php echo $eventoId; ?>" class="admin-eventi-btn-annulla">Annulla</a><?php endif; ?>
        </form>
    </div>

    <!-- LISTA TAVOLE EVENTO -->
    <div class="admin-eventi-cards-grid">
        <?php foreach ($tutteTavoleEvento as $t): ?>
        <?php $occ = $occupatiMap[$t['id']] ?? 0; $disp = max(0, (int) $t['capacita'] - $occ); ?>
        <div class="admin-eventi-tavola-card <?php echo !$t['attiva'] ? 'admin-eventi-tavola-disattiva' : ''; ?>">
            <?php if (!empty($t['foto1'])): ?>
                <img src="/<?php echo htmlspecialchars($t['foto1']); ?>" alt="<?php echo htmlspecialchars($t['nome']); ?>">
            <?php else: ?>
                <div class="admin-eventi-tavola-noimg">Nessuna foto</div>
            <?php endif; ?>
            <div class="admin-eventi-tavola-card-body">
                <h3><?php echo htmlspecialchars($t['nome']); ?></h3>
                <p><?php echo (int) $disp; ?> / <?php echo (int) $t['capacita']; ?> posti disponibili</p>
                <p><small><?php echo $t['attiva'] ? 'Attiva e visibile' : 'Disattivata (non visibile ai clienti)'; ?></small></p>
                <div class="admin-table-actions">
                    <a href="evento_tavole.php?evento_id=<?php echo $eventoId; ?>&edit=<?php echo (int) $t['id']; ?>" class="admin-action-link">Modifica</a>
                    <a href="evento_tavole.php?evento_id=<?php echo $eventoId; ?>&toggle=<?php echo (int) $t['id']; ?>&csrf_token=<?php echo urlencode(app_csrf_token()); ?>" class="admin-action-link"><?php echo $t['attiva'] ? 'Disattiva' : 'Attiva'; ?></a>
                    <a href="evento_tavole.php?evento_id=<?php echo $eventoId; ?>&delete=<?php echo (int) $t['id']; ?>&csrf_token=<?php echo urlencode(app_csrf_token()); ?>" class="admin-action-link admin-action-link-danger" onclick="return confirm('Rimuovere questa tavola dall\'evento?');">Elimina</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($tutteTavoleEvento)): ?><p class="admin-table-empty">Nessuna tavola ancora aggiunta a questo evento.</p><?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
