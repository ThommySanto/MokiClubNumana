<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';

$extraCssFiles = ['/assets/css/cliente-form-signature.css', '/assets/css/lang-switcher.css'];
$pageTitle = "Rimessaggio Tavola — Moki Club Numana";
$seoMeta = [
    'description'    => 'Prenota il rimessaggio della tua tavola da SUP, surf o windsurf al Moki Club Numana. Custodia sicura a Numana, sul mare delle Marche.',
    'keywords'       => 'rimessaggio tavola numana, rimessaggio sup numana, custodia tavola windsurf numana, deposito tavola numana, moki club rimessaggio',
    'canonical'      => 'https://mokiclub.infinityfreeapp.com/cliente/rimessaggio.php',
    'og_title'       => 'Rimessaggio Tavola — Moki Club Numana',
    'og_description' => 'Prenota il rimessaggio della tua tavola da SUP, surf o windsurf al Moki Club Numana. Sicuro, comodo, direttamente sul mare.',
    'schema'         => json_encode([
        '@context'    => 'https://schema.org',
        '@type'       => 'WebPage',
        'name'        => 'Rimessaggio Tavola — Moki Club Numana',
        'url'         => 'https://mokiclub.infinityfreeapp.com/cliente/rimessaggio.php',
        'description' => 'Modulo di prenotazione rimessaggio tavola al Moki Club Numana.',
        'isPartOf'    => ['@type' => 'WebSite', 'url' => 'https://mokiclub.infinityfreeapp.com/'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
];
require_once __DIR__ . "/../includes/header.php";
?>

<style>
/* ── Date input neumorphic style ── */
input[data-date-it] {
    width: 100%;
    min-height: 56px;
    border: 1px solid rgba(61, 68, 104, 0.18);
    outline: none;
    border-radius: 18px;
    padding: 18px 54px 18px 22px;
    background-color: #f7f9fc;
    box-shadow:
        0 10px 24px rgba(190, 195, 207, 0.45),
        inset 4px 4px 10px rgba(190, 195, 207, 0.55),
        inset -4px -4px 10px rgba(255, 255, 255, 0.95);
    font: inherit;
    font-size: 16px;
    color: #3d4468;
    -webkit-text-fill-color: #3d4468;
    caret-color: #3d4468;
    font-variant-numeric: tabular-nums;
    -webkit-appearance: auto;
    appearance: auto;
    background-clip: padding-box;
    box-sizing: border-box;
}

input[data-date-it]:focus {
    box-shadow:
        0 12px 28px rgba(190, 195, 207, 0.55),
        inset 3px 3px 8px rgba(190, 195, 207, 0.6),
        inset -3px -3px 8px rgba(255, 255, 255, 1);
    background-color: #ffffff;
    border-color: rgba(61, 68, 104, 0.28);
}

input[data-date-it]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 1;
    width: 20px;
    height: 20px;
    margin-right: 2px;
    filter: invert(23%) sepia(16%) saturate(604%) hue-rotate(194deg) brightness(91%) contrast(89%);
}

input[data-date-it]::-webkit-datetime-edit,
input[data-date-it]::-webkit-datetime-edit-text,
input[data-date-it]::-webkit-datetime-edit-month-field,
input[data-date-it]::-webkit-datetime-edit-day-field,
input[data-date-it]::-webkit-datetime-edit-year-field {
    color: #3d4468;
}

input[data-date-it]::placeholder {
    color: rgba(61, 68, 104, 0.75);
    opacity: 1;
}

@media (hover: none), (pointer: coarse), (max-width: 768px) {
    input[data-date-it] {
        min-height: 50px;
        padding: 12px 14px;
        border-radius: 14px;
        background-color: #ffffff;
        box-shadow:
            0 8px 18px rgba(190, 195, 207, 0.34),
            inset 2px 2px 6px rgba(190, 195, 207, 0.35),
            inset -2px -2px 6px rgba(255, 255, 255, 0.95);
        cursor: pointer;
    }

    input[data-date-it]::-webkit-calendar-picker-indicator {
        width: 22px;
        height: 22px;
    }
}
</style>

<div class="form-wrapper">

    <!-- SELETTORE LINGUA (IT / EN / FR / ES / DE) -->
    <div class="lang-switcher-wrapper" data-lang-switcher></div>

    <div class="form-card">
        <h2 class="form-title" data-i18n="rimessaggio_title">Modulo Rimessaggio Moki Club Numana</h2>


        <form action="submit_rimessaggio.php" method="POST" enctype="multipart/form-data" class="modern-form">
            <?= app_csrf_input() ?>

            <!-- DATI PERSONALI -->
            <div class="form-row">
                <input type="text" name="nome" placeholder="Nome" required autocomplete="off"
                    data-i18n-placeholder="nome_placeholder">
                <input type="text" name="cognome" placeholder="Cognome" required autocomplete="off"
                    data-i18n-placeholder="cognome_placeholder">
            </div>

            <input type="text" name="luogo_nascita" placeholder="Luogo di nascita" autocomplete="off"
                data-i18n-placeholder="luogo_nascita_placeholder">

            <div class="form-row form-row-dati-anagrafici">
                <div class="form-group form-group-date-small">
                    <label data-i18n="data_nascita_label">Data di nascita</label>
                    <input type="date" name="data_nascita" autocomplete="off" placeholder="gg/mm/aaaa" data-date-it
                        data-i18n-placeholder="date_placeholder">
                </div>

                <div class="form-group form-group-address-large">
                    <label data-i18n="indirizzo_label">Indirizzo</label>
                    <input type="text" name="indirizzo" placeholder="Indirizzo" autocomplete="off"
                        data-i18n-placeholder="indirizzo_placeholder">
                </div>
            </div>

            <div class="form-row">
                <input type="text" name="citta" placeholder="Città" autocomplete="off"
                    data-i18n-placeholder="citta_placeholder">
                <input type="text" name="cap" placeholder="CAP" autocomplete="off"
                    data-i18n-placeholder="cap_placeholder">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label data-i18n="telefono_label">Telefono</label>
                    <div class="phone-input-container">
                        <div class="prefix-selector">
                            <select name="prefisso_tel">
                                <option value="+39">IT (+39)</option>
                                <option value="+44">UK (+44)</option>
                                <option value="+33">FR (+33)</option>
                                <option value="+49">DE (+49)</option>
                                <option value="+41">CH (+41)</option>
                                <option value="+43">AT (+43)</option>
                            </select>
                        </div>
                        <input type="tel" name="telefono" placeholder="333 0000000" required autocomplete="off"
                            class="input-tel" data-i18n-placeholder="telefono_placeholder">
                    </div>
                </div>
                <div class="form-group">
                    <label data-i18n="email_label">Email</label>
                    <input type="email" name="email" placeholder="Email" autocomplete="off"
                        data-i18n-placeholder="email_placeholder">
                </div>
            </div>

            <!-- DATI ATTREZZATURA -->
            <h3 class="section-title" data-i18n="dati_tavola_title">Dati Tavola</h3>

            <div class="form-row">
                <input type="text" name="tavola_marca" placeholder="Tavola Marca" autocomplete="off"
                    data-i18n-placeholder="tavola_marca_placeholder">
                <input type="text" name="tavola_modello" placeholder="Tavola Modello" autocomplete="off"
                    data-i18n-placeholder="tavola_modello_placeholder">
            </div>

            <input type="text" name="tavola_anno" placeholder="Anno Tavola" autocomplete="off"
                data-i18n-placeholder="tavola_anno_placeholder">

            <select name="sacca" autocomplete="off">
                <option value="" disabled selected hidden data-i18n="sacca_placeholder">Sacca</option>
                <option value="Si" data-i18n="sacca_si">Sì</option>
                <option value="No" data-i18n="sacca_no">No</option>
            </select>

            <input type="text" name="pagaia" placeholder="Pagaia (Marca e Modello)" autocomplete="off"
                data-i18n-placeholder="pagaia_placeholder">
            <input type="text" name="altro" placeholder="Altro materiale lasciato" autocomplete="off"
                data-i18n-placeholder="altro_placeholder">

            <!-- DOCUMENTI -->
            <h3 class="section-title" data-i18n="dati_documento_title">Dati Documento</h3>

            <select name="adulto_kid" required autocomplete="off">
                <option value="" disabled selected hidden data-i18n="adulto_kid_placeholder">Adulto o Kid</option>
                <option value="Adulto" data-i18n="categoria_adulto">Adulto</option>
                <option value="Kid" data-i18n="categoria_kid">Kid</option>
            </select>

            <select name="tipo_documento" autocomplete="off">
                <option value="" disabled selected hidden data-i18n="tipo_documento_placeholder">Tipo Documento</option>
                <option value="Carta Identità" data-i18n="doc_carta_identita_alt">Carta Identità</option>
                <option value="Codice Fiscale" data-i18n="doc_codice_fiscale">Codice Fiscale</option>
                <option value="Patente" data-i18n="doc_patente">Patente</option>
                <option value="Passaporto" data-i18n="doc_passaporto">Passaporto</option>
            </select>

            <input type="text" name="numero_documento" placeholder="Numero Documento" autocomplete="off"
                data-i18n-placeholder="numero_documento_placeholder">

            <!-- TIPO RIMESSAGGIO -->
            <h3 class="section-title" data-i18n="tipo_rimessaggio_placeholder">Tipo Rimessaggio</h3>

            <select name="tipo_rimessaggio" required autocomplete="off">
                <option value="" disabled selected hidden data-i18n="tipo_rimessaggio_placeholder">Tipo Rimessaggio</option>
                <option value="Settimanale" data-i18n="tr_settimanale">Settimanale (90€ + 10€ tesseramento)</option>
                <option value="Mensile" data-i18n="tr_mensile">Mensile (180€ + 10€ tesseramento)</option>
                <option value="Annuale" data-i18n="tr_annuale">Annuale (450€)</option>
            </select>

            <div class="form-row">
                <input type="number" step="0.01" name="acconto" placeholder="Acconto €" autocomplete="off"
                    data-i18n-placeholder="acconto_placeholder">
                <!-- <input type="number" step="0.01" name="saldo_totale" id="saldo_totale" placeholder="Saldo Totale €"
                    readonly autocomplete="off"> -->
            </div>

            <div class="form-group">
                <label data-i18n="data_versamento_label">Data versamento acconto</label>
                <input type="date" name="data_versamento_acconto" autocomplete="off" placeholder="gg/mm/aaaa" data-date-it
                    data-i18n-placeholder="date_placeholder">
            </div>

            <div class="form-group">
                <label data-i18n="ricevuta_label">Ricevuta di pagamento</label>
                <input type="file" name="ricevuta_pagamento" accept=".pdf,.jpg,.jpeg,.png"
                    autocomplete="off">
                <small class="cliente-receipt-help" data-i18n="ricevuta_help">
                    Carica la ricevuta di pagamento in formato PDF o immagine.
                </small>
            </div>

            <!-- FIRMA -->
            <div class="signature-section cliente-signature-section">
                <label class="cliente-signature-label" data-i18n="firma_label">Firma Digitale</label>
                <div class="signature-placeholder" id="open-signature">
                    <span id="placeholder-text" data-i18n="firma_placeholder_text">🖌️ Tocca qui per firmare</span>
                    <img id="signature-preview" class="cliente-signature-preview-hidden">
                </div>
            </div>

            <!-- MODAL FIRMA -->
            <div id="signature-modal" class="signature-modal">
                <div class="modal-content-neu">
                    <h2 class="cliente-signature-modal-title" data-i18n="firma_modal_title">Firma qui</h2>
                    <p class="cliente-signature-modal-subtitle" data-i18n="firma_modal_subtitle">Usa il dito o il mouse per firmare nello spazio bianco
                    </p>

                    <canvas id="signature-pad" class="modal-signature-pad"></canvas>

                    <div class="cliente-signature-actions-row">
                        <button type="button" class="neu-button mini-btn cliente-signature-action-btn" id="save-signature" data-i18n="btn_conferma">Conferma</button>
                        <button type="button" class="neu-button mini-btn logout cliente-signature-action-btn" id="clear-signature" data-i18n="btn_cancella">Cancella</button>
                        <button type="button" class="neu-button mini-btn logout cliente-signature-action-btn cliente-signature-close-btn" id="close-modal" data-i18n="btn_chiudi">Chiudi</button>
                    </div>
                </div>
            </div>

            <!-- Rimosso duplicato della checkbox privacy e corretto il comportamento -->
            <div class="checkbox-group">
                <label class="container">
                    <input type="checkbox" name="privacy" id="privacy-checkbox" disabled>
                    <div class="checkmark"></div>
                    <span data-i18n="privacy_text">Accetto le condizioni della privacy policy</span>
                    <span data-i18n="privacy_hint">(Leggi le privacy policy per abilitare)</span>
                </label>
                <p class="cliente-privacy-note">
                    <a href="../includes/privacy.pdf" target="_blank" id="privacy-link"><strong data-i18n="privacy_link_text">Leggi le privacy policy</strong></a>
                </p>
            </div>

            <input type="hidden" name="firma" id="firma">

            <button type="submit" class="btn-primary cliente-form-submit-btn" data-i18n="salva_rimessaggio_btn">
                Salva Rimessaggio
            </button>

        </form>
    </div>
</div>
<script src="/assets/js/i18n.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/i18n.js'); ?>"></script>
<script src="/assets/js/cliente-rimessaggio.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/cliente-rimessaggio.js'); ?>"></script>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
