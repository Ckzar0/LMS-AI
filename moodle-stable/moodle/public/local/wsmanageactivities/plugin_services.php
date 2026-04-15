<?php
/**
 * Web service external functions and service definitions.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_wsmanageactivities_create_page' => array(
        'classname'   => 'local_wsmanageactivities\external\create_page',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Create a new page activity in a course section',
        'type'        => 'write',
        'capabilities'=> 'mod/page:addinstance',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'        => true,
        'loginrequired' => true,
    ),
    
    'local_wsmanageactivities_create_quiz' => array(
        'classname'   => 'local_wsmanageactivities\external\create_quiz',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Create a new quiz activity with basic configuration',
        'type'        => 'write',
        'capabilities'=> 'mod/quiz:addinstance',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'        => true,
        'loginrequired' => true,
    ),
    
    'local_wsmanageactivities_add_quiz_questions' => array(
        'classname'   => 'local_wsmanageactivities\external\add_quiz_questions',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Add questions to an existing quiz',
        'type'        => 'write',
        'capabilities'=> 'mod/quiz:manage',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'        => true,
        'loginrequired' => true,
    ),
    
    'local_wsmanageactivities_get_module_types' => array(
        'classname'   => 'local_wsmanageactivities\external\get_module_types',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get available module types and their capabilities',
        'type'        => 'read',
        'capabilities'=> '',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'        => true,
        'loginrequired' => true,
    ),

    'local_wsmanageactivities_create_course_with_content' => array(
        'classname'   => 'local_wsmanageactivities\external\create_course_with_content',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Create a complete course with contents from JSON',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:create',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'        => true,
        'loginrequired' => true,
    ),

    'local_wsmanageactivities_process_pdf' => array(
        'classname'   => 'local_wsmanageactivities\external\process_pdf',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Upload a PDF and extract images for course generation',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:create',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'        => true,
        'loginrequired' => true,
    ),
);

$services = array(
    'Activity Management Service' => array(
        'functions' => array(
            'local_wsmanageactivities_create_page',
            'local_wsmanageactivities_create_quiz', 
            'local_wsmanageactivities_add_quiz_questions',
            'local_wsmanageactivities_get_module_types',
            'local_wsmanageactivities_create_course_with_content',
            'local_wsmanageactivities_process_pdf'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'wsmanageactivities',
        'downloadfiles' => 1,
        'uploadfiles' => 1
    )
);