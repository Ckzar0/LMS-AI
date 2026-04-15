<?php
/**
 * External API for adding questions to quiz activities.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use context_course;
use context_module;
use moodle_exception;
use local_wsmanageactivities\local\validation;
use local_wsmanageactivities\local\quiz_helper;

/**
 * External API for adding questions to quiz activities.
 */
class add_quiz_questions extends external_api {
    
    /**
     * Describes the parameters for add_quiz_questions.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz course module ID or quiz instance ID'),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'type' => new external_value(PARAM_ALPHA, 'Question type (multichoice, shortanswer, essay, truefalse)'),
                    'name' => new external_value(PARAM_TEXT, 'Question name'),
                    'questiontext' => new external_value(PARAM_RAW, 'Question text (HTML)'),
                    'mark' => new external_value(PARAM_FLOAT, 'Question marks', VALUE_OPTIONAL, 1.0),
                    'questiondata' => new external_value(PARAM_RAW, 'Question-specific data (JSON)', VALUE_OPTIONAL, '{}'),
                ]), 
                'Questions to add to quiz', VALUE_REQUIRED
            ),
            'idtype' => new external_value(PARAM_ALPHA, 'ID type: "cmid" for course module ID or "instance" for quiz instance ID', VALUE_OPTIONAL, 'cmid')
        ]);
    }
    
    /**
     * Add questions to an existing quiz.
     *
     * @param int $quizid Quiz ID (course module or instance)
     * @param array $questions Questions to add
     * @param string $idtype Type of ID provided
     * @return array Result of question addition
     * @throws moodle_exception
     */
    public static function execute($quizid, $questions, $idtype = 'cmid') {
        global $DB, $CFG;
        
        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
            'questions' => $questions,
            'idtype' => $idtype
        ]);
        
        try {
            // Get quiz and course module info
            if ($params['idtype'] === 'cmid') {
                // Quiz ID is course module ID
                $cm = get_coursemodule_from_id('quiz', $params['quizid'], 0, false, MUST_EXIST);
                $quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);
            } else {
                // Quiz ID is instance ID
                $quiz = $DB->get_record('quiz', array('id' => $params['quizid']), '*', MUST_EXIST);
                $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
            }
            
            // Get course
            $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
            
            // Validate context and permissions
            $context = context_module::instance($cm->id);
            self::validate_context($context);
            require_capability('mod/quiz:manage', $context);
            
            // Validate questions
            if (empty($params['questions'])) {
                throw new moodle_exception('error:invalidmoduledata', 'local_wsmanageactivities', '', 'questions');
            }
            
            // Add questions to quiz
            $questionsadded = quiz_helper::create_quiz_questions($quiz->id, $params['questions']);
            
            // Log the action
            self::log_action('questions_added', [
                'quizid' => $cm->id,
                'quiz_instance' => $quiz->id,
                'courseid' => $course->id,
                'questions_added' => $questionsadded,
                'total_questions' => count($params['questions'])
            ]);
            
            // Format and return result
            return [
                'quizid' => $cm->id,
                'quiz_instance' => $quiz->id,
                'quiz_name' => $quiz->name,
                'questions_requested' => count($params['questions']),
                'questions_added' => $questionsadded,
                'quiz_url' => $CFG->wwwroot . '/mod/quiz/view.php?id=' . $cm->id,
                'success' => true,
                'warnings' => $questionsadded < count($params['questions']) ? [
                    [
                        'item' => 'questions',
                        'itemid' => $quiz->id,
                        'warningcode' => 'partialcreation',
                        'message' => 'Some questions could not be created. Check logs for details.'
                    ]
                ] : []
            ];
            
        } catch (moodle_exception $e) {
            // Return error in consistent format
            return [
                'quizid' => $params['quizid'],
                'quiz_instance' => 0,
                'quiz_name' => '',
                'questions_requested' => count($params['questions']),
                'questions_added' => 0,
                'quiz_url' => '',
                'success' => false,
                'warnings' => [
                    [
                        'item' => 'quiz',
                        'itemid' => $params['quizid'],
                        'warningcode' => 'questionadditionfailed',
                        'message' => $e->getMessage()
                    ]
                ]
            ];
        }
    }
    
    /**
     * Describes the return structure for add_quiz_questions.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'quizid' => new external_value(PARAM_INT, 'Quiz course module ID'),
            'quiz_instance' => new external_value(PARAM_INT, 'Quiz instance ID'),
            'quiz_name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'questions_requested' => new external_value(PARAM_INT, 'Number of questions requested'),
            'questions_added' => new external_value(PARAM_INT, 'Number of questions successfully added'),
            'quiz_url' => new external_value(PARAM_URL, 'Quiz URL'),
            'success' => new external_value(PARAM_BOOL, 'Operation success status'),
            'warnings' => new external_warnings()
        ]);
    }
    
    /**
     * Log action for debugging.
     *
     * @param string $action Action performed
     * @param array $data Additional data
     */
    private static function log_action($action, $data = []) {
        if (debugging()) {
            $logdata = array_merge(['action' => $action, 'time' => time()], $data);
            error_log('local_wsmanageactivities: ' . json_encode($logdata));
        }
    }
}