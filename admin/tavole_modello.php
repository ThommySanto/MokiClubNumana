<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . '/../includes/eventi_functions.php';

$successo = "";
$errore = "";

if (isset($_GET['delete'])) {
    if (!app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $errore = "Richiesta non valida (CSRF).";
    } else {
        $id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM tavole_modello WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $successo = "Tavola modello eliminata.";
    }
}

if (isset($_POST['salva_tavola'])) {
    app_require_csrf();

    $id = isset($_POST['tavola_id']) ? intval($_POST['tavola_id']) : 0;
    $nome = trim($_POST['nome'] ?? '');
    $tipo = ($_POST['tipo'] ?? '') === 'gruppo' ? 'gruppo' : 'singola';
    $capacita = max(1, intval($_POST['capacita'] ?? 1));
    $descrizione = trim($_POST['descrizione'] ?? '');

    if ($nome === '') {
        $errore = "Il nome della tavola è obbligatorio.";
    } else {
        $esistenti = [];
        if ($id > 0) {
            $stmtCur = $conn->prepare("SELECT foto1, foto2, foto3 FROM tavole_modello WHERE id = ?");
            $stmtCur->bind_param("i", $id);
            $stmtCur->execute();
            $esistenti = $stmtCur->get_result()->fetch_assoc() ?: [];
        }

        $foto = tavolaGestisciUploadFoto($_FILES, $esistenti);

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE tavole_modello SET nome=?, tipo=?, capacita=?, descrizione=?, foto1=?, foto2=?, foto3=? WHERE id=?");
            $stmt->bind_param("ssissssi", $nome, $tipo, $capacita, $descrizione, $foto['foto1'], $foto['foto2'], $foto['foto3'], $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO tavole_modello (nome, tipo, capacita, descrizione, foto1, foto2, foto3) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("ssissss", $nome, $tipo, $capacita, $descrizione, $foto['foto1'], $foto['foto2'], $foto['foto3']);
        }

        if ($stmt->execute()) {
            $successo = "Tavola modello salvata con successo.";
        } else {
            $errore = "Errore durante il salvataggio.";
        }
    }
}

$tavolaModifica = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM tavole_modello WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $tavolaModifica = $stmt->get_result()->fetch_assoc();
}

$tavole = $conn->query("SELECT * FROM tavole_modello ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

$extraCssFiles = ['/assets/css/admin-eventi.css'];
$pageTitle = "Magazzino Tavole - Moki Club Numana";
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-container admin-eventi-container">
    <div class="admin-eventi-header">
        <h1>Magazzino Tavole</h1>
        <div class="admin-eventi-header-actions">
            <a href="eventi.php" class="btn-primary admin-eventi-btn-secondary">Torna agli Eventi</a>
        </div>
    </div>
    <p class="admin-eventi-hint">Qui gestisci le tavole "modello" riutilizzabili: quando crei un evento potrai attivarle e personalizzarle per quella specifica uscita.</p>

    <?php if ($successo): ?><p class="admin-msg admin-msg-success"><?php echo htmlspecialchars($successo); ?></p><?php endif; ?>
    <?php if ($errore): ?><p class="admin-msg admin-msg-error"><?php echo htmlspecialchars($errore); ?></p><?php endif; ?>

    <div class="admin-eventi-form-card">
        <h2><?php echo $tavolaModifica ? 'Modifica tavola modello' : 'Aggiungi tavola modello'; ?></h2>
        <form method="POST" enctype="multipart/form-data" class="modern-form admin-eventi-form">
            <?= app_csrf_input() ?>
            <input type="hidden" name="tavola_id" value="<?php echo (int) ($tavolaModifica['id'] ?? 0); ?>">

            <div class="form-row">
                <input type="text" name="nome" placeholder="Nome/numero tavola (es. Tavola 1, Tavola Gigante)" required
                    value="<?php echo htmlspecialchars($tavolaModifica['nome'] ?? ''); ?>">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="singola" <?php echo (($tavolaModifica['tipo'] ?? 'singola') === 'singola') ? 'selected' : ''; ?>>Singola</option>
                        <option value="gruppo" <?php echo (($tavolaModifica['tipo'] ?? '') === 'gruppo') ? 'selected' : ''; ?>>Gruppo (più persone)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Capacità massima (persone)</label>
                <input type="number" min="1" name="capacita" value="<?php echo (int) ($tavolaModifica['capacita'] ?? 1); ?>">
            </div>

            <textarea name="descrizione" placeholder="Mini descrizione" rows="3"><?php echo htmlspecialchars($tavolaModifica['descrizione'] ?? ''); ?></textarea>

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

            <button type="submit" name="salva_tavola" class="btn-primary"><?php echo $tavolaModifica ? 'Salva modifiche' : 'Aggiungi tavola'; ?></button>
            <?php if ($tavolaModifica): ?><a href="tavole_modello.php" class="admin-eventi-btn-annulla">Annulla</a><?php endif; ?>
        </form>
    </div>

    <div class="admin-eventi-cards-grid">
        <?php foreach ($tavole as $t): ?>
        <div class="admin-eventi-tavola-card">
            <?php if (!empty($t['foto1'])): ?>
                <img src="/<?php echo htmlspecialchars($t['foto1']); ?>" alt="<?php echo htmlspecialchars($t['nome']); ?>">
            <?php else: ?>
                <div class="admin-eventi-tavola-noimg">Nessuna foto</div>
            <?php endif; ?>
            <div class="admin-eventi-tavola-card-body">
                <h3><?php echo htmlspecialchars($t['nome']); ?></h3>
                <p><?php echo htmlspecialchars($t['tipo'] === 'gruppo' ? 'Gruppo · fino a ' . $t['capacita'] . ' persone' : 'Singola'); ?></p>
                <div class="admin-table-actions">
                    <a href="tavole_modello.php?edit=<?php echo (int) $t['id']; ?>" class="admin-action-link">Modifica</a>
                    <a href="tavole_modello.php?delete=<?php echo (int) $t['id']; ?>&csrf_token=<?php echo urlencode(app_csrf_token()); ?>"
                        class="admin-action-link admin-action-link-danger" onclick="return confirm('Eliminare questa tavola modello?');">Elimina</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($tavole)): ?><p class="admin-table-empty">Nessuna tavola modello ancora creata.</p><?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
