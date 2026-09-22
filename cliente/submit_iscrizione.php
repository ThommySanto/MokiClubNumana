<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';

if (!app_is_post()) {
    http_response_code(405);
    exit('Metodo non consentito');
}

app_require_csrf();

require_once __DIR__ . "/../config/config.php";

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

$nome = $_POST['nome'];
$cognome = $_POST['cognome'];
$luogo_nascita = $_POST['luogo_nascita'];
$data_nascita = parseItalianOrIsoDate($_POST['data_nascita'] ?? null);

if ($data_nascita === null) {
    http_response_code(422);
    exit('Data di nascita non valida. Usa il formato gg/mm/aaaa.');
}
$indirizzo = $_POST['indirizzo'];
$citta = $_POST['citta'];
$cap = $_POST['cap'];
$telefono = $_POST['telefono'];
$email = $_POST['email'];
$tipo_documento = $_POST['tipo_documento'];
$numero_documento = $_POST['numero_documento'];
$adulto_kid = $_POST['categoria'];

$interessato_offerte = isset($_POST['newsletter']) ? 1 : 0;
$dichiaro_nuoto = isset($_POST['sa_nuotare']) ? 1 : 0;
$consenso_privacy = isset($_POST['privacy']) ? 1 : 0;

$tipo_iscrizione = $_POST['tipo_iscrizione'];
$allowed_tipi = ['Iscrizione', 'Iscrizione + Noleggio', 'Abbonamento', 'Lezione', 'Alba', 'Notturna'];
if (!in_array($tipo_iscrizione, $allowed_tipi, true)) {
    die(json_encode(['success' => false, 'message' => 'Tipo iscrizione non valido.']));
}
$data_iscrizione = date("Y-m-d");

$anno = date("Y");
$target_dir = "uploads/firme/" . $anno . "/";

if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$target_file = NULL;

if (!empty($_POST['firma_base64'])) {
    $firma_base64 = str_replace('data:image/png;base64,', '', $_POST['firma_base64']);
    $firma_base64 = str_replace(' ', '+', $firma_base64);
    $firma_data = base64_decode($firma_base64);

    $nome_pulito = preg_replace('/[^a-zA-Z0-9]/', '_', $nome);
    $cognome_pulito = preg_replace('/[^a-zA-Z0-9]/', '_', $cognome);

    $nome_file = strtolower($nome_pulito . "_" . $cognome_pulito . "_" . date("Ymd_His") . ".png");

    // Salvataggio fisico in cliente/uploads/firme/2026/
    file_put_contents($target_dir . $nome_file, $firma_data);

    // Percorso da salvare nel DB (relativo alla root del sito)
    $target_file = "cliente/" . $target_dir . $nome_file;
}


$stmt = $conn->prepare("
INSERT INTO iscritti
(nome, cognome, luogo_nascita, data_nascita,
indirizzo_residenza, citta_residenza, cap,
telefono, email,
tipo_documento, numero_documento,
adulto_kid,
interessato_offerte, dichiaro_nuoto, consenso_privacy,
data_iscrizione, tipo_iscrizione,
firma)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "ssssssssssssiiisss",
    $nome,
    $cognome,
    $luogo_nascita,
    $data_nascita,
    $indirizzo,
    $citta,
    $cap,
    $telefono,
    $email,
    $tipo_documento,
    $numero_documento,
    $adulto_kid,
    $interessato_offerte,
    $dichiaro_nuoto,
    $consenso_privacy,
    $data_iscrizione,
    $tipo_iscrizione,
    $target_file
);
if ($stmt->execute()) {

    // Invia email solo se valida
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {

        require_once __DIR__ . '/../includes/mailer.php';
        require_once __DIR__ . '/../includes/email_templates/iscrizione_template.php';

        $html = templateIscrizione($nome);

        inviaEmail(
            'iscrizione',
            $email,
            'Benvenuto al MOKI CLUB NUMANA',
            $html
        );
    }

    // Pagina di Successo Decorata
    $pageTitle = "Iscrizione Completata";
    $extraCssFiles = ['/assets/css/cliente-submit-result.css'];
    include __DIR__ . "/../includes/header.php";
    ?>
    <main class="admin-container cliente-submit-main" data-auto-redirect-url="/index.php" data-auto-redirect-delay="5000">
        <div class="success-card cliente-submit-card">
            <div class="success-icon cliente-submit-icon">✅</div>
            <h1 class="cliente-submit-title">Iscrizione Completata!</h1>
            <p class="cliente-submit-message">
                Grazie <strong><?php echo htmlspecialchars($nome); ?></strong>, la tua richiesta è stata inviata con
                successo.<br>
                Ti abbiamo inviato un'email di conferma all'indirizzo <?php echo htmlspecialchars($email); ?>.
            </p>
            <p class="cliente-submit-subtitle">Verrai reindirizzato alla home tra pochi secondi...</p>
            <div class="loading-bar cliente-submit-loading-bar">
                <div class="loading-progress cliente-submit-loading-progress">
                </div>
            </div>
            <a href="/index.php" class="btn-primary cliente-submit-link">Torna subito alla Home</a>
        </div>
    </main>
    <script src="/assets/js/cliente-submit-redirect.js"></script>
    <?php
    include __DIR__ . "/../includes/footer.php";

} else {
    $pageTitle = "Errore Iscrizione";
    $extraCssFiles = ['/assets/css/cliente-submit-result.css'];
    include __DIR__ . "/../includes/header.php";
    error_log('Errore submit_iscrizione: ' . $stmt->error);
    echo "<div class='error-message cliente-submit-error-box'>Errore durante l'invio. Riprova più tardi.</div>";
    include __DIR__ . "/../includes/footer.php";
}

$stmt->close();
$conn->close();
?>