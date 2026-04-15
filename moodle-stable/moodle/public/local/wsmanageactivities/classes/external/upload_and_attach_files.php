<?php
/**
 * Upload and Attach Files - Função Auxiliar
 * Versão: 1.0
 * Data: 16 de Julho de 2025, 20:15
 * 
 * Função auxiliar para upload de ficheiros a contextos específicos
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\external;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/filelib.php');

// Incluir nossa classe de upload
require_once(__DIR__ . '/file_management/file_uploader.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use context;
use moodle_exception;
use Exception;

// Usar nossa classe
use local_wsmanageactivities\external\file_management\file_uploader;

class upload_and_attach_files extends external_api {

    /**
     * Definir parâmetros da função
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'files' => new external_multiple_structure(
                new external_single_structure([
                    'filename' => new external_value(PARAM_TEXT, 'File name'),
                    'filecontent' => new external_value(PARAM_RAW, 'Base64 encoded file content'),
                    'contextid' => new external_value(PARAM_INT, 'Context ID to attach file to')
                ])
            )
        ]);
    }

    /**
     * Executar a função
     */
    public static function execute($files) {
        global $DB;

        // Validar parâmetros
        $params = self::validate_parameters(self::execute_parameters(), [
            'files' => $files
        ]);

        try {
            $uploaded_files = [];
            
            foreach ($params['files'] as $file) {
                // Validar tipo de ficheiro
                if (!file_uploader::validate_file_type($file['filename'])) {
                    throw new moodle_exception('Invalid file type: ' . $file['filename']);
                }

                // Validar contexto
                $context = context::instance_by_id($file['contextid']);
                self::validate_context($context);
                require_capability('moodle/files:uploadfiles', $context);

                // Upload para draft area
                $upload_result = file_uploader::upload_to_draft_area($file['filename'], $file['filecontent']);
                
                if (!$upload_result['success']) {
                    throw new moodle_exception('Upload failed: ' . $upload_result['error']);
                }

                // Determinar componente baseado no contexto
                $component = self::get_component_from_context($context);
                
                // Mover para área final
                $final_urls = file_uploader::move_to_final_area(
                    $upload_result['draft_id'],
                    $context->id,
                    $component,
                    'content',
                    0
                );

                if (!empty($final_urls)) {
                    $uploaded_files[] = [
                        'filename' => $file['filename'],
                        'url' => $final_urls[0]['url'],
                        'size' => $final_urls[0]['size'],
                        'contextid' => $context->id,
                        'component' => $component
                    ];
                }
            }

            return [
                'success' => true,
                'files_uploaded' => count($uploaded_files),
                'files' => $uploaded_files,
                'warnings' => []
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'files_uploaded' => 0,
                'files' => [],
                'warnings' => [[
                    'item' => 'file',
                    'warningcode' => 'uploadfailed',
                    'message' => $e->getMessage()
                ]]
            ];
        }
    }

    /**
     * Determinar componente baseado no contexto
     */
    private static function get_component_from_context($context) {
        switch ($context->contextlevel) {
            case CONTEXT_MODULE:
                // Determinar tipo de módulo
                global $DB;
                $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
                $module = $DB->get_record('modules', ['id' => $cm->module]);
                return 'mod_' . $module->name;
            
            case CONTEXT_COURSE:
                return 'course';
            
            case CONTEXT_USER:
                return 'user';
            
            default:
                return 'core';
        }
    }

    /**
     * Definir estrutura de retorno
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'files_uploaded' => new external_value(PARAM_INT, 'Number of files uploaded'),
            'files' => new external_multiple_structure(
                new external_single_structure([
                    'filename' => new external_value(PARAM_TEXT, 'File name'),
                    'url' => new external_value(PARAM_URL, 'File URL'),
                    'size' => new external_value(PARAM_INT, 'File size in bytes'),
                    'contextid' => new external_value(PARAM_INT, 'Context ID'),
                    'component' => new external_value(PARAM_TEXT, 'Component name')
                ])
            ),
            'warnings' => new external_warnings()
        ]);
    }
}
