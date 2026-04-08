<?php
/**
 * External API for creating quiz activities.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/modlib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use context_course;
use moodle_exception;
use local_wsmanageactivities\local\validation;
use local_wsmanageactivities\local\quiz_helper;

/**
 * External API for creating quiz activities.
 */
class create_quiz extends external_api {
    
    /**
     * Describes the parameters for create_quiz.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number where to add the quiz'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'config' => new external_single_structure([
                'intro' => new external_value(PARAM_RAW, 'Quiz introduction', VALUE_OPTIONAL, ''),
                'introformat' => new external_value(PARAM_INT, 'Introduction format', VALUE_OPTIONAL, FORMAT_HTML),
                'timeopen' => new external_value(PARAM_INT, 'Quiz open time (timestamp)', VALUE_OPTIONAL, 0),
                'timeclose' => new external_value(PARAM_INT, 'Quiz close time (timestamp)', VALUE_OPTIONAL, 0),
                'timelimit' => new external_value(PARAM_INT, 'Time limit in seconds', VALUE_OPTIONAL, 0),
                'attempts' => new external_value(PARAM_INT, 'Number of attempts allowed (0 = unlimited)', VALUE_OPTIONAL, 0),
                'grademethod' => new external_value(PARAM_INT, 'Grading method (1=Highest, 2=Average, 3=First, 4=Last)', VALUE_OPTIONAL, 1),
                'grade' => new external_value(PARAM_FLOAT, 'Maximum grade', VALUE_OPTIONAL, 10.0),
                'questionsperpage' => new external_value(PARAM_INT, 'Questions per page', VALUE_OPTIONAL, 1),
                'shufflequestions' => new external_value(PARAM_BOOL, 'Shuffle questions', VALUE_OPTIONAL, false),
                'shuffleanswers' => new external_value(PARAM_BOOL, 'Shuffle answers', VALUE_OPTIONAL, true),
                'reviewoptions' => new external_single_structure([
                    'attempt' => new external_value(PARAM_INT, 'Review during attempt', VALUE_OPTIONAL, 0x10101),
                    'correctness' => new external_value(PARAM_INT, 'Show correctness', VALUE_OPTIONAL, 0x10101),
                    'marks' => new external_value(PARAM_INT, 'Show marks', VALUE_OPTIONAL, 0x10101),
                    'feedback' => new external_value(PARAM_INT, 'Show feedback', VALUE_OPTIONAL, 0x10101),
                ], 'Review options', VALUE_OPTIONAL),
            ], 'Quiz configuration'),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'type' => new external_value(PARAM_ALPHA, 'Question type (multichoice, shortanswer, essay, truefalse)'),
                    'name' => new external_value(PARAM_TEXT, 'Question name'),
                    'questiontext' => new external_value(PARAM_RAW, 'Question text (HTML)'),
                    'mark' => new external_value(PARAM_FLOAT, 'Question marks', VALUE_OPTIONAL, 1.0),
                    'questiondata' => new external_value(PARAM_RAW, 'Question-specific data (JSON)', VALUE_OPTIONAL, '{}'),
                ]), 
                'Questions to add to quiz', VALUE_OPTIONAL, []
            ),
            'options' => new external_single_structure([
                'visible' => new external_value(PARAM_BOOL, 'Visible to students', VALUE_OPTIONAL, true),
                'groupmode' => new external_value(PARAM_INT, 'Group mode', VALUE_OPTIONAL, 0),
                'groupingid' => new external_value(PARAM_INT, 'Grouping ID', VALUE_OPTIONAL, 0),
                'availability' => new external_value(PARAM_RAW, 'Availability conditions (JSON)', VALUE_OPTIONAL, null),
            ], 'Additional quiz options', VALUE_OPTIONAL)
        ]);
    }
    
    /**
     * Create a quiz activity.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param string $name Quiz name
     * @param array $config Quiz configuration
     * @param array $questions Questions to add
     * @param array $options Additional options
     * @return array Quiz creation result
     * @throws moodle_exception
     */
    public static function execute($courseid, $sectionnum, $name, $config, $questions = [], $options = []) {
        global $CFG, $DB;
        
        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionnum' => $sectionnum,
            'name' => $name,
            'config' => $config,
            'questions' => $questions,
            'options' => $options
        ]);
        
        try {
            // Validate course and section
            $coursedata = validation::validate_course_and_section($params['courseid'], $params['sectionnum']);
            $course = $coursedata['course'];
            $context = context_course::instance($params['courseid']);
            
            // Validate context and permissions
            self::validate_context($context);
            validation::validate_module_permissions($context, 'quiz', 'addinstance');
            
            // Prepare module info
            $moduleinfo = quiz_helper::prepare_quiz_moduleinfo($params, $course);
            
            // Create the quiz activity
            $cm = add_moduleinfo($moduleinfo, $course);
            
            // Add questions if provided
            $questionsadded = 0;
            if (!empty($params['questions'])) {
                require_capability('mod/quiz:manage', $context);
                $questionsadded = quiz_helper::create_quiz_questions($cm->instance, $params['questions']);
            }
            
            // Log the action
            self::log_action('quiz_created', [
                'courseid' => $params['courseid'],
                'sectionnum' => $params['sectionnum'],
                'quizid' => $cm->id,
                'name' => $params['name'],
                'questions_added' => $questionsadded
            ]);
            
            // Format and return result
            return quiz_helper::format_quiz_result($cm, $questionsadded);
            
        } catch (moodle_exception $e) {
            // Return error in consistent format
            return [
                'id' => 0,
                'instance' => 0,
                'name' => $params['name'],
                'url' => '',
                'questions_added' => 0,
                'grade' => 0,
                'attempts' => 0,
                'success' => false,
                'warnings' => [
                    [
                        'item' => 'quiz',
                        'itemid' => 0,
                        'warningcode' => 'quizcreationfailed',
                        'message' => $e->getMessage()
                    ]
                ]
            ];
        }
    }
    
    /**
     * Describes the return structure for create_quiz.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Course module ID'),
            'instance' => new external_value(PARAM_INT, 'Quiz instance ID'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'url' => new external_value(PARAM_URL, 'Quiz URL'),
            'questions_added' => new external_value(PARAM_INT, 'Number of questions added'),
            'grade' => new external_value(PARAM_FLOAT, 'Maximum grade'),
            'attempts' => new external_value(PARAM_INT, 'Attempts allowed'),
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