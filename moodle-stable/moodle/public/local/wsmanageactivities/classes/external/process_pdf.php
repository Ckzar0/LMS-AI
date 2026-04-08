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
        
        // Caminho físico REAL no contentor
        $plugin_root = $CFG->dirroot . "/local/wsmanageactivities";
        
        $temp_dir = $plugin_root . "/temp_pdfs";
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);

        $pdf_path = $temp_dir . "/" . time() . "_" . $safe_filename;
        $decoded_content = base64_decode($params['filecontent']);
        if (!$decoded_content) throw new Exception("Invalid base64 content");
        
        file_put_contents($pdf_path, $decoded_content);

        // 2. Pasta de destino
        $target_dir = $plugin_root . "/extracted_images/" . $pdf_name;
        if (!is_dir($plugin_root . "/extracted_images")) mkdir($plugin_root . "/extracted_images", 0777, true);
        
        if (is_dir($target_dir)) {
            exec("rm -rf \"$target_dir\"/*");
        } else {
            mkdir($target_dir, 0777, true);
        }

        $all_output = [];
        // 3. Extração (Loop Idêntico ao upload.php)
        for ($p = 1; $p <= 150; $p++) {
            $p_pad = str_pad($p, 3, '0', STR_PAD_LEFT);
            $cmd = "pdfimages -f $p -l $p -j \"$pdf_path\" \"$target_dir/img-$p_pad\" 2>&1";
            exec($cmd, $all_output);
        }

        // 4. Otimização
        $py_script = $plugin_root . "/optimize_images.py";
        if (file_exists($py_script)) {
            exec("python3 \"$py_script\" \"$target_dir\" 2>&1", $all_output);
        }

        // Cleanup do PDF temporário
        unlink($pdf_path);

        $final_count = count(glob("$target_dir/*.jpg"));

        // Debug: Log output if no images found
        if ($final_count === 0) {
            error_log("PDF extraction failed for $filename. Cmd output: " . implode("\n", $all_output));
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
