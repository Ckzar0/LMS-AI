<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;
use stdClass;

class submit_quiz_grade extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'grade' => new external_value(PARAM_FLOAT, 'Grade obtained (0 to 100 or actual scale)')
        ]);
    }

    public static function execute($cmid, $grade) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'grade' => $grade
        ]);

        // Validate context
        $cm = get_coursemodule_from_id('quiz', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Get quiz record
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

        // Prepare grade record
        $grades = [];
        $grades[$USER->id] = new stdClass();
        $grades[$USER->id]->userid = $USER->id;
        $grades[$USER->id]->rawgrade = $params['grade'];

        // 1. Update grade in Moodle Gradebook
        quiz_grade_item_update($quiz, $grades);

        // 2. FORCE COMPLETION STATE
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $completion = new \completion_info($course);
        
        // Manual override for completion state to ensure UI reflects it
        if ($completion->is_enabled($cm)) {
            $data = $completion->get_data($cm, true, $USER->id);
            $data->completionstate = COMPLETION_COMPLETE;
            $data->timemodified = time();
            $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id, true);
            
            // Re-fetch to confirm
            $check = $completion->get_data($cm, true, $USER->id);
            $final_state = $check->completionstate;
        } else {
            $final_state = 'not_enabled';
        }

        return [
            'status' => 'success',
            'message' => "Grade submitted. Final completion state: $final_state"
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success/error)'),
            'message' => new external_value(PARAM_TEXT, 'Status message')
        ]);
    }
}
