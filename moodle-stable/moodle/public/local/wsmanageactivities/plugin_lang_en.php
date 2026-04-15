<?php
/**
 * English language strings.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Web Services Activity Management';
$string['privacy:metadata'] = 'The Web Services Activity Management plugin does not store any personal data.';

// Capabilities
$string['wsmanageactivities:createpage'] = 'Create page activities via web service';
$string['wsmanageactivities:createquiz'] = 'Create quiz activities via web service';
$string['wsmanageactivities:managequestions'] = 'Manage quiz questions via web service';
$string['wsmanageactivities:viewmodules'] = 'View module information via web service';

// Web service descriptions
$string['createpage'] = 'Create page activity';
$string['createquiz'] = 'Create quiz activity';
$string['addquizquestions'] = 'Add questions to quiz';
$string['getmoduletypes'] = 'Get available module types';

// Error messages
$string['error:invalidcourseid'] = 'Invalid course ID';
$string['error:invalidsection'] = 'Invalid section number';
$string['error:nopermission'] = 'You do not have permission to perform this action';
$string['error:modulenotfound'] = 'Module type not found';
$string['error:questioncreationfailed'] = 'Failed to create question';
$string['error:quiznotfound'] = 'Quiz not found';
$string['error:invalidquestiontype'] = 'Invalid question type';
$string['error:invalidmoduledata'] = 'Invalid module data provided';

// Success messages
$string['success:pagecreated'] = 'Page activity created successfully';
$string['success:quizcreated'] = 'Quiz activity created successfully';
$string['success:questionsadded'] = 'Questions added to quiz successfully';

// Question types
$string['questiontype:multichoice'] = 'Multiple choice';
$string['questiontype:shortanswer'] = 'Short answer';
$string['questiontype:essay'] = 'Essay';
$string['questiontype:truefalse'] = 'True/False';

// Configuration
$string['defaultquizattempts'] = 'Default quiz attempts';
$string['defaultquizattempts_desc'] = 'Default number of attempts allowed for new quizzes';
$string['defaultquizgrade'] = 'Default quiz grade';
$string['defaultquizgrade_desc'] = 'Default maximum grade for new quizzes';
$string['defaultquestionmark'] = 'Default question mark';
$string['defaultquestionmark_desc'] = 'Default marks for new questions';