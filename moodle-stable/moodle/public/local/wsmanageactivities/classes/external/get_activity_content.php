<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;
use local_wsmanageactivities\local\image_processor;

/**
 * External API to get the processed content of an activity (Page, etc.)
 */
class get_activity_content extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    public static function execute($cmid) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        // 1. Get Course Module and Activity Data
        $cm = get_coursemodule_from_id('', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $content = '';
        $name = $cm->name;
        $type = $cm->modname;

        // 2. Fetch specific content based on module type
        if ($type === 'page') {
            $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
            $content = $page->content;
            
            // 3. Process Images and Placeholders
            // We need to identify the image folder. Usually it matches the course shortname or idnumber
            $course = $DB->get_record('course', ['id' => $cm->course]);
            $image_folder = $course->idnumber ?: $course->shortname;

            // Use our existing high-fidelity processor
            $content = image_processor::process_placeholders(
                $content, 
                $context->id, 
                'mod_page', 
                'content', 
                $page->id, 
                $image_folder,
                $course->id
            );
        } else if ($type === 'quiz') {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
            $content = $quiz->intro; // For now, just intro. Full quiz engine later.
        }

        return [
            'id' => $cm->id,
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'courseid' => $cm->course
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Activity name'),
            'type' => new external_value(PARAM_TEXT, 'Activity type (page, quiz, etc.)'),
            'content' => new external_value(PARAM_RAW, 'Processed HTML content'),
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }
}
