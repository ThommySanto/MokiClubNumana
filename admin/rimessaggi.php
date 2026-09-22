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

$successo = "";
$errore = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (isset($_GET['delete'])) {
    if (!app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $errore = "Richiesta non valida (CSRF).";
    } else {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM rimessaggi WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $successo = "Rimessaggio eliminato con successo";
    } else {
        $errore = "Errore nell'eliminare il rimessaggio";
    }
    }
}

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM rimessaggi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rimessaggio = $result->fetch_assoc();
}
if (isset($_POST['update'])) {
    app_require_csrf();

    $id = intval($_POST['update_id']);
    $nome_cliente = '';
    $cognome_cliente = '';

    $stmtCustomer = $conn->prepare("SELECT nome, cognome FROM rimessaggi WHERE id = ? LIMIT 1");
    if ($stmtCustomer) {
        $stmtCustomer->bind_param("i", $id);
        $stmtCustomer->execute();
        $customerResult = $stmtCustomer->get_result()->fetch_assoc();
        $nome_cliente = trim((string)($customerResult['nome'] ?? ''));
        $cognome_cliente = trim((string)($customerResult['cognome'] ?? ''));
    }

    unset($_POST['update'], $_POST['update_id'], $_POST['csrf_token']);

    $campi = [];
    $valori = [];
    $tipi = "";
    $ricevuta_path = null;
    $uploadError = null;

    if (!empty($_FILES['ricevuta_pagamento']['name'])) {
        $cliente_folder = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim($nome_cliente . '_' . $cognome_cliente)));
        if ($cliente_folder === '' || $cliente_folder === '_') {
            $cliente_folder = 'cliente_senza_nome';
        }

        $directory = __DIR__ . '/../cliente/uploads/ricevute/' . $cliente_folder . '/';

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $original_name = basename($_FILES['ricevuta_pagamento']['name']);
        $clean_original_name = strtolower(preg_replace('/[^a-zA-Z0-9._-]+/', '_', $original_name));
        $file_name = date('Ymd_His') . '_' . $clean_original_name;
        $target = $directory . $file_name;

        if (move_uploaded_file($_FILES['ricevuta_pagamento']['tmp_name'], $target)) {
            $ricevuta_path = 'cliente/uploads/ricevute/' . $cliente_folder . '/' . $file_name;
        } else {
            error_log('Errore durante il caricamento della ricevuta in admin/rimessaggi: ' . $id);
            $uploadError = 'Errore durante il caricamento della ricevuta.';
        }
    }

    // Se vengono modificati acconto o saldo_totale ricalcoliamo rimanente
if (isset($_POST['acconto']) && isset($_POST['saldo_totale'])) {
    $acconto = floatval($_POST['acconto']);
    $saldo_totale = floatval($_POST['saldo_totale']);

    $_POST['rimanente'] = $saldo_totale - $acconto;
}

    if ($uploadError) {
        $errore = $uploadError;
    } else {
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
            $tipi .= "s"; // trattiamo tutto come stringa
        }

        if (!empty($ricevuta_path)) {
            $campi[] = "ricevuta_pagamento = ?";
            $valori[] = $ricevuta_path;
            $tipi .= "s";
        }

        if (empty($campi)) {
            $errore = "Nessun campo valido da aggiornare";
        } else {

        $valori[] = $id;
        $tipi .= "i";

        $sql = "UPDATE rimessaggi SET " . implode(", ", $campi) . " WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($tipi, ...$valori);

        if ($stmt->execute()) {
            $successo = "Rimessaggio aggiornato con successo";
            unset($rimessaggio);
        } else {
            error_log('Errore update admin/rimessaggi: ' . $stmt->error);
            $errore = "Errore durante l'aggiornamento";
        }
        }
    }
}

$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Calcolo Totale Record per Paginazione
$count_query = "SELECT COUNT(*) as total FROM rimessaggi WHERE 1";
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

