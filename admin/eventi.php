<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . '/../includes/eventi_functions.php';

$successo = "";
$errore = "";

// ─── ELIMINA EVENTO ───
if (isset($_GET['delete'])) {
    if (!app_validate_csrf($_GET['csrf_token'] ?? null)) {
        $errore = "Richiesta non valida (CSRF).";
    } else {
        $id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM eventi WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $successo = "Evento eliminato con successo.";
        } else {
            $errore = "Errore nell'eliminare l'evento.";
        }
    }
}

// ─── CREA / MODIFICA EVENTO ───
if (isset($_POST['salva_evento'])) {
    app_require_csrf();

    $id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;
    $titolo = trim($_POST['titolo'] ?? '');
    $sottotitolo = trim($_POST['sottotitolo'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $data_evento = !empty($_POST['data_evento']) ? $_POST['data_evento'] : null;
    $ora_evento = !empty($_POST['ora_evento']) ? $_POST['ora_evento'] : null;
    $luogo = trim($_POST['luogo'] ?? '');
    $stato = in_array($_POST['stato'] ?? '', ['bozza', 'pubblicato', 'archiviato'], true) ? $_POST['stato'] : 'bozza';
    $giorni_limite = max(0, intval($_POST['giorni_limite_modifica'] ?? 2));
    $modalita_tariffe = in_array($_POST['modalita_tariffe'] ?? '', ['dettagliata', 'semplice'], true) ? $_POST['modalita_tariffe'] : 'dettagliata';

    if ($titolo === '') {
        $errore = "Il titolo dell'evento è obbligatorio.";
    } else {
        // Costruisci array tariffe in base alla modalità
        if ($modalita_tariffe === 'semplice') {
            $datiTariffe = [
                'quota_unica' => [
                    'importo' => floatval($_POST['tariffa_quota_unica_importo'] ?? 0),
                    'descrizione' => trim($_POST['tariffa_quota_unica_descrizione'] ?? ''),
                ],
            ];
        } else {
            $datiTariffe = [
                'tesserato' => [
                    'importo' => floatval($_POST['tariffa_tesserato_importo'] ?? 0),
                    'descrizione' => trim($_POST['tariffa_tesserato_descrizione'] ?? ''),
                ],
                'non_tesserato' => [
                    'importo' => floatval($_POST['tariffa_non_tesserato_importo'] ?? 0),
                    'descrizione' => trim($_POST['tariffa_non_tesserato_descrizione'] ?? ''),
                ],
                'tavola_propria' => [
                    'importo' => floatval($_POST['tariffa_tavola_propria_importo'] ?? 0),
                    'descrizione' => trim($_POST['tariffa_tavola_propria_descrizione'] ?? ''),
                ],
            ];
        }

        if ($id > 0) {
            // Modifica: recupero copertina esistente
            $stmtCur = $conn->prepare("SELECT immagine_copertina, slug FROM eventi WHERE id = ?");
            $stmtCur->bind_param("i", $id);
            $stmtCur->execute();
            $cur = $stmtCur->get_result()->fetch_assoc();
            $copertinaEsistente = $cur['immagine_copertina'] ?? null;

            $copertina = eventoGestisciUploadCopertina($_FILES['immagine_copertina'] ?? [], $copertinaEsistente);
            $slug = eventoGeneraSlug($conn, $titolo, $id);

            $stmt = $conn->prepare(
                "UPDATE eventi SET titolo=?, slug=?, sottotitolo=?, descrizione=?, immagine_copertina=?, data_evento=?, ora_evento=?, luogo=?, stato=?, giorni_limite_modifica=?, modalita_tariffe=? WHERE id=?"
            );
            $stmt->bind_param(
                "sssssssssssi",
                $titolo, $slug, $sottotitolo, $descrizione, $copertina, $data_evento, $ora_evento, $luogo, $stato, $giorni_limite, $modalita_tariffe, $id
            );
            if ($stmt->execute()) {
                eventoSalvaTariffe($conn, $id, $datiTariffe, $modalita_tariffe);
                $successo = "Evento e tariffe aggiornati con successo.";
            } else {
                $errore = "Errore durante l'aggiornamento dell'evento.";
            }
        } else {
            $slug = eventoGeneraSlug($conn, $titolo);
            $copertina = eventoGestisciUploadCopertina($_FILES['immagine_copertina'] ?? [], null);

            $stmt = $conn->prepare(
                "INSERT INTO eventi (titolo, slug, sottotitolo, descrizione, immagine_copertina, data_evento, ora_evento, luogo, stato, giorni_limite_modifica, modalita_tariffe) VALUES (?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param(
                "sssssssssss",
                $titolo, $slug, $sottotitolo, $descrizione, $copertina, $data_evento, $ora_evento, $luogo, $stato, $giorni_limite, $modalita_tariffe
            );
            if ($stmt->execute()) {
                $nuovoId = $stmt->insert_id;
                eventoSalvaTariffe($conn, $nuovoId, $datiTariffe, $modalita_tariffe);
                $successo = "Evento creato con successo. Ora puoi aggiungere le tavole disponibili.";
                header("Location: evento_tavole.php?evento_id=" . $nuovoId . "&creato=1");
                exit();
            } else {
                $errore = "Errore durante la creazione dell'evento.";
            }
        }
    }
}

$eventoModifica = null;
$tariffeModifica = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM eventi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $eventoModifica = $stmt->get_result()->fetch_assoc();
    if ($eventoModifica) {
        $tariffeModifica = eventoOttieniTariffeComplete($conn, $id);
    }
}
// Default per un evento nuovo (nessuna tariffa ancora salvata)
if ($tariffeModifica === null) {
    $tariffeModifica = eventoOttieniTariffeComplete($conn, 0);
}

// ─── ELENCO EVENTI ───
$eventi = $conn->query(
    "SELECT e.*,
        (SELECT COUNT(*) FROM prenotazioni p WHERE p.evento_id = e.id AND p.stato != 'annullata') AS totale_prenotazioni,
        (SELECT COUNT(*) FROM prenotazioni p WHERE p.evento_id = e.id AND p.stato = 'in_attesa') AS in_attesa
     FROM eventi e
     ORDER BY e.data_evento DESC, e.id DESC"
)->fetch_all(MYSQLI_ASSOC);

$prenotazioniNonLette = contaPrenotazioniNonLette($conn);

$extraCssFiles = ['/assets/css/admin-eventi.css'];
$pageTitle = "Gestione Eventi - Moki Club Numana";
require_once __DIR__ . "/../includes/header.php";
?>

<main class="admin-container admin-eventi-container">
    <div class="admin-eventi-header">
        <h1>Gestione Eventi</h1>
        <div class="admin-eventi-header-actions">
            <a href="prenotazioni.php" class="btn-primary admin-eventi-btn-secondary">
                Prenotazioni
                <?php if ($prenotazioniNonLette > 0): ?>
                    <span class="admin-badge-nuove"><?php echo $prenotazioniNonLette; ?></span>
                <?php endif; ?>
            </a>
            <a href="tavole_modello.php" class="btn-primary admin-eventi-btn-secondary">Magazzino Tavole</a>
            <a href="dashboard.php" class="btn-primary admin-eventi-btn-secondary">Dashboard</a>
        </div>
    </div>

    <?php if ($successo): ?><p class="admin-msg admin-msg-success"><?php echo htmlspecialchars($successo); ?></p><?php endif; ?>
    <?php if ($errore): ?><p class="admin-msg admin-msg-error"><?php echo htmlspecialchars($errore); ?></p><?php endif; ?>

    <!-- FORM CREA / MODIFICA EVENTO -->
    <div class="admin-eventi-form-card">
        <h2><?php echo $eventoModifica ? 'Modifica evento' : 'Crea nuovo evento'; ?></h2>
        <form method="POST" enctype="multipart/form-data" class="modern-form admin-eventi-form">
            <?= app_csrf_input() ?>
            <input type="hidden" name="evento_id" value="<?php echo (int) ($eventoModifica['id'] ?? 0); ?>">

            <div class="form-row">
                <input type="text" name="titolo" placeholder="Titolo evento (es. Uscita 10 Agosto)" required
                    value="<?php echo htmlspecialchars($eventoModifica['titolo'] ?? ''); ?>">
                <input type="text" name="luogo" placeholder="Luogo (facoltativo)"
                    value="<?php echo htmlspecialchars($eventoModifica['luogo'] ?? ''); ?>">
            </div>

            <input type="text" name="sottotitolo" placeholder="Sottotitolo breve (facoltativo)"
                value="<?php echo htmlspecialchars($eventoModifica['sottotitolo'] ?? ''); ?>">

            <textarea name="descrizione" placeholder="Descrizione evento" rows="4"><?php echo htmlspecialchars($eventoModifica['descrizione'] ?? ''); ?></textarea>

            <div class="form-row">
                <div class="form-group">
                    <label>Data evento</label>
                    <input type="date" name="data_evento" value="<?php echo htmlspecialchars($eventoModifica['data_evento'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Ora evento</label>
                    <input type="time" name="ora_evento" value="<?php echo htmlspecialchars(substr($eventoModifica['ora_evento'] ?? '', 0, 5)); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Stato</label>
                    <select name="stato">
                        <?php $statoAttuale = $eventoModifica['stato'] ?? 'bozza'; ?>
                        <option value="bozza" <?php echo $statoAttuale === 'bozza' ? 'selected' : ''; ?>>Bozza (non visibile)</option>
                        <option value="pubblicato" <?php echo $statoAttuale === 'pubblicato' ? 'selected' : ''; ?>>Pubblicato</option>
                        <option value="archiviato" <?php echo $statoAttuale === 'archiviato' ? 'selected' : ''; ?>>Archiviato</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Giorni limite per modifiche cliente</label>
                    <input type="number" min="0" name="giorni_limite_modifica"
                        value="<?php echo (int) ($eventoModifica['giorni_limite_modifica'] ?? 2); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Immagine di copertina</label>
                <input type="file" name="immagine_copertina" accept=".jpg,.jpeg,.png,.webp">
                <?php if (!empty($eventoModifica['immagine_copertina'])): ?>
                    <img src="/<?php echo htmlspecialchars($eventoModifica['immagine_copertina']); ?>" class="admin-eventi-cover-preview" alt="Copertina attuale">
                <?php endif; ?>
            </div>

            <hr class="admin-eventi-form-divider">

            <h3 class="admin-eventi-tariffe-titolo">💶 Modalità quote di partecipazione</h3>
            <p class="admin-eventi-hint">Scegli se il tuo evento usa quote dettagliate oppure una sola quota unica valida per tutti. In modalità semplice la tavola propria è disabilitata.</p>

            <div class="form-group">
                <label>Tipo di quota</label>
                <select name="modalita_tariffe" id="modalita_tariffe_select">
                    <?php $modalitaAttuale = $eventoModifica['modalita_tariffe'] ?? 'dettagliata'; ?>
                    <option value="dettagliata" <?php echo $modalitaAttuale === 'dettagliata' ? 'selected' : ''; ?>>Dettagliata (tesserato / non tesserato / tavola propria)</option>
                    <option value="semplice" <?php echo $modalitaAttuale === 'semplice' ? 'selected' : ''; ?>>Quota unica per tutti</option>
                </select>
            </div>

            <div id="tariffe-dettagliate" class="admin-eventi-tariffe-grid" <?php echo $modalitaAttuale === 'semplice' ? 'style="display:none;"' : ''; ?>>
                <?php
                $tipiTariffe = [
                    'tesserato' => 'Partecipante tesserato 2026',
                    'non_tesserato' => 'Partecipante non tesserato',
                    'tavola_propria' => 'Con tavola propria',
                ];
                ?>
                <?php foreach ($tipiTariffe as $tipo => $label): ?>
                <div class="admin-eventi-tariffa-card">
                    <h4><?php echo htmlspecialchars($label); ?></h4>

                    <div class="form-group">
                        <label>Importo (€)</label>
                        <input type="number" step="0.01" min="0" name="tariffa_<?php echo $tipo; ?>_importo"
                            value="<?php echo number_format($tariffeModifica[$tipo]['importo'], 2, '.', ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Descrizione mostrata ai clienti</label>
                        <input type="text" name="tariffa_<?php echo $tipo; ?>_descrizione"
                            value="<?php echo htmlspecialchars($tariffeModifica[$tipo]['descrizione']); ?>" maxlength="255"
                            placeholder="Es. Tesseramento + quota partecipazione">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="tariffa-semplice" class="admin-eventi-tariffa-card" <?php echo $modalitaAttuale === 'dettagliata' ? 'style="display:none;"' : ''; ?>>
                <h4>Quota unica</h4>
                <div class="form-group">
                    <label>Importo (€)</label>
                    <input type="number" step="0.01" min="0" name="tariffa_quota_unica_importo"
                        value="<?php echo number_format(($tariffeModifica['quota_unica']['importo'] ?? 25), 2, '.', ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Descrizione mostrata ai clienti</label>
                    <input type="text" name="tariffa_quota_unica_descrizione" maxlength="255"
                        value="<?php echo htmlspecialchars(($tariffeModifica['quota_unica']['descrizione'] ?? 'Quota di partecipazione all\'evento')); ?>"
                        placeholder="Es. Quota di partecipazione all'evento">
                </div>
            </div>

            <button type="submit" name="salva_evento" class="btn-primary"><?php echo $eventoModifica ? 'Salva modifiche' : 'Crea evento'; ?></button>
            <?php if ($eventoModifica): ?>
                <a href="eventi.php" class="admin-eventi-btn-annulla">Annulla modifica</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- LISTA EVENTI -->
    <div class="admin-table-wrapper admin-eventi-list">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Data</th>
                    <th>Stato</th>
                    <th>Prenotazioni</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventi as $ev): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($ev['titolo']); ?></strong>
                        <?php if (!empty($ev['luogo'])): ?><br><small><?php echo htmlspecialchars($ev['luogo']); ?></small><?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(formattaDataIt($ev['data_evento'])) ?: '—'; ?></td>
                    <td><span class="admin-badge-stato admin-badge-stato-<?php echo htmlspecialchars($ev['stato']); ?>"><?php echo htmlspecialchars(ucfirst($ev['stato'])); ?></span></td>
                    <td>
                        <?php echo (int) $ev['totale_prenotazioni']; ?>
                        <?php if ((int) $ev['in_attesa'] > 0): ?>
                            <span class="admin-badge-nuove"><?php echo (int) $ev['in_attesa']; ?> in attesa</span>
                        <?php endif; ?>
                    </td>
                    <td class="admin-table-actions">
                        <a href="evento_tavole.php?evento_id=<?php echo (int) $ev['id']; ?>" class="admin-action-link">Tavole</a>
                        <a href="prenotazioni.php?evento_id=<?php echo (int) $ev['id']; ?>" class="admin-action-link">Prenotazioni</a>
                        <a href="eventi.php?edit=<?php echo (int) $ev['id']; ?>" class="admin-action-link">Modifica</a>
                        <a href="eventi.php?delete=<?php echo (int) $ev['id']; ?>&csrf_token=<?php echo urlencode(app_csrf_token()); ?>"
                            class="admin-action-link admin-action-link-danger"
                            onclick="return confirm('Eliminare questo evento e tutte le prenotazioni collegate?');">Elimina</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($eventi)): ?>
                <tr><td colspan="5" class="admin-table-empty">Nessun evento creato.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
(function () {
    var select = document.getElementById('modalita_tariffe_select');
    var dettagliate = document.getElementById('tariffe-dettagliate');
    var semplice = document.getElementById('tariffa-semplice');

    if (!select || !dettagliate || !semplice) return;

    function aggiornaVistaModalita() {
        var valore = select.value;
        var mostraDettagliate = valore === 'dettagliata';
        dettagliate.style.display = mostraDettagliate ? 'grid' : 'none';
        semplice.style.display = mostraDettagliate ? 'none' : 'block';

        var inputsDettagliate = dettagliate.querySelectorAll('input[required]');
        var inputsSemplice = semplice.querySelectorAll('input[required]');
        inputsDettagliate.forEach(function (input) {
            input.required = mostraDettagliate;
        });
        inputsSemplice.forEach(function (input) {
            input.required = !mostraDettagliate;
        });
    }

    select.addEventListener('change', aggiornaVistaModalita);
    aggiornaVistaModalita();
})();
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
