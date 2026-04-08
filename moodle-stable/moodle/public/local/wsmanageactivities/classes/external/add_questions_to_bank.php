<?php
/**
 * Add Questions to Bank API - CORREÇÃO FINAL v22.2
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    22.2 - CORREÇÃO: Import correto do external_multiple_structure
 * @date       22 de Janeiro de 2025, 21:15
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

// Incluir handlers existentes
$handler_path = __DIR__ . '/question_types/';
require_once($handler_path . 'multichoice_handler.php');
require_once($handler_path . 'truefalse_handler.php');
require_once($handler_path . 'numerical_handler.php');
require_once($handler_path . 'shortanswer_handler.php');
require_once($handler_path . 'matching_handler.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;  // ✅ CORREÇÃO: Import correto
use context_course;
use moodle_exception;
use stdClass;
use Exception;
use context_module;  // ← ADICIONAR ESTA LINHA


class add_questions_to_bank extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'category_name' => new external_value(PARAM_TEXT, 'Category name'),
            'questions_data' => new external_value(PARAM_RAW, 'JSON array of questions')
        ]);
    }

    public static function execute($courseid, $category_name, $questions_data) {
        global $DB, $USER;
        
        $params = self::validate_parameters(self::execute_parameters(), 
            compact('courseid', 'category_name', 'questions_data'));
        
        // Validar e decodificar JSON
        $questions = json_decode($params['questions_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new moodle_exception('Invalid JSON data for questions');
        }
        
        // Validar curso e permissões
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = self::get_qbank_context($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/question:add', $context);
        
        try {
            // Obter categoria
            $category = $DB->get_record('question_categories', [
                'contextid' => $context->id,
                'name' => $params['category_name']
            ], '*', MUST_EXIST);
            
            $created_questions = [];
            $transaction = $DB->start_delegated_transaction();
            
            foreach ($questions as $q_data) {
                $question_id = self::create_question_in_bank($category, $q_data);
                $created_questions[] = [
                    'question_id' => $question_id,
                    'name' => $q_data['name'] ?? 'Unnamed question',
                    'type' => $q_data['type'] ?? 'unknown'
                ];
            }
            
            $transaction->allow_commit();
            
            return [
                'success' => true,
                'category_id' => (int)$category->id,
                'category_name' => $category->name,
                'questions_created' => count($created_questions),
                'questions' => $created_questions,
                'message' => 'Questions added to bank successfully'
            ];
            
        } catch (Exception $e) {
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
            return [
                'success' => false,
                'category_id' => 0,
                'category_name' => '',
                'questions_created' => 0,
                'questions' => [],
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
    
    private static function create_question_in_bank($category, $q_data) {
        global $DB, $USER;
        
        // Criar bank entry
        $bankentry = new stdClass();
        $bankentry->questioncategoryid = $category->id;
        $bankentry->ownerid = $USER->id;
        $bankentry->id = $DB->insert_record('question_bank_entries', $bankentry);
        
        // Criar questão
        $question = new stdClass();
        $question->parent = 0;
        $question->name = $q_data['name'] ?? 'Unnamed question';
        $question->questiontext = $q_data['questiontext'] ?? '';
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = $q_data['generalfeedback'] ?? '';
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->qtype = $q_data['type'] ?? 'multichoice';
        $question->defaultmark = $q_data['defaultmark'] ?? 1.0;
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->stamp = make_unique_id_code();
        $question->id = $DB->insert_record('question', $question);
        
        // Criar version
        $version = new stdClass();
        $version->questionbankentryid = $bankentry->id;
        $version->version = 1;
        $version->questionid = $question->id;
        $version->status = 'ready';
        $DB->insert_record('question_versions', $version);
        
        // Criar opções específicas do tipo
        self::create_question_type_options($question, $q_data);
        
        return $question->id;
    }
    
    private static function create_question_type_options($question, $q_data) {
        // Reutilizar handlers existentes
        switch ($q_data['type']) {
            case 'multichoice':
                \local_wsmanageactivities\external\question_types\multichoice_handler::create_options($question, $q_data['answers'] ?? []);
                break;
            case 'truefalse':
                $correctanswer = filter_var($q_data['correctanswer'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
                \local_wsmanageactivities\external\question_types\truefalse_handler::create_options($question, $correctanswer);
                break;
            case 'numerical':
                \local_wsmanageactivities\external\question_types\numerical_handler::create_options($question, $q_data);
                break;
            case 'shortanswer':
                \local_wsmanageactivities\external\question_types\shortanswer_handler::create_options($question, $q_data);
                break;
            case 'matching':
                \local_wsmanageactivities\external\question_types\matching_handler::create_options($question, $q_data['subquestions'] ?? []);
                break;
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'category_id' => new external_value(PARAM_INT, 'Category ID'),
            'category_name' => new external_value(PARAM_TEXT, 'Category name'),
            'questions_created' => new external_value(PARAM_INT, 'Number of questions created'),
            'questions' => new external_multiple_structure(  // ✅ CORREÇÃO: Usar external_multiple_structure
                new external_single_structure([
                    'question_id' => new external_value(PARAM_INT, 'Question ID'),
                    'name' => new external_value(PARAM_TEXT, 'Question name'),
                    'type' => new external_value(PARAM_TEXT, 'Question type')
                ])
            ),
            'message' => new external_value(PARAM_TEXT, 'Result message')
        ]);
    }
}