<?php
require_once dirname(__DIR__) . '/security_headers.php';

/**
 * Crea una directory basata sull'anno corrente.
 *
 * @param string $baseDir La directory di base (es. 'uploads/firme').
 * @return string Il percorso completo della directory creata.
 */
function creaCartellaPerAnno($baseDir) {
    $anno = date("Y");
    $directory = __DIR__ . "/../$baseDir/$anno/";

    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    return $directory;
}

function eliminaFile($filePath) {
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

function gestisciUploadRicevuta($file, $uploadDir, $id) {
    $anno = date('Y');
    $directory = creaCartellaPerAnno($uploadDir);
    $fileName = $id . '_' . time() . '.pdf';
    $filePath = $directory . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        chmod($filePath, 0644);
        return $anno . '/' . $fileName;
    }

    return false;
}
