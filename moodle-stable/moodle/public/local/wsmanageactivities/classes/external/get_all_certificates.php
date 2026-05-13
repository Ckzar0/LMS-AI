<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

class get_all_certificates extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([]);
    }

    public static function execute() {
        global $DB, $CFG;

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/course:view', $context);

        // Query to find unique issued certificates per course
        $sql = "SELECT MIN(fc.id) as id, c.id as courseid, c.fullname as coursename, u.id as userid, u.firstname, u.lastname, 
                       MAX(fc.timemodified) as timeissued, cc.id as cmid
                FROM {feedback_completed} fc
                JOIN {feedback} f ON fc.feedback = f.id
                JOIN {course} c ON f.course = c.id
                JOIN {user} u ON fc.userid = u.id
                JOIN {course_modules} cc ON cc.course = c.id 
                     AND cc.module = (SELECT id FROM {modules} WHERE name = 'customcert')
                GROUP BY c.id, c.fullname, u.id, u.firstname, u.lastname, cc.id
                ORDER BY timeissued DESC";
        
        $records = $DB->get_records_sql($sql);
        $certificates = [];

        foreach ($records as $rec) {
            $certificates[] = [
                'course_name' => (string)$rec->coursename,
                'user_name' => (string)$rec->firstname . ' ' . $rec->lastname,
                'date' => (int)$rec->timeissued,
                'cmid' => (int)($rec->cmid ?: 0)
            ];
        }

        return $certificates;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'course_name' => new external_value(PARAM_TEXT, 'Course name'),
                'user_name' => new external_value(PARAM_TEXT, 'User name'),
                'date' => new external_value(PARAM_INT, 'Issue date timestamp'),
                'cmid' => new external_value(PARAM_INT, 'CustomCert course module ID')
            ])
        );
    }
}
