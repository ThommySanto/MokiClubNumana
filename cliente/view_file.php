<?php
require_once __DIR__ . '/../config/config.php';

// Verifica che il file sia specificato
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die('File non specificato.');
}

// Percorso base delle directory consentite
$baseDirs = [
    'firme' => __DIR__ . '/uploads/firme/',
    'ricevute' => __DIR__ . '/uploads/ricevute/'
];

// Ottieni il tipo di file (firme o ricevute)
$type = $_GET['type'] ?? '';
if (!array_key_exists($type, $baseDirs)) {
    die('Tipo di file non valido.');
}

// Normalizza il percorso ricevuto: può essere solo il nome file oppure un path relativo già completo
$requestedFile = str_replace('\\', '/', $_GET['file']);
$requestedFile = ltrim($requestedFile, '/');

$relativePath = $requestedFile;
$prefixes = [
    'firme' => 'cliente/uploads/firme/',
    'ricevute' => 'cliente/uploads/ricevute/',
];

if (isset($prefixes[$type]) && str_starts_with($relativePath, $prefixes[$type])) {
    $relativePath = substr($relativePath, strlen($prefixes[$type]));
}

// Costruisci il percorso completo del file
$filePath = realpath($baseDirs[$type] . $relativePath);

// Verifica che il file esista e sia all'interno della directory consentita
if (!$filePath || strpos($filePath, realpath($baseDirs[$type])) !== 0 || !file_exists($filePath)) {
    die('File non trovato o accesso non consentito.');
}

// Determina il MIME reale del file per supportare PDF e immagini
$mimeType = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detectedMime = finfo_file($finfo, $filePath);
        if (!empty($detectedMime)) {
            $mimeType = $detectedMime;
        }
        finfo_close($finfo);
    }
} else {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeMap = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    if (isset($mimeMap[$extension])) {
        $mimeType = $mimeMap[$extension];
    }
}

// Imposta gli header per servire il file
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));

// Leggi e invia il file al browser
readfile($filePath);
exit;