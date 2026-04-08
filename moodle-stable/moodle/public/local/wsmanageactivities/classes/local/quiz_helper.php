<?php
/**
 * Quiz activity helper functions.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_course;
use question_engine;
use moodle_exception;

/**
 * Helper functions for quiz activities.
 */
class quiz_helper {
    
    /**
     * Prepare moduleinfo object for quiz creation.
     *
     * @param array $params Input parameters
     * @param object $course Course object
     * @return stdClass Module info object
     */
    public static function prepare_quiz_moduleinfo($params, $course) {
        global $DB;
        
        // Get module info
        $module = $DB->get_record('modules', array('name' => 'quiz', 'visible' => 1), '*', MUST_EXIST);
        
        // Create base moduleinfo
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->module = $module->id;
        $moduleinfo->modulename = 'quiz';
        $moduleinfo->instance = 0;
        $moduleinfo->section = $params['sectionnum'];
        
        // Basic quiz fields
        $moduleinfo->name = $params['name'];
        
        // Apply quiz configuration
        $config = validation::validate_quiz_config($params['config']);
        self::apply_quiz_config($moduleinfo, $config);
        
        // Apply standard module fields
        self::apply_standard_module_fields($moduleinfo, $params['options'] ?? array());
        
        return $moduleinfo;
    }
    
    /**
     * Apply quiz configuration to moduleinfo.
     *
     * @param stdClass $moduleinfo Module info object
     * @param array $config Quiz configuration
     */
    private static function apply_quiz_config($moduleinfo, $config) {
        // Introduction
        $moduleinfo->intro = $config['intro'];
        $moduleinfo->introformat = $config['introformat'];
        
        // Timing
        $moduleinfo->timeopen = $config['timeopen'];
        $moduleinfo->timeclose = $config['timeclose'];
        $moduleinfo->timelimit = $config['timelimit'];
        
        // Grade and attempts
        $moduleinfo->attempts = $config['attempts'];
        $moduleinfo->grademethod = $config['grademethod'];
        $moduleinfo->grade = $config['grade'];
        
        // Layout
        $moduleinfo->questionsperpage = $config['questionsperpage'];
        $moduleinfo->shufflequestions = $config['shufflequestions'] ? 1 : 0;
        $moduleinfo->shuffleanswers = $config['shuffleanswers'] ? 1 : 0;
        
        // Review options (simplified)
        $moduleinfo->reviewattempt = 0x10101;      // During attempt, immediately after, later
        $moduleinfo->reviewcorrectness = 0x10101;
        $moduleinfo->reviewmarks = 0x10101;
        $moduleinfo->reviewspecificfeedback = 0x10101;
        $moduleinfo->reviewgeneralfeedback = 0x10101;
        $moduleinfo->reviewrightanswer = 0x10101;
        $moduleinfo->reviewoverallfeedback = 0x10101;
        
        // Additional quiz settings with defaults
        $moduleinfo->overduehandling = 'autosubmit';
        $moduleinfo->graceperiod = 86400; // 1 day
        $moduleinfo->preferredbehaviour = 'deferredfeedback';
        $moduleinfo->canredoquestions = 0;
        $moduleinfo->attemptonlast = 0;
        $moduleinfo->decimalpoints = 2;
        $moduleinfo->questiondecimalpoints = -1;
        $moduleinfo->showuserpicture = 0;
        $moduleinfo->showblocks = 0;
        $moduleinfo->browsersecurity = '-';
        $moduleinfo->allowofflineattempts = 0;
        $moduleinfo->autosaveperiod = 0;
        $moduleinfo->navmethod = 'free';
    }
    
