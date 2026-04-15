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
}