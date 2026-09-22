<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . '/../includes/eventi_functions.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    http_response_code(404);
    exit('Evento non trovato.');
}

$stmt = $conn->prepare("SELECT * FROM eventi WHERE slug = ? AND stato = 'pubblicato' LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$evento = $stmt->get_result()->fetch_assoc();

if (!$evento) {
    http_response_code(404);
    exit('Evento non trovato o non disponibile.');
}

$eventoId = (int) $evento['id'];
$tavole = eventoElencoTavoleConDisponibilita($conn, $eventoId);
$tariffe = eventoOttieniTariffeComplete($conn, $eventoId, $evento['modalita_tariffe'] ?? 'dettagliata');
$modalitaTariffeEvento = $evento['modalita_tariffe'] ?? 'dettagliata';

$extraCssFiles = ['/assets/css/eventi.css'];
$pageTitle = htmlspecialchars($evento['titolo']) . " — Moki Club Numana";
$bodyAttributes = 'data-modalita-tariffe="' . htmlspecialchars($modalitaTariffeEvento, ENT_QUOTES, 'UTF-8') . '"';
$seoMeta = [
    'description' => 'Prenota il tuo posto per ' . $evento['titolo'] . ' al Moki Club Numana.',
    'canonical'    => 'https://mokiclub.infinityfreeapp.com/cliente/evento.php?slug=' . urlencode($slug),
];
require_once __DIR__ . "/../includes/header.php";
?>

<div class="evento-page-wrapper">

    <section class="evento-hero-card">
        <?php if (!empty($evento['immagine_copertina'])): ?>
            <img src="/<?php echo htmlspecialchars($evento['immagine_copertina']); ?>" alt="<?php echo htmlspecialchars($evento['titolo']); ?>" class="evento-hero-img">
        <?php else: ?>
            <div class="evento-hero-noimg">🌊</div>
        <?php endif; ?>
        <div class="evento-hero-body">
            <h1><?php echo htmlspecialchars($evento['titolo']); ?></h1>
            <?php if (!empty($evento['sottotitolo'])): ?><p class="evento-hero-sottotitolo"><?php echo htmlspecialchars($evento['sottotitolo']); ?></p><?php endif; ?>
            <div class="evento-hero-meta">
                <?php if (!empty($evento['data_evento'])): ?><span>📅 <?php echo htmlspecialchars(formattaDataIt($evento['data_evento'])); ?></span><?php endif; ?>
                <?php if (!empty($evento['ora_evento'])): ?><span>🕐 <?php echo htmlspecialchars(substr($evento['ora_evento'], 0, 5)); ?></span><?php endif; ?>
                <?php if (!empty($evento['luogo'])): ?><span>📍 <?php echo htmlspecialchars($evento['luogo']); ?></span><?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($evento['descrizione'])): ?>
    <section class="evento-descrizione">
        <p><?php echo nl2br(htmlspecialchars($evento['descrizione'])); ?></p>
    </section>
    <?php endif; ?>

    <!-- CARD TARIFFE -->
    <section class="evento-tariffe-section">
        <h2>Tariffe di partecipazione</h2>
        <div class="evento-tariffe-cards">
            <?php if ($modalitaTariffeEvento === 'semplice'): ?>
                <div class="evento-tariffa-card">
                    <h4>Quota unica</h4>
                    <p class="evento-tariffa-prezzo"><?php echo number_format($tariffe['quota_unica']['importo'], 2, ',', ''); ?>€</p>
                    <p class="evento-tariffa-desc"><?php echo htmlspecialchars($tariffe['quota_unica']['descrizione']); ?></p>
                </div>
            <?php else: ?>
                <?php $etichetteTariffe = ['tesserato' => 'Tesserato 2026', 'non_tesserato' => 'Non tesserato', 'tavola_propria' => 'Con tavola propria']; ?>
                <?php foreach ($etichetteTariffe as $tipo => $etichetta): ?>
                <div class="evento-tariffa-card">
                    <h4><?php echo htmlspecialchars($etichetta); ?></h4>
                    <p class="evento-tariffa-prezzo"><?php echo number_format($tariffe[$tipo]['importo'], 2, ',', ''); ?>€</p>
                    <p class="evento-tariffa-desc"><?php echo htmlspecialchars($tariffe[$tipo]['descrizione']); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($modalitaTariffeEvento === 'dettagliata'): ?>
    <!-- SELEZIONA TAVOLA PROPRIA -->
    <section class="evento-scelta-tavola">
        <h2>Come parteciperai?</h2>
        <div class="evento-toggle-tavola">
            <label class="evento-toggle-label">
                <input type="radio" name="tipo-partecipazione" value="noleggio" checked class="evento-toggle-input">
                <span>Noleggierò una tavola</span>
            </label>
            <label class="evento-toggle-label">
                <input type="radio" name="tipo-partecipazione" value="propria" class="evento-toggle-input">
                <span>Ho già una tavola</span>
            </label>
        </div>
    </section>
    <?php else: ?>
    <section class="evento-scelta-tavola">
        <h2>Modalità quota unica</h2>
        <p class="evento-tavole-hint">Per questo evento la quota è unica e valida per tutti, senza tavola propria.</p>
        <input type="hidden" name="tipo-partecipazione" value="noleggio">
    </section>
    <?php endif; ?>

    <!-- SELEZIONE TAVOLE (nascosto se tavola propria) -->
    <section class="evento-tavole-section" id="sezione-tavole">
        <h2>Scegli il tuo posto / tavola</h2>
        <p class="evento-tavole-hint">Seleziona una o più tavole. Per ogni posto occupato ti chiederemo nome e cognome della persona.</p>

        <div class="evento-tavole-grid">
            <?php foreach ($tavole as $t): ?>
            <?php $esaurita = $t['posti_disponibili'] <= 0; ?>
            <div class="evento-tavola-card <?php echo $esaurita ? 'evento-tavola-esaurita' : ''; ?>"
                 data-tavola-id="<?php echo (int) $t['id']; ?>"
                 data-tavola-nome="<?php echo htmlspecialchars($t['nome']); ?>"
                 data-tavola-tipo="<?php echo htmlspecialchars($t['tipo']); ?>"
                 data-posti-disponibili="<?php echo (int) $t['posti_disponibili']; ?>">

                <div class="evento-tavola-foto">
                    <?php
                    $foto = array_filter([$t['foto1'], $t['foto2'], $t['foto3']]);
                    if (!empty($foto)):
                        foreach ($foto as $i => $f): ?>
                            <img src="/<?php echo htmlspecialchars($f); ?>" alt="<?php echo htmlspecialchars($t['nome']); ?>" class="<?php echo $i === 0 ? 'evento-tavola-foto-attiva' : ''; ?>">
                        <?php endforeach;
                    else: ?>
                        <div class="evento-tavola-foto-placeholder">🏄</div>
                    <?php endif; ?>
                    <span class="evento-tavola-badge <?php echo $esaurita ? 'evento-tavola-badge-esaurita' : 'evento-tavola-badge-disponibile'; ?>">
                        <?php echo $esaurita ? 'Esaurita' : $t['posti_disponibili'] . ' posti liberi'; ?>
                    </span>
                </div>

                <div class="evento-tavola-body">
                    <h3><?php echo htmlspecialchars($t['nome']); ?></h3>
                    <?php if (!empty($t['descrizione'])): ?><p class="evento-tavola-descrizione"><?php echo htmlspecialchars($t['descrizione']); ?></p><?php endif; ?>

                    <?php if (!$esaurita): ?>
                        <?php if ($t['tipo'] === 'gruppo'): ?>
                            <label class="evento-tavola-select-label">
                                Posti da occupare
                                <input type="number" min="0" max="<?php echo (int) $t['posti_disponibili']; ?>" value="0" class="evento-tavola-qty-input">
                            </label>
                        <?php else: ?>
                            <label class="evento-tavola-select-label evento-tavola-checkbox-label">
                                <input type="checkbox" class="evento-tavola-checkbox">
                                Prenota questa tavola
                            </label>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($tavole)): ?>
                <p class="evento-tavole-empty">Al momento non ci sono tavole disponibili per questo evento.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- FORM PRENOTAZIONE -->
    <section class="evento-form-section">
        <h2>Completa la prenotazione</h2>

        <form action="submit_prenotazione.php" method="POST" class="modern-form evento-prenotazione-form" id="form-prenotazione">
            <?= app_csrf_input() ?>
            <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
            <input type="hidden" name="modalita" id="input-modalita" value="singola">
            <input type="hidden" name="posti_json" id="input-posti-json" value="[]">
            <input type="hidden" name="tipo_partecipazione" id="input-tipo-partecipazione" value="noleggio">

            <h3 class="section-title">I tuoi dati (referente)</h3>
            <div class="form-row">
                <input type="text" name="referente_nome" placeholder="Nome" required autocomplete="off">
                <input type="text" name="referente_cognome" placeholder="Cognome" required autocomplete="off">
            </div>
            <div class="form-row">
                <input type="tel" name="referente_telefono" placeholder="Telefono" required autocomplete="off">
                <input type="email" name="referente_email" placeholder="Email" required autocomplete="off">
            </div>

            <div id="riepilogo-tavole" class="evento-riepilogo-tavole">
                <p class="evento-riepilogo-vuoto">Nessuna tavola selezionata.</p>
            </div>

            <textarea name="note" placeholder="Note aggiuntive (facoltativo)" rows="3"></textarea>

            <h3 class="section-title">Metodo di pagamento</h3>
            <p class="evento-tavole-hint">Seleziona come preferisci saldare la quota di partecipazione. È solo un'indicazione per lo staff: il pagamento avviene direttamente con noi, non in questo form.</p>
            <div class="checkbox-group evento-pagamento-group" id="gruppo-pagamento">
                <label class="container">
                    <input type="checkbox" name="metodo_pagamento[]" value="bonifico" class="evento-pagamento-checkbox" id="chk-pagamento-bonifico">
                    <div class="checkmark"></div>
                    <span>Bonifico bancario<br><small>IBAN: <strong>IT44K0538737471000004261922</strong></small></span>
                </label>
                <label class="container">
                    <input type="checkbox" name="metodo_pagamento[]" value="contanti_carta" class="evento-pagamento-checkbox" id="chk-pagamento-contanti">
                    <div class="checkmark"></div>
                    <span>Contanti / Carta al Moki Club Numana<br><small>Il pagamento diretto va effettuato entro il giorno prima dell’evento</small></span>
                </label>
            </div>

            <div class="checkbox-group">
                <label class="container">
                    <input type="checkbox" name="privacy" required>
                    <div class="checkmark"></div>
                    <span>Accetto le condizioni della <a href="/includes/privacy.pdf" target="_blank">privacy policy</a></span>
                </label>
            </div>

            <button type="submit" class="btn-primary evento-submit-btn" id="btn-submit-prenotazione">Invia richiesta di prenotazione</button>
        </form>
    </section>

</div>

<script src="/assets/js/cliente-evento.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/cliente-evento.js'); ?>"></script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
</body>