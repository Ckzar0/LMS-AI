<?php
/**
 * Quiz helper - SIMPLIFIED VERSION
 */

namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;

class quiz_helper {
    
    public static function prepare_quiz_moduleinfo($params, $course) {
        global $DB;
        
        $module = $DB->get_record('modules', array('name' => 'quiz', 'visible' => 1), '*', MUST_EXIST);
        
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->module = $module->id;
        $moduleinfo->modulename = 'quiz';
        $moduleinfo->instance = 0;
        $moduleinfo->section = $params['sectionnum'];
        $moduleinfo->name = $params['name'];
        
        // Basic config with safe defaults
        $config = isset($params['config']) && is_array($params['config']) ? $params['config'] : array();
        
        $moduleinfo->intro = isset($config['intro']) ? $config['intro'] : '';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->timeopen = isset($config['timeopen']) ? (int)$config['timeopen'] : 0;
        $moduleinfo->timeclose = isset($config['timeclose']) ? (int)$config['timeclose'] : 0;
        $moduleinfo->timelimit = isset($config['timelimit']) ? (int)$config['timelimit'] : 0;
        $moduleinfo->attempts = isset($config['attempts']) ? (int)$config['attempts'] : 0;
        $moduleinfo->grademethod = isset($config['grademethod']) ? (int)$config['grademethod'] : 1;
        $moduleinfo->grade = isset($config['grade']) ? (float)$config['grade'] : 10.0;
        $moduleinfo->questionsperpage = isset($config['questionsperpage']) ? (int)$config['questionsperpage'] : 1;
        
        // Required quiz fields with safe defaults
        $moduleinfo->overduehandling = 'autosubmit';
        $moduleinfo->graceperiod = 86400;
        $moduleinfo->preferredbehaviour = 'deferredfeedback';
        $moduleinfo->canredoquestions = 0;
        $moduleinfo->attemptonlast = 0;
        $moduleinfo->decimalpoints = 2;
        $moduleinfo->questiondecimalpoints = -1;
        $moduleinfo->reviewattempt = 0x10101;
        $moduleinfo->reviewcorrectness = 0x10101;
        $moduleinfo->reviewmarks = 0x10101;
        $moduleinfo->reviewspecificfeedback = 0x10101;
        $moduleinfo->reviewgeneralfeedback = 0x10101;
        $moduleinfo->reviewrightanswer = 0x10101;
        $moduleinfo->reviewoverallfeedback = 0x10101;
        $moduleinfo->showuserpicture = 0;
        $moduleinfo->showblocks = 0;
        $moduleinfo->browsersecurity = '-';
        $moduleinfo->password = '';
        $moduleinfo->subnet = '';
        $moduleinfo->delay1 = 0;
        $moduleinfo->delay2 = 0;
        $moduleinfo->shufflequestions = 0;
        $moduleinfo->shuffleanswers = 1;
        
        // Standard fields
        $options = isset($params['options']) && is_array($params['options']) ? $params['options'] : array();
        $moduleinfo->visible = isset($options['visible']) ? ($options['visible'] ? 1 : 0) : 1;
        $moduleinfo->visibleoncoursepage = $moduleinfo->visible;
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->availability = null;
        $moduleinfo->completion = COMPLETION_TRACKING_MANUAL;
        $moduleinfo->showdescription = 0;
        $moduleinfo->added = time();
        $moduleinfo->cmidnumber = '';
        $moduleinfo->idnumber = '';
        
        return $moduleinfo;
    }
    
    public static function create_quiz_questions($quizid, $questions) {
        // Simplified - just return count for now
        return count($questions);
    }
    
    public static function format_quiz_result($cm, $questionsadded = 0) {
        global $CFG, $DB;
        
        $quiz = $DB->get_record('quiz', array('id' => $cm->instance));
        
        return array(
            'id' => (int)$cm->id,
            'instance' => (int)$cm->instance,
            'name' => $cm->name,
            'url' => $CFG->wwwroot . '/mod/quiz/view.php?id=' . $cm->id,
            'questions_added' => (int)$questionsadded,
            'grade' => $quiz ? (float)$quiz->grade : 0,
            'attempts' => $quiz ? (int)$quiz->attempts : 0,
            'success' => true,
            'warnings' => array()
        );
    }
    
    public static function get_question_types() {
        return array(
            'multichoice' => 'Multiple Choice',
            'shortanswer' => 'Short Answer',
            'essay' => 'Essay',
            'truefalse' => 'True/False'
        );
    }
}
