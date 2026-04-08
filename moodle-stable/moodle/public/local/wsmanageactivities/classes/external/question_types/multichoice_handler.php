<?php
/**
 * Multichoice question type handler
 * * @package    local_wsmanageactivities
 * @subpackage question_types
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    1.2 - Corrigido nome da tabela de respostas
 */

namespace local_wsmanageactivities\external\question_types;

defined('MOODLE_INTERNAL') || die();

use stdClass;

class multichoice_handler {
    
    public static function create_options($question, $answers) {
        global $DB;
        
        $options = new stdClass();
        $options->questionid = $question->id;
        $options->layout = 0;
        $options->single = 1;
        $options->shuffleanswers = 1;
        $options->correctfeedback = 'Correto!';
        $options->correctfeedbackformat = FORMAT_HTML;
        $options->partiallycorrectfeedback = 'Parcialmente correto.';
        $options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $options->incorrectfeedback = 'Incorreto.';
        $options->incorrectfeedbackformat = FORMAT_HTML;
        $options->answernumbering = 'abc';
        $options->shownumcorrect = 1;
        
        $DB->insert_record('qtype_multichoice_options', $options);
        
        foreach ($answers as $answerdata) {
            $answer = new stdClass();
            $answer->question = $question->id;
            $answer->answer = clean_param($answerdata['text'], PARAM_TEXT);
            $answer->answerformat = FORMAT_HTML;
            $answer->fraction = (float)$answerdata['fraction'];
            $answer->feedback = isset($answerdata['feedback']) ? $answerdata['feedback'] : '';
            $answer->feedbackformat = FORMAT_HTML;
            
            $DB->insert_record('question_answers', $answer);
        }
    }
    
    public static function validate_params($params) {
        if (!isset($params['answers']) || count($params['answers']) < 2) {
            throw new \moodle_exception('Multichoice precisa de pelo menos 2 respostas');
        }
        
        $has_correct = false;
        foreach ($params['answers'] as $answer) {
            if (isset($answer['fraction']) && $answer['fraction'] > 0) {
                $has_correct = true;
                break;
            }
        }
        
        if (!$has_correct) {
            throw new \moodle_exception('Multichoice precisa de pelo menos uma resposta correta');
        }
        
        return true;
    }
}
