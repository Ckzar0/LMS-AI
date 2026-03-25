<?php
/**
 * Configure Sequential Workflow - API
 * VERSÃO 30.0 - ABORDAGEM FINAL COM 'course_update_module'
 */
namespace local_wsmanageactivities\external;

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use stdClass;

class configure_sequential_workflow extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID do Curso'),
            'options' => new external_single_structure([
                'quiz_pass_grade' => new external_value(PARAM_FLOAT, 'Nota mínima', VALUE_DEFAULT, 70.0),
                'strict_sequential' => new external_value(PARAM_BOOL, 'Sequencial', VALUE_DEFAULT, true),
                'hide_unavailable' => new external_value(PARAM_BOOL, 'Ocultar', VALUE_DEFAULT, false)
            ], 'Opções', VALUE_DEFAULT, [])
        ]);
    }

    public static function execute($courseid, $options = []) {
        global $CFG, $DB;
        
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/lib/gradelib.php');

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid, 'options' => $options]);
        
        $course = \get_course($params['courseid']);
        $course_context = \context_course::instance($course->id);
        self::validate_context($course_context);
        require_capability('moodle/course:update', $course_context);
            
        if ($course->enablecompletion != 1) {
            $update = new stdClass();
            $update->id = $course->id;
            $update->enablecompletion = 1;
            $DB->update_record('course', $update);
            \rebuild_course_cache($course->id, true);
        }
        
        $modinfo = \get_fast_modinfo($course);
        $activities = array_values($modinfo->get_cms());
        if (empty($activities)) {
            return ['success' => true, 'message' => 'Nenhuma atividade para configurar.', 'activities_configured' => 0];
        }

        $activities_configured = 0;
        $previous_cm = null;

        foreach ($activities as $cm) {
            $update_data = new stdClass();
            $update_data->id = $cm->id;

            // ===== ABORDAGEM FINAL: Configurar tudo via course_update_module =====
            $update_data->completion = COMPLETION_TRACKING_AUTOMATIC;

            switch ($cm->modname) {
                case 'page':
                case 'resource':
                    // A API course_update_module ainda aceita 'completionview' para definir o critério.
                    $update_data->completionview = 1;
                    break;
                case 'quiz':
                    $update_data->completionusegrade = 1; // Requer nota
                    $update_data->completionpassgrade = 1; // Requer nota para passar
                    self::set_quiz_pass_grade($cm, $params['options']['quiz_pass_grade']);
                    break;
                default:
                    $update_data->completionview = 1;
                    break;
            }
            
            if (($params['options']['strict_sequential'] ?? true) && !is_null($previous_cm)) {
                $availability_conditions = array(
                    'op' => '&',
                    'c' => array(array('type' => 'completion', 'cm' => $previous_cm->id, 'showc' => !$params['options']['hide_unavailable'])),
                    'showc' => null
                );
                $update_data->availability = json_encode($availability_conditions);
            }
            
            \course_update_module($cm, $update_data);
            // =====================================================================

            $previous_cm = $cm;
            $activities_configured++;
        }

        \rebuild_course_cache($course->id, true);

        return ['success' => true, 'message' => 'Workflow configurado com sucesso.', 'activities_configured' => $activities_configured];
    }
    
    private static function set_quiz_pass_grade($cm, $pass_grade_percent) {
        global $DB;
        $quiz = $DB->get_record('quiz', array('id' => $cm->instance), 'id, grade', \MUST_EXIST);
        if ($quiz->grade <= 0) { return; }
        
        $conditions = array('itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id);
        $grade_item = \grade_item::fetch($conditions);

        if ($grade_item) {
            $pass_grade_value = ($pass_grade_percent / 100.0) * $grade_item->grademax;
            $updated_item = new stdClass();
            $updated_item->id = $grade_item->id;
            $updated_item->gradepass = $pass_grade_value;
            \grade_update_item($updated_item);
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Indica se a operação foi bem-sucedida.'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem de estado.'),
            'activities_configured' => new external_value(PARAM_INT, 'Número de atividades configuradas no workflow.')
        ]);
    }
}