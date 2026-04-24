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
 */
class create_course_with_content extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'coursedata' => new external_value(PARAM_RAW, 'JSON string containing course structure')
        ]);
    }

    public static function execute($coursedata) {
        global $DB, $CFG;

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

        // 1. Create Course
        $category = $DB->get_record('course_categories', [], '*', IGNORE_MULTIPLE);
        $course_data = new \stdClass();
        $course_data->fullname = $data['course_name'];
        $course_data->shortname = $data['course_shortname'] . '_' . time();
        $course_data->category = $category->id;
        $course_data->summary = $data['course_summary'];
        $course_data->format = 'topics';
        $course_data->numsections = 1;
        $course_data->enablecompletion = 1; // ATIVAR VISTOS VERDES NO CURSO
        
        $course = create_course($course_data);
        $courseid = $course->id;

        // 2. Process Question Banks
        $bank_mapping = [];
        if (!empty($data['question_banks'])) {
            foreach ($data['question_banks'] as $bank) {
                // Create category
                $cat = new \stdClass();
                $cat->name = $bank['name'];
                $cat->contextid = \context_course::instance($courseid)->id;
                $cat->info = "Automated bank for " . $data['course_name'];
                $catid = $DB->insert_record('question_categories', $cat);
                
                $bank_mapping[$bank['name']] = $catid;

                // Add questions
                foreach ($bank['questions'] as $q) {
                    QuestionCreator::create_question($catid, $cat->contextid, $q);
                }
            }
        }

        // 3. Process Activities
        $importer = new ActivityCreator($courseid);
        $global_folder = $data['image_folder'] ?? $data['source_file'] ?? '';
        
        $current_prerequisites = []; // IDs das páginas antes do quiz
        $after_quiz_prerequisites = []; // IDs para atividades depois do quiz (apenas o quiz cmid)
        $has_passed_quiz = false;

        foreach ($data['activities'] as $index => $activity) {
            // Injetar pasta global se a atividade não tiver uma local
            if (empty($activity['image_folder']) && empty($activity['source_file'])) {
                $activity['image_folder'] = $global_folder;
            }

            if ($activity['type'] === 'page') {
                // Se já passámos pelo quiz, as próximas páginas dependem do quiz
                $prereqs = $has_passed_quiz ? $after_quiz_prerequisites : [];
                $cmid = $importer->create_page($courseid, $activity, 1, $prereqs);
                
                if (!$has_passed_quiz) {
                    $current_prerequisites[] = $cmid;
                }
            } else if ($activity['type'] === 'quiz') {
                // O Quiz depende de todas as páginas criadas até agora
                $quiz_cmid = $importer->create_quiz($courseid, $activity, $data, 1, $current_prerequisites);
                
                // Atividades depois do quiz agora dependem do quiz cmid
                $after_quiz_prerequisites = [$quiz_cmid];
                $has_passed_quiz = true;
            }
        }


        return [
            'status' => 'success',
            'courseid' => $courseid,
            'message' => 'Course created successfully'
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success/error)'),
            'courseid' => new external_value(PARAM_INT, 'The ID of the created course'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message')
        ]);
    }
}
