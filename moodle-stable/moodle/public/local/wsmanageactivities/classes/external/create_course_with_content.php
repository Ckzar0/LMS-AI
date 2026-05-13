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
        global $CFG, $DB;

        // Impedir que Warnings/Notices sujem o JSON
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

        // 1. Create Course
        $category = $DB->get_record('course_categories', [], '*', IGNORE_MULTIPLE);
        $course_data = new \stdClass();
        $course_data->fullname = $data['course_name'];
        $course_data->shortname = $data['course_shortname'] . '_' . time();
        $course_data->category = $category->id;
        $course_data->summary = $data['course_summary'];
        $course_data->format = 'topics';
        $course_data->newsitems = 0; // DESATIVAR ANÚNCIOS
        $course_data->numsections = 1;
        $course_data->enablecompletion = 1; // ATIVAR VISTOS VERDES NO CURSO
        
        $course = create_course($course_data);
        $courseid = $course->id;

        // 1.1 Remover Fórum de Anúncios se existir (para garantir 100% de conclusão)
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
            // Log silencioso se não conseguir apagar
        }

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
        
        // Robust way to get global folder
        $global_folder = !empty($data['image_folder']) ? $data['image_folder'] : 
                         (!empty($data['source_file']) ? $data['source_file'] : '');
        
        $current_prerequisites = []; // IDs das páginas antes do quiz
        $after_quiz_prerequisites = []; // IDs para atividades depois do quiz (apenas o quiz cmid)
        $has_passed_quiz = false;
        $created_activities = [];

        foreach ($data['activities'] as $index => $activity) {
            // Injetar pasta global se a atividade não tiver uma local (handling empty strings)
            if (empty($activity['image_folder']) && empty($activity['source_file'])) {
                $activity['image_folder'] = $global_folder;
            }

            if ($activity['type'] === 'page') {
                // Se já passámos pelo quiz, as próximas páginas dependem do quiz
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
                // O Quiz depende de todas as páginas criadas até agora
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

        // 4. Create Feedback/Evaluation if requested
        $evaluation_cmid = null;
        $evaluation_enabled = !empty($data['generate_evaluation']) || !empty($data['evaluation']);
        if ($evaluation_enabled) {
            $evaluation_cmid = ActivityCreator::create_feedback($courseid);
            if ($evaluation_cmid) {
                // Determine which activity should unlock the feedback (Quiz)
                $quiz_cmid = null;
                foreach ($created_activities as $act) {
                    if ($act['type'] === 'quiz') {
                        $quiz_cmid = $act['cmid'];
                        break;
                    }
                }
                
                if ($quiz_cmid) {
                    // Bloquear Avaliação até PASSAR no Quiz (require_grade = true)
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

        // 5. Create Certificate if requested
        $certificate_enabled = !empty($data['generate_certificate']) || !empty($data['certificate']);
        if ($certificate_enabled) {
            $cert_cmid = ActivityCreator::create_certificate($courseid, 'LMS-AI_Certificate');
            if ($cert_cmid) {
                // O Certificado deve ser a ÚLTIMA coisa. 
                // Se houver Avaliação, depende da Avaliação. Se não, depende do Quiz.
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
                    // Se for desbloqueado pelo Quiz, exigir nota. Se for pela Avaliação, apenas conclusão.
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
