<?php
/**
 * External API for creating page activities - FIXED VERSION.
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/modlib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_warnings;
use context_course;
use moodle_exception;
use local_wsmanageactivities\local\validation;
use local_wsmanageactivities\local\page_helper;

class create_page extends external_api {
    
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number'),
            'name' => new external_value(PARAM_TEXT, 'Page name'),
            'content' => new external_value(PARAM_RAW, 'Page content'),
            'options' => new external_single_structure([
                'intro' => new external_value(PARAM_RAW, 'Introduction', VALUE_OPTIONAL, ''),
                'introformat' => new external_value(PARAM_INT, 'Format', VALUE_OPTIONAL, FORMAT_HTML),
                'visible' => new external_value(PARAM_BOOL, 'Visible', VALUE_OPTIONAL, true),
                'groupmode' => new external_value(PARAM_INT, 'Group mode', VALUE_OPTIONAL, 0),
                'groupingid' => new external_value(PARAM_INT, 'Grouping ID', VALUE_OPTIONAL, 0),
                'availability' => new external_value(PARAM_RAW, 'Availability', VALUE_OPTIONAL, null),
                'completion' => new external_single_structure([
                    'completionview' => new external_value(PARAM_BOOL, 'View completion', VALUE_OPTIONAL, false),
                    'completionexpected' => new external_value(PARAM_INT, 'Expected date', VALUE_OPTIONAL, 0),
                ], 'Completion', VALUE_OPTIONAL),
            ], 'Options', VALUE_OPTIONAL)
        ]);
    }
    
    public static function execute($courseid, $sectionnum, $name, $content, $options = []) {
        global $CFG, $DB;
        
        // FIX: Ensure options is array
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionnum' => $sectionnum,
            'name' => $name,
            'content' => $content,
            'options' => is_array($options) ? $options : []
        ]);
        
        try {
            $coursedata = validation::validate_course_and_section($params['courseid'], $params['sectionnum']);
            $course = $coursedata['course'];
            $context = context_course::instance($params['courseid']);
            
            self::validate_context($context);
            validation::validate_module_permissions($context, 'page', 'addinstance');
            
            $validatedparams = page_helper::validate_page_params($params);
            $moduleinfo = page_helper::prepare_moduleinfo($validatedparams, $course);
            
            if (debugging()) {
                error_log('Creating page with data: ' . print_r($moduleinfo, true));
            }
            
            $cm = add_moduleinfo($moduleinfo, $course);
            
            return page_helper::format_page_result($cm);
            
        } catch (\Exception $e) {
            if (debugging()) {
                error_log('Page creation error: ' . $e->getMessage());
            }
            
            return [
                'id' => 0, 'instance' => 0, 'name' => $params['name'], 'url' => '',
                'section' => $params['sectionnum'], 'visible' => false, 'success' => false,
                'warnings' => [['item' => 'page', 'itemid' => 0, 'warningcode' => 'failed', 'message' => $e->getMessage()]]
            ];
        }
    }
    
    public static function execute_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Course module ID'),
            'instance' => new external_value(PARAM_INT, 'Page instance ID'),
            'name' => new external_value(PARAM_TEXT, 'Page name'),
            'url' => new external_value(PARAM_URL, 'Page URL'),
            'section' => new external_value(PARAM_INT, 'Section number'),
            'visible' => new external_value(PARAM_BOOL, 'Visibility'),
            'success' => new external_value(PARAM_BOOL, 'Success'),
            'warnings' => new external_warnings()
        ]);
    }
}
