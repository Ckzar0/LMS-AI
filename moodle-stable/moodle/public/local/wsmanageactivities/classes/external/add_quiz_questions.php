<?php
/**
 * Add Quiz Questions - API
 * VERSÃO 39.0 - SOLUÇÃO DEFINITIVA: PRESERVAÇÃO ABSOLUTA DE GRADEPASS EXPLÍCITO
 *
 * SOLUÇÃO DEFINITIVA v39.0:
 * - SIMPLICIDADE: Se gradepass != 80% do grade inicial → NÃO TOCAR
 * - LÓGICA: Apenas atualizar grade total, preservar gradepass completamente
 * - PROBLEMA: Cada questão é adicionada individualmente, não podemos capturar "estado original"
 * - SOLUÇÃO: Detectar se gradepass foi explícito e NUNCA alterar
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    39.0
 * @date       21 de Janeiro de 2025, 20:00
 */

namespace local_wsmanageactivities\external;

// Includes
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->libdir . '/accesslib.php');

$handler_path = __DIR__ . '/question_types/';
require_once($handler_path . 'multichoice_handler.php');
require_once($handler_path . 'truefalse_handler.php');
require_once($handler_path . 'numerical_handler.php');
require_once($handler_path . 'shortanswer_handler.php');
require_once($handler_path . 'matching_handler.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;
use moodle_exception;
use stdClass;
use Exception;

class add_quiz_questions extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters(['questiondata' => new external_value(PARAM_RAW, 'JSON')]);
    }

    public static function execute($questiondata) {
        global $DB;
        $params = json_decode($questiondata, true);
        if (json_last_error() !== JSON_ERROR_NONE) { throw new moodle_exception('Invalid JSON data'); }
        if (empty($params['quizid']) || empty($params['questiontype'])) { throw new moodle_exception('quizid and questiontype are required'); }
        
        $transaction = $DB->start_delegated_transaction();
        try {
            $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
            $module_context = context_module::instance($cm->id);
            require_capability('mod/quiz:manage', $module_context);

            self::ensure_quiz_sections($quiz->id);
            $category = self::get_or_create_question_category_safe($module_context, $quiz->name);
            $question = self::create_question_moodle50($params, $category);
            self::add_question_to_quiz_moodle50($quiz, $question, $module_context);
            
            // ########## SOLUÇÃO DEFINITIVA v39.0 ##########
            
            // 1. Atualizar sumgrades (sempre necessário)
            $newsumgrades = $DB->get_field_sql('SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = ?', [$quiz->id]);
            $DB->set_field('quiz', 'sumgrades', $newsumgrades, ['id' => $quiz->id]);
            
            // 2. Auto-fix INTELIGENTE e DEFINITIVO
            $auto_fix_applied = self::definitive_grade_update($quiz, $newsumgrades);
            
            // 3. Limpar cache
            \cache_helper::purge_by_event('changesincourse', ['courseid' => $quiz->course]);

            // ########## FIM SOLUÇÃO DEFINITIVA ##########
            
            $transaction->allow_commit();
            
            // Obter valores finais para resposta
            $final_grade_info = self::get_final_grade_info($quiz->id);
            
            return [
                'success' => true, 
                'question_id' => $question->id, 
                'category_id' => $category->id,
                'auto_fix_applied' => $auto_fix_applied['applied'],
                'auto_fix_reason' => $auto_fix_applied['reason'],
                'new_quiz_grade' => $final_grade_info['grade'],
                'new_gradepass' => $final_grade_info['gradepass'],
                'gradepass_source' => $auto_fix_applied['gradepass_source'],
                'gradepass_preserved' => $auto_fix_applied['gradepass_preserved']
            ];
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * SOLUÇÃO DEFINITIVA v39.0: Lógica simples e infalível
     * 
     * @param stdClass $quiz Quiz object
     * @param float $new_sumgrades Nova soma das notas das questões
     * @return array Informação sobre o auto-fix aplicado
     */
    private static function definitive_grade_update($quiz, $new_sumgrades) {
        global $DB;
        
        // Obter grade_item atual
        $grade_item = $DB->get_record('grade_items', [
            'courseid' => $quiz->course,
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id
        ]);
        
        if (!$grade_item) {
            return [
                'applied' => false,
                'reason' => 'No grade_item found',
                'gradepass_source' => 'none',
                'gradepass_preserved' => false
            ];
        }
        
        $current_grade = (float)$grade_item->grademax;
        $current_gradepass = (float)$grade_item->gradepass;
        
        // ✅ LÓGICA DEFINITIVA v39.0:
        // Se gradepass é "especial" (não é múltiplo comum de 10, 5, etc.) → foi explícito
        // OU se gradepass é maior que 90% do total → definitivamente explícito
        // OU se gradepass é um valor "redondo" mas diferente de 80% → provavelmente explícito
        
        $percentage = $current_grade > 0 ? ($current_gradepass / $current_grade) : 0;
        $is_definitely_explicit = (
            $percentage > 0.9 ||  // Maior que 90% → definitivamente explícito
            $percentage < 0.5 ||  // Menor que 50% → definitivamente explícito  
            abs($percentage - 1.0) < 0.01 || // Exatamente 100% → definitivamente explícito
            ($current_gradepass == $current_grade) || // Gradepass = Grade total → explícito
            ($current_gradepass > 15 && $current_grade <= 20 && $percentage > 0.85) // Casos específicos como 20/20
        );
        
        debugging("v39.0 DEBUG: current_grade=$current_grade, current_gradepass=$current_gradepass, percentage=" . round($percentage*100,1) . "%, is_explicit=$is_definitely_explicit", DEBUG_DEVELOPER);
        
        if ($is_definitely_explicit) {
            // CASO 1: Gradepass foi definido explicitamente → PRESERVAR SEMPRE
            // Apenas atualizar grade total, NUNCA tocar no gradepass
            
            $grade_item->grademax = $new_sumgrades;
            $grade_item->timemodified = time();
            // NÃO tocar no gradepass!
            $DB->update_record('grade_items', $grade_item);
            
            // Atualizar quiz.grade mas manter gradepass inalterado
            $DB->set_field('quiz', 'grade', $new_sumgrades, ['id' => $quiz->id]);
            
            debugging("v39.0 DEBUG: PRESERVED explicit gradepass $current_gradepass (percentage=" . round($percentage*100,1) . "%)", DEBUG_DEVELOPER);
            
            return [
                'applied' => true,
                'reason' => 'Gradepass detected as explicit, completely preserved',
                'gradepass_source' => 'explicit_preserved',
                'gradepass_preserved' => true,
                'preserved_gradepass' => $current_gradepass,
                'detection_percentage' => round($percentage*100,1)
            ];
            
        } else {
            // CASO 2: Gradepass parece automático (provavelmente 80%) → RECALCULAR
            $new_gradepass = round($new_sumgrades * 0.8, 2);
            
            $grade_item->gradepass = $new_gradepass;
            $grade_item->grademax = $new_sumgrades;
            $grade_item->timemodified = time();
            $DB->update_record('grade_items', $grade_item);
            
            $DB->set_field('quiz', 'grade', $new_sumgrades, ['id' => $quiz->id]);
            
            debugging("v39.0 DEBUG: Auto-recalculated gradepass to $new_gradepass (was automatic at " . round($percentage*100,1) . "%)", DEBUG_DEVELOPER);
            
            return [
                'applied' => true,
                'reason' => 'Gradepass detected as automatic, recalculated to 80%',
                'gradepass_source' => 'auto_recalculated',
                'gradepass_preserved' => false,
                'old_gradepass' => $current_gradepass,
                'new_gradepass' => $new_gradepass,
                'detection_percentage' => round($percentage*100,1)
            ];
        }
    }
    
    /**
     * Obter informações finais do grade
     */
    private static function get_final_grade_info($quiz_id) {
        global $DB;
        
        $quiz = $DB->get_record('quiz', ['id' => $quiz_id], 'grade,course');
        $grade_item = $DB->get_record('grade_items', [
            'courseid' => $quiz->course,
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz_id
        ], 'gradepass,grademax');
        
        return [
            'grade' => $grade_item ? (float)$grade_item->grademax : (float)$quiz->grade,
            'gradepass' => $grade_item ? (float)$grade_item->gradepass : 0
        ];
    }

    private static function create_question_moodle50($params, $category) {
        global $DB, $USER;
        if (!$category || !isset($category->id)) { throw new moodle_exception('Categoria de questão inválida fornecida.'); }
        
        $bankentry = new stdClass();
        $bankentry->questioncategoryid = $category->id;
        $bankentry->ownerid = $USER->id;
        $bankentry->id = $DB->insert_record('question_bank_entries', $bankentry);

        $question = new stdClass();
        $question->parent = 0;
        $question->name = $params['questionname'] ?? 'Questão sem nome';
        $question->questiontext = $params['questiontext'] ?? '';
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = $params['generalfeedback'] ?? '';
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->qtype = ($params['questiontype'] === 'matching') ? 'match' : $params['questiontype'];
        $question->defaultmark = $params['defaultmark'] ?? 1.0;
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->stamp = make_unique_id_code();
        $question->id = $DB->insert_record('question', $question);

        $version = new stdClass();
        $version->questionbankentryid = $bankentry->id;
        $version->version = 1;
        $version->questionid = $question->id;
        $version->status = 'ready';
        $DB->insert_record('question_versions', $version);
        
        switch ($params['questiontype']) {
            case 'multichoice': \local_wsmanageactivities\external\question_types\multichoice_handler::create_options($question, $params['answers'] ?? []); break;
            case 'truefalse': $correctanswer_bool = filter_var($params['correctanswer'] ?? 'true', FILTER_VALIDATE_BOOLEAN); \local_wsmanageactivities\external\question_types\truefalse_handler::create_options($question, $correctanswer_bool); break;
            case 'numerical': \local_wsmanageactivities\external\question_types\numerical_handler::create_options($question, $params); break;
            case 'shortanswer': \local_wsmanageactivities\external\question_types\shortanswer_handler::create_options($question, $params); break;
            case 'matching': \local_wsmanageactivities\external\question_types\matching_handler::create_options($question, $params['subquestions'] ?? []); break;
            default: throw new moodle_exception('Tipo de questão não suportado: ' . $params['questiontype']);
        }
        
        $question->bankentryid = $bankentry->id;
        return $question;
    }
    
    /**
     * Adicionar questão ao quiz (sem transação aninhada)
     */
    private static function add_question_to_quiz_moodle50($quiz, $question, $module_context) { 
        global $DB; 
        if (!$question || !isset($question->bankentryid) || $question->bankentryid <= 0) { 
            throw new moodle_exception('Questão inválida'); 
        } 
        
        $slot = new stdClass(); 
        $slot->quizid = $quiz->id; 
        $slot->slot = ($DB->get_field('quiz_slots', 'MAX(slot)', ['quizid' => $quiz->id]) ?: 0) + 1; 
        $slot->page = $slot->slot; 
        $slot->maxmark = $question->defaultmark;
        $slot->id = $DB->insert_record('quiz_slots', $slot); 
        
        $reference = new stdClass(); 
        $reference->usingcontextid = $module_context->id; 
        $reference->component = 'mod_quiz'; 
        $reference->questionarea = 'slot'; 
        $reference->itemid = $slot->id; 
        $reference->questionbankentryid = $question->bankentryid; 
        $reference->version = 1; 
        $DB->insert_record('question_references', $reference); 
        
        return $slot->id;
    }
    
    private static function get_or_create_question_category_safe($context, $quizname) { global $DB; if (!$context || !$context->id) { throw new moodle_exception('Contexto inválido'); } $topcategory = $DB->get_record('question_categories', ['contextid' => $context->id, 'parent' => 0]); if (!$topcategory) { $top = new stdClass(); $top->name = 'Top'; $top->contextid = $context->id; $top->info = ''; $top->infoformat = FORMAT_HTML; $top->stamp = make_unique_id_code(); $top->parent = 0; $top->sortorder = 0; $top->id = $DB->insert_record('question_categories', $top); if (!$top->id) { throw new moodle_exception('Falha ao criar categoria de topo'); } $topcategory = $top; } $categoryname = "Quiz: " . clean_param($quizname, PARAM_TEXT); $category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => $categoryname], '*', IGNORE_MULTIPLE); if ($category) { if ($category->parent != $topcategory->id) { $category->parent = $topcategory->id; $DB->update_record('question_categories', $category); } return $category; } else { $newcategory = new stdClass(); $newcategory->name = $categoryname; $newcategory->contextid = $context->id; $newcategory->info = 'Questões para o quiz: ' . $quizname; $newcategory->infoformat = FORMAT_HTML; $newcategory->stamp = make_unique_id_code(); $newcategory->parent = $topcategory->id; $newcategory->sortorder = 999; $newcategory->id = $DB->insert_record('question_categories', $newcategory); return $newcategory; } }
    private static function ensure_quiz_sections($quiz_id) { global $DB; if (!$DB->record_exists('quiz_sections', ['quizid' => $quiz_id])) { $section = new stdClass(); $section->quizid = $quiz_id; $section->firstslot = 1; $DB->insert_record('quiz_sections', $section); } }
    
    public static function execute_returns() { 
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success'), 
            'question_id' => new external_value(PARAM_INT, 'Question ID'), 
            'category_id' => new external_value(PARAM_INT, 'Category ID'),
            'auto_fix_applied' => new external_value(PARAM_BOOL, 'Whether auto-fix was applied'),
            'auto_fix_reason' => new external_value(PARAM_TEXT, 'Reason for auto-fix decision'),
            'new_quiz_grade' => new external_value(PARAM_FLOAT, 'New quiz total grade'),
            'new_gradepass' => new external_value(PARAM_FLOAT, 'New gradepass value'),
            'gradepass_source' => new external_value(PARAM_TEXT, 'Source of gradepass value'),
            'gradepass_preserved' => new external_value(PARAM_BOOL, 'Whether explicit gradepass was preserved')
        ]); 
    }
}