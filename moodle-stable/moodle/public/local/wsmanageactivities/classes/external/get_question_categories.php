<?php
/**
 * Get Question Categories API - local_wsmanageactivities v22.0
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    22.0
 * @date       22 de Janeiro de 2025, 17:30
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;
use moodle_exception;
use Exception;
use context_module;  // ← ADICIONAR ESTA LINHA

class get_question_categories extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'include_question_count' => new external_value(PARAM_BOOL, 'Include question count per category', VALUE_DEFAULT, true)
        ]);
    }

    public static function execute($courseid, $include_question_count = true) {
        global $DB;
        
        $params = self::validate_parameters(self::execute_parameters(), 
            compact('courseid', 'include_question_count'));
        
        // Validar curso e permissões
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = self::get_qbank_context($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/question:viewall', $context);
        
        try {
            // Obter categorias
            $categories = $DB->get_records('question_categories', [
                'contextid' => $context->id
            ], 'parent ASC, sortorder ASC, name ASC');
            
            $result_categories = [];
            
            foreach ($categories as $category) {
                $category_data = [
                    'id' => (int)$category->id,
                    'name' => $category->name,
                    'description' => $category->info,
                    'parent_id' => (int)$category->parent,
                    'sort_order' => (int)$category->sortorder,
                    'id_number' => $category->idnumber ?? '',
                    'is_top_level' => ($category->parent == 0),
                    'stamp' => $category->stamp
                ];
                
                // Adicionar contagem de questões se solicitado
                if ($params['include_question_count']) {
                    $question_count = $DB->count_records_sql("
                        SELECT COUNT(DISTINCT q.id)
                        FROM {question} q
                        JOIN {question_versions} qv ON qv.questionid = q.id
                        JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                        WHERE qbe.questioncategoryid = ?
                        AND qv.status = 'ready'
                    ", [$category->id]);
                    
                    $category_data['question_count'] = (int)$question_count;
                    $category_data['has_questions'] = ($question_count > 0);
                }
                
                $result_categories[] = $category_data;
            }
            
            return [
                'success' => true,
                'course_id' => (int)$params['courseid'],
                'context_id' => (int)$context->id,
                'total_categories' => count($result_categories),
                'categories' => $result_categories,
                'message' => 'Categories retrieved successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'course_id' => (int)$params['courseid'],
                'context_id' => 0,
                'total_categories' => 0,
                'categories' => [],
                'message' => 'Error: ' . $e->getMessage()
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

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'course_id' => new external_value(PARAM_INT, 'Course ID'),
            'context_id' => new external_value(PARAM_INT, 'Context ID'),
            'total_categories' => new external_value(PARAM_INT, 'Total number of categories'),
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Category ID'),
                    'name' => new external_value(PARAM_TEXT, 'Category name'),
                    'description' => new external_value(PARAM_RAW, 'Category description'),
                    'parent_id' => new external_value(PARAM_INT, 'Parent category ID'),
                    'sort_order' => new external_value(PARAM_INT, 'Sort order'),
                    'id_number' => new external_value(PARAM_TEXT, 'ID number'),
                    'is_top_level' => new external_value(PARAM_BOOL, 'Is top level category'),
                    'stamp' => new external_value(PARAM_TEXT, 'Category stamp'),
                    'question_count' => new external_value(PARAM_INT, 'Number of questions', VALUE_OPTIONAL),
                    'has_questions' => new external_value(PARAM_BOOL, 'Has questions', VALUE_OPTIONAL)
                ])
            ),
            'message' => new external_value(PARAM_TEXT, 'Result message')
        ]);
    }
}