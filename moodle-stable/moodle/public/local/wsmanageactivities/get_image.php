<?php
/**
 * Proxy Inteligente para Imagens com Smart Search (Fuzzy Match).
 * Versão: 1.1.0
 */
require_once(__DIR__ . '/../../config.php');

header("Access-Control-Allow-Origin: *");

$path = optional_param('path', '', PARAM_PATH);
if (empty($path)) die("Path missing");

$parts = explode('/', $path);
$folder = $parts[0];
$filename = end($parts);

$base_dir = __DIR__ . '/extracted_images/';
$full_path = $base_dir . $path;

// 1. Tentar caminho direto
if (file_exists($full_path) && is_readable($full_path)) {
    serve_file($full_path);
}

// 2. SMART SEARCH: Se falhar, procurar por página e índice
// Ex: img-021-000 -> queremos a 1ª imagem da página 21
if (preg_match('/img-(\d+)-(\d+)/i', $filename, $matches)) {
    $p_pad = $matches[1];
    $s_idx = (int)$matches[2];
    
    $folder_path = $base_dir . $folder;
    if (is_dir($folder_path)) {
        // Buscar todos os ficheiros daquela página (ex: img-021-*.jpg)
        $files = glob($folder_path . "/img-$p_pad-*.{jpg,png}", GLOB_BRACE);
        sort($files); // Garantir ordem numérica global
        
        if (isset($files[$s_idx])) {
            serve_file($files[$s_idx]);
        } 
        // Fallback: se pediu o 1 (001) mas só existe o 0 (000), entregamos o 0
        elseif (!empty($files)) {
            serve_file($files[0]);
        }
    }
}

header("HTTP/1.0 404 Not Found");
echo "Ficheiro não localizado: " . $path;

function serve_file($file) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    header("Content-Type: image/" . ($ext == 'png' ? 'png' : 'jpeg'));
    header("Content-Length: " . filesize($file));
    readfile($file);
    exit;
}