    /**
     * Apply standard module fields for quiz.
     *
     * @param stdClass $moduleinfo Module info object
     * @param array $options Options array
     */
    private static function apply_standard_module_fields($moduleinfo, $options) {
        // Visibility
        $moduleinfo->visible = isset($options['visible']) ? 
            ($options['visible'] ? 1 : 0) : 1;
        $moduleinfo->visibleoncoursepage = $moduleinfo->visible;
        
        // Group settings
        $moduleinfo->groupmode = isset($options['groupmode']) ? 
            (int)$options['groupmode'] : 0;
        $moduleinfo->groupingid = isset($options['groupingid']) ? 
            (int)$options['groupingid'] : 0;
        
        // Availability
        $moduleinfo->availability = isset($options['availability']) ? 
            validation::validate_availability($options['availability']) : null;
        
        // Completion tracking (quiz has specific completion criteria)
        $moduleinfo->completion = COMPLETION_TRACKING_MANUAL;
        $moduleinfo->completionpass = 0;
        $moduleinfo->completionattemptsexhausted = 0;
        $moduleinfo->completionminattempts = 0;
        
        // Other standard fields
        $moduleinfo->showdescription = 0;
        $moduleinfo->indent = 0;
        $moduleinfo->score = 0;
    }
    
    /**
     * Create questions for a quiz.
     *
     * @param int $quizid Quiz ID
     * @param array $questions Array of questions
     * @return int Number of questions created
     */
    public static function create_quiz_questions($quizid, $questions) {
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        
        $quiz = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        
        $questionsadded = 0;
        
        foreach ($questions as $questiondata) {
            try {
                $validatedquestion = validation::validate_question_data($questiondata);
                $questionid = self::create_single_question($validatedquestion, $context);
                
                if ($questionid) {
                    // Add question to quiz
                    quiz_add_quiz_question($questionid, $quiz);
                    $questionsadded++;
                }
            } catch (Exception $e) {
                // Log error but continue with other questions
                error_log('Failed to create question: ' . $e->getMessage());
            }
        }
        
        // Rebuild quiz structure
        if ($questionsadded > 0) {
            if (class_exists('\mod_quiz\quiz_settings')) {
                $quizobj = \mod_quiz\quiz_settings::create($quiz->id);
                \mod_quiz\grade_calculator::create($quizobj)->recompute_quiz_sumgrades();
            }
        }
        
        return $questionsadded;
    }
    
    /**
     * Create a single question.
     *
     * @param array $questiondata Question data
     * @param object $context Course context
     * @return int Question ID or false on failure
     */
    private static function create_single_question($questiondata, $context) {
        global $CFG, $USER;
        
        require_once($CFG->dirroot . '/question/type/' . $questiondata['type'] . '/questiontype.php');
        
        switch ($questiondata['type']) {
            case 'multichoice':
                return self::create_multichoice_question($questiondata, $context);
            case 'shortanswer':
                return self::create_shortanswer_question($questiondata, $context);
            case 'essay':
                return self::create_essay_question($questiondata, $context);
            case 'truefalse':
                return self::create_truefalse_question($questiondata, $context);
            default:
                return false;
        }
    }
    
    /**
     * Create multiple choice question.
     *
     * @param array $data Question data
     * @param object $context Context
     * @return int Question ID
     */
    private static function create_multichoice_question($data, $context) {
        global $DB, $USER;
        
        // Create question record
        $question = new stdClass();
        $question->category = self::get_default_question_category($context->id);
        $question->qtype = 'multichoice';
        $question->name = $data['name'];
        $question->questiontext = $data['questiontext'];
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = '';
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->defaultmark = $data['mark'];
        $question->penalty = 0.3333333;
        $question->length = 1;
        $question->stamp = make_unique_id_code();
        $question->version = 1;
        $question->hidden = 0;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        
        $questionid = $DB->insert_record('question', $question);
        
        // Create multichoice options
        $options = new stdClass();
        $options->questionid = $questionid;
        $options->layout = 0; // Radio buttons
        $options->single = 1; // Single answer
        $options->shuffleanswers = 1;
        $options->correctfeedback = 'Correto!';
        $options->correctfeedbackformat = FORMAT_HTML;
        $options->incorrectfeedback = 'Incorreto!';
        $options->incorrectfeedbackformat = FORMAT_HTML;
        $options->partiallycorrectfeedback = 'Parcialmente correto!';
        $options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $options->answernumbering = 'abc';
        $options->shownumcorrect = 1;
        
        $DB->insert_record('qtype_multichoice_options', $options);
        
        // Create default answers if provided in questiondata
        $answers = isset($data['questiondata']['answers']) ? 
            $data['questiondata']['answers'] : array(
                array('text' => 'Opção A', 'fraction' => 1),
                array('text' => 'Opção B', 'fraction' => 0),
                array('text' => 'Opção C', 'fraction' => 0),
                array('text' => 'Opção D', 'fraction' => 0)
            );
        
        foreach ($answers as $answerdata) {
            $answer = new stdClass();
            $answer->question = $questionid;
            $answer->answer = $answerdata['text'];
            $answer->answerformat = FORMAT_HTML;
            $answer->fraction = $answerdata['fraction'];
            $answer->feedback = '';
            $answer->feedbackformat = FORMAT_HTML;
            
            $DB->insert_record('question_answers', $answer);
        }
        
        return $questionid;
    }
    
