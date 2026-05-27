<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use local_wsmanageactivities\importer\ActivityCreator;
use local_wsmanageactivities\importer\QuestionCreator;

/**
 * External function to create a complete course with sections, pages and quizzes in one call.
 * This class acts as the main entry point for the Next.js FrontEnd to inject AI-generated courses into Moodle.
 */
class create_course_with_content extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'coursedata' => new external_value(PARAM_RAW, 'JSON string containing course structure')
        ]);
    }

    public static function execute($coursedata) {
        global $CFG, $DB;

        // Output Buffering & Error Suppression
        // Prevents PHP Warnings/Notices from corrupting the JSON response sent back to the Next.js frontend.
        @error_reporting(0);
        @ini_set('display_errors', 0);
        while (ob_get_level()) ob_end_clean();

        $params = self::validate_parameters(self::execute_parameters(), [
            'coursedata' => $coursedata
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/course:create', $context);

        $data = json_decode($params['coursedata'], true);
        if (!$data) {
            throw new \invalid_parameter_exception('Invalid JSON data');
        }

        // Feature Flags Logging
        // Records whether the frontend requested Certification and Evaluation generation.
        $eval_flag = isset($data['generate_evaluation']) ? ($data['generate_evaluation'] ? 'true' : 'false') : 'not set';
        $cert_flag = isset($data['generate_certificate']) ? ($data['generate_certificate'] ? 'true' : 'false') : 'not set';

        // Phase 1: Core Course Creation
        // Creates the Moodle course container and enforces completion tracking requirements.
        $category = $DB->get_record('course_categories', [], '*', IGNORE_MULTIPLE);
        $course_data = new \stdClass();
        $course_data->fullname = $data['course_name'];
        $course_data->shortname = $data['course_shortname'] . '_' . time();
        $course_data->category = $category->id;
        $course_data->summary = $data['course_summary'];
        $course_data->format = 'topics';
        $course_data->newsitems = 0; // Disable announcements to keep the course linear
        $course_data->numsections = 1;
        $course_data->enablecompletion = 1; // Mandatory for Quiz tracking
        
        $course = create_course($course_data);
        $courseid = $course->id;

        // Phase 1.1: Cleanup Defaults
        // Automatically removes the default 'Announcements' forum.
        // If left intact, Moodle expects the user to view it to reach 100% completion, which breaks our flow.
        try {
            $forum_module = $DB->get_record('modules', ['name' => 'forum']);
            if ($forum_module) {
                $announcements = $DB->get_records('course_modules', ['course' => $courseid, 'module' => $forum_module->id]);
                foreach ($announcements as $ann) {
                    $forum_instance = $DB->get_record('forum', ['id' => $ann->instance]);
                    if ($forum_instance && ($forum_instance->type === 'news' || strpos(strtolower($forum_instance->name), 'anúncios') !== false || strpos(strtolower($forum_instance->name), 'announcements') !== false)) {
                        \course_delete_module($ann->id);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silent catch to prevent breaking the transaction if deletion fails
        }

        // Phase 2: Question Bank Initialization
        // Iterates through AI-generated banks and registers them securely in Moodle's question engine.
        $bank_mapping = [];
        if (!empty($data['question_banks'])) {
            foreach ($data['question_banks'] as $bank) {
                $course_context = \context_course::instance($courseid);
                
                $existing = $DB->get_record('question_categories', [
                    'contextid' => $course_context->id,
                    'name' => $bank['name']
                ]);

                if ($existing) {
                    $catid = $existing->id;
                } else {
                    // Unique Category Creation
                    // Moodle strictly requires a unique 'stamp' string for each question category to prevent corruption.
                    $cat = new \stdClass();
                    $cat->name = $bank['name'];
                    $cat->contextid = $course_context->id;
                    $cat->info = "Automated bank for " . $data['course_name'];
                    $cat->stamp = make_unique_id_code(); 
                    $cat->parent = 0;
                    $catid = $DB->insert_record('question_categories', $cat);
                }
                
                $bank_mapping[$bank['name']] = $catid;

                foreach ($bank['questions'] as $q) {
                    QuestionCreator::create_question($catid, $course_context->id, $q);
                }
            }
        }

        // Phase 3: Activity Pipeline (Pages, Quizzes, Certificates)
        // Orchestrates the chronological creation of course elements, managing strict access restrictions
        // (e.g., locking the Quiz until all pages are read, locking the Certificate until the Quiz is passed).
        $importer = new ActivityCreator($courseid);
        
        $global_folder = !empty($data['image_folder']) ? $data['image_folder'] : 
                         (!empty($data['source_file']) ? $data['source_file'] : '');
        
        $current_prerequisites = []; 
        $after_quiz_prerequisites = []; 
        $has_passed_quiz = false;
        $created_activities = [];

        foreach ($data['activities'] as $index => $activity) {
            if (empty($activity['image_folder']) && empty($activity['source_file'])) {
                $activity['image_folder'] = $global_folder;
            }

            if ($activity['type'] === 'page') {
                $prereqs = $has_passed_quiz ? $after_quiz_prerequisites : [];
                $res = $importer->create_page($courseid, $activity, 1, $prereqs);
                $cmid = $res['cmid'];
                
                $created_activities[] = [
                    'cmid' => $cmid,
                    'name' => $activity['name'],
                    'type' => 'page',
                    'content' => $res['content'],
                    'url' => $CFG->wwwroot . '/mod/page/view.php?id=' . $cmid
                ];


                if (!$has_passed_quiz) {
                    $current_prerequisites[] = $cmid;
                }
            } else if ($activity['type'] === 'quiz') {
                // Dependency Injection for Quizzes
                // Locks the quiz module until the user has successfully viewed all preceding HTML pages.
                $quiz_cmid = $importer->create_quiz($courseid, $activity, $data, 1, $current_prerequisites);
                
                $created_activities[] = [
                    'cmid' => $quiz_cmid,
                    'name' => $activity['name'],
                    'type' => 'quiz',
                    'content' => $activity['intro'] ?? '',
                    'url' => $CFG->wwwroot . '/mod/quiz/view.php?id=' . $quiz_cmid
                ];

                // Atividades depois do quiz agora dependem do quiz cmid
                $after_quiz_prerequisites = [$quiz_cmid];
                $has_passed_quiz = true;
            }
        }

        // Phase 4: Feedback/Evaluation Module
        // Triggered only if requested by the FrontEnd. Always locks behind the successful completion of the Quiz.
        $evaluation_cmid = null;
        $evaluation_enabled = !empty($data['generate_evaluation']) || !empty($data['evaluation']);
        if ($evaluation_enabled) {
            $evaluation_cmid = ActivityCreator::create_feedback($courseid);
            if ($evaluation_cmid) {
                // Discover the Quiz CMID to set as a prerequisite
                $quiz_cmid = null;
                foreach ($created_activities as $act) {
                    if ($act['type'] === 'quiz') {
                        $quiz_cmid = $act['cmid'];
                        break;
                    }
                }
                
                if ($quiz_cmid) {
                    // Strict grading requirement: User must PASS the quiz (100% of required grade) to unlock evaluation.
                    ActivityCreator::add_completion_restriction($evaluation_cmid, $quiz_cmid, true, true);
                }

                $created_activities[] = [
                    'cmid' => $evaluation_cmid,
                    'name' => 'Avaliação da Formação',
                    'type' => 'feedback',
                    'content' => 'A sua opinião é fundamental.',
                    'url' => $CFG->wwwroot . '/mod/feedback/view.php?id=' . $evaluation_cmid
                ];
            }
        }

        // Phase 5: Custom Certificate Generation
        // Triggered only if requested. It locates the Master Template and clones it privately for this course.
        // It locks behind either the Evaluation (if it exists) or the Quiz.
        $certificate_enabled = !empty($data['generate_certificate']) || !empty($data['certificate']);
        if ($certificate_enabled) {
            $cert_cmid = ActivityCreator::create_certificate($courseid, 'LMS-AI_Certificate');
            if ($cert_cmid) {
                $unlock_cmid = null;
                if ($evaluation_cmid) {
                    $unlock_cmid = $evaluation_cmid;
                } else {
                    foreach ($created_activities as $act) {
                        if ($act['type'] === 'quiz') {
                            $unlock_cmid = $act['cmid'];
                            break;
                        }
                    }
                }

                if ($unlock_cmid) {
                    // If unlocking via Quiz, a passing grade is required. If via Evaluation, just completion is enough.
                    $is_quiz = $evaluation_cmid ? false : true;
                    ActivityCreator::add_completion_restriction($cert_cmid, $unlock_cmid, true, $is_quiz);
                }

                $created_activities[] = [
                    'cmid' => $cert_cmid,
                    'name' => 'Certificado de Conclusão',
                    'type' => 'customcert', 
                    'content' => 'Descarregue aqui o seu certificado.',
                    'url' => $CFG->wwwroot . '/mod/customcert/view.php?id=' . $cert_cmid
                ];
            }
        }

        return [
            'status' => 'success',
            'courseid' => $courseid,
            'message' => 'Course created successfully',
            'activities' => $created_activities
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success/error)'),
            'courseid' => new external_value(PARAM_INT, 'The ID of the created course'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message'),
            'activities' => new external_multiple_structure(
                new external_single_structure([
                    'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                    'name' => new external_value(PARAM_TEXT, 'Activity name'),
                    'type' => new external_value(PARAM_ALPHA, 'Activity type (page/quiz/feedback)'),
                    'content' => new external_value(PARAM_RAW, 'Processed HTML content'),
                    'url' => new external_value(PARAM_URL, 'Absolute URL to the activity')
                ]), 'List of created activities', VALUE_OPTIONAL
            )
        ]);
    }
}
