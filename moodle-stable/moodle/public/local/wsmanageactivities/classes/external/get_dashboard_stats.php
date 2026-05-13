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

        // 2. Total Users (excluding guest)
        $total_users = $DB->count_records_select('user', "deleted = 0 AND suspended = 0 AND username != 'guest'");

        // 3. Total Certificates (Unique courses with certificate activity completed)
        $cert_sql = "SELECT COUNT(DISTINCT f.course)
                     FROM {feedback_completed} fc
                     JOIN {feedback} f ON fc.feedback = f.id
                     JOIN {course_modules} cm ON cm.course = f.course
                     JOIN {modules} m ON cm.module = m.id
                     WHERE m.name = 'customcert'";
        $total_certificates = $DB->count_records_sql($cert_sql);

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

        // 6. Recent Activity (Filtered and Grouped to avoid duplicates)
        $recent_activity = [];
        $sql = "SELECT MIN(l.id) as id, l.eventname, MAX(l.timecreated) as timecreated, u.firstname, u.lastname, c.fullname as coursename
                FROM {logstore_standard_log} l
                JOIN {user} u ON l.userid = u.id
                LEFT JOIN {course} c ON l.courseid = c.id
                WHERE l.eventname LIKE '%course_created'
                   OR l.eventname LIKE '%course_updated'
                   OR l.eventname LIKE '%course_module_created'
                   OR l.eventname LIKE '%user_enrolment_created'
                   OR l.eventname LIKE '%course_completed'
                   OR l.eventname LIKE '%response_submitted'
                   OR l.eventname LIKE '%attempt_submitted'
                GROUP BY l.eventname, l.courseid, l.userid, u.firstname, u.lastname, c.fullname
                ORDER BY timecreated DESC";
        $logs = $DB->get_records_sql($sql, null, 0, 5);

        foreach ($logs as $log) {
            $action = 'Realizou uma ação';
            if (strpos($log->eventname, 'course_created') !== false) $action = 'Criou o curso';
            if (strpos($log->eventname, 'course_updated') !== false) $action = 'Atualizou o curso';
            if (strpos($log->eventname, 'course_module_created') !== false) $action = 'Adicionou conteúdo em';
            if (strpos($log->eventname, 'user_enrolment_created') !== false) $action = 'Inscreveu-se no curso';
            if (strpos($log->eventname, 'course_completed') !== false) $action = 'Completou o curso';
            if (strpos($log->eventname, 'response_submitted') !== false) $action = 'Enviou uma avaliação em';
            if (strpos($log->eventname, 'attempt_submitted') !== false) $action = 'Submeteu um quiz em';

            $recent_activity[] = [
                'user' => $log->firstname . ' ' . $log->lastname,
                'action' => $action,
                'course' => $log->coursename ?: 'Sistema',
                'time' => (int)$log->timecreated
            ];
        }

        return [
            'total_courses' => (int)$total_courses,
            'total_users' => (int)$total_users,
            'total_certificates' => (int)$total_certificates,
            'global_rating' => (float)$global_rating,
            'weekly_activity' => $weekly_activity,
            'recent_activity' => $recent_activity
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
            ),
            'recent_activity' => new external_multiple_structure(
                new external_single_structure([
                    'user' => new external_value(PARAM_TEXT, 'User name'),
                    'action' => new external_value(PARAM_TEXT, 'Action description'),
                    'course' => new external_value(PARAM_TEXT, 'Course name'),
                    'time' => new external_value(PARAM_INT, 'Timestamp')
                ])
            )
        ]);
    }
}
