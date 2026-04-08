<?php
/**
 * External API for creating page activities.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

/**
 * External API for creating page activities.
 */
class create_page extends external_api {
    
    /**
     * Describes the parameters for create_page.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number where to add the page'),
            'name' => new external_value(PARAM_TEXT, 'Page name'),
            'content' => new external_value(PARAM_RAW, 'Page content (HTML)'),
            'options' => new external_single_structure([
                'intro' => new external_value(PARAM_RAW, 'Introduction text', VALUE_OPTIONAL, ''),
                'introformat' => new external_value(PARAM_INT, 'Introduction format', VALUE_OPTIONAL, FORMAT_HTML),
                'visible' => new external_value(PARAM_BOOL, 'Visible to students', VALUE_OPTIONAL, true),
                'groupmode' => new external_value(PARAM_INT, 'Group mode (0=No groups, 1=Separate groups, 2=Visible groups)', VALUE_OPTIONAL, 0),
                'groupingid' => new external_value(PARAM_INT, 'Grouping ID', VALUE_OPTIONAL, 0),
                'availability' => new external_value(PARAM_RAW, 'Availability conditions (JSON)', VALUE_OPTIONAL, null),
                'completion' => new external_single_structure([
                    'completionview' => new external_value(PARAM_BOOL, 'Completion requires view', VALUE_OPTIONAL, false),
                    'completionexpected' => new external_value(PARAM_INT, 'Expected completion date (timestamp)', VALUE_OPTIONAL, 0),
                ], 'Completion settings', VALUE_OPTIONAL),
                'display' => new external_value(PARAM_INT, 'Display mode', VALUE_OPTIONAL, 0),
                'displayoptions' => new external_value(PARAM_RAW, 'Display options', VALUE_OPTIONAL, null),
            ], 'Additional page options', VALUE_OPTIONAL)
        ]);
    }
    
    /**
     * Create a page activity.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param string $name Page name
     * @param string $content Page content
     * @param array $options Additional options
     * @return array Page creation result
     * @throws moodle_exception
     */
    public static function execute($courseid, $sectionnum, $name, $content, $options = []) {
        global $CFG, $DB;
        
        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionnum' => $sectionnum,
            'name' => $name,
            'content' => $content,
            'options' => $options
        ]);
        
        try {
            // Validate course and section
            $coursedata = validation::validate_course_and_section($params['courseid'], $params['sectionnum']);
            $course = $coursedata['course'];
            $context = context_course::instance($params['courseid']);
            
            // Validate context and permissions
            self::validate_context($context);
            validation::validate_module_permissions($context, 'page', 'addinstance');
            
            // Validate page parameters
            $validatedparams = page_helper::validate_page_params($params);
            
            // Prepare module info
            $moduleinfo = page_helper::prepare_moduleinfo($validatedparams, $course);
            
            // Create the page activity
            $cm = add_moduleinfo($moduleinfo, $course);
            
            // Log the action
            self::log_action('page_created', [
                'courseid' => $params['courseid'],
                'sectionnum' => $params['sectionnum'],
                'pageid' => $cm->id,
                'name' => $params['name']
            ]);
            
            // Format and return result
            return page_helper::format_page_result($cm);
            
        } catch (moodle_exception $e) {
            // Return error in consistent format
            return [
                'id' => 0,
                'instance' => 0,
                'name' => $params['name'],
                'url' => '',
                'section' => $params['sectionnum'],
                'visible' => false,
                'success' => false,
                'warnings' => [
                    [
                        'item' => 'page',
                        'itemid' => 0,
                        'warningcode' => 'pagecreationfailed',
                        'message' => $e->getMessage()
                    ]
                ]
            ];
        }
    }
    
    /**
     * Describes the return structure for create_page.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Course module ID'),
            'instance' => new external_value(PARAM_INT, 'Page instance ID'),
            'name' => new external_value(PARAM_TEXT, 'Page name'),
            'url' => new external_value(PARAM_URL, 'Page URL'),
            'section' => new external_value(PARAM_INT, 'Section number'),
            'visible' => new external_value(PARAM_BOOL, 'Visibility status'),
            'success' => new external_value(PARAM_BOOL, 'Operation success status'),
            'warnings' => new external_warnings()
        ]);
    }
    
    /**
     * Log action for debugging.
     *
     * @param string $action Action performed
     * @param array $data Additional data
     */
    private static function log_action($action, $data = []) {
        if (debugging()) {
            $logdata = array_merge(['action' => $action, 'time' => time()], $data);
            error_log('local_wsmanageactivities: ' . json_encode($logdata));
        }
    }
}