    /**
     * Create short answer question.
     *
     * @param array $data Question data
     * @param object $context Context
     * @return int Question ID
     */
    private static function create_shortanswer_question($data, $context) {
        global $DB, $USER;
        
        // Create question record
        $question = new stdClass();
        $question->category = self::get_default_question_category($context->id);
        $question->qtype = 'shortanswer';
        $question->name = $data['name'];
        $question->questiontext = $data['questiontext'];
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = '';
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->defaultmark = $data['mark'];
        $question->penalty = 0.3333333;
        $question->length = 1;
        $question->stamp = make_unique_id_code();
        $question->version = 1;
        $question->hidden = 0;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        
        $questionid = $DB->insert_record('question', $question);
        
        // Create shortanswer options
        $options = new stdClass();
        $options->questionid = $questionid;
        $options->usecase = 0; // Case insensitive
        
        $DB->insert_record('qtype_shortanswer_options', $options);
        
        // Create default answer
        $answers = isset($data['questiondata']['answers']) ? 
            $data['questiondata']['answers'] : array(
                array('text' => '*', 'fraction' => 1) // Accept any answer
            );
        
        foreach ($answers as $answerdata) {
            $answer = new stdClass();
            $answer->question = $questionid;
            $answer->answer = $answerdata['text'];
            $answer->answerformat = FORMAT_MOODLE;
            $answer->fraction = $answerdata['fraction'];
            $answer->feedback = '';
            $answer->feedbackformat = FORMAT_HTML;
            
            $DB->insert_record('question_answers', $answer);
        }
        
        return $questionid;
    }
    
    /**
     * Create essay question.
     *
     * @param array $data Question data
     * @param object $context Context
     * @return int Question ID
     */
    private static function create_essay_question($data, $context) {
        global $DB, $USER;
        
        // Create question record
        $question = new stdClass();
        $question->category = self::get_default_question_category($context->id);
        $question->qtype = 'essay';
        $question->name = $data['name'];
        $question->questiontext = $data['questiontext'];
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = '';
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->defaultmark = $data['mark'];
        $question->penalty = 0;
        $question->length = 1;
        $question->stamp = make_unique_id_code();
        $question->version = 1;
        $question->hidden = 0;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        
        $questionid = $DB->insert_record('question', $question);
        
        // Create essay options
        $options = new stdClass();
        $options->questionid = $questionid;
        $options->responseformat = 'editor'; // HTML editor
        $options->responserequired = 1;
        $options->responsefieldlines = 15;
        $options->attachments = 0;
        $options->attachmentsrequired = 0;
        $options->graderinfo = '';
        $options->graderinfoformat = FORMAT_HTML;
        $options->responsetemplate = '';
        $options->responsetemplateformat = FORMAT_HTML;
        
        $DB->insert_record('qtype_essay_options', $options);
        
        return $questionid;
    }
    
    /**
     * Create true/false question.
     *
     * @param array $data Question data
     * @param object $context Context
     * @return int Question ID
     */
    private static function create_truefalse_question($data, $context) {
        global $DB, $USER;
        
        // Create question record
        $question = new stdClass();
        $question->category = self::get_default_question_category($context->id);
        $question->qtype = 'truefalse';
        $question->name = $data['name'];
        $question->questiontext = $data['questiontext'];
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = '';
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->defaultmark = $data['mark'];
        $question->penalty = 1;
        $question->length = 1;
        $question->stamp = make_unique_id_code();
        $question->version = 1;
        $question->hidden = 0;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        
        $questionid = $DB->insert_record('question', $question);
        
        // Get correct answer from question data (default: true)
        $correctanswer = isset($data['questiondata']['correctanswer']) ? 
            $data['questiondata']['correctanswer'] : true;
        
        // Create True answer
        $trueanswer = new stdClass();
        $trueanswer->question = $questionid;
        $trueanswer->answer = get_string('true', 'qtype_truefalse');
        $trueanswer->answerformat = FORMAT_MOODLE;
        $trueanswer->fraction = $correctanswer ? 1 : 0;
        $trueanswer->feedback = '';
        $trueanswer->feedbackformat = FORMAT_HTML;
        
        $DB->insert_record('question_answers', $trueanswer);
        
        // Create False answer
        $falseanswer = new stdClass();
        $falseanswer->question = $questionid;
        $falseanswer->answer = get_string('false', 'qtype_truefalse');
        $falseanswer->answerformat = FORMAT_MOODLE;
        $falseanswer->fraction = $correctanswer ? 0 : 1;
        $falseanswer->feedback = '';
        $falseanswer->feedbackformat = FORMAT_HTML;
        
        $DB->insert_record('question_answers', $falseanswer);
        
        return $questionid;
    }
    
    /**
     * Get default question category for context.
     *
     * @param int $contextid Context ID
     * @return int Category ID
     */
    private static function get_default_question_category($contextid) {
        global $DB, $CFG;
        
        $category = $DB->get_record('question_categories', 
            array('contextid' => (int)$contextid, 'parent' => 0));
        
        if (!$category) {
            // Create default category if it doesn't exist
            require_once($CFG->libdir . '/questionlib.php');
            try {
                $context = \context::instance_by_id($contextid, false);
                if ($context) {
                    $category = question_make_default_categories(array($context));
                }
            } catch (\Throwable $e) {
                error_log("quiz_helper - Erro ao obter contexto: " . $e->getMessage());
            }
        }
        
        return $category ? $category->id : 0;
    }
    
    /**
     * Format quiz creation result.
     *
     * @param object $cm Course module
     * @param int $questionsadded Number of questions added
     * @return array Formatted result
     */
    public static function format_quiz_result($cm, $questionsadded = 0) {
        global $CFG, $DB;
        
        $quiz = $DB->get_record('quiz', array('id' => $cm->instance));
        
        $result = array(
            'id' => $cm->id,
            'instance' => $cm->instance,
            'name' => $cm->name,
            'url' => $CFG->wwwroot . '/mod/quiz/view.php?id=' . $cm->id,
            'questions_added' => $questionsadded,
            'grade' => $quiz ? $quiz->grade : 0,
            'attempts' => $quiz ? $quiz->attempts : 0,
            'success' => true,
            'warnings' => array()
        );
        
        return $result;
    }
    
    /**
     * Get supported question types.
     *
     * @return array Question types
     */
    public static function get_question_types() {
        return array(
            'multichoice' => get_string('questiontype:multichoice', 'local_wsmanageactivities'),
            'shortanswer' => get_string('questiontype:shortanswer', 'local_wsmanageactivities'),
            'essay' => get_string('questiontype:essay', 'local_wsmanageactivities'),
            'truefalse' => get_string('questiontype:truefalse', 'local_wsmanageactivities')
        );
    }
}