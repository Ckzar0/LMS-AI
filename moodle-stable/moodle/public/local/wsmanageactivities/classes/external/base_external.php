<?php
/**
 * Base external API class with common functionality.
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
use external_multiple_structure;
use external_warnings;
use context_course;
use moodle_exception;
use stdClass;

/**
 * Base class for external API functions.
 */
abstract class base_external extends external_api {
    
    /**
     * Validate course and section.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @return array Contains course object and section info
     * @throws moodle_exception
     */
    protected static function validate_course_and_section($courseid, $sectionnum) {
        global $DB;
        
        // Validate course exists
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        
        // Validate context
        $context = context_course::instance($courseid);
        self::validate_context($context);
        
        // Validate section exists or can be created
        $section = $DB->get_record('course_sections', 
            array('course' => $courseid, 'section' => $sectionnum));
        
        if (!$section && $sectionnum > 0) {
            // Try to create section if it doesn't exist
            require_once($CFG->dirroot . '/course/lib.php');
            course_create_sections_if_missing($course, $sectionnum);
            $section = $DB->get_record('course_sections', 
                array('course' => $courseid, 'section' => $sectionnum));
        }
        
        if (!$section) {
            throw new moodle_exception('error:invalidsection', 'local_wsmanageactivities');
        }
        
        return array('course' => $course, 'section' => $section, 'context' => $context);
    }
    
    /**
     * Validate module permissions.
     *
     * @param object $context Course context
     * @param string $modulename Module name
     * @param string $action Action to perform
     * @throws moodle_exception
     */
    protected static function validate_module_permissions($context, $modulename, $action = 'addinstance') {
        $capability = "mod/{$modulename}:{$action}";
        
        if (!has_capability($capability, $context)) {
            throw new moodle_exception('error:nopermission', 'local_wsmanageactivities');
        }
    }
    
    /**
     * Sanitize HTML content.
     *
     * @param string $content HTML content
     * @param array $options Cleaning options
     * @return string Sanitized content
     */
    protected static function sanitize_html_content($content, $options = array()) {
        // Default options for content cleaning
        $defaultoptions = array(
            'trusted' => false,
            'noclean' => false,
            'filter' => true
        );
        
        $options = array_merge($defaultoptions, $options);
        
        // Clean the content
        if (!$options['noclean']) {
            $content = clean_text($content, FORMAT_HTML, $options);
        }
        
        return $content;
    }
    
    /**
     * Get module info from database.
     *
     * @param string $modulename Module name
     * @return object Module info
     * @throws moodle_exception
     */
    protected static function get_module_info($modulename) {
        global $DB;
        
        $module = $DB->get_record('modules', array('name' => $modulename, 'visible' => 1));
        if (!$module) {
            throw new moodle_exception('error:modulenotfound', 'local_wsmanageactivities');
        }
        
        return $module;
    }
    
    /**
     * Format URL for activity.
     *
     * @param object $cm Course module
     * @return string Activity URL
     */
    protected static function format_activity_url($cm) {
        global $CFG;
        return $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cm->id;
    }
    
    /**
     * Create common warnings structure.
     *
     * @param string $item Item that caused warning
     * @param int $itemid Item ID
     * @param string $warningcode Warning code
     * @param string $message Warning message
     * @return array Warning structure
     */
    protected static function create_warning($item, $itemid, $warningcode, $message) {
        return array(
            'item' => $item,
            'itemid' => $itemid,
            'warningcode' => $warningcode,
            'message' => $message
        );
    }
    
    /**
     * Log action for debugging.
     *
     * @param string $action Action performed
     * @param array $data Additional data
     */
    protected static function log_action($action, $data = array()) {
        // Simple logging for debugging
        if (debugging()) {
            $logdata = array_merge(array('action' => $action, 'time' => time()), $data);
            error_log('local_wsmanageactivities: ' . json_encode($logdata));
        }
    }
}