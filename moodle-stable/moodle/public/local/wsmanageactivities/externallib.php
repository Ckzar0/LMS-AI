<?php
/**
 * External API legacy support for local_wsmanageactivities.
 *
 * This file provides backward compatibility for older Moodle versions
 * that expect external functions to be defined in externallib.php.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Legacy external API class for backward compatibility.
 * 
 * In modern Moodle versions, external functions are defined in classes/external/
 * but this file provides compatibility for older versions.
 */
class local_wsmanageactivities_external extends external_api {
    
    /**
     * Legacy wrapper for create_page function.
     * Delegates to the new class-based implementation.
     */
    public static function create_page_parameters() {
        return \local_wsmanageactivities\external\create_page::execute_parameters();
    }
    
    public static function create_page($courseid, $sectionnum, $name, $content, $options = []) {
        return \local_wsmanageactivities\external\create_page::execute($courseid, $sectionnum, $name, $content, $options);
    }
    
    public static function create_page_returns() {
        return \local_wsmanageactivities\external\create_page::execute_returns();
    }
    
    /**
     * Legacy wrapper for create_quiz function.
     * Delegates to the new class-based implementation.
     */
    public static function create_quiz_parameters() {
        return \local_wsmanageactivities\external\create_quiz::execute_parameters();
    }
    
    public static function create_quiz($courseid, $sectionnum, $name, $config, $questions = [], $options = []) {
        return \local_wsmanageactivities\external\create_quiz::execute($courseid, $sectionnum, $name, $config, $questions, $options);
    }
    
    public static function create_quiz_returns() {
        return \local_wsmanageactivities\external\create_quiz::execute_returns();
    }
    
    /**
     * Legacy wrapper for add_quiz_questions function.
     * Delegates to the new class-based implementation.
     */
    public static function add_quiz_questions_parameters() {
        return \local_wsmanageactivities\external\add_quiz_questions::execute_parameters();
    }
    
    public static function add_quiz_questions($quizid, $questions, $idtype = 'cmid') {
        return \local_wsmanageactivities\external\add_quiz_questions::execute($quizid, $questions, $idtype);
    }
    
    public static function add_quiz_questions_returns() {
        return \local_wsmanageactivities\external\add_quiz_questions::execute_returns();
    }
    
    /**
     * Legacy wrapper for get_module_types function.
     * Delegates to the new class-based implementation.
     */
    public static function get_module_types_parameters() {
        return \local_wsmanageactivities\external\get_module_types::execute_parameters();
    }
    
    public static function get_module_types($courseid = 0, $filter = 'all') {
        return \local_wsmanageactivities\external\get_module_types::execute($courseid, $filter);
    }
    
    public static function get_module_types_returns() {
        return \local_wsmanageactivities\external\get_module_types::execute_returns();
    }

    /**
     * Get feedback data for the evaluation module.
     */
    public static function get_feedback_data_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    public static function get_feedback_data($cmid) {
        global $DB;
        $cm = get_coursemodule_from_id('feedback', $cmid, 0, false, MUST_EXIST);
        $feedback = $DB->get_record('feedback', ['id' => $cm->instance], '*', MUST_EXIST);
        $items = $DB->get_records('feedback_item', ['feedback' => $feedback->id, 'template' => 0], 'position ASC');
        
        $processed_items = [];

        foreach ($items as $item) {
            if (empty($item->typ) || $item->typ === 'label') continue;
            $options = [];
            if ($item->typ === 'multichoice') {
                $clean = str_replace(['r>>>>>', '<<<<<1'], '', $item->presentation);
                $parts = explode('|', $clean);
                foreach ($parts as $p) { $options[] = trim($p); }
            }
            $processed_items[] = [
                'id' => (int)$item->id,
                'name' => (string)$item->name,
                'type' => (string)$item->typ,
                'required' => (bool)$item->required,
                'position' => (int)$item->position,
                'options' => $options
            ];
        }

        return [
            'id' => (int)$feedback->id,
            'name' => (string)$feedback->name,
            'intro' => strip_tags($feedback->intro),
            'items' => $processed_items
        ];
    }

