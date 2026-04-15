<?php
/**
 * Sequential Workflow Native - Configuração Automática
 * 
 * FUNCIONALIDADES v1.0:
 * - Configuração automática de fluxo sequencial
 * - Usa APENAS funcionalidades nativas do Moodle
 * - Páginas: completion on view + restricted access
 * - Quizzes: completion on grade + restricted access
 * - Ordem automática baseada na sequência das seções
 * 
 * @package    local_wsmanageactivities
 * @subpackage sequential_workflow
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    1.0 - 17 de Julho de 2025, 19:00
 */

namespace local_wsmanageactivities\external;

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use context_course;
use completion_info;
use moodle_exception;
use stdClass;
use Exception;

class sequential_workflow_native extends external_api {

    /**
     * Parâmetros para configurar workflow sequencial
     */
    public static function configure_sequential_workflow_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'options' => new external_single_structure([
                'quiz_pass_grade' => new external_value(PARAM_FLOAT, 'Minimum grade to pass quiz (0-100)', VALUE_DEFAULT, 70.0),
                'enable_completion_tracking' => new external_value(PARAM_BOOL, 'Enable completion tracking for course', VALUE_DEFAULT, true),
                'strict_sequential' => new external_value(PARAM_BOOL, 'Strict sequential order (hide if not available)', VALUE_DEFAULT, true)
            ], 'Configuration options', VALUE_DEFAULT, [])
        ]);
    }

    /**
     * Configurar workflow sequencial automático
     */
    public static function configure_sequential_workflow($courseid, $options = []) {
        global $DB, $CFG;

        // Validar parâmetros
        $params = self::validate_parameters(self::configure_sequential_workflow_parameters(), [
            'courseid' => $courseid,
            'options' => $options
        ]);

        try {
            // Verificar curso e permissões
            $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
            $context = context_course::instance($course->id);
            self::validate_context($context);
            require_capability('moodle/course:manageactivities', $context);

            $results = [
                'course_id' => $course->id,
                'course_name' => $course->fullname,
                'completion_enabled' => false,
                'activities_configured' => 0,
                'sequential_links_created' => 0,
                'activities_processed' => [],
                'errors' => []
            ];

            // Passo 1: Ativar completion no curso se necessário
            if ($params['options']['enable_completion_tracking']) {
                self::enable_course_completion($course);
                $results['completion_enabled'] = true;
            }

            // Verificar se completion está ativo
            if (!$CFG->enablecompletion || !$course->enablecompletion) {
                throw new moodle_exception('Completion tracking não está ativo para este curso');
            }

            // Passo 2: Obter todas as atividades em ordem sequencial
            $activities = self::get_course_activities_in_order($course->id);
            
            if (empty($activities)) {
                return array_merge($results, [
                    'success' => true,
                    'message' => 'Nenhuma atividade encontrada no curso'
                ]);
            }

            // Passo 3: Configurar completion para cada atividade
            $previous_activity = null;
            $configured_count = 0;
            $links_created = 0;

            foreach ($activities as $activity) {
                try {
                    // Configurar completion para atividade atual
                    $completion_result = self::configure_activity_completion($activity, $params['options']);
                    
                    if ($completion_result['success']) {
                        $configured_count++;
                        
                        $activity_info = [
                            'cmid' => $activity->id,
                            'name' => $activity->name,
                            'module_type' => $activity->modname,
                            'completion_configured' => true,
                            'completion_type' => $completion_result['completion_type']
                        ];

                        // Se não é a primeira atividade, configurar restricted access
                        if ($previous_activity !== null) {
                            $restriction_result = self::configure_activity_restriction(
                                $activity, 
                                $previous_activity, 
                                $params['options']
                            );
                            
                            if ($restriction_result['success']) {
                                $links_created++;
                                $activity_info['restricted_access'] = true;
                                $activity_info['depends_on'] = $previous_activity->id;
                            } else {
                                $activity_info['restricted_access'] = false;
                                $activity_info['restriction_error'] = $restriction_result['error'];
                            }
                        } else {
                            $activity_info['restricted_access'] = false;
                            $activity_info['depends_on'] = null;
                        }

                        $results['activities_processed'][] = $activity_info;
                        $previous_activity = $activity;
                    }

                } catch (Exception $e) {
                    $results['errors'][] = [
                        'cmid' => $activity->id,
                        'name' => $activity->name,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $results['activities_configured'] = $configured_count;
            $results['sequential_links_created'] = $links_created;

            // Passo 4: Rebuild course cache
            rebuild_course_cache($course->id, true);

            return array_merge($results, [
                'success' => true,
                'message' => "Workflow sequencial configurado: {$configured_count} atividades, {$links_created} ligações sequenciais"
            ]);

        } catch (Exception $e) {
            return [
                'success' => false,
                'course_id' => $courseid,
                'course_name' => '',
                'completion_enabled' => false,
                'activities_configured' => 0,
                'sequential_links_created' => 0,
                'activities_processed' => [],
                'errors' => [['general' => $e->getMessage()]],
                'message' => 'Erro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retorno da função configure_sequential_workflow
     */
    public static function configure_sequential_workflow_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'course_id' => new external_value(PARAM_INT, 'Course ID'),
            'course_name' => new external_value(PARAM_TEXT, 'Course name'),
            'completion_enabled' => new external_value(PARAM_BOOL, 'Completion tracking enabled'),
            'activities_configured' => new external_value(PARAM_INT, 'Number of activities configured'),
            'sequential_links_created' => new external_value(PARAM_INT, 'Number of sequential links created'),
            'activities_processed' => new external_multiple_structure(
                new external_single_structure([
                    'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                    'name' => new external_value(PARAM_TEXT, 'Activity name'),
                    'module_type' => new external_value(PARAM_TEXT, 'Module type (page, quiz, etc.)'),
                    'completion_configured' => new external_value(PARAM_BOOL, 'Completion configured'),
                    'completion_type' => new external_value(PARAM_TEXT, 'Type of completion (view, grade)', VALUE_OPTIONAL),
                    'restricted_access' => new external_value(PARAM_BOOL, 'Restricted access configured'),
                    'depends_on' => new external_value(PARAM_INT, 'CMID of prerequisite activity', VALUE_OPTIONAL),
                    'restriction_error' => new external_value(PARAM_TEXT, 'Error in restriction setup', VALUE_OPTIONAL)
                ])
            ),
            'errors' => new external_multiple_structure(
                new external_single_structure([
                    'cmid' => new external_value(PARAM_INT, 'Course module ID', VALUE_OPTIONAL),
                    'name' => new external_value(PARAM_TEXT, 'Activity name', VALUE_OPTIONAL),
                    'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
                    'general' => new external_value(PARAM_TEXT, 'General error', VALUE_OPTIONAL)
                ])
            ),
            'message' => new external_value(PARAM_TEXT, 'Result message')
        ]);
    }

    /**
     * Ativar completion tracking no curso
     */
    private static function enable_course_completion($course) {
        global $DB;

        if (!$course->enablecompletion) {
            $course->enablecompletion = 1;
            $DB->update_record('course', $course);
        }
    }

    /**
     * Obter atividades do curso em ordem sequencial
     */
    private static function get_course_activities_in_order($courseid) {
        global $DB;

        $sql = "
            SELECT cm.*, m.name as modname, cs.section as sectionnumber
            FROM {course_modules} cm
            JOIN {modules} m ON cm.module = m.id
            JOIN {course_sections} cs ON cm.section = cs.id
            WHERE cm.course = :courseid
            AND cm.visible = 1
            AND cm.deletioninprogress = 0
            ORDER BY cs.section, FIND_IN_SET(cm.id, cs.sequence)
        ";

        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }

    /**
     * Configurar completion para uma atividade
     */
    private static function configure_activity_completion($activity, $options) {
        global $DB;

        $cm = new stdClass();
        $cm->id = $activity->id;

        // Determinar tipo de completion baseado no tipo de módulo
        switch ($activity->modname) {
            case 'page':
            case 'resource':
            case 'url':
            case 'book':
                // Atividades de conteúdo: completion on view
                $cm->completion = COMPLETION_TRACKING_AUTOMATIC;
                $cm->completionview = 1;
                $completion_type = 'view';
                break;

            case 'quiz':
            case 'assign':
            case 'lesson':
                // Atividades avaliadas: completion on grade
                $cm->completion = COMPLETION_TRACKING_AUTOMATIC;
                $cm->completionview = 1; // Também requer visualização
                $cm->completiongrade = 1; // Requer nota
                $cm->completionpassgrade = 1; // Requer nota de aprovação
                $completion_type = 'grade';
                break;

            case 'forum':
            case 'chat':
            case 'glossary':
                // Atividades sociais: completion on view
                $cm->completion = COMPLETION_TRACKING_AUTOMATIC;
                $cm->completionview = 1;
                $completion_type = 'view';
                break;

            default:
                // Por defeito: completion on view
                $cm->completion = COMPLETION_TRACKING_AUTOMATIC;
                $cm->completionview = 1;
                $completion_type = 'view';
                break;
        }

        // Configurar nota de aprovação para quizzes se especificada
        if ($activity->modname === 'quiz' && isset($options['quiz_pass_grade'])) {
            // Atualizar gradepass na tabela quiz
            $quiz = $DB->get_record('quiz', ['id' => $activity->instance]);
            if ($quiz) {
                $quiz->gradepass = $options['quiz_pass_grade'];
                $DB->update_record('quiz', $quiz);
            }
        }

        // Atualizar course module
        $DB->update_record('course_modules', $cm);

        return [
            'success' => true,
            'completion_type' => $completion_type
        ];
    }

    /**
     * Configurar restricted access para uma atividade
     */
    private static function configure_activity_restriction($current_activity, $previous_activity, $options) {
        global $DB;

        try {
            // Criar condição de availability baseada no completion da atividade anterior
            $availability_condition = [
                'op' => '&', // AND condition
                'c' => [
                    [
                        'type' => 'completion',
                        'cm' => (int)$previous_activity->id,
                        'e' => 1 // Expected completion state (completed)
                    ]
                ]
            ];

            // Configurar se deve mostrar ou esconder quando não disponível
            if ($options['strict_sequential']) {
                $availability_condition['showc'] = [false]; // Esconder se não disponível
            } else {
                $availability_condition['showc'] = [true]; // Mostrar mas desabilitado
            }

            // Atualizar availability JSON na atividade atual
            $cm = new stdClass();
            $cm->id = $current_activity->id;
            $cm->availability = json_encode($availability_condition);
            
            $DB->update_record('course_modules', $cm);

            return [
                'success' => true,
                'restriction_type' => 'completion',
                'depends_on' => $previous_activity->id
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar se workflow sequencial está configurado
     */
    public static function check_sequential_workflow_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    /**
     * Verificar se workflow sequencial está configurado
     */
    public static function check_sequential_workflow($courseid) {
        global $DB;

        try {
            $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
            $context = context_course::instance($course->id);
            self::validate_context($context);

            // Obter atividades do curso
            $activities = self::get_course_activities_in_order($courseid);
            
            $workflow_status = [
                'course_id' => $courseid,
                'completion_enabled' => (bool)$course->enablecompletion,
                'total_activities' => count($activities),
                'activities_with_completion' => 0,
                'activities_with_restrictions' => 0,
                'sequential_workflow_active' => false,
                'activities_details' => []
            ];

            foreach ($activities as $activity) {
                $has_completion = ($activity->completion > 0);
                $has_restrictions = !empty($activity->availability);
                
                if ($has_completion) {
                    $workflow_status['activities_with_completion']++;
                }
                
                if ($has_restrictions) {
                    $workflow_status['activities_with_restrictions']++;
                }

                $workflow_status['activities_details'][] = [
                    'cmid' => $activity->id,
                    'name' => $activity->modname,
                    'completion_enabled' => $has_completion,
                    'has_restrictions' => $has_restrictions
                ];
            }

            // Considerar workflow ativo se maioria das atividades tem completion e restrições
            $completion_percentage = $workflow_status['total_activities'] > 0 ? 
                ($workflow_status['activities_with_completion'] / $workflow_status['total_activities']) : 0;
            
            $workflow_status['sequential_workflow_active'] = 
                $workflow_status['completion_enabled'] && 
                $completion_percentage >= 0.8; // 80% das atividades com completion

            return array_merge($workflow_status, ['success' => true]);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retorno da função check_sequential_workflow
     */
    public static function check_sequential_workflow_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
            'course_id' => new external_value(PARAM_INT, 'Course ID', VALUE_OPTIONAL),
            'completion_enabled' => new external_value(PARAM_BOOL, 'Completion tracking enabled', VALUE_OPTIONAL),
            'total_activities' => new external_value(PARAM_INT, 'Total activities in course', VALUE_OPTIONAL),
            'activities_with_completion' => new external_value(PARAM_INT, 'Activities with completion configured', VALUE_OPTIONAL),
            'activities_with_restrictions' => new external_value(PARAM_INT, 'Activities with access restrictions', VALUE_OPTIONAL),
            'sequential_workflow_active' => new external_value(PARAM_BOOL, 'Sequential workflow is active', VALUE_OPTIONAL),
            'activities_details' => new external_multiple_structure(
                new external_single_structure([
                    'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                    'name' => new external_value(PARAM_TEXT, 'Module name'),
                    'completion_enabled' => new external_value(PARAM_BOOL, 'Completion enabled'),
                    'has_restrictions' => new external_value(PARAM_BOOL, 'Has access restrictions')
                ]),
                'Details of each activity',
                VALUE_OPTIONAL
            ),
            'message' => new external_value(PARAM_TEXT, 'Result message', VALUE_OPTIONAL)
        ]);
    }
}