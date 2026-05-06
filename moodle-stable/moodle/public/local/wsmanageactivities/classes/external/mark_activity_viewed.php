<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/completionlib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;

class mark_activity_viewed extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    public static function execute($cmid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        // Validar contexto
        $cm = get_coursemodule_from_id('', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Marcar como visto
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $completion = new \completion_info($course);
        
        if ($completion->is_enabled($cm) && $cm->completionview == COMPLETION_VIEW_REQUIRED) {
            $completion->set_module_viewed($cm);
            return [
                'status' => 'success',
                'message' => 'Activity marked as viewed'
            ];
        }

        return [
            'status' => 'ignored',
            'message' => 'Completion tracking not enabled or not required for this activity'
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success/ignored/error)'),
            'message' => new external_value(PARAM_TEXT, 'Status message')
        ]);
    }
}
