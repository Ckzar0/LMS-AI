<?php
/**
 * Create Question Bank Module API - Moodle 5.0
 * 
 * API para criar módulo Question Bank programaticamente usando add_moduleinfo()
 * (Abordagem oficial do Moodle)
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    1.0 - Baseado na pesquisa oficial Moodle docs
 * @date       22 de Janeiro de 2025, 17:15
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/course/lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;
use moodle_exception;
use stdClass;
use Exception;

class create_qbank_module extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number', VALUE_DEFAULT, 0),
            'name' => new external_value(PARAM_TEXT, 'Question Bank name', VALUE_DEFAULT, ''),
            'visible' => new external_value(PARAM_BOOL, 'Visible to students', VALUE_DEFAULT, true)
        ]);
    }

    public static function execute($courseid, $sectionnum = 0, $name = '', $visible = true) {
        global $DB;
        
        $params = self::validate_parameters(self::execute_parameters(), 
            compact('courseid', 'sectionnum', 'name', 'visible'));
        
        // Validar curso e permissões
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);
        
        try {
            // Verificar se módulo qbank existe no sistema
            $module = $DB->get_record('modules', ['name' => 'qbank']);
            if (!$module) {
                return [
                    'success' => false,
                    'coursemodule_id' => 0,
                    'instance_id' => 0,
                    'name' => '',
                    'section' => 0,
                    'visible' => false,
                    'message' => 'Question Bank module (qbank) not available in this Moodle installation',
                    'created_new' => false,
                    'url' => '',
                    'debug_info' => json_encode([
                        'available_modules' => $DB->get_records_menu('modules', null, 'name', 'name, name'),
                        'qbank_searched' => true
                    ])
                ];
            }
            
            // Verificar se módulo qbank já existe
            $existing_qbank = $DB->get_record_sql("
                SELECT cm.id, cm.instance 
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                WHERE cm.course = ? AND m.name = 'qbank'
            ", [$params['courseid']]);
            
            if ($existing_qbank) {
                return [
                    'success' => true,
                    'coursemodule_id' => (int)$existing_qbank->id,
                    'instance_id' => (int)$existing_qbank->instance,
                    'message' => 'Question Bank already exists',
                    'created_new' => false,
                    'url' => (string)new \moodle_url('/mod/qbank/view.php', ['id' => $existing_qbank->id])
                ];
            }
            
            // Obter module ID para qbank (já verificado acima)
            // $module já foi obtido e validado
            
            // Obter seção
            $section = $DB->get_record('course_sections', [
                'course' => $params['courseid'], 
                'section' => $params['sectionnum']
            ], '*', MUST_EXIST);
            
            // Nome padrão se não fornecido
            $qbank_name = !empty($params['name']) ? $params['name'] : 'Question Bank';
            
            // Preparar dados do módulo usando estrutura oficial add_moduleinfo
            $moduleinfo = new stdClass();
            $moduleinfo->name = $qbank_name;
            $moduleinfo->intro = 'Question bank for organizing and managing questions';
            $moduleinfo->introformat = FORMAT_HTML;
            $moduleinfo->modulename = 'qbank';
            $moduleinfo->module = $module->id;
            $moduleinfo->section = $params['sectionnum'];
            $moduleinfo->course = $params['courseid'];
            $moduleinfo->coursemodule = 0; // Será definido por add_moduleinfo
            $moduleinfo->visible = $params['visible'] ? 1 : 0;
            $moduleinfo->visibleoncoursepage = $moduleinfo->visible;
            $moduleinfo->cmidnumber = '';
            $moduleinfo->groupmode = NOGROUPS;
            $moduleinfo->groupingid = 0;
            $moduleinfo->availability = null;
            $moduleinfo->completion = COMPLETION_TRACKING_NONE;
            $moduleinfo->completionview = 0;
            $moduleinfo->completionexpected = 0;
            
            // Campos específicos do qbank (se houver)
            // Adicionar aqui campos específicos conforme necessário
            
            debugging("create_qbank_module DEBUG: Calling add_moduleinfo with modulename=qbank", DEBUG_DEVELOPER);
            
            // Criar módulo usando função oficial
            $result = add_moduleinfo($moduleinfo, $course);
            
            if (!$result || !isset($result->coursemodule)) {
                throw new moodle_exception('Failed to create question bank module');
            }
            
            // Reconstruir cache do curso
            rebuild_course_cache($params['courseid'], true);
            
            return [
                'success' => true,
                'coursemodule_id' => (int)$result->coursemodule,
                'instance_id' => (int)$result->instance,
                'name' => $result->name,
                'section' => (int)$params['sectionnum'],
                'visible' => (bool)$result->visible,
                'message' => 'Question Bank module created successfully',
                'created_new' => true,
                'url' => (string)new \moodle_url('/mod/qbank/view.php', ['id' => $result->coursemodule]),
                'debug_info' => json_encode([
                    'module_id' => (int)$module->id,
                    'course_id' => (int)$params['courseid'],
                    'add_moduleinfo_result' => 'success'
                ])
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'coursemodule_id' => 0,
                'instance_id' => 0,
                'name' => '',
                'section' => 0,
                'visible' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'created_new' => false,
                'url' => '',
                'debug_info' => json_encode([
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                    'course_id' => $params['courseid']
                ])
            ];
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'coursemodule_id' => new external_value(PARAM_INT, 'Course module ID'),
            'instance_id' => new external_value(PARAM_INT, 'Question Bank instance ID'),
            'name' => new external_value(PARAM_TEXT, 'Module name'),
            'section' => new external_value(PARAM_INT, 'Section number'),
            'visible' => new external_value(PARAM_BOOL, 'Visibility'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'created_new' => new external_value(PARAM_BOOL, 'Whether new module was created'),
            'url' => new external_value(PARAM_URL, 'Module URL'),
            'debug_info' => new external_value(PARAM_TEXT, 'Debug information as JSON string', VALUE_OPTIONAL)
        ]);
    }
}