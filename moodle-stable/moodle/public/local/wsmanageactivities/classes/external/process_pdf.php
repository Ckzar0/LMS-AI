<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use Exception;

/**
 * External function to upload a PDF and extract its images using the same logic as upload.php
 */
class process_pdf extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'filename' => new external_value(PARAM_FILE, 'PDF filename'),
            'filecontent' => new external_value(PARAM_RAW, 'Base64 encoded PDF content')
        ]);
    }

    public static function execute($filename, $filecontent) {
        global $CFG;
        
        // Impedir que Warnings/Notices sujem o JSON
        @error_reporting(0);
        @ini_set('display_errors', 0);
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
        $pdf_name = str_ireplace('.pdf', '', $safe_filename);
        
        // Caminho relativo ao ficheiro para garantir que fica na pasta public/local/
        $plugin_root = dirname(dirname(dirname(__FILE__)));
        
        $temp_dir = $plugin_root . "/temp_pdfs";
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);

        $pdf_path = $temp_dir . "/" . time() . "_" . $safe_filename;
        $log_file = $plugin_root . "/debug_log.txt";

        $server_pdf_path = $CFG->dirroot . "/../Cursos/" . $params['filename'];
        $pdf_already_on_server = file_exists($server_pdf_path);

        if ($pdf_already_on_server) {
            $pdf_path = $server_pdf_path;
        } else {
            $decoded_content = base64_decode($params['filecontent']);
            if (!$decoded_content) {
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ❌ Erro: Falha ao descodificar Base64\n", FILE_APPEND);
                throw new Exception("Invalid base64 content");
            }
            file_put_contents($pdf_path, $decoded_content);
        }

        // 2. Pasta de destino
        $target_dir = $plugin_root . "/extracted_images/" . $pdf_name;
        
        if (!is_dir($plugin_root . "/extracted_images")) {
            mkdir($plugin_root . "/extracted_images", 0777, true);
        }
        
        if (is_dir($target_dir)) {
            exec("rm -rf \"$target_dir\"/*");
        } else {
            mkdir($target_dir, 0777, true);
        }

        // 3. Extração
        $all_output = [];
        $cmd = "pdfimages -p -j \"$pdf_path\" \"$target_dir/img\" 2>&1";
        exec($cmd, $all_output);

        // 4. Otimização Python
        $py_script = $plugin_root . "/optimize_images.py";
        if (file_exists($py_script)) {
            $py_output = [];
            $py_cmd = "python3 \"$py_script\" \"$target_dir\" 2>&1";
            exec($py_cmd, $py_output);
        }

        // Cleanup apenas se foi upload temporário
        if (!$pdf_already_on_server && file_exists($pdf_path)) {
            unlink($pdf_path);
        }

        $final_count = count(glob("$target_dir/*.jpg"));

        // Debug: Log output only if no images found
        if ($final_count === 0) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ⚠️ PDF extraction failed for $filename. Cmd output: " . implode("\n", $all_output) . "\n", FILE_APPEND);
        }

        return [
            'status' => 'success',
            'image_folder' => $pdf_name,
            'count' => $final_count,
            'message' => "Extracted $final_count images into $pdf_name. Logs: " . count($all_output) . " lines."
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success/error)'),
            'image_folder' => new external_value(PARAM_TEXT, 'The folder where images were extracted'),
            'count' => new external_value(PARAM_INT, 'Number of images extracted'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message')
        ]);
    }
}
