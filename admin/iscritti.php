<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";

function parseItalianOrIsoDate(?string $rawDate): ?string
{
    $rawDate = trim((string) $rawDate);
    if ($rawDate === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
        return $rawDate;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $rawDate, $matches)) {
        $day = (int) $matches[1];
        $month = (int) $matches[2];
        $year = (int) $matches[3];

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    return null;
}

$extraCssFiles = ['/assets/css/admin-iscritti.css'];
$pageTitle = "Gestisci Iscritti - Moki SUP Club";
require_once __DIR__ . "/../includes/header.php";

$successo = "";
$errore = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

/* =========================
   ELIMINAZIONE
========================= */
if (isset($_GET['delete'])) {
    if (!app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $errore = "Richiesta non valida (CSRF).";
    } else {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM iscritti WHERE id_modulo = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $successo = "Iscritto eliminato con successo";
    } else {
        $errore = "Errore nell'eliminare l'iscritto";
    }
    }
}

/* =========================
   MODIFICA - CARICAMENTO
========================= */
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("SELECT * FROM iscritti WHERE id_modulo = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultEdit = $stmt->get_result();
    $iscritto = $resultEdit->fetch_assoc();
}

/* =========================
   AGGIORNAMENTO
========================= */
if (isset($_POST['update'])) {
    app_require_csrf();

    $id = intval($_POST['update_id']);
    unset($_POST['update'], $_POST['update_id'], $_POST['csrf_token']);

    $campi = [];
    $valori = [];
    $tipi = "";

    foreach ($_POST as $campo => $valore) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $campo)) {
            continue;
        }

        if (strpos($campo, 'data') !== false && trim((string) $valore) !== '') {
            $parsedDate = parseItalianOrIsoDate((string) $valore);
            if ($parsedDate === null) {
                $errore = "Formato data non valido per il campo " . $campo . ". Usa gg/mm/aaaa.";
                $campi = [];
                $valori = [];
                $tipi = "";
                break;
            }
            $valore = $parsedDate;
        }

        $campi[] = "$campo = ?";
        $valori[] = $valore;
        $tipi .= "s";
    }

    if (empty($campi)) {
        $errore = "Nessun campo valido da aggiornare";
    } else {
        $valori[] = $id;
        $tipi .= "i";

        $sql = "UPDATE iscritti SET " . implode(", ", $campi) . " WHERE id_modulo = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            error_log('Errore prepare admin/iscritti update: ' . $conn->error);
            $errore = "Errore durante l'aggiornamento";
        } else {
            $bind_names = [];
            $bind_names[] = $tipi;
            for ($i = 0; $i < count($valori); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $valori[$i];
                $bind_names[] = &$$bind_name;
            }

            call_user_func_array([$stmt, 'bind_param'], $bind_names);

            if ($stmt->execute()) {
                $successo = "Iscritto aggiornato con successo";
            } else {
                error_log('Errore execute admin/iscritti update: ' . $stmt->error);
                $errore = "Errore durante l'aggiornamento";
            }
        }
    }
}
/* =========================
   RICERCA
========================= */
/* =========================
   PAGINAZIONE E RICERCA
========================= */
$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Calcolo Totale Record per Paginazione
$count_query = "SELECT COUNT(*) as total FROM iscritti WHERE 1";
if ($search) {
    $count_query .= " AND (nome LIKE ? OR cognome LIKE ?)";
}
$stmt_count = $conn->prepare($count_query);
if ($search) {
    $search_param = "%$search%";
    $stmt_count->bind_param("ss", $search_param, $search_param);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Query vera
$query = "SELECT * FROM iscritti WHERE 1";
if ($search) {
    $query .= " AND (nome LIKE ? OR cognome LIKE ?)";
}
$query .= " ORDER BY id_modulo DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if ($search) {
    $stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<main class="admin-dashboard-wrapper">
    <header class="db-header-section admin-toolbar-header">
        <div class="db-title-area">
            <div class="admin-title-row">
                <h1>Gestione Iscritti <span class="green-dot">.</span></h1>
                <div class="admin-top-actions">
                    <a href="export_csv.php?type=iscritti" class="button type1 iscritti-export-btn">
                        <span class="btn-txt iscritti-btn-txt-spacing">📊 ESPORTA EXCEL</span>
                    </a>
                    <button id="open-bulk-email" class="button type1 iscritti-bulk-btn">
                        <span class="btn-txt iscritti-btn-txt-spacing">📧 INVIA AVVISO MASSIVO</span>
                    </button>
                </div>
            </div>
            <p>Visualizza, cerca e modifica i membri del Moki SUP Club.</p>
        </div>
        <div class="db-status-badge records-badge">
            <span class="status-dot"></span> <?= $total_rows ?> Totali
        </div>
    </header>

    <?php if ($successo): ?>
        <div class="success-container iscritti-message-box">
            <?= htmlspecialchars($successo) ?>
        </div>
    <?php endif; ?>

    <?php if ($errore): ?>
        <div class="error-container iscritti-message-box">
            <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <!-- =========================
         FORM RICERCA
    ========================= -->
    <form method="GET" class="search-form-neu">
        <label class="search-label-text">
            🔍 Strumenti di Ricerca
        </label>
        <input type="text" name="search" placeholder="Cerca per nome o cognome..."
            value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="mini-btn">Cerca</button>
        <?php if ($search): ?>
            <a href="iscritti.php" class="mini-btn logout iscritti-link-plain">Reset</a>
        <?php endif; ?>
    </form>

    <!-- =========================
         MODIFICA (Card Neumorphica)
    ========================= -->
    <?php if (isset($iscritto)): ?>
        <div class="login-card iscritti-edit-card">
            <h2 class="iscritti-edit-title">Modifica Iscritto:
                <?= htmlspecialchars($iscritto['nome'] . ' ' . $iscritto['cognome']) ?>
            </h2>

            <form method="POST" class="login-form">
                <?= app_csrf_input() ?>
                <input type="hidden" name="update_id" value="<?php echo $iscritto['id_modulo']; ?>">

                <div class="iscritti-edit-grid">
                    <?php
                    $enumFields = [
                        "tipo_iscrizione" => ["Iscrizione", "Iscrizione + Noleggio", "Abbonamento", "Lezione", "Alba", "Notturna"]
                    ];

                    foreach ($iscritto as $campo => $valore):
                        if ($campo == 'id_modulo')
                            continue;
                        ?>
                        <div class="form-group iscritti-form-group">
                            <label class="iscritti-field-label"><?php echo strtoupper(str_replace('_', ' ', $campo)); ?></label>
                            <div class="neu-input iscritti-neu-input-inset">
                                <?php if (isset($enumFields[$campo])): ?>
                                    <select name="<?php echo $campo; ?>"
                                        class="iscritti-input-control iscritti-select-control">
                                        <?php foreach ($enumFields[$campo] as $opzione): ?>
                                            <option value="<?php echo $opzione; ?>" <?php if ($valore == $opzione)
                                                   echo "selected"; ?>>
                                                <?php echo $opzione; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($campo === 'firma'): ?>
                                    <?php
                                    $signaturePath = trim((string) $valore);
                                    $signatureUrl = '';
                                    $isSignaturePathValid = preg_match('#^cliente/uploads/firme/[0-9]{4}/[a-zA-Z0-9._-]+$#', $signaturePath);
                                    if ($signaturePath !== '' && $isSignaturePathValid) {
                                        $signatureUrl = '/' . ltrim($signaturePath, '/');
                                    }
                                    ?>

                                    <input type="hidden" name="<?php echo $campo; ?>" value="<?php echo htmlspecialchars($signaturePath); ?>">

                                    <?php if ($signatureUrl !== ''): ?>
                                        <button type="button" class="neu-button mini-btn toggle-signature-preview iscritti-signature-toggle-btn"
                                            data-signature-target="signature-preview-<?php echo (int) $iscritto['id_modulo']; ?>">
                                            Visualizza Firma
                                        </button>

                                        <div id="signature-preview-<?php echo (int) $iscritto['id_modulo']; ?>"
                                            class="iscritti-signature-preview-box">
                                            <img src="<?php echo htmlspecialchars($signatureUrl); ?>" alt="Firma iscrizione"
                                                class="iscritti-signature-preview-img">
                                        </div>
                                    <?php else: ?>
                                        <div class="iscritti-signature-empty">
                                            Nessuna firma disponibile da visualizzare.
                                        </div>
                                    <?php endif; ?>
                                <?php elseif (strpos($campo, 'data') !== false): ?>
                                    <?php
                                    $dateValue = (string) $valore;
                                    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateValue, $matchesDate)) {
                                        $dateValue = $matchesDate[3] . '/' . $matchesDate[2] . '/' . $matchesDate[1];
                                    }
                                    ?>
                                    <input type="text" name="<?php echo $campo; ?>" value="<?php echo htmlspecialchars($dateValue); ?>" placeholder="gg/mm/aaaa" inputmode="numeric" maxlength="10" data-date-it
                                        class="iscritti-input-control">
                                <?php else: ?>
                                    <input type="text" name="<?php echo $campo; ?>" value="<?php echo htmlspecialchars($valore); ?>"
                                        class="iscritti-input-control">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="iscritti-edit-actions">
                    <button type="submit" name="update" class="neu-button mini-btn">Salva Modifiche</button>
                    <a href="iscritti.php" class="neu-button mini-btn logout iscritti-link-plain iscritti-btn-center">Annulla</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- =========================
         LISTA (Tabella Neumorphica)
    ========================= -->
    <div class="neu-table-card">
        <h3 class="iscritti-table-title">Tutti gli Iscritti</h3>

        <table class="admin-table-neu">
            <thead>
                <tr>
                    <?php
                    $fields = $result->fetch_fields();
                    foreach ($fields as $field) {
                        if (in_array($field->name, ['id_modulo', 'nome', 'cognome', 'email', 'telefono', 'tipo_iscrizione', 'data_iscrizione'])) {
                            echo "<th>" . strtoupper(str_replace('_', ' ', $field->name)) . "</th>";
                        }
                    }
                    echo "<th>AZIONI</th>";
                    ?>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($fields as $field):
                            if (!in_array($field->name, ['id_modulo', 'nome', 'cognome', 'email', 'telefono', 'tipo_iscrizione', 'data_iscrizione']))
                                continue;
                            ?>
                            <td data-label="<?= strtoupper(htmlspecialchars($field->name)) ?>">
                                <?= htmlspecialchars($row[$field->name]) ?>
                            </td>
                        <?php endforeach; ?>

                        <td data-label="AZIONI" class="iscritti-actions-cell">
                            <a href="?edit=<?= $row['id_modulo'] ?>" class="neu-toggle mini-btn iscritti-action-link" title="Modifica">
                                ✏️
                            </a>
                            <a href="?delete=<?= $row['id_modulo'] ?>&csrf_token=<?= urlencode(app_csrf_token()) ?>" class="neu-toggle mini-btn logout iscritti-action-link iscritti-delete-action" title="Elimina"
                                data-confirm-message="Sei sicuro di voler eliminare questo iscritto?">
                                🗑️
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINAZIONE -->
    <?php if ($total_pages > 1): ?>
        <div class="iscritti-pagination-wrap">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                    class="neu-button mini-btn iscritti-page-link <?= $i === $page ? '' : 'logout' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</main>

<!-- MODAL COMUNICAZIONE DI MASSA -->
<div class="signature-modal" id="bulk-email-modal">
    <div class="modal-content-neu iscritti-bulk-modal-content">
        <h2 class="iscritti-bulk-title">📧 Comunicazione di Massa</h2>

        <div class="bulk-modal-grid">
            <!-- SELEZIONE SOCI -->
            <div class="user-selection-panel">
                <label class="search-label-text">1. Seleziona Destinatari</label>
                <div class="iscritti-bulk-row">
                    <input type="text" id="bulk-user-search" placeholder="Cerca socio..."
                        class="iscritti-bulk-search-input">
                    <button id="bulk-select-all" class="button type1 iscritti-bulk-select-btn">
                        <span class="btn-txt">Tutti</span>
                    </button>
                </div>
                <div class="user-selection-list" id="bulk-user-list">
                    <!-- Dinamico da JS -->
                </div>
            </div>

            <!-- COMPOSIZIONE EMAIL -->
            <div class="composition-panel">
                <label class="search-label-text">2. Componi Messaggio</label>

                <div class="bulk-input-group">
                    <label>OGGETTO</label>
                    <input type="text" id="bulk-subject" placeholder="Es: Avviso chiusura Club">
                </div>

                <div class="bulk-input-group">
                    <label>MESSAGGIO (Usa {NOME} per personalizzare)</label>
                    <textarea id="bulk-message" placeholder="Ciao {NOME}, ti scriviamo per..."></textarea>
                </div>

                <div class="bulk-input-group">
                    <label>ALLEGATO (Opzionale)</label>
                    <input type="file" id="bulk-attachment"
                        class="iscritti-bulk-attachment-input">
                </div>

                <div class="iscritti-bulk-actions-row">
                    <button id="bulk-send-btn" class="button type1 iscritti-bulk-send-btn">
                        <span class="btn-txt">🚀 INVIA</span>
                    </button>
                    <button id="close-bulk-modal" class="button type1 iscritti-bulk-close-btn">
                        <span class="btn-txt">INDIETRO</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PROGRESS BAR IBRIDA -->
<div class="bulk-progress-container" id="bulk-progress-container">
    <div class="bulk-progress-header">
        <span>INVIO IN CORSO...</span>
    </div>
    <div class="progress-track">
        <div class="progress-fill"></div>
    </div>
</div>

<script src="/assets/js/bulk_email.js"></script>
<script src="/assets/js/admin-iscritti.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin-iscritti.js'); ?>"></script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>