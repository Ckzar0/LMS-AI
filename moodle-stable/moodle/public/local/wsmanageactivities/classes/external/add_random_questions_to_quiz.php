<?php
/**
 * Add Random Questions to Quiz API - VERSÃO 24.0 CORREÇÃO COMPLETA
 * 
 * CORREÇÃO DEFINITIVA v24.0:
 * - BUG CRÍTICO RESOLVIDO: filtercondition no formato correto do Moodle
 * - QUIZ_SECTIONS: Criação automática obrigatória
 * - ESTRUTURA COMPLETA: Igual ao quiz manual que funciona
 * - TODOS OS CAMPOS: cmid, courseid, jointype, qpage, etc.
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    24.0 - CORREÇÃO DEFINITIVA baseada no quiz funcional
 * @date       22 de Janeiro de 2025, 17:45
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;
use context_module;
use moodle_exception;
use stdClass;
use Exception;

class add_random_questions_to_quiz extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz instance ID'),
            'category_name' => new external_value(PARAM_TEXT, 'Question category name'),
            'number_of_questions' => new external_value(PARAM_INT, 'Number of random questions'),
            'points_each' => new external_value(PARAM_FLOAT, 'Points per question', VALUE_DEFAULT, 1.0),
            'randomization_rules' => new external_value(PARAM_RAW, 'JSON with randomization rules', VALUE_DEFAULT, '{}')
        ]);
    }

    public static function execute($quizid, $category_name, $number_of_questions, $points_each = 1.0, $randomization_rules = '{}') {
        global $DB;
        
        $params = self::validate_parameters(self::execute_parameters(), 
            compact('quizid', 'category_name', 'number_of_questions', 'points_each', 'randomization_rules'));
        
        // Validar quiz e obter contextos
        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
        $quiz_cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
        $quiz_context = context_module::instance($quiz_cm->id);
        
        // Validar permissões
        self::validate_context($quiz_context);
        require_capability('mod/quiz:manage', $quiz_context);
        
        try {
            // Obter contexto correto do Question Bank
            $qbank_context = self::get_qbank_context($quiz->course);
            
            // Obter categoria no contexto correto
            $category = $DB->get_record('question_categories', [
                'contextid' => $qbank_context->id,
                'name' => $params['category_name']
            ], '*', MUST_EXIST);
            
            // Verificar se há questões suficientes na categoria
            $available_questions = $DB->count_records_sql("
                SELECT COUNT(DISTINCT q.id)
                FROM {question} q
                JOIN {question_versions} qv ON qv.questionid = q.id
                JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                WHERE qbe.questioncategoryid = ?
                AND qv.status = 'ready'
            ", [$category->id]);
            
            if ($available_questions < $params['number_of_questions']) {
                return [
                    'success' => false,
                    'slots_created' => 0,
                    'message' => "Not enough questions in category. Available: {$available_questions}, Requested: {$params['number_of_questions']}"
                ];
            }
            
            $transaction = $DB->start_delegated_transaction();
            
            // ✅ CORREÇÃO v24.0: Preparar filtercondition no formato CORRETO
            $filter_condition_array = [
                'filter' => [
                    'category' => [
                        'name' => 'category',                    // ← OBRIGATÓRIO!
                        'jointype' => 1,                         // ← NÚMERO, não string
                        'values' => [(int)$category->id],
                        'filteroptions' => [
                            'includesubcategories' => true       // ← TRUE como no manual
                        ]
                    ]
                ],
                'cmid' => (int)$quiz_cm->id,                     // ← OBRIGATÓRIO!
                'courseid' => (int)$quiz->course,               // ← OBRIGATÓRIO!
                'jointype' => 2,                                // ← OBRIGATÓRIO!
                'qpage' => 0,                                   // ← OBRIGATÓRIO!
                'qperpage' => 100,                              // ← OBRIGATÓRIO!
                'sortdata' => [],                               // ← OBRIGATÓRIO!
                'cat' => $category->id . ',' . $qbank_context->id, // ← OBRIGATÓRIO!
                'tabname' => 'questions'                        // ← OBRIGATÓRIO!
            ];
            
            // Criar slots e referências para questões aleatórias
            $slots_created = 0;
            for ($i = 0; $i < $params['number_of_questions']; $i++) {
                // PASSO 1: Criar slot no quiz
                $slot = new stdClass();
                $slot->quizid = $quiz->id;
                $slot->slot = self::get_next_slot_number($quiz->id);
                $slot->page = $slot->slot;
                $slot->maxmark = $params['points_each'];
                $slot->requireprevious = 0;
                $slot->id = $DB->insert_record('quiz_slots', $slot);

                // PASSO 2: Criar referência na tabela question_set_references
                $set_reference = new stdClass();
                $set_reference->usingcontextid = $quiz_context->id;        // Contexto do quiz
                $set_reference->component = 'mod_quiz';
                $set_reference->questionarea = 'slot';                     // 'slot', não 'quiz_slot'!
                $set_reference->itemid = $slot->id;                        // ID do slot criado
                $set_reference->questionscontextid = $qbank_context->id;   // Contexto do Question Bank
                $set_reference->filtercondition = json_encode($filter_condition_array);
                
                // Inserir na tabela correta
                $reference_id = $DB->insert_record('question_set_references', $set_reference);
                
                debugging("v24.0 DEBUG: Created slot {$slot->id} with set_reference {$reference_id}", DEBUG_DEVELOPER);
                
                $slots_created++;
            }
            
            // ✅ CORREÇÃO v24.0: Criar quiz_sections OBRIGATÓRIO
            self::ensure_quiz_sections($quiz->id);
            
            // Atualizar sumgrades do quiz
            $new_sumgrades = $DB->get_field_sql('SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = ?', [$quiz->id]);
            $DB->set_field('quiz', 'sumgrades', $new_sumgrades, ['id' => $quiz->id]);
            
            // Atualizar grade items se necessário
            self::update_grade_item($quiz, $new_sumgrades);
            
            $transaction->allow_commit();
            
            // Converter filter_condition para string JSON para retorno
            $filter_condition_string = json_encode($filter_condition_array);
            
            return [
                'success' => true,
                'slots_created' => $slots_created,
                'category_id' => (int)$category->id,
                'category_name' => $category->name,
                'questions_per_slot' => 1,
                'points_per_question' => (float)$params['points_each'],
                'total_points_added' => (float)($slots_created * $params['points_each']),
                'new_quiz_total' => (float)$new_sumgrades,
                'quiz_context_id' => (int)$quiz_context->id,
                'qbank_context_id' => (int)$qbank_context->id,
                'filter_condition_used' => $filter_condition_string,
                'quiz_sections_created' => true,                        // ← NOVO!
                'message' => 'Random questions added successfully with complete structure',
                'version' => '24.0',
                'architecture' => 'Moodle 5.0 compliant - Fixed'
            ];
            
        } catch (Exception $e) {
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
            return [
                'success' => false,
                'slots_created' => 0,
                'message' => 'Error v24.0: ' . $e->getMessage(),
                'debug_info' => [
                    'quiz_id' => $quiz->id ?? 0,
                    'category_name' => $params['category_name'],
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile())
                ]
            ];
        }
    }
    
    /**
     * ✅ NOVA FUNÇÃO v24.0: Garantir quiz_sections existe
     */
    private static function ensure_quiz_sections($quiz_id) {
        global $DB;
        
        // Verificar se já existe
        $existing_section = $DB->get_record('quiz_sections', ['quizid' => $quiz_id]);
        
        if (!$existing_section) {
            // Criar nova seção
            $section = new stdClass();
            $section->quizid = $quiz_id;
            $section->firstslot = 1;
            $section->heading = '';
            $section->shufflequestions = 1;    // Embaralhar questões ativado
            
            $section_id = $DB->insert_record('quiz_sections', $section);
            
            debugging("v24.0 DEBUG: Created quiz_sections id={$section_id} for quiz {$quiz_id}", DEBUG_DEVELOPER);
            
            return $section_id;
        }
        
        debugging("v24.0 DEBUG: Quiz_sections already exists for quiz {$quiz_id}", DEBUG_DEVELOPER);
        return $existing_section->id;
    }
    
    /**
     * Context adaptativo baseado na situação
     */
    private static function get_qbank_context($courseid) {
        global $DB;
        
        // Tentar encontrar módulo Question Bank específico primeiro
        $cm = $DB->get_record_sql("
            SELECT cm.id 
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE cm.course = ? AND m.name = 'qbank'
        ", [$courseid]);
        
        if ($cm) {
            // Question Bank module existe - usar seu contexto
            debugging("v24.0 DEBUG: Using qbank module context: {$cm->id}", DEBUG_DEVELOPER);
            return context_module::instance($cm->id);
        }
        
        // Fallback: usar contexto do curso (compatibilidade)
        debugging("v24.0 DEBUG: No qbank module found, using course context for courseid: $courseid", DEBUG_DEVELOPER);
        return context_course::instance($courseid);
    }
    
    /**
     * Obter próximo número de slot
     */
    private static function get_next_slot_number($quiz_id) {
        global $DB;
        
        $max_slot = $DB->get_field('quiz_slots', 'MAX(slot)', ['quizid' => $quiz_id]);
        return ($max_slot ?: 0) + 1;
    }
    
    /**
     * Atualizar grade item mantendo proporções
     */
    private static function update_grade_item($quiz, $new_sumgrades) {
        global $DB;
        
        $grade_item = $DB->get_record('grade_items', [
            'courseid' => $quiz->course,
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id
        ]);
        
        if ($grade_item) {
            // Manter proporção do gradepass
            $old_grademax = (float)$grade_item->grademax;
            $old_gradepass = (float)$grade_item->gradepass;
            
            if ($old_grademax > 0) {
                $gradepass_percentage = $old_gradepass / $old_grademax;
                $new_gradepass = round($new_sumgrades * $gradepass_percentage, 2);
            } else {
                $new_gradepass = round($new_sumgrades * 0.8, 2); // Default 80%
            }
            
            $grade_item->grademax = $new_sumgrades;
            $grade_item->gradepass = $new_gradepass;
            $grade_item->timemodified = time();
            
            $DB->update_record('grade_items', $grade_item);
            
            debugging("v24.0 DEBUG: Updated grade_item - grademax: {$new_sumgrades}, gradepass: {$new_gradepass}", DEBUG_DEVELOPER);
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'slots_created' => new external_value(PARAM_INT, 'Number of slots created'),
            'category_id' => new external_value(PARAM_INT, 'Category ID', VALUE_OPTIONAL),
            'category_name' => new external_value(PARAM_TEXT, 'Category name', VALUE_OPTIONAL),
            'questions_per_slot' => new external_value(PARAM_INT, 'Questions per slot', VALUE_OPTIONAL),
            'points_per_question' => new external_value(PARAM_FLOAT, 'Points per question', VALUE_OPTIONAL),
            'total_points_added' => new external_value(PARAM_FLOAT, 'Total points added', VALUE_OPTIONAL),
            'new_quiz_total' => new external_value(PARAM_FLOAT, 'New quiz total points', VALUE_OPTIONAL),
            'quiz_context_id' => new external_value(PARAM_INT, 'Quiz context ID', VALUE_OPTIONAL),
            'qbank_context_id' => new external_value(PARAM_INT, 'Question Bank context ID', VALUE_OPTIONAL),
            'filter_condition_used' => new external_value(PARAM_TEXT, 'Filter condition JSON used as string', VALUE_OPTIONAL),
            'quiz_sections_created' => new external_value(PARAM_BOOL, 'Whether quiz_sections was ensured', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'version' => new external_value(PARAM_TEXT, 'API version', VALUE_OPTIONAL),
            'architecture' => new external_value(PARAM_TEXT, 'Architecture compliance', VALUE_OPTIONAL),
            'debug_info' => new external_value(PARAM_RAW, 'Debug information', VALUE_OPTIONAL)
        ]);
    }
}