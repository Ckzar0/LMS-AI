<?php
/**
 * Create Question Category API - local_wsmanageactivities v22.1
 * 
 * CORREÇÃO v22.1: Resolver erro "Duplicate entry" para idnumber
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    22.1
 * @date       22 de Janeiro de 2025, 18:30
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;
use moodle_exception;
use stdClass;
use Exception;
use context_module;  // ← ADICIONAR ESTA LINHA



class create_question_category extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'name' => new external_value(PARAM_TEXT, 'Category name'),
            'description' => new external_value(PARAM_RAW, 'Category description', VALUE_DEFAULT, ''),
            'parent_category' => new external_value(PARAM_TEXT, 'Parent category name (optional)', VALUE_DEFAULT, ''),
            'idnumber' => new external_value(PARAM_TEXT, 'ID number for category', VALUE_DEFAULT, '')
        ]);
    }

    public static function execute($courseid, $name, $description = '', $parent_category = '', $idnumber = '') {
        global $DB;
        
        $params = self::validate_parameters(self::execute_parameters(), 
            compact('courseid', 'name', 'description', 'parent_category', 'idnumber'));
        
        // Validar curso e permissões
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = self::get_qbank_context($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/question:managecategory', $context);
        
        try {
            // Obter ou criar categoria de topo
            $top_category = self::get_or_create_top_category($context);
            
            // Determinar categoria pai
            $parent_id = $top_category->id;
            if (!empty($params['parent_category'])) {
                $parent = $DB->get_record('question_categories', [
                    'contextid' => $context->id,
                    'name' => $params['parent_category']
                ]);
                if ($parent) {
                    $parent_id = $parent->id;
                }
            }
            
            // Verificar se categoria já existe
            $existing = $DB->get_record('question_categories', [
                'contextid' => $context->id,
                'name' => $params['name']
            ]);
            
            if ($existing) {
                return [
                    'success' => true,
                    'category_id' => (int)$existing->id,
                    'category_name' => $existing->name,
                    'message' => 'Category already exists',
                    'created_new' => false
                ];
            }
            
            // CORREÇÃO v22.1: Gerar idnumber único se vazio
            $final_idnumber = $params['idnumber'];
            if (empty($final_idnumber)) {
                $final_idnumber = 'cat_' . time() . '_' . rand(1000, 9999);
                
                // Garantir que é único
                while ($DB->record_exists('question_categories', [
                    'contextid' => $context->id,
                    'idnumber' => $final_idnumber
                ])) {
                    $final_idnumber = 'cat_' . time() . '_' . rand(1000, 9999);
                }
            } else {
                // Verificar se idnumber já existe
                if ($DB->record_exists('question_categories', [
                    'contextid' => $context->id,
                    'idnumber' => $final_idnumber
                ])) {
                    return [
                        'success' => false,
                        'category_id' => 0,
                        'category_name' => '',
                        'message' => "ID number '{$final_idnumber}' already exists in this context",
                        'created_new' => false
                    ];
                }
            }
            
            // Criar nova categoria
            $category = new stdClass();
            $category->name = clean_param($params['name'], PARAM_TEXT);
            $category->contextid = $context->id;
            $category->info = $params['description'];
            $category->infoformat = FORMAT_HTML;
            $category->stamp = make_unique_id_code();
            $category->parent = $parent_id;
            $category->sortorder = 999;
            $category->idnumber = $final_idnumber;
            
            $category_id = $DB->insert_record('question_categories', $category);
            
            return [
                'success' => true,
                'category_id' => (int)$category_id,
                'category_name' => $category->name,
                'parent_id' => (int)$parent_id,
                'context_id' => (int)$context->id,
                'idnumber_used' => $final_idnumber,
                'message' => 'Category created successfully',
                'created_new' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'category_id' => 0,
                'category_name' => '',
                'message' => 'Error: ' . $e->getMessage(),
                'created_new' => false
            ];
        }
    }
    
private static function get_qbank_context($courseid) {
    global $DB;
    
    // Encontrar o course module do qbank para este curso
    $cm = $DB->get_record_sql("
        SELECT cm.id 
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        WHERE cm.course = ? AND m.name = 'qbank'
    ", [$courseid]);
    
    if (!$cm) {
        throw new moodle_exception('No question bank found for this course');
    }
    
    return context_module::instance($cm->id);
}

    private static function get_or_create_top_category($context) {
        global $DB;
        
        $top_category = $DB->get_record('question_categories', [
            'contextid' => $context->id,
            'parent' => 0
        ]);
        
        if (!$top_category) {
            $top = new stdClass();
            $top->name = 'Top';
            $top->contextid = $context->id;
            $top->info = 'The top-level category for questions in this context.';
            $top->infoformat = FORMAT_HTML;
            $top->stamp = make_unique_id_code();
            $top->parent = 0;
            $top->sortorder = 0;
            $top->idnumber = null; // Top category não precisa de idnumber
            
            $top->id = $DB->insert_record('question_categories', $top);
            return $top;
        }
        
        return $top_category;
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'category_id' => new external_value(PARAM_INT, 'Category ID'),
            'category_name' => new external_value(PARAM_TEXT, 'Category name'),
            'parent_id' => new external_value(PARAM_INT, 'Parent category ID', VALUE_OPTIONAL),
            'context_id' => new external_value(PARAM_INT, 'Context ID', VALUE_OPTIONAL),
            'idnumber_used' => new external_value(PARAM_TEXT, 'ID number used', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'created_new' => new external_value(PARAM_BOOL, 'Whether new category was created')
        ]);
    }
}