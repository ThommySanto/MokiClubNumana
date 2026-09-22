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

// =============================
// RECUPERO DATI POST
// =============================
$cognome = $_POST['cognome'] ?? null;
$nome = $_POST['nome'] ?? null;
$data_nascita = parseItalianOrIsoDate($_POST['data_nascita'] ?? null);
$luogo_nascita = $_POST['luogo_nascita'] ?? null;
$indirizzo = $_POST['indirizzo'] ?? null;
$citta = $_POST['citta'] ?? null;
$cap = $_POST['cap'] ?? null;
$telefono = $_POST['telefono'] ?? null;
$email = $_POST['email'] ?? null;

$tavola_marca = $_POST['tavola_marca'] ?? null;
$tavola_modello = $_POST['tavola_modello'] ?? null;
$tavola_anno = $_POST['tavola_anno'] ?? null;
$sacca = $_POST['sacca'] ?? null;
$pagaia = $_POST['pagaia'] ?? null;
$altro = $_POST['altro'] ?? null;

$adulto_kid = $_POST['adulto_kid'] ?? null;
$tipo_documento = $_POST['tipo_documento'] ?? null;
$numero_documento = $_POST['numero_documento'] ?? null;

$tipo_rimessaggio = $_POST['tipo_rimessaggio'] ?? null;
$acconto = isset($_POST['acconto']) ? floatval($_POST['acconto']) : 0;

// =============================
// CALCOLO PREZZO
// =============================
switch ($tipo_rimessaggio) {
    case "Settimanale":
        $saldo_totale = 100;
        break;
    case "Mensile":
        $saldo_totale = 190;
        break;
    case "Annuale":
        $saldo_totale = 450;
        break;
    default:
        die("Tipo rimessaggio non valido");
}

$rimanente = $saldo_totale - $acconto;

$data_versamento_acconto = !empty($_POST['data_versamento_acconto'])
    ? parseItalianOrIsoDate($_POST['data_versamento_acconto'])
    : NULL;

if (!empty($_POST['data_nascita']) && $data_nascita === null) {
    http_response_code(422);
    exit('Data di nascita non valida. Usa il formato gg/mm/aaaa.');
}

if (!empty($_POST['data_versamento_acconto']) && $data_versamento_acconto === null) {
    http_response_code(422);
    exit('Data versamento acconto non valida. Usa il formato gg/mm/aaaa.');
}

// =============================
// SALVATAGGIO FIRMA
// =============================
$firma_path = NULL;

if (!empty($_POST['firma'])) {

    $firma_base64 = str_replace('data:image/png;base64,', '', $_POST['firma']);
    $firma_base64 = str_replace(' ', '+', $firma_base64);
    $firma_data = base64_decode($firma_base64);

    $anno = date("Y");
    $directory = __DIR__ . "/uploads/firme/" . $anno . "/";

    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    $nome_file = strtolower($nome . "_" . $cognome . "_" . time() . ".png");
    $percorso_completo = $directory . $nome_file;

    file_put_contents($percorso_completo, $firma_data);

    // Percorso relativo alla root per il DB
    $firma_path = "cliente/uploads/firme/" . $anno . "/" . $nome_file;
}

// =============================
// UPLOAD RICEVUTA (Adattato dalla logica delle firme)
// =============================
$ricevuta_path = NULL;

if (!empty($_FILES['ricevuta_pagamento']['name'])) {
    $cliente_folder = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '_', trim(($nome ?? '') . '_' . ($cognome ?? ''))));
    if ($cliente_folder === '' || $cliente_folder === '_') {
        $cliente_folder = 'cliente_senza_nome';
    }

    $directory = __DIR__ . '/uploads/ricevute/' . $cliente_folder . '/';

    // Crea la directory se non esiste
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    // Genera un nome unico per il file
    $original_name = basename($_FILES['ricevuta_pagamento']['name']);
    $clean_original_name = strtolower(preg_replace('/[^a-zA-Z0-9._-]+/', '_', $original_name));
    $file_name = date('Ymd_His') . '_' . $clean_original_name;
    $target = $directory . $file_name;

    // Sposta il file nella directory
    if (move_uploaded_file($_FILES['ricevuta_pagamento']['tmp_name'], $target)) {
        // Percorso relativo per il database
        $ricevuta_path = 'cliente/uploads/ricevute/' . $cliente_folder . '/' . $file_name;
    } else {
        error_log('Errore durante il caricamento della ricevuta.');
        http_response_code(500);
        exit('Errore durante il caricamento della ricevuta.');
    }
}

// =============================
// INSERT DATABASE
// =============================
$stmt = $conn->prepare("INSERT INTO rimessaggi (
cognome,
nome,
data_nascita,
luogo_nascita,
indirizzo,
citta,
cap,
telefono,
email,
tavola_marca,
tavola_modello,
tavola_anno,
sacca,
pagaia,
adulto_kid,
tipo_documento,
numero_documento,
altro,
tipo_rimessaggio,
acconto,
saldo_totale,
rimanente,
data_versamento_acconto,
ricevuta_pagamento,
firma
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

if (!$stmt) {
    error_log('Errore prepare submit_rimessaggio: ' . $conn->error);
    http_response_code(500);
    exit('Servizio temporaneamente non disponibile.');
}

$stmt->bind_param(
    "sssssssssssssssssssdddsss",
    $cognome,
    $nome,
    $data_nascita,
    $luogo_nascita,
    $indirizzo,
    $citta,
    $cap,
    $telefono,
    $email,
    $tavola_marca,
    $tavola_modello,
    $tavola_anno,
    $sacca,
    $pagaia,
    $adulto_kid,
    $tipo_documento,
    $numero_documento,
    $altro,
    $tipo_rimessaggio,
    $acconto,
    $saldo_totale,
    $rimanente,
    $data_versamento_acconto,
    $ricevuta_path,
    $firma_path
);

if ($stmt->execute()) {

    // Invia email solo se valida
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        require_once __DIR__ . '/../includes/mailer.php';
        require_once __DIR__ . '/../includes/email_templates/rimessaggio_template.php';

        $html = templateRimessaggio($nome);

        inviaEmail(
            'rimessaggio',
            $email,
            'Conferma rimessaggio MOKI CLUB NUMANA',
            $html
        );
    }

    // Mostra pagina di successo decorata (coerente con submit_iscrizione.php)
    $pageTitle = "Rimessaggio Completato";
    $extraCssFiles = ['/assets/css/cliente-submit-result.css'];
    require_once __DIR__ . "/../includes/header.php";
    ?>
    
    <main class="admin-container cliente-submit-main" data-auto-redirect-url="/index.php" data-auto-redirect-delay="5000">
        <div class="success-card cliente-submit-card">
            <div class="success-icon cliente-submit-icon">✅</div>
            <h1 class="cliente-submit-title">Rimessaggio Completato!</h1>
            <p class="cliente-submit-message">
                Grazie <strong><?php echo htmlspecialchars($nome); ?></strong>, il tuo rimessaggio è stato inviato con
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
    require_once __DIR__ . "/../includes/footer.php";
    $stmt->close();
    $conn->close();
    exit();

} else {
    error_log('Errore execute submit_rimessaggio: ' . $stmt->error);
    http_response_code(500);
    exit('Errore durante il salvataggio del rimessaggio.');
}
?>