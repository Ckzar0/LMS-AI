<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;

/**
 * External API to generate and return a certificate PDF in base64
 */
class get_certificate_pdf extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID of the certificate')
        ]);
    }

    public static function execute($cmid) {
        global $DB, $USER, $CFG;

        // Impedir que Warnings sujem o output
        @error_reporting(0);
        @ini_set('display_errors', 0);

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        // 1. Validar contexto
        $cm = get_coursemodule_from_id('customcert', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        try {
            self::validate_context($context);
        } catch (\Throwable $e) {
            // Ignorar erro de contexto se formos admin (utilizador do Token)
            if (!is_siteadmin()) {
                throw $e;
            }
        }

        $customcert = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
        $template_record = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);

        // 3. Gerar o PDF capturando o buffer
        $plugin_lib = $CFG->dirroot . '/mod/customcert/lib.php';
        if (file_exists($plugin_lib)) {
            require_once($plugin_lib);
        }

        // Forçar a emissão do certificado se ainda não existir registo
        if (!$DB->record_exists('customcert_issues', ['userid' => $USER->id, 'customcertid' => $customcert->id])) {
            \mod_customcert\certificate::issue_certificate($customcert->id, $USER->id);
        }

        ob_start();
        try {
            $template = new \mod_customcert\template($template_record);
            // generate_pdf(false, $userid) gera o PDF e envia para o buffer
            $template->generate_pdf(false, $USER->id);
            $pdf_content = ob_get_clean();
        } catch (\Throwable $e) {
            $err_msg = ob_get_clean();
            $log_file = dirname(dirname(dirname(__FILE__))) . "/debug_log.txt";
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ❌ PDF Error: " . $e->getMessage() . " | Output: " . $err_msg . "\n", FILE_APPEND);
            throw new \moodle_exception('errorgeneratingpdf', 'local_wsmanageactivities', '', $e->getMessage());
        }

        if (empty($pdf_content)) {
            throw new \moodle_exception('empty_pdf', 'local_wsmanageactivities');
        }

        return [
            'filename' => clean_filename($customcert->name . '.pdf'),
            'filecontent' => base64_encode($pdf_content),
            'mimetype' => 'application/pdf'
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'filename' => new external_value(PARAM_TEXT, 'The filename of the certificate'),
            'filecontent' => new external_value(PARAM_RAW, 'Base64 encoded PDF content'),
            'mimetype' => new external_value(PARAM_TEXT, 'MIME type of the file')
        ]);
    }
}
