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

        $log_file = $CFG->dirroot . "/local/wsmanageactivities/debug_log.txt";
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] 📥 [get_certificate_pdf] Starting for CMID: $cmid\n", FILE_APPEND);

        // Impedir que Warnings sujem o output
        @error_reporting(0);
        @ini_set('display_errors', 0);

        try {
            // DESATIVAR DEBUGGING DO MOODLE TEMPORARIAMENTE
            // Isso evita que o Moodle jogue "Multiple records found" no meio do PDF
            global $CFG;
            $old_debug = $CFG->debug;
            $CFG->debug = 0;

            $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

            // 1. Validar contexto
            $cm = get_coursemodule_from_id('customcert', $params['cmid'], 0, false, MUST_EXIST);
            $context = \context_module::instance($cm->id);
            
            // FORÇAR LOGIN COMO ADMIN para este processo se for via WebService (Token)
            if (empty($USER->id) || !is_siteadmin($USER->id)) {
                $admin = $DB->get_record('user', ['username' => 'admin', 'deleted' => 0], '*', MUST_EXIST);
                \core\session\manager::set_user($admin);
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] 👤 [get_certificate_pdf] Forced Admin session (User ID: {$admin->id})\n", FILE_APPEND);
            }

            self::validate_context($context);

            $customcert_rec = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
            $template_record = $DB->get_record('customcert_templates', ['id' => $customcert_rec->templateid], '*', MUST_EXIST);

            // 3. Gerar o PDF capturando o buffer
            $plugin_lib = $CFG->dirroot . '/mod/customcert/lib.php';
            if (file_exists($plugin_lib)) {
                require_once($plugin_lib);
            }

            // Forçar a emissão do certificado se ainda não existir registo
            if (!$DB->record_exists('customcert_issues', ['userid' => $USER->id, 'customcertid' => $customcert_rec->id])) {
                \mod_customcert\certificate::issue_certificate($customcert_rec->id, $USER->id);
            }

            // LIMPAR TUDO antes de começar o PDF
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();

            try {
                $template = new \mod_customcert\template($template_record);
                $template->generate_pdf(false, $USER->id);
                $pdf_content = ob_get_clean();
                
                // Restaurar Debug
                $CFG->debug = $old_debug;

                if (empty($pdf_content)) {
                    throw new \moodle_exception('empty_pdf', 'local_wsmanageactivities');
                }

                return [
                    'filename' => clean_filename($customcert_rec->name . '.pdf'),
                    'filecontent' => base64_encode($pdf_content),
                    'pdf' => base64_encode($pdf_content),
                    'mimetype' => 'application/pdf'
                ];

            } catch (\Throwable $e) {
                if (ob_get_level()) ob_end_clean();
                $CFG->debug = $old_debug;
                throw $e;
            }

        } catch (\Throwable $e) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ❌ [get_certificate_pdf] Fatal Error: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'filename' => new external_value(PARAM_TEXT, 'The filename of the certificate'),
            'filecontent' => new external_value(PARAM_RAW, 'Base64 encoded PDF content'),
            'pdf' => new external_value(PARAM_RAW, 'Alias for filecontent'),
            'mimetype' => new external_value(PARAM_TEXT, 'MIME type of the file')
        ]);
    }
}
