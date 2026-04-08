<?php
/**
 * Page activity helper functions - FIXED VERSION.
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
 * Helper functions for page activities - FIXED.
 */
class page_helper {
    
    /**
     * Prepare moduleinfo object for page creation.
     */
    public static function prepare_moduleinfo($params, $course) {
        global $DB;
        
        $module = $DB->get_record('modules', array('name' => 'page', 'visible' => 1), '*', MUST_EXIST);
        
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->module = $module->id;
        $moduleinfo->modulename = 'page';
        $moduleinfo->instance = 0;
        $moduleinfo->section = $params['sectionnum'];
        
        // FIX: Direct string assignment for content
        $moduleinfo->name = $params['name'];
        $moduleinfo->content = $params['content'];
        $moduleinfo->contentformat = FORMAT_HTML;
        
        // Handle options safely
        $options = isset($params['options']) ? $params['options'] : array();
        $moduleinfo->intro = isset($options['intro']) ? $options['intro'] : '';
        $moduleinfo->introformat = isset($options['introformat']) ? $options['introformat'] : FORMAT_HTML;
        
        // Apply settings
        self::apply_page_options($moduleinfo, $options);
        self::apply_standard_module_fields($moduleinfo, $options);
        
        return $moduleinfo;
    }
    
    private static function apply_page_options($moduleinfo, $options) {
        $moduleinfo->display = isset($options['display']) ? (int)$options['display'] : 0;
        $moduleinfo->displayoptions = 'a:1:{s:12:"printheading";s:1:"1";}';
        $moduleinfo->legacyfiles = 0;
        $moduleinfo->legacyfileslast = null;
        $moduleinfo->revision = 1;
    }
    
    private static function apply_standard_module_fields($moduleinfo, $options) {
        $moduleinfo->visible = isset($options['visible']) ? ($options['visible'] ? 1 : 0) : 1;
        $moduleinfo->visibleoncoursepage = $moduleinfo->visible;
        $moduleinfo->groupmode = isset($options['groupmode']) ? (int)$options['groupmode'] : 0;
        $moduleinfo->groupingid = isset($options['groupingid']) ? (int)$options['groupingid'] : 0;
        
        // FIX: Handle availability properly
        $availability = isset($options['availability']) ? $options['availability'] : null;
        $moduleinfo->availability = (!empty($availability) && $availability !== 'null') ? $availability : null;
        
        // FIX: Handle completion array properly
        if (isset($options['completion']) && is_array($options['completion'])) {
            $completion = $options['completion'];
            $moduleinfo->completion = COMPLETION_TRACKING_MANUAL;
            $moduleinfo->completionview = !empty($completion['completionview']) ? 1 : 0;
            $moduleinfo->completionexpected = isset($completion['completionexpected']) ? (int)$completion['completionexpected'] : 0;
        } else {
            $moduleinfo->completion = COMPLETION_TRACKING_NONE;
            $moduleinfo->completionview = 0;
            $moduleinfo->completionexpected = 0;
        }
        
        // Required fields
        $moduleinfo->showdescription = 0;
        $moduleinfo->downloadcontent = 1;
        $moduleinfo->indent = 0;
        $moduleinfo->score = 0;
        $moduleinfo->added = time();
        $moduleinfo->cmidnumber = '';
        $moduleinfo->idnumber = '';
    }
    
    public static function format_page_result($cm) {
        global $CFG;
        
        return array(
            'id' => (int)$cm->id,
            'instance' => (int)$cm->instance,
            'name' => $cm->name,
            'url' => $CFG->wwwroot . '/mod/page/view.php?id=' . $cm->id,
            'section' => (int)$cm->sectionnum,
            'visible' => $cm->visible ? true : false,
            'success' => true,
            'warnings' => array()
        );
    }
    
    public static function validate_page_params($params) {
        $validated = array();
        
        if (empty($params['name'])) {
            throw new \moodle_exception('error:invalidmoduledata', 'local_wsmanageactivities', '', 'name');
        }
        $validated['name'] = clean_param($params['name'], PARAM_TEXT);
        
        if (empty($params['content'])) {
            throw new \moodle_exception('error:invalidmoduledata', 'local_wsmanageactivities', '', 'content');
        }
        $validated['content'] = validation::sanitize_html_content($params['content']);
        
        $validated['courseid'] = (int)$params['courseid'];
        $validated['sectionnum'] = (int)$params['sectionnum'];
        $validated['options'] = isset($params['options']) && is_array($params['options']) ? $params['options'] : array();
        
        return $validated;
    }
}
