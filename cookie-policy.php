<?php
require_once __DIR__ . '/security_headers.php';
$pageTitle = "Cookie Policy - Moki Club Numana";
require_once __DIR__ . "/includes/header.php";
?>

<div class="legal-page-wrapper">
    <div class="legal-page-container">

        <div class="legal-page-header">
            <h1 class="legal-page-title">Cookie Policy</h1>
            <p class="legal-page-meta">Informativa sull'uso dei cookie ai sensi del D.Lgs. 196/2003 e del Reg. UE 2016/679</p>
            <p class="legal-page-date">Ultimo aggiornamento: <?php echo date('d/m/Y'); ?></p>
        </div>

        <div class="legal-page-content">

            <section class="legal-section">
                <h2>1. Cosa sono i cookie</h2>
                <p>I cookie sono piccoli file di testo che i siti web visitati dall'utente inviano al suo terminale (computer, tablet, smartphone), dove vengono memorizzati per essere ritrasmessi agli stessi siti alla visita successiva. Servono a far funzionare i siti in modo efficiente, a fornire informazioni ai proprietari del sito e a migliorare l'esperienza di navigazione.</p>
            </section>

            <section class="legal-section">
                <h2>2. Cookie utilizzati da questo sito</h2>

                <h3>2.1 Cookie tecnici (necessari)</h3>
                <p>Questi cookie sono indispensabili per il corretto funzionamento del sito e non richiedono il Suo consenso. Non raccolgono informazioni personali e non possono essere disattivati.</p>
                <div class="legal-table-wrapper">
                    <table class="legal-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Finalità</th>
                                <th>Durata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>PHPSESSID</code></td>
                                <td>Gestione della sessione utente (sicurezza form, token CSRF)</td>
                                <td>Sessione (eliminato alla chiusura del browser)</td>
                            </tr>
                            <tr>
                                <td><code>moki_cookie_consent</code></td>
                                <td>Memorizza la preferenza sul consenso ai cookie</td>
                                <td>12 mesi</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3>2.2 Cookie di terze parti</h3>
                <p>Il sito incorpora una mappa di Google Maps. Quando la mappa viene visualizzata, Google può impostare propri cookie. Per informazioni sui cookie di Google, consultare la <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy di Google</a>.</p>
            </section>

            <section class="legal-section">
                <h2>3. Come gestire i cookie</h2>
                <p>Può modificare le Sue preferenze sui cookie in qualsiasi momento tramite il banner presente sul sito oppure direttamente dalle impostazioni del Suo browser:</p>
                <ul>
                    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer">Google Chrome</a></li>
                    <li><a href="https://support.mozilla.org/it/kb/Attivare%20e%20disattivare%20i%20cookie" target="_blank" rel="noopener noreferrer">Mozilla Firefox</a></li>
                    <li><a href="https://support.apple.com/it-it/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer">Apple Safari</a></li>
                    <li><a href="https://support.microsoft.com/it-it/windows/eliminare-e-gestire-i-cookie-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener noreferrer">Microsoft Edge</a></li>
                </ul>
                <p>La disabilitazione dei cookie tecnici potrebbe compromettere il corretto funzionamento del sito.</p>
            </section>

            <section class="legal-section">
                <h2>4. Titolare del trattamento</h2>
                <div class="legal-info-box">
                    <strong>Moki Club Numana</strong><br>
                    Piazzale di Porto Molo Nord, 60026 Numana AN<br>
                    Email: <a href="mailto:infomokiclubnumana@gmail.com">infomokiclubnumana@gmail.com</a><br>
                    Telefono: <a href="tel:+393332028111">+39 333 202 8111</a>
                </div>
                <p>Per ulteriori informazioni sul trattamento dei dati personali consulta la nostra <a href="/privacy-policy.php">Privacy Policy</a>.</p>
            </section>

        </div>

        <div class="legal-page-footer">
            <a href="/" class="legal-back-link">← Torna alla Home</a>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
