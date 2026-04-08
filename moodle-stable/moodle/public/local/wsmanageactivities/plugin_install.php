<?php
/**
 * Post installation and migration code.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to execute after plugin installation.
 */
function xmldb_local_wsmanageactivities_install() {
    global $DB;
    
    // Enable the plugin's web services by default
    $service = $DB->get_record('external_services', array('shortname' => 'wsmanageactivities'));
    if ($service) {
        $service->enabled = 1;
        $DB->update_record('external_services', $service);
    }
    
    // Add the functions to the mobile service
    $mobileservice = $DB->get_record('external_services', array('shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE));
    if ($mobileservice) {
        $functions = array(
            'local_wsmanageactivities_create_page',
            'local_wsmanageactivities_create_quiz',
            'local_wsmanageactivities_add_quiz_questions', 
            'local_wsmanageactivities_get_module_types'
        );
        
        foreach ($functions as $functionname) {
            $function = $DB->get_record('external_functions', array('name' => $functionname));
            if ($function) {
                $servicefunction = new stdClass();
                $servicefunction->externalserviceid = $mobileservice->id;
                $servicefunction->functionname = $functionname;
                
                // Check if already exists
                if (!$DB->record_exists('external_services_functions', 
                    array('externalserviceid' => $mobileservice->id, 'functionname' => $functionname))) {
                    $DB->insert_record('external_services_functions', $servicefunction);
                }
            }
        }
    }
    
    return true;
}