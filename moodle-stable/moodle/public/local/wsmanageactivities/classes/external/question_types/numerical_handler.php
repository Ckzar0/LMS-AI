<?php
// Versão 1.3 - Corrigido para ler parâmetros do objeto principal
namespace local_wsmanageactivities\external\question_types;
defined('MOODLE_INTERNAL') || die();
use stdClass;

class numerical_handler {
    public static function create_options($question, $params) {
        global $DB;
        
        $answer = new stdClass();
        $answer->question = $question->id;
        $answer->answer = (string)$params['numerical_answer'];
        $answer->answerformat = 0;
        $answer->fraction = 1.0;
        $answer->feedback = 'Correto!';
        $answer->feedbackformat = FORMAT_HTML;
        $answer_id = $DB->insert_record('question_answers', $answer);
        
        $numerical = new stdClass();
        $numerical->question = $question->id;
        $numerical->answer = $answer_id;
        $numerical->tolerance = $params['numerical_tolerance'] ?? '0';
        $numerical->tolerancetype = 2; // Default para absoluto
        $DB->insert_record('question_numerical', $numerical);
    }
}
