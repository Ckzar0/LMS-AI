<?php
/**
 * Page activity helper functions.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_course;

/**
 * Helper functions for page activities.
 */
class page_helper {
    
    /**
     * Prepare moduleinfo object for page creation.
     *
     * @param array $params Input parameters
     * @param object $course Course object
     * @return stdClass Module info object
     */
    public static function prepare_moduleinfo($params, $course) {
        global $DB;
        
        // Get module info
        $module = $DB->get_record('modules', array('name' => 'page', 'visible' => 1), '*', MUST_EXIST);
        
        // Create base moduleinfo
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->module = $module->id;
        $moduleinfo->modulename = 'page';
        $moduleinfo->instance = 0; // Will be set by add_moduleinfo
        $moduleinfo->section = $params['sectionnum'];
        
        // Basic page fields
        $moduleinfo->name = $params['name'];
        $moduleinfo->content = $params['content'];
        $moduleinfo->contentformat = FORMAT_HTML;
        
        // Introduction (optional)
        $intro = isset($params['options']['intro']) ? $params['options']['intro'] : '';
        $introformat = isset($params['options']['introformat']) ? 
            $params['options']['introformat'] : FORMAT_HTML;
        
        $moduleinfo->intro = $intro;
        $moduleinfo->introformat = $introformat;
        
        // Apply additional options
        self::apply_page_options($moduleinfo, $params['options'] ?? array());
        
        // Standard module fields
        self::apply_standard_module_fields($moduleinfo, $params['options'] ?? array());
        
        return $moduleinfo;
    }
    
    /**
     * Apply page-specific options.
     *
     * @param stdClass $moduleinfo Module info object
     * @param array $options Page options
     */
    private static function apply_page_options($moduleinfo, $options) {
        // Page-specific settings
        $moduleinfo->display = isset($options['display']) ? $options['display'] : 0;
        $moduleinfo->displayoptions = isset($options['displayoptions']) ? 
            $options['displayoptions'] : 'a:1:{s:12:"printheading";s:1:"1";}';
        
        // Content options
        $moduleinfo->contentformat = FORMAT_HTML;
        $moduleinfo->legacyfiles = 0;
        $moduleinfo->legacyfileslast = null;
        $moduleinfo->revision = 1;
    }
    
    /**
     * Apply standard module fields.
     *
     * @param stdClass $moduleinfo Module info object
     * @param array $options Options array
     */
    private static function apply_standard_module_fields($moduleinfo, $options) {
        // Visibility
        $moduleinfo->visible = isset($options['visible']) ? 
            ($options['visible'] ? 1 : 0) : 1;
        $moduleinfo->visibleoncoursepage = $moduleinfo->visible;
        
        // Group settings
        $moduleinfo->groupmode = isset($options['groupmode']) ? 
            (int)$options['groupmode'] : 0;
        $moduleinfo->groupingid = isset($options['groupingid']) ? 
            (int)$options['groupingid'] : 0;
        
        // Availability
        $moduleinfo->availability = isset($options['availability']) ? 
            validation::validate_availability($options['availability']) : null;
        
        // Completion settings
        if (isset($options['completion'])) {
            $completion = $options['completion'];
            $moduleinfo->completion = COMPLETION_TRACKING_MANUAL;
            $moduleinfo->completionview = !empty($completion['completionview']) ? 1 : 0;
            $moduleinfo->completionexpected = isset($completion['completionexpected']) ? 
                (int)$completion['completionexpected'] : 0;
        } else {
            $moduleinfo->completion = COMPLETION_TRACKING_NONE;
            $moduleinfo->completionview = 0;
            $moduleinfo->completionexpected = 0;
        }
        
        // Other standard fields
        $moduleinfo->showdescription = 0;
        $moduleinfo->downloadcontent = 1;
        $moduleinfo->indent = 0;
        $moduleinfo->score = 0;
    }
    
    /**
     * Format page creation result.
     *
     * @param object $cm Course module object
     * @return array Formatted result
     */
    public static function format_page_result($cm) {
        global $CFG;
        
        $result = array(
            'id' => $cm->id,
            'instance' => $cm->instance,
            'name' => $cm->name,
            'url' => $CFG->wwwroot . '/mod/page/view.php?id=' . $cm->id,
            'section' => $cm->sectionnum,
            'visible' => $cm->visible ? true : false,
            'success' => true,
            'warnings' => array()
        );
        
        return $result;
    }
    
    /**
     * Get page defaults.
     *
     * @return array Default values
     */
    public static function get_page_defaults() {
        return array(
            'display' => 0,
            'displayoptions' => 'a:1:{s:12:"printheading";s:1:"1";}',
            'contentformat' => FORMAT_HTML,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'visible' => true,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => array(
                'completionview' => false,
                'completionexpected' => 0
            )
        );
    }
    
    /**
     * Validate page parameters.
     *
     * @param array $params Parameters to validate
     * @return array Validated parameters
     * @throws moodle_exception
     */
    public static function validate_page_params($params) {
        $validated = array();
        
        // Required fields
        if (empty($params['name'])) {
            throw new moodle_exception('error:invalidmoduledata', 'local_wsmanageactivities', '', 'name');
        }
        $validated['name'] = clean_param($params['name'], PARAM_TEXT);
        
        if (empty($params['content'])) {
            throw new moodle_exception('error:invalidmoduledata', 'local_wsmanageactivities', '', 'content');
        }
        $validated['content'] = validation::sanitize_html_content($params['content']);
        
        // Section and course
        $validated['courseid'] = (int)$params['courseid'];
        $validated['sectionnum'] = (int)$params['sectionnum'];
        
        // Options
        $validated['options'] = isset($params['options']) ? $params['options'] : array();
        
        return $validated;
    }
}