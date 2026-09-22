<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";

$extraCssFiles = ['/assets/css/admin-email-log.css'];
$pageTitle = "Log Email - Moki SUP Club";
require_once __DIR__ . "/../includes/header.php";

// =============================
// FILTRI
// =============================

$tipo = $_GET['tipo'] ?? '';
$stato = $_GET['stato'] ?? '';

// SVUOTA LOG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    app_require_csrf();
    if ($conn->query("DELETE FROM email_log")) {
        $successo = "Log email svuotati correttamente.";
    } else {
        $errore = "Errore durante lo svuotamento dei log.";
    }
}

$query = "SELECT * FROM email_log WHERE 1";
$params = [];
$types = "";

if (!empty($tipo)) {
    $query .= " AND tipo = ?";
    $params[] = $tipo;
    $types .= "s";
}

if (!empty($stato)) {
    $query .= " AND stato = ?";
    $params[] = $stato;
    $types .= "s";
}

$query .= " ORDER BY data_invio DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<main class="admin-dashboard-wrapper">
    <header class="db-header-section">
        <div class="db-title-area">
            <div class="email-log-title-row">
                <h1>Log Email <span class="green-dot">.</span></h1>
                <form method="POST" class="email-log-clear-form"
                    data-confirm-message="Sei sicuro di voler eliminare TUTTI i log email? Questa operazione non è reversibile.">
                    <?= app_csrf_input() ?>
                    <button type="submit" name="clear_logs" value="1" class="mini-btn logout email-log-link-plain">🗑️
                        Svuota Log</button>
                </form>
            </div>
            <p>Cronologia delle comunicazioni automatiche inviate ai soci.</p>
        </div>
        <div class="db-status-badge">
            <span class="status-dot"></span> Sincronizzato
        </div>
    </header>

    <?php if (isset($successo) && $successo): ?>
        <div class="success-container email-log-message-box">
            <?= htmlspecialchars($successo) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errore) && $errore): ?>
        <div class="error-container email-log-message-box">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <!-- CONTROLLI E FILTRI -->
    <div class="search-form-neu email-log-controls-grid">

        <!-- INVIO TEST -->
        <div>
            <label class="search-label-text">🔍 Invia Test Rapido</label>
            <form method="POST" action="send_test_email.php" class="email-log-vertical-form">
                <?= app_csrf_input() ?>
                <input type="email" name="destinatario" placeholder="Email del destinatario..." required>
                <div class="email-log-row-gap-10">
                    <select name="tipo" required
                        class="email-log-neu-select">
                        <option value="">Seleziona modello...</option>
                        <option value="iscrizione">Iscrizione</option>
                        <option value="rimessaggio">Rimessaggio</option>
                    </select>
                    <button type="submit" class="mini-btn">Invia Prova</button>
                </div>
            </form>
        </div>

        <!-- FILTRA RISULTATI -->
        <div>
            <label class="search-label-text">📊 Filtra Comunicazioni</label>
            <form method="GET" class="email-log-vertical-form">
                <div class="email-log-row-gap-10">
                    <select name="tipo"
                        class="email-log-neu-select">
                        <option value="">Tutti i tipi</option>
                        <option value="iscrizione" <?= $tipo == 'iscrizione' ? 'selected' : '' ?>>Iscrizione</option>
                        <option value="rimessaggio" <?= $tipo == 'rimessaggio' ? 'selected' : '' ?>>Rimessaggio</option>
                    </select>
                    <select name="stato"
                        class="email-log-neu-select">
                        <option value="">Tutti gli stati</option>
                        <option value="inviata" <?= $stato == 'inviata' ? 'selected' : '' ?>>Inviata</option>
                        <option value="errore" <?= $stato == 'errore' ? 'selected' : '' ?>>Errore</option>
                    </select>
                </div>
                <div class="email-log-row-gap-10">
                    <button type="submit" class="mini-btn email-log-flex-1">Applica Filtri</button>
                    <a href="email_log.php" class="mini-btn logout"
                        class="email-log-link-plain email-log-flex-1 email-log-btn-center">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABELLA -->
    <div class="neu-table-card">
        <h3 class="email-log-table-title">Cronologia Invii</h3>
        <table class="admin-table-neu">
            <thead>
                <tr>
                    <th>TIPO</th>
                    <th>DESTINATARIO</th>
                    <th>OGGETTO</th>
                    <th>STATO</th>
                    <th>DATA</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="TIPO"><span
                                class="email-log-type-badge"><?= strtoupper(htmlspecialchars($row['tipo'])) ?></span>
                        </td>
                        <td data-label="DESTINATARIO"><?= htmlspecialchars($row['destinatario']) ?></td>
                        <td data-label="OGGETTO"><?= htmlspecialchars($row['oggetto']) ?></td>
                        <td data-label="STATO">
                            <?php if ($row['stato'] == 'inviata'): ?>
                                <span class="email-log-status-ok">✔ Inviata</span>
                            <?php else: ?>
                                <span class="email-log-status-ko">✖ Errore</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="DATA" class="email-log-date-cell"><?= $row['data_invio'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<script src="/assets/js/admin-email-log.js"></script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>