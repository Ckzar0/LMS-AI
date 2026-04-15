<?php
/**
 * External API for getting available module types.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use context_course;
use context_system;
use moodle_exception;
use local_wsmanageactivities\local\quiz_helper;

/**
 * External API for getting available module types.
 */
class get_module_types extends external_api {
    
    /**
     * Describes the parameters for get_module_types.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID to check permissions', VALUE_OPTIONAL, 0),
            'filter' => new external_value(PARAM_ALPHA, 'Filter by type: "supported" for plugin supported types, "all" for all available', VALUE_OPTIONAL, 'all')
        ]);
    }
    
    /**
     * Get available module types and their information.
     *
     * @param int $courseid Course ID for permission checking
     * @param string $filter Filter type
     * @return array Module types information
     * @throws moodle_exception
     */
    public static function execute($courseid = 0, $filter = 'all') {
        global $DB, $CFG;
        
        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'filter' => $filter
        ]);
        
        try {
            // Validate context
            if ($params['courseid'] > 0) {
                $context = context_course::instance($params['courseid']);
                self::validate_context($context);
                $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
            } else {
                $context = context_system::instance();
                self::validate_context($context);
                $course = null;
            }
            
            // Get all available modules
            $modules = $DB->get_records('modules', array('visible' => 1), 'name ASC');
            
            $result = [];
            $supportedtypes = ['page', 'quiz']; // Types supported by this plugin
            $questiontypes = quiz_helper::get_question_types();
            
            foreach ($modules as $module) {
                // Apply filter
                if ($params['filter'] === 'supported' && !in_array($module->name, $supportedtypes)) {
                    continue;
                }
                
                // Check if user can add this module type
                $canadd = false;
                if ($course) {
                    $canadd = has_capability("mod/{$module->name}:addinstance", $context);
                }
                
                $moduleinfo = [
                    'name' => $module->name,
                    'displayname' => get_string('modulename', "mod_{$module->name}"),
                    'version' => $module->version,
                    'supported_by_plugin' => in_array($module->name, $supportedtypes),
                    'can_add' => $canadd,
                    'capabilities' => self::get_module_capabilities($module->name)
                ];
                
                // Add specific information for supported types
                if ($module->name === 'quiz') {
                    $moduleinfo['question_types'] = $questiontypes;
                    $moduleinfo['quiz_features'] = [
                        'time_limits' => true,
                        'multiple_attempts' => true,
                        'question_shuffling' => true,
                        'grading_methods' => [
                            1 => 'Highest grade',
                            2 => 'Average grade', 
                            3 => 'First attempt',
                            4 => 'Last attempt'
                        ]
                    ];
                } elseif ($module->name === 'page') {
                    $moduleinfo['page_features'] = [
                        'html_content' => true,
                        'file_embedding' => true,
                        'completion_tracking' => true,
                        'display_options' => true
                    ];
                }
                
                $result[] = $moduleinfo;
            }
            
            // Log the action
            self::log_action('module_types_retrieved', [
                'courseid' => $params['courseid'],
                'filter' => $params['filter'],
                'total_modules' => count($result)
            ]);
            
            return [
                'modules' => $result,
                'total_count' => count($result),
                'supported_count' => count(array_filter($result, function($m) { return $m['supported_by_plugin']; })),
                'plugin_info' => [
                    'name' => 'local_wsmanageactivities',
                    'version' => '1.0.0',
                    'supported_types' => $supportedtypes,
                    'capabilities' => [
                        'create_page' => 'Create page activities via API',
                        'create_quiz' => 'Create quiz activities via API',
                        'add_questions' => 'Add questions to quizzes via API',
                        'question_types' => array_keys($questiontypes)
                    ]
                ],
                'warnings' => []
            ];
            
        } catch (moodle_exception $e) {
            return [
                'modules' => [],
                'total_count' => 0,
                'supported_count' => 0,
                'plugin_info' => [],
                'warnings' => [
                    [
                        'item' => 'modules',
                        'itemid' => $params['courseid'],
                        'warningcode' => 'moduleretrievalfailed',
                        'message' => $e->getMessage()
                    ]
                ]
            ];
        }
    }
    
    /**
     * Get module capabilities.
     *
     * @param string $modulename Module name
     * @return array Module capabilities
     */
    private static function get_module_capabilities($modulename) {
        global $CFG;
        
        $capabilities = [];
        
        // Common capabilities
        $commoncaps = [
            'addinstance' => "mod/{$modulename}:addinstance",
            'view' => "mod/{$modulename}:view"
        ];
        
        // Module specific capabilities
        $specificcaps = [];
        switch ($modulename) {
            case 'quiz':
                $specificcaps = [
                    'manage' => "mod/quiz:manage",
                    'attempt' => "mod/quiz:attempt",
                    'reviewmyattempts' => "mod/quiz:reviewmyattempts"
                ];
                break;
            case 'page':
                // Page has minimal specific capabilities
                break;
            default:
                // Try to get common ones
                break;
        }
        
        return array_merge($commoncaps, $specificcaps);
    }
    
    /**
     * Describes the return structure for get_module_types.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'modules' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_ALPHA, 'Module name'),
                    'displayname' => new external_value(PARAM_TEXT, 'Module display name'),
                    'version' => new external_value(PARAM_INT, 'Module version'),
                    'supported_by_plugin' => new external_value(PARAM_BOOL, 'Whether this module is supported by the plugin'),
                    'can_add' => new external_value(PARAM_BOOL, 'Whether user can add this module'),
                    'capabilities' => new external_single_structure([], 'Module capabilities', VALUE_OPTIONAL),
                    'question_types' => new external_single_structure([], 'Available question types (quiz only)', VALUE_OPTIONAL),
                    'quiz_features' => new external_single_structure([], 'Quiz features (quiz only)', VALUE_OPTIONAL),
                    'page_features' => new external_single_structure([], 'Page features (page only)', VALUE_OPTIONAL),
                ])
            ),
            'total_count' => new external_value(PARAM_INT, 'Total number of modules'),
            'supported_count' => new external_value(PARAM_INT, 'Number of modules supported by plugin'),
            'plugin_info' => new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Plugin name', VALUE_OPTIONAL),
                'version' => new external_value(PARAM_TEXT, 'Plugin version', VALUE_OPTIONAL),
                'supported_types' => new external_multiple_structure(
                    new external_value(PARAM_ALPHA, 'Supported module type'), VALUE_OPTIONAL
                ),
                'capabilities' => new external_single_structure([], 'Plugin capabilities', VALUE_OPTIONAL),
            ], 'Plugin information', VALUE_OPTIONAL),
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