$query = "SELECT * FROM rimessaggi WHERE 1";
if ($search) {
    $query .= " AND (nome LIKE ? OR cognome LIKE ?)";
    $search_param = "%$search%";
}
$query .= " ORDER BY id DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if ($search) {
    $stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$extraCssFiles = ['/assets/css/admin-rimessaggi.css'];
$pageTitle = "Gestisci Rimessaggi - Moki SUP Club";
include "../includes/header.php";
?>

<main class="admin-dashboard-wrapper">
    <header class="db-header-section admin-toolbar-header">
        <div class="db-title-area">
            <div class="admin-title-row">
                <h1>Gestione Rimessaggi <span class="green-dot">.</span></h1>
                <div class="admin-top-actions">
                    <a href="export_csv.php?type=rimessaggi" class="button type1 rimessaggi-export-btn">
                        <span class="btn-txt rimessaggi-btn-txt-spacing">📊 ESPORTA EXCEL</span>
                    </a>
                    <button id="open-bulk-email" class="button type1 rimessaggi-bulk-btn">
                        <span class="btn-txt rimessaggi-btn-txt-spacing">📧 INVIA AVVISO MASSIVO</span>
                    </button>
                </div>
            </div>
            <p>Monitora le tavole in magazzino e gestisci i pagamenti dei soci.</p>
        </div>
        <div class="db-status-badge records-badge">
            <span class="status-dot"></span> <?= $total_rows ?> Totali
        </div>
    </header>

    <?php if ($successo): ?>
        <div class="success-container rimessaggi-message-box">
            <?= htmlspecialchars($successo) ?>
        </div>
    <?php endif; ?>

    <?php if ($errore): ?>
        <div class="error-container rimessaggi-message-box">
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
            <a href="rimessaggi.php" class="mini-btn logout rimessaggi-link-plain">Reset</a>
        <?php endif; ?>
    </form>

    <!-- =========================
         MODIFICA (Card Neumorphica)
    ========================= -->
    <?php if (isset($rimessaggio)): ?>
        <?php
        $allRicevute = [];
        $clienteFolder = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim((string)($rimessaggio['nome'] ?? '') . '_' . (string)($rimessaggio['cognome'] ?? ''))));

        if ($clienteFolder !== '' && $clienteFolder !== '_') {
            $absoluteReceiptsDir = __DIR__ . '/../cliente/uploads/ricevute/' . $clienteFolder . '/';
            $relativeReceiptsDir = 'cliente/uploads/ricevute/' . $clienteFolder . '/';

            if (is_dir($absoluteReceiptsDir)) {
                $receiptEntries = scandir($absoluteReceiptsDir);
                if (is_array($receiptEntries)) {
                    foreach ($receiptEntries as $entry) {
                        if ($entry === '.' || $entry === '..') {
                            continue;
                        }

                        $entryAbsPath = $absoluteReceiptsDir . $entry;
                        if (!is_file($entryAbsPath)) {
                            continue;
                        }

                        $allRicevute[] = [
                            'path' => $relativeReceiptsDir . $entry,
                            'label' => $entry,
                            'mtime' => (int) @filemtime($entryAbsPath)
                        ];
                    }
                }
            }
        }

        if (empty($allRicevute) && !empty($rimessaggio['ricevuta_pagamento'])) {
            $legacyPath = trim((string) $rimessaggio['ricevuta_pagamento']);
            $allRicevute[] = [
                'path' => $legacyPath,
                'label' => basename($legacyPath),
                'mtime' => 0
            ];
        }

        if (!empty($allRicevute)) {
            usort($allRicevute, function ($a, $b) {
                return $b['mtime'] <=> $a['mtime'];
            });
        }
        ?>
        <div class="login-card rimessaggi-edit-card">
            <h2 class="rimessaggi-edit-title">Modifica Rimessaggio:
                <?= htmlspecialchars($rimessaggio['nome'] . ' ' . $rimessaggio['cognome']) ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" class="login-form">
                <?= app_csrf_input() ?>
                <input type="hidden" name="update_id" value="<?php echo $rimessaggio['id']; ?>">

                <div class="rimessaggi-edit-grid">
                    <?php
                    $enumFields = [
                        "tipo_documento" => ["codice fiscale", "carta identità", "patente", "passaporto"],
                        "adulto_kid" => ["Adulto", "Kid"],
                        "tipo_rimessaggio" => ["Settimanale", "Mensile", "Annuale"],
                        "sacca" => ["Si", "No"]
                    ];

                    foreach ($rimessaggio as $campo => $valore):
                        if (in_array($campo, ['id', 'created_at', 'ricevuta_pagamento']))
                            continue;
                        ?>
                        <div class="form-group rimessaggi-form-group">
                            <label class="rimessaggi-field-label"><?php echo strtoupper(str_replace('_', ' ', $campo)); ?></label>
                            <div class="neu-input rimessaggi-neu-input-inset">
                                <?php if (isset($enumFields[$campo])): ?>
                                    <select name="<?php echo $campo; ?>"
                                        class="rimessaggi-input-control rimessaggi-select-control">
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
                                        <button type="button" class="neu-button mini-btn toggle-rimessaggio-signature-preview rimessaggi-signature-toggle-btn"
                                            data-signature-target="rimessaggi-signature-preview-<?php echo (int) $rimessaggio['id']; ?>">
                                            Visualizza Firma
                                        </button>

                                        <div id="rimessaggi-signature-preview-<?php echo (int) $rimessaggio['id']; ?>"
                                            class="rimessaggi-signature-preview-box">
                                            <img src="<?php echo htmlspecialchars($signatureUrl); ?>" alt="Firma rimessaggio"
                                                class="rimessaggi-signature-preview-img">
                                        </div>
                                    <?php else: ?>
                                        <div class="rimessaggi-signature-empty">
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
                                        class="rimessaggi-input-control">
                                <?php else: ?>
                                    <input type="text" name="<?php echo $campo; ?>" value="<?php echo htmlspecialchars($valore); ?>"
                                        class="rimessaggi-input-control">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-group rimessaggi-receipt-group">
                    <label class="rimessaggi-field-label">RICEVUTA PAGAMENTO</label>
                    <div class="neu-input rimessaggi-neu-input-inset rimessaggi-receipt-box">
                        <?php if (!empty($allRicevute)): ?>
                            <div class="rimessaggi-receipt-current-wrap">
                                <strong class="rimessaggi-receipt-list-title">Ricevute disponibili:</strong>
                                <ul class="rimessaggi-receipt-list">
                                    <?php foreach ($allRicevute as $ricevutaEntry): ?>
                                        <li>
                                            <a href="/<?= htmlspecialchars($ricevutaEntry['path']) ?>"
                                                target="_blank" class="rimessaggi-receipt-link">
                                                <?= htmlspecialchars($ricevutaEntry['label']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="ricevuta_pagamento" accept=".pdf,.jpg,.jpeg,.png"
                            class="rimessaggi-receipt-input">
                        <small class="rimessaggi-receipt-help">
                            Carica un nuovo file solo se vuoi sostituire la ricevuta esistente.
                        </small>
                    </div>
                </div>

                <div class="rimessaggi-edit-actions">
                    <button type="submit" name="update" class="neu-button mini-btn">Salva Modifiche</button>
                    <a href="rimessaggi.php" class="neu-button mini-btn logout rimessaggi-link-plain rimessaggi-btn-center">Annulla</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- =========================
         LISTA (Tabella Neumorphica)
    ========================= -->
    <div class="neu-table-card">
        <h3 class="rimessaggi-table-title">Tutti i Rimessaggi</h3>

        <table class="admin-table-neu">
            <thead>
                <tr>
                    <?php
                    $result->data_seek(0);
                    $fields = $result->fetch_fields();
                    foreach ($fields as $field) {
                        if (in_array($field->name, ['id', 'nome', 'cognome', 'email', 'telefono', 'codice_tavola', 'tipo_rimessaggio', 'scadenza'])) {
                            echo "<th>" . strtoupper(str_replace('_', ' ', $field->name)) . "</th>";
                        }
                    }
                    echo "<th>AZIONI</th>";
                    ?>
                </tr>
            </thead>

            <tbody>
                <?php
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()): ?>
                    <?php
                    // Calcola lo stato di pagamento per la riga corrente
                    $acconto = floatval($row['acconto'] ?? 0);
                    $saldo = floatval($row['saldo_totale'] ?? 0);
                    if ($saldo > 0 && $acconto >= $saldo) {
                        $statusClass = 'status-paid'; // Pagamento completo
                    } elseif ($acconto > 0) {
                        $statusClass = 'status-partial'; // Pagamento parziale
                    } else {
                        $statusClass = 'status-unpaid'; // Nessun pagamento
                    }
                    ?>
                    <tr class="<?php echo $statusClass; ?>">
                        <?php foreach ($fields as $field):
                            if (!in_array($field->name, ['id', 'nome', 'cognome', 'email', 'telefono', 'codice_tavola', 'tipo_rimessaggio', 'scadenza']))
                                continue;
                            ?>
                            <td data-label="<?= strtoupper(htmlspecialchars($field->name)) ?>">
                                <?= htmlspecialchars($row[$field->name] ?? '') ?>
                            </td>
                        <?php endforeach; ?>

                        <td data-label="AZIONI" class="rimessaggi-actions-cell">
                            <a href="?edit=<?= $row['id'] ?>" class="neu-toggle mini-btn rimessaggi-action-link" title="Modifica">
                                ✏️
                            </a>
                            <a href="?delete=<?= $row['id'] ?>&csrf_token=<?= urlencode(app_csrf_token()) ?>" class="neu-toggle mini-btn logout rimessaggi-action-link rimessaggi-delete-action" title="Elimina"
                                data-confirm-message="Sei sicuro di voler eliminare questo rimessaggio?">
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
        <div class="rimessaggi-pagination-wrap">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                    class="neu-button mini-btn rimessaggi-page-link <?= $i === $page ? '' : 'logout' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</main>

<!-- MODAL COMUNICAZIONE DI MASSA -->
<div class="signature-modal" id="bulk-email-modal" data-source="rimessaggi">
    <div class="modal-content-neu rimessaggi-bulk-modal-content">
        <h2 class="rimessaggi-bulk-title">📧 Comunicazione di Massa</h2>

        <div class="bulk-modal-grid">
            <!-- SELEZIONE DESTINATARI -->
            <div class="user-selection-panel">
                <label class="search-label-text">1. Seleziona Destinatari</label>
                <div class="rimessaggi-bulk-row">
                    <input type="text" id="bulk-user-search" placeholder="Cerca destinatario..."
                        class="rimessaggi-bulk-search-input">
                    <button id="bulk-select-all" class="button type1 rimessaggi-bulk-select-btn">
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
                    <input type="text" id="bulk-subject" placeholder="Es: Avviso rinnovo rimessaggio">
                </div>

                <div class="bulk-input-group">
                    <label>MESSAGGIO (Usa {NOME} per personalizzare)</label>
                    <textarea id="bulk-message" placeholder="Ciao {NOME}, ti scriviamo per..."></textarea>
                </div>

                <div class="bulk-input-group">
                    <label>ALLEGATO (Opzionale)</label>
                    <input type="file" id="bulk-attachment"
                        class="rimessaggi-bulk-attachment-input">
                </div>

                <div class="rimessaggi-bulk-actions-row">
                    <button id="bulk-send-btn" class="button type1 rimessaggi-bulk-send-btn">
                        <span class="btn-txt">🚀 INVIA</span>
                    </button>
                    <button id="close-bulk-modal" class="button type1 rimessaggi-bulk-close-btn">
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
<script src="/assets/js/admin-rimessaggi.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin-rimessaggi.js'); ?>"></script>

<?php include "../includes/footer.php"; ?>