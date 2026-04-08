<?php
/**
 * Validation helper class.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

use context_course;
use moodle_exception;

/**
 * Validation helper functions.
 */
class validation {
    
    /**
     * Validate course and section existence.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @return array Course and section objects
     * @throws moodle_exception
     */
    public static function validate_course_and_section($courseid, $sectionnum) {
        global $DB, $CFG;
        
        // Validate course
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        
        // Validate section
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
        
        return array('course' => $course, 'section' => $section);
    }
    
    /**
     * Validate module permissions.
     *
     * @param object $context Course context
     * @param string $modulename Module name
     * @param string $action Action to check
     * @throws moodle_exception
     */
    public static function validate_module_permissions($context, $modulename, $action = 'addinstance') {
        $capability = "mod/{$modulename}:{$action}";
        
        if (!has_capability($capability, $context)) {
            throw new moodle_exception('error:nopermission', 'local_wsmanageactivities');
        }
    }
    
    /**
     * Sanitize and validate HTML content.
     *
     * @param string $content HTML content
     * @param array $options Cleaning options
     * @return string Cleaned content
     */
    public static function sanitize_html_content($content, $options = array()) {
        $defaultoptions = array(
            'trusted' => false,
            'noclean' => false,
            'filter' => true
        );
        
        $options = array_merge($defaultoptions, $options);
        
        if (!$options['noclean']) {
            $content = clean_text($content, FORMAT_HTML, $options);
        }
        
        return $content;
    }
    
    /**
     * Validate quiz configuration.
     *
     * @param array $config Quiz configuration
     * @return array Validated configuration
     */
    public static function validate_quiz_config($config) {
        $validated = array();
        
        // Time limits
        $validated['timeopen'] = isset($config['timeopen']) ? 
            max(0, (int)$config['timeopen']) : 0;
        $validated['timeclose'] = isset($config['timeclose']) ? 
            max(0, (int)$config['timeclose']) : 0;
        $validated['timelimit'] = isset($config['timelimit']) ? 
            max(0, (int)$config['timelimit']) : 0;
        
        // Attempts
        $validated['attempts'] = isset($config['attempts']) ? 
            max(0, (int)$config['attempts']) : 0;
        
        // Grade
        $validated['grade'] = isset($config['grade']) ? 
            max(0, (float)$config['grade']) : 10.0;
        
        // Grading method
        $validmethods = array(1, 2, 3, 4); // QUIZ_GRADE constants
        $validated['grademethod'] = isset($config['grademethod']) && 
            in_array($config['grademethod'], $validmethods) ? 
            (int)$config['grademethod'] : 1;
        
        // Questions per page
        $validated['questionsperpage'] = isset($config['questionsperpage']) ? 
            max(1, (int)$config['questionsperpage']) : 1;
        
        // Boolean options
        $validated['shufflequestions'] = !empty($config['shufflequestions']);
        $validated['shuffleanswers'] = isset($config['shuffleanswers']) ? 
            !empty($config['shuffleanswers']) : true;
        
        // Text fields
        $validated['intro'] = isset($config['intro']) ? 
            self::sanitize_html_content($config['intro']) : '';
        $validated['introformat'] = isset($config['introformat']) ? 
            (int)$config['introformat'] : FORMAT_HTML;
        
        return $validated;
    }
    
    /**
     * Validate question data.
     *
     * @param array $question Question data
     * @return array Validated question
     * @throws moodle_exception
     */
    public static function validate_question_data($question) {
        $validtypes = array('multichoice', 'shortanswer', 'essay', 'truefalse');
        
        if (!isset($question['type']) || !in_array($question['type'], $validtypes)) {
            throw new moodle_exception('error:invalidquestiontype', 'local_wsmanageactivities');
        }
        
        $validated = array();
        $validated['type'] = $question['type'];
        $validated['name'] = isset($question['name']) ? 
            clean_param($question['name'], PARAM_TEXT) : '';
        $validated['questiontext'] = isset($question['questiontext']) ? 
            self::sanitize_html_content($question['questiontext']) : '';
        $validated['mark'] = isset($question['mark']) ? 
            max(0.1, (float)$question['mark']) : 1.0;
        
        // Parse question-specific data
        if (isset($question['questiondata'])) {
            $questiondata = is_string($question['questiondata']) ? 
                json_decode($question['questiondata'], true) : $question['questiondata'];
            $validated['questiondata'] = $questiondata ?: array();
        } else {
            $validated['questiondata'] = array();
        }
        
        return $validated;
    }
    
    /**
     * Validate module name.
     *
     * @param string $modulename Module name
     * @return object Module record
     * @throws moodle_exception
     */
    public static function validate_module_name($modulename) {
        global $DB;
        
        $module = $DB->get_record('modules', 
            array('name' => $modulename, 'visible' => 1));
        
        if (!$module) {
            throw new moodle_exception('error:modulenotfound', 'local_wsmanageactivities');
        }
        
        return $module;
    }
    
    /**
     * Validate availability conditions JSON.
     *
     * @param string $availability Availability JSON
     * @return string Valid availability JSON or null
     */
    public static function validate_availability($availability) {
        if (empty($availability)) {
            return null;
        }
        
        // Basic JSON validation
        $decoded = json_decode($availability);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null; // Invalid JSON, return null for no restrictions
        }
        
        return $availability;
    }
}