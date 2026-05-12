<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use stdClass;

class get_dashboard_stats extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([]);
    }

    public static function execute() {
        global $DB, $USER;

        // 1. Total Courses (excluding site course)
        $total_courses = $DB->count_records_select('course', 'id > 1');

        // 2. Total Users
        $total_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);

        // 3. Total Certificates (count from customcert or unique completions of certificate activities)
        // Here we'll count entries in feedback_completed as a proxy for 'courses rated/completed' 
        // OR if customcert exists, we count that. For now, let's count completed feedback as 'certificates'
        $total_certificates = $DB->count_records('feedback_completed');

        // 4. Weekly Activity (last 7 days)
        $weekly_activity = [];
        for ($i = 6; $i >= 0; $i--) {
            $day_start = strtotime("-$i days 00:00:00");
            $day_end = strtotime("-$i days 23:59:59");
            
            // Count log entries (Standard log)
            $count = $DB->count_records_select('logstore_standard_log', 
                "timecreated >= ? AND timecreated <= ?", 
                [$day_start, $day_end]
            );

            $weekly_activity[] = [
                'day' => date('D', $day_start),
                'count' => (int)$count
            ];
        }

        // 5. Average Global Rating
        $rating_sql = "SELECT AVG(CAST(SUBSTRING(v.value, 1, 1) AS UNSIGNED)) as avg_rating
                       FROM {feedback_value} v
                       JOIN {feedback_item} i ON v.item = i.id
                       WHERE i.typ = 'multichoice'";
        $rating_record = $DB->get_record_sql($rating_sql);
        $global_rating = $rating_record ? round((float)$rating_record->avg_rating, 1) : 0.0;

        return [
            'total_courses' => (int)$total_courses,
            'total_users' => (int)$total_users,
            'total_certificates' => (int)$total_certificates,
            'global_rating' => (float)$global_rating,
            'weekly_activity' => $weekly_activity
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'total_courses' => new external_value(PARAM_INT, 'Total number of courses'),
            'total_users' => new external_value(PARAM_INT, 'Total number of users'),
            'total_certificates' => new external_value(PARAM_INT, 'Total certificates issued'),
            'global_rating' => new external_value(PARAM_FLOAT, 'Global average rating'),
            'weekly_activity' => new external_multiple_structure(
                new external_single_structure([
                    'day' => new external_value(PARAM_TEXT, 'Day of week'),
                    'count' => new external_value(PARAM_INT, 'Activity count')
                ])
            )
        ]);
    }
}
