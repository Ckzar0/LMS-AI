<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;

/**
 * External API to automatically enrol a user in a course
 */
class enrol_user extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'roleid' => new external_value(PARAM_INT, 'Role ID (optional, default to student)', VALUE_DEFAULT, 5)
        ]);
    }

    public static function execute($courseid, $roleid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'roleid' => $roleid
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        self::validate_context($context);

        // We use the manual enrolment plugin
        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            return ['status' => 'error', 'message' => 'Manual enrolment plugin not found'];
        }

        $instances = enrol_get_instances($course->id, true);
        $instance = null;
        foreach ($instances as $i) {
            if ($i->enrol === 'manual') {
                $instance = $i;
                break;
            }
        }

        if (!$instance) {
            // Create a manual enrolment instance if not exists
            $enrolid = $enrol->add_default_instance($course);
            $instance = $DB->get_record('enrol', ['id' => $enrolid]);
        }

        // Check if user is already enrolled
        if (!$DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $USER->id])) {
            $enrol->enrol_user($instance, $USER->id, $params['roleid']);
            return ['status' => 'success', 'message' => 'User enrolled successfully as student'];
        }

        return ['status' => 'ignored', 'message' => 'User already enrolled'];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success/ignored/error)'),
            'message' => new external_value(PARAM_TEXT, 'Status message')
        ]);
    }
}