    public static function get_feedback_data_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Feedback ID'),
            'name' => new external_value(PARAM_TEXT, 'Feedback name'),
            'intro' => new external_value(PARAM_TEXT, 'Feedback introduction'),
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Item ID'),
                    'name' => new external_value(PARAM_TEXT, 'Question text'),
                    'type' => new external_value(PARAM_TEXT, 'Question type'),
                    'required' => new external_value(PARAM_BOOL, 'Is required'),
                    'position' => new external_value(PARAM_INT, 'Position'),
                    'options' => new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'Option text'), 'Options', VALUE_OPTIONAL
                    )
                ])
            )
        ]);
    }

    /**
     * Submit feedback responses.
     */
    public static function submit_feedback_responses_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'responses' => new external_multiple_structure(
                new external_single_structure([
                    'itemid' => new external_value(PARAM_INT, 'Item ID'),
                    'value' => new external_value(PARAM_RAW, 'Response value')
                ])
            )
        ]);
    }

    public static function submit_feedback_responses($cmid, $responses) {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        
        $cm = get_coursemodule_from_id('feedback', $cmid, 0, false, MUST_EXIST);
        $feedback = $DB->get_record('feedback', ['id' => $cm->instance], '*', MUST_EXIST);
        
        // 1. Create a completion record
        $completed = new \stdClass();
        $completed->feedback = $feedback->id;
        $completed->userid = $USER->id;
        $completed->timemodified = time();
        $completed->anonymous_response = 1; // Anonymous as per our XML
        $completedid = $DB->insert_record('feedback_completed', $completed);

        // 2. Insert values
        foreach ($responses as $resp) {
            $val = new \stdClass();
            $val->feedback = $feedback->id;
            $val->completed = $completedid;
            $val->item = $resp['itemid'];
            $val->value = $resp['value'];
            $DB->insert_record('feedback_value', $val);
        }

        // 3. Trigger activity completion
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $completion = new \completion_info($course);
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
        }

        return [
            'status' => 'success',
            'message' => 'Feedback submitted successfully'
        ];
    }

    public static function submit_feedback_responses_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status'),
            'message' => new external_value(PARAM_TEXT, 'Message')
        ]);
    }

    /**
     * Get average rating for a course based on feedback activities.
     */
    public static function get_course_rating_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    public static function get_course_rating($courseid) {
        global $DB;
        
        // 1. Find feedback modules in this course
        $feedbacks = $DB->get_records('feedback', ['course' => $courseid]);
        if (empty($feedbacks)) {
            return ['rating' => 0.0, 'count' => 0];
        }

        $total_rating = 0;
        $total_count = 0;

        foreach ($feedbacks as $feedback) {
            // 2. Get multichoice items (usually used for ratings)
            $items = $DB->get_records('feedback_item', ['feedback' => $feedback->id, 'typ' => 'multichoice']);
            if (empty($items)) continue;

            $itemids = array_keys($items);
            list($insql, $inparams) = $DB->get_in_or_equal($itemids);

            // 3. Get values linked to COMPLETED submissions for this feedback
            // Join with feedback_completed to ensure we only get submitted values
            $sql = "SELECT v.id, v.value 
                    FROM {feedback_value} v
                    JOIN {feedback_completed} c ON v.completed = c.id
                    WHERE c.feedback = ? AND v.item $insql";
            
            $params = array_merge([$feedback->id], $inparams);
            $values = $DB->get_records_sql($sql, $params);
            
            foreach ($values as $val) {
                // Extract first character (e.g., "5 (Excelente)" -> 5 or just "5")
                $clean_value = trim($val->value);
                if (empty($clean_value)) continue;
                
                $num = (int)substr($clean_value, 0, 1);
                if ($num >= 1 && $num <= 5) {
                    $total_rating += $num;
                    $total_count++;
                }
            }
        }

        $average = $total_count > 0 ? round($total_rating / $total_count, 1) : 0.0;

        return [
            'rating' => (float)$average,
            'count' => (int)$total_count
        ];
    }

    public static function get_course_rating_returns() {
        return new external_single_structure([
            'rating' => new external_value(PARAM_FLOAT, 'Average rating (0-5)'),
            'count' => new external_value(PARAM_INT, 'Total number of ratings')
        ]);
    }
}