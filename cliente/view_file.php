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

// Ottieni il nome del file
$file = $_GET['file'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_\/]+\.pdf$/', $file)) {
    die('Nome file non valido.');
}

// Costruisci il percorso completo del file
$filePath = realpath($baseDirs[$type] . $file);

// Verifica che il file esista e sia all'interno della directory consentita
if (!$filePath || strpos($filePath, realpath($baseDirs[$type])) !== 0 || !file_exists($filePath)) {
    die('File non trovato o accesso non consentito.');
}

// Imposta gli header per servire il file
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));

// Leggi e invia il file al browser
readfile($filePath);
exit;