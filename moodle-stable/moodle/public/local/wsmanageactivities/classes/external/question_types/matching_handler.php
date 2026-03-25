<?php
/**
 * Matching question type handler - VERSÃO FINAL
 * @package    local_wsmanageactivities
 * @subpackage question_types
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    Final - Estrutura correta para Moodle 5.0
 */

namespace local_wsmanageactivities\external\question_types;

defined('MOODLE_INTERNAL') || die();

use stdClass;

class matching_handler {
    
    public static function create_options($question, $params) {
        global $DB;
        
        $subquestions = $params;

        if (empty($subquestions) || !is_array($subquestions) || count($subquestions) < 2) {
            throw new \moodle_exception('Pelo menos duas subquestões são necessárias para o tipo correspondência.');
        }

        // Forçar o tipo correto na questão principal
        $question_update = new stdClass();
        $question_update->id = $question->id;
        $question_update->qtype = 'match';
        $DB->update_record('question', $question_update);

        // Criar opções de matching
        $options = new stdClass();
        $options->questionid = $question->id;
        $options->shuffleanswers = 1;
        $options->correctfeedback = '<p>Your answer is correct.</p>';
        $options->correctfeedbackformat = 1;
        $options->partiallycorrectfeedback = '<p>Your answer is partially correct.</p>';
        $options->partiallycorrectfeedbackformat = 1;
        $options->incorrectfeedback = '<p>Your answer is incorrect.</p>';
        $options->incorrectfeedbackformat = 1;
        $options->shownumcorrect = 1;
        
        $DB->insert_record('qtype_match_options', $options);
        
        // Criar subquestões com estrutura correta do Moodle 5.0
        foreach ($subquestions as $sub) {
            if (!empty($sub['question']) && !empty($sub['answer'])) {
                $subquestion = new stdClass();
                $subquestion->questionid = $question->id;
                $subquestion->questiontext = '<p>' . $sub['question'] . '</p>';
                $subquestion->questiontextformat = 1;
                $subquestion->answertext = $sub['answer'];
                
                $DB->insert_record('qtype_match_subquestions', $subquestion);
            }
        }
        
        // Limpeza de cache
        if (function_exists('purge_all_caches')) {
            purge_all_caches();
        }
        
        $cache = \cache::make('core', 'questiondata');
        $cache->delete($question->id);
        
        \question_bank::notify_question_edited($question->id);
    }
}