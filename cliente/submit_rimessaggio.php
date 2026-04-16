<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . '/../includes/security_utils.php';

if (!app_is_post()) {
    http_response_code(405);
    exit('Metodo non consentito');
}

app_require_csrf();

require_once __DIR__ . "/../config/config.php";

// =============================
// RECUPERO DATI POST
// =============================
$cognome = $_POST['cognome'] ?? null;
$nome = $_POST['nome'] ?? null;
$data_nascita = $_POST['data_nascita'] ?? null;
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
    ? $_POST['data_versamento_acconto']
    : NULL;

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
    $anno = date('Y');
    $directory = __DIR__ . '/uploads/ricevute/' . $anno . '/';

    // Crea la directory se non esiste
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    // Genera un nome unico per il file
    $file_name = strtolower($nome . '_' . $cognome . '_' . time() . '_' . basename($_FILES['ricevuta_pagamento']['name']));
    $target = $directory . $file_name;

    // Sposta il file nella directory
    if (move_uploaded_file($_FILES['ricevuta_pagamento']['tmp_name'], $target)) {
        // Percorso relativo per il database
        $ricevuta_path = 'cliente/uploads/ricevute/' . $anno . '/' . $file_name;
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

    // Conferma inserimento nel database
    header("Location: ../success.php?msg=Rimessaggio salvato con successo");
    exit();

} else {
    error_log('Errore execute submit_rimessaggio: ' . $stmt->error);
    http_response_code(500);
    exit('Errore durante il salvataggio del rimessaggio.');
}

$stmt->close();
$conn->close();

if (isset($_POST['upload_ricevuta'])) {
    app_require_csrf();
    $id = intval($_POST['id']);

    if ($id > 0 && isset($_FILES['ricevuta_pagamento'])) {
        $file = $_FILES['ricevuta_pagamento'];

        if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= 8 * 1024 * 1024) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if ($mime === 'application/pdf') {
                $stmt = $conn->prepare("SELECT ricevuta_pagamento FROM rimessaggi WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $vecchioFile = $row['ricevuta_pagamento'];

                    $nuovoFile = gestisciUploadRicevuta($file, 'cliente/uploads/ricevute', $id);

                    if ($nuovoFile) {
                        $stmt = $conn->prepare("UPDATE rimessaggi SET ricevuta_pagamento = ? WHERE id = ?");
                        $stmt->bind_param("si", $nuovoFile, $id);

                        if ($stmt->execute()) {
                            if (!empty($vecchioFile)) {
                                eliminaFile(__DIR__ . '/../cliente/uploads/ricevute/' . $vecchioFile);
                            }
                            header('Location: ../admin/gestione_admin.php?ok=1');
                            exit;
                        }
                    }
                }
            }
        }
    }

    header('Location: ../admin/gestione_admin.php?error=1');
    exit;
}