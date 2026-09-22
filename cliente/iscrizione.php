<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';
$extraCssFiles = ['/assets/css/cliente-form-signature.css', '/assets/css/lang-switcher.css'];
$pageTitle = "Iscrizione al Club — Moki Club Numana";
$seoMeta = [
    'description'    => 'Iscriviti al Moki Club Numana. Compila il modulo di iscrizione per accedere alle attività di SUP, Windsurf, Surf e Foil a Numana.',
    'keywords'       => 'iscrizione moki club numana, iscriversi sup numana, modulo iscrizione water sports numana, club sup marche',
    'canonical'      => 'https://mokiclub.infinityfreeapp.com/cliente/iscrizione.php',
    'og_title'       => 'Iscriviti al Moki Club Numana',
    'og_description' => 'Compila il modulo di iscrizione al Moki Club Numana e inizia a praticare SUP, Windsurf, Surf e Foil.',
    'schema'         => json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'WebPage',
        'name'      => 'Iscrizione al Moki Club Numana',
        'url'       => 'https://mokiclub.infinityfreeapp.com/cliente/iscrizione.php',
        'description' => 'Modulo di iscrizione al Moki Club Numana per attività di water sports.',
        'isPartOf'  => ['@type' => 'WebSite', 'url' => 'https://mokiclub.infinityfreeapp.com/'],
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

    @media (hover: none),
    (pointer: coarse),
    (max-width: 768px) {
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
        <h2 class="form-title" data-i18n="iscrizione_title">Modulo Iscrizione Moki Club Numana</h2>

        <form action="submit_iscrizione.php" method="POST">
            <?= app_csrf_input() ?>

            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="nome" placeholder="Nome" required autocomplete="off"
                        data-i18n-placeholder="nome_placeholder">
                </div>

                <div class="form-group">
                    <input type="text" name="cognome" placeholder="Cognome" required autocomplete="off"
                        data-i18n-placeholder="cognome_placeholder">
                </div>
            </div>

            <div class="form-group">
                <input type="text" name="luogo_nascita" placeholder="Luogo di nascita" required autocomplete="off"
                    data-i18n-placeholder="luogo_nascita_placeholder">
            </div>

            <div class="form-row form-row-dati-anagrafici">
                <div class="form-group form-group-date-small">
                    <label data-i18n="data_nascita_label">Data di nascita</label>
                    <input type="date" name="data_nascita" required autocomplete="off" placeholder="gg/mm/aaaa"
                        data-date-it data-i18n-placeholder="date_placeholder">
                </div>

                <div class="form-group form-group-address-large">
                    <label data-i18n="indirizzo_residenza_label">Indirizzo di residenza</label>
                    <input type="text" name="indirizzo" placeholder="Indirizzo di residenza" required
                        autocomplete="off" data-i18n-placeholder="indirizzo_residenza_placeholder">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="citta" placeholder="Città" required autocomplete="off"
                        data-i18n-placeholder="citta_placeholder">
                </div>

                <div class="form-group">
                    <input type="text" name="cap" placeholder="CAP" required autocomplete="off"
                        data-i18n-placeholder="cap_placeholder">
                </div>
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
                    <input type="email" name="email" placeholder="Email" required autocomplete="off"
                        data-i18n-placeholder="email_placeholder">
                </div>
            </div>

            <div class="form-group">
                <select name="tipo_documento" required autocomplete="off">
                    <option value="" disabled selected data-i18n="tipo_documento_placeholder">Tipo Documento</option>
                    <option value="carta_identita" data-i18n="doc_carta_identita">Carta d'identità</option>
                    <option value="codice_fiscale" data-i18n="doc_codice_fiscale">Codice Fiscale</option>
                    <option value="patente" data-i18n="doc_patente">Patente</option>
                    <option value="passaporto" data-i18n="doc_passaporto">Passaporto</option>
                </select>
            </div>

            <div class="form-group">
                <input type="text" name="numero_documento" placeholder="Numero documento" required autocomplete="off"
                    data-i18n-placeholder="numero_documento_placeholder">
            </div>

            <div class="form-group">
                <select name="categoria" required>
                    <option value="" disabled selected data-i18n="categoria_placeholder">Adulto o Kid</option>
                    <option value="adulto" data-i18n="categoria_adulto">Adulto</option>
                    <option value="kid" data-i18n="categoria_kid">Kid</option>
                </select>
            </div>

            <div class="form-group">
                <select name="tipo_iscrizione" required>
                    <option value="" disabled selected data-i18n="tipo_iscrizione_placeholder">Tipo iscrizione</option>
                    <option value="Iscrizione" data-i18n="ti_iscrizione">Iscrizione</option>
                    <option value="Iscrizione + Noleggio" data-i18n="ti_iscrizione_noleggio">Iscrizione + Noleggio</option>
                    <option value="Abbonamento" data-i18n="ti_abbonamento">Abbonamento</option>
                    <option value="Lezione" data-i18n="ti_lezione">Lezione</option>
                    <option value="Alba" data-i18n="ti_alba">Alba</option>
                    <option value="Notturna" data-i18n="ti_notturna">Notturna</option>
                </select>
            </div>

            <div class="checkbox-group">
                <label class="container">
                    <input type="checkbox" name="newsletter">
                    <div class="checkmark"></div>
                    <span data-i18n="newsletter_text">Voglio ricevere comunicazioni via email</span>
                </label>

                <label class="container">
                    <input type="checkbox" name="sa_nuotare" required>
                    <div class="checkmark"></div>
                    <span data-i18n="sa_nuotare_text">Dichiaro di saper nuotare</span>
                </label>

                <label class="container">
                    <input type="checkbox" name="privacy" id="privacy-checkbox" disabled>
                    <div class="checkmark"></div>
                    <span data-i18n="privacy_text">Accetto le condizioni della privacy policy</span>
                    <strong> <span data-i18n="privacy_hint">(Leggi le privacy policy per abilitare)</span></strong>
                </label>
                <p class="cliente-privacy-note">
                    <a href="../includes/privacy.pdf" target="_blank" id="privacy-link"><strong data-i18n="privacy_link_text">Leggi le privacy
                            policy</strong></a>
                </p>
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
                        <button type="button" class="neu-button mini-btn cliente-signature-action-btn"
                            id="save-signature" data-i18n="btn_conferma">Conferma</button>
                        <button type="button" class="neu-button mini-btn logout cliente-signature-action-btn"
                            id="clear-signature" data-i18n="btn_cancella">Cancella</button>
                        <button type="button"
                            class="neu-button mini-btn logout cliente-signature-action-btn cliente-signature-close-btn"
                            id="close-modal" data-i18n="btn_chiudi">Chiudi</button>
                    </div>
                </div>
            </div>

            <!-- Visualizzazione della firma caricata con icona accanto -->
            <?php if (!empty($firma_url)): ?>
                <div class="form-group cliente-signature-uploaded-row">
                    <label class="cliente-signature-uploaded-label" data-i18n="firma_caricata_label">Firma Caricata</label>
                    <a href="/<?= htmlspecialchars($firma_url) ?>" target="_blank"
                        class="cliente-link-plain cliente-inline-flex-center">
                        <img src="/assets/img/icon-eye.svg" alt="Visualizza" class="cliente-eye-icon">
                    </a>
                </div>
            <?php endif; ?>



            <input type="hidden" name="firma_base64" id="firma_base64">

            <button type="submit" class="btn-primary cliente-form-submit-btn" data-i18n="invia_iscrizione_btn">Invia iscrizione</button>

        </form>
    </div>
</div>

<script
    src="/assets/js/i18n.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/i18n.js'); ?>"></script>
<script
    src="/assets/js/cliente-iscrizione.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/cliente-iscrizione.js'); ?>"></script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>