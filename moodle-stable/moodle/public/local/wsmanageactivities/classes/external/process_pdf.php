<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use Exception;

/**
 * External function to upload a PDF and extract its images using the same logic as upload.php
 */
class process_pdf extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'filename' => new external_value(PARAM_RAW, 'PDF filename'),
            'filecontent' => new external_value(PARAM_RAW, 'Base64 encoded PDF content', VALUE_DEFAULT, '')
        ]);
    }

    public static function execute($filename, $filecontent) {
        global $CFG;
        
        // Habilitar erros para debug temporário
        @error_reporting(E_ALL);
        @ini_set('display_errors', 1);
        @ini_set('memory_limit', '512M');
        @set_time_limit(300); // 5 minutos
        while (ob_get_level()) ob_end_clean();

        $params = self::validate_parameters(self::execute_parameters(), [
            'filename' => $filename,
            'filecontent' => $filecontent
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/course:create', $context);

        // 1. Preparar caminhos absolutos (considerando a pasta public/)
        $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $params['filename']);
        // Remover .pdf de forma insensível a maiúsculas/minúsculas
        $pdf_name = preg_replace('/\.pdf$/i', '', $safe_filename);
        
        // Caminho relativo ao ficheiro para garantir que fica na pasta public/local/
        $plugin_root = dirname(dirname(dirname(__FILE__)));
        
        $temp_dir = $plugin_root . "/temp_pdfs";
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0777, true);
            @chmod($temp_dir, 0777);
        }

        $pdf_path = $temp_dir . "/" . time() . "_" . $safe_filename;
        $log_file = $plugin_root . "/debug_log.txt";

        // Procura Robusta no Servidor
        $possible_paths = [
            "/var/www/html/Cursos/" . $params['filename'],
            "/var/www/html/Cursos/" . $safe_filename,
            "/var/www/Cursos/" . $params['filename'],
            "/var/www/Cursos/" . $safe_filename,
            $CFG->dirroot . "/Cursos/" . $params['filename'],
            $CFG->dirroot . "/../Cursos/" . $params['filename']
        ];
        
        $pdf_already_on_server = false;
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $pdf_path = $path;
                $pdf_already_on_server = true;
                break;
            }
        }

        if (!$pdf_already_on_server) {
            if (empty($params['filecontent'])) {
                return [
                    'status' => 'error',
                    'image_folder' => $pdf_name,
                    'count' => 0,
                    'message' => "Ficheiro demasiado grande (>15MB). Por favor, coloque '{$params['filename']}' na pasta /Cursos/ do servidor."
                ];
            }
            
            $decoded_content = base64_decode($params['filecontent']);
            if (!$decoded_content) {
                throw new Exception("Invalid base64 content");
            }
            file_put_contents($pdf_path, $decoded_content);
            @chmod($pdf_path, 0777);
        }

        // 2. Pasta de destino (extracted_images deve existir)
        $images_root = $plugin_root . "/extracted_images";
        if (!is_dir($images_root)) {
            mkdir($images_root, 0777, true);
            @chmod($images_root, 0777);
        }

        $target_dir = $images_root . "/" . $pdf_name;
        
        if (is_dir($target_dir)) {
            // Limpar conteúdo mantendo a pasta
            $files = glob($target_dir . '/*');
            foreach($files as $file){
                if(is_file($file)) unlink($file);
            }
        } else {
            mkdir($target_dir, 0777, true);
        }
        @chmod($target_dir, 0777);

        // 3. Extração
        $all_output = [];
        $cmd = "pdfimages -p -all \"$pdf_path\" \"$target_dir/img\" 2>&1";
        exec($cmd, $all_output);
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ⏳ Executing: $cmd\n", FILE_APPEND);
        if (!empty($all_output)) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] 🗨️ Output: " . implode("\n", $all_output) . "\n", FILE_APPEND);
        }

        // 4. Otimização Python (OBRIGATÓRIO para converter PPM para JPG)
        $py_script = $plugin_root . "/optimize_images.py";
        if (file_exists($py_script)) {
            $py_output = [];
            $py_cmd = "python3 \"$py_script\" \"$target_dir\" 2>&1";
            exec($py_cmd, $py_output);
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ⏳ Executing Python: $py_cmd\n", FILE_APPEND);
        }
        
        // --- NOVO: FILTRO DE RUÍDO (Pancada Final nas imagens pretas/laranjas) ---
        $all_images = glob("$target_dir/*.{jpg,png}", GLOB_BRACE);
        foreach ($all_images as $img) {
            // A) Filtro por Peso
            if (filesize($img) < 10240) {
                unlink($img);
                continue;
            }
            
            // B) Filtro por Dimensões
            $size = @getimagesize($img);
            if ($size) {
                $w = $size[0];
                $h = $size[1];
                if ($w < 50 || $h < 50 || ($w / $h > 10) || ($h / $w > 10)) {
                    unlink($img);
                    continue;
                }
            }

            // C) DETEÇÃO DE COR SÓLIDA (Mata os quadrados pretos/laranjas)
            // Usamos ImageMagick para ver o desvio padrão das cores
            $std_dev_cmd = "identify -format \"%[standard-deviation]\" \"$img\" 2>&1";
            $std_dev = (float)exec($std_dev_cmd);
            if ($std_dev < 10) { // Se o desvio for muito baixo, a imagem é quase toda de uma cor só
                unlink($img);
                continue;
            }
        }

        // Garantir permissões nos ficheiros extraídos
        exec("chmod -R 777 \"$target_dir\"");

        // Cleanup apenas se foi upload temporário
        if (!$pdf_already_on_server && file_exists($pdf_path)) {
            unlink($pdf_path);
        }

        // Recalcular lista de imagens reais após a limpeza do lixo
        $image_files = glob("$target_dir/*.{jpg,png}", GLOB_BRACE);
        $final_count = count($image_files);

        // Extrair lista de páginas únicas que têm imagens ÚTEIS
        $pages_with_images = [];
        foreach ($image_files as $file) {
            if (preg_match('/img-(\d+)-/', basename($file), $matches)) {
                $pages_with_images[] = (int)$matches[1];
            }
        }
        $pages_with_images = array_values(array_unique($pages_with_images));
        sort($pages_with_images);

        // Log de atividade
        $log_msg = "[" . date('Y-m-d H:i:s') . "] Processed: $pdf_name | Found: $final_count images on " . count($pages_with_images) . " pages.\n";
        if ($final_count === 0 && !empty($all_output)) {
            $log_msg .= "   ⚠️ Cmd Output: " . implode(" ", $all_output) . "\n";
        }
        file_put_contents($log_file, $log_msg, FILE_APPEND);

        return [
            'status' => 'success',
            'image_folder' => $pdf_name,
            'count' => $final_count,
            'pages_with_images' => $pages_with_images,
            'message' => "Extracted $final_count images into $pdf_name."
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success/error)'),
            'image_folder' => new external_value(PARAM_TEXT, 'The folder where images were extracted'),
            'count' => new external_value(PARAM_INT, 'Number of images extracted'),
            'pages_with_images' => new external_multiple_structure(new external_value(PARAM_INT, 'Page number')),
            'message' => new external_value(PARAM_TEXT, 'Success or error message')
        ]);
    }
}
