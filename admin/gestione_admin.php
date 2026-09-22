<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . '/../includes/functions.php';

$extraCssFiles = ['/assets/css/admin-gestione-admin.css'];
$successo = "";
$errore = "";

/* ---------------- DELETE ---------------- */
if (isset($_GET['delete'])) {
    if (!app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $errore = "Richiesta non valida (CSRF).";
    } else {
    $id = intval($_GET['delete']);

    if ($id === 0) {
        $errore = "L'utente admin (ID 0) non può essere eliminato";
    } else {

    $stmt = $conn->prepare("SELECT username FROM utenti_admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['username'] === 'admin') {
            $errore = "Non puoi eliminare l'utente admin";
        } else {
            $stmt = $conn->prepare("DELETE FROM utenti_admin WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $successo = "Utente eliminato con successo";
            } else {
                $errore = "Errore nell'eliminare l'utente";
            }
        }
    }
    }
    }
}

/* ---------------- EDIT ---------------- */
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);

    if ($id === 0) {
        $errore = "L'utente admin (ID 0) non può essere modificato";
    } else {

    $stmt = $conn->prepare("SELECT username FROM utenti_admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $edit_username = $user['username'];
        $edit_id = $id;
    }
    }
}

/* ---------------- UPDATE ---------------- */
if (isset($_POST['update'])) {
    app_require_csrf();

    $id = intval($_POST['update_id']);
    $username = trim($_POST['update_username']);
    $password = $_POST['update_password'];

    if ($id === 0) {
        $errore = "L'utente admin (ID 0) non può essere modificato";
    } else {

    $stmt = $conn->prepare("SELECT username FROM utenti_admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current_user = $stmt->get_result()->fetch_assoc();

    if ($current_user['username'] === 'admin' && $username !== 'admin') {
        $errore = "Non puoi cambiare il nome dell'utente admin (motivi di sicurezza)";
    } else {

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE utenti_admin SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $hashed_password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE utenti_admin SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $id);
        }

        if ($stmt->execute()) {
            $successo = "Utente aggiornato con successo";
        } else {
            $errore = "Errore nell'aggiornare l'utente";
        }
    }
}
}

/* ---------------- CREATE ---------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['update'])) {
    app_require_csrf();

    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password !== $confirm_password) {
        $errore = "Le password non coincidono";
    } elseif (strlen($password) < 6) {
        $errore = "La password deve essere di almeno 6 caratteri";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO utenti_admin (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            $successo = "Utente aggiunto con successo";
        } else {
            $errore = "Errore nell'aggiungere l'utente";
        }
    }
}

$pageTitle = "Gestione Admin - Moki SUP Club";
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-dashboard-wrapper">
    <header class="db-header-section">
        <div class="db-title-area">
            <h1>Gestione Staff <span class="green-dot">.</span></h1>
            <p>Aggiungi o modifica gli account amministrativi del portale.</p>
        </div>
        <div class="db-status-badge">
            <span class="status-dot"></span> Account Protetti
        </div>
    </header>

    <?php if ($successo): ?>
        <div class="success-container gestione-admin-message-box">
            <?= htmlspecialchars($successo) ?>
        </div>
    <?php endif; ?>

    <?php if ($errore): ?>
        <div class="error-container gestione-admin-message-box">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <div class="admin-management-grid">

        <!-- STRUMENTI DI GESTIONE -->
        <div class="search-form-neu management-inner-grid">

            <!-- AGGIUNTA -->
            <div>
                <label class="search-label-text">👤 Nuovo Account Admin</label>
                <form method="POST" class="gestione-admin-stack-form">
                    <?= app_csrf_input() ?>
                    <input type="text" name="username" required placeholder="Scegli Username...">
                    <div class="gestione-admin-row-gap-10">
                        <input type="password" name="password" required placeholder="Password..." class="gestione-admin-flex-1">
                        <input type="password" name="confirm_password" required placeholder="Conferma..."
                            class="gestione-admin-flex-1">
                    </div>
                    <button type="submit" class="mini-btn">Crea Profilo</button>
                </form>
            </div>

            <!-- MODIFICA -->
            <?php if (isset($edit_id)): ?>
                <div class="edit-account-panel">
                    <label class="search-label-text">✏️ Modifica: <?= htmlspecialchars($edit_username) ?></label>
                    <form method="POST" enctype="multipart/form-data" class="gestione-admin-stack-form">
                        <?= app_csrf_input() ?>
                        <input type="hidden" name="update_id" value="<?php echo $edit_id; ?>">
                        <input type="text" name="update_username" value="<?php echo htmlspecialchars($edit_username); ?>"
                            required <?php if ($edit_username === 'admin')
                                echo 'readonly'; ?>>
                        <input type="password" name="update_password"
                            placeholder="Nuova password (lascia vuoto per non cambiare)">

                        <div class="gestione-admin-row-gap-10">
                            <button type="submit" name="update" class="mini-btn gestione-admin-flex-1">Aggiorna</button>
                            <a href="gestione_admin.php" class="mini-btn logout gestione-admin-link-plain gestione-admin-flex-1 gestione-admin-btn-center">Annulla</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="gestione-admin-edit-placeholder">
                    Seleziona un utente dalla lista per modificarlo.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- TABELLA UTENTI -->
    <div class="neu-table-card gestione-admin-table-card">
        <h3 class="gestione-admin-table-title">Admin Esistenti</h3>
        <table class="admin-table-neu">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>USERNAME</th>
                    <th class="gestione-admin-actions-header">AZIONI</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT id, username FROM utenti_admin ORDER BY id = 0 DESC, id ASC");
                while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td data-label="ID"><?= $row['id'] ?></td>
                        <td data-label="USERNAME">
                            <strong class="gestione-admin-username"><?= htmlspecialchars($row['username']) ?></strong>
                            <?php if ((int) $row['id'] === 0): ?>
                                <span class="gestione-admin-root-badge">ROOT</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="AZIONI" class="gestione-admin-actions-cell">
                            <?php if ((int) $row['id'] !== 0): ?>
                                <a href="?edit=<?= $row['id'] ?>" class="neu-toggle mini-btn gestione-admin-action-link" title="Modifica">
                                    ✏️
                                </a>
                                <a href="?delete=<?= $row['id'] ?>&csrf_token=<?= urlencode(app_csrf_token()) ?>" class="neu-toggle mini-btn logout gestione-admin-action-link gestione-admin-delete-action" title="Elimina"
                                    data-confirm-message="Sei sicuro?">
                                    🗑️
                                </a>
                            <?php else: ?>
                                <span class="gestione-admin-protected">Protetto</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</main>

<script src="/assets/js/admin-gestione-admin.js"></script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>