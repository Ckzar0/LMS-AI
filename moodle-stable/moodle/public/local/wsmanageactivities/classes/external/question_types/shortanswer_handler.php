<?php
// Versão 1.3 - Corrigido para ler parâmetros do objeto principal
namespace local_wsmanageactivities\external\question_types;
defined('MOODLE_INTERNAL') || die();
use stdClass;

class shortanswer_handler {
    public static function create_options($question, $params) {
        global $DB;
        
        $options = new stdClass();
        $options->questionid = $question->id;
        $options->usecase = (int)($params['shortanswer_usecase'] ?? 0);
        $DB->insert_record('qtype_shortanswer_options', $options);
        
        $answers = $params['answers'] ?? [];
        if (empty($answers)) {
            throw new \moodle_exception('Pelo menos uma resposta é obrigatória para questão shortanswer');
        }
        
        foreach ($answers as $answerdata) {
            $answer = new stdClass();
            $answer->question = $question->id;
            $answer->answer = clean_param($answerdata['text'], PARAM_TEXT);
            $answer->answerformat = 0;
            $answer->fraction = (float)($answerdata['fraction'] ?? 1.0);
            $answer->feedback = $answerdata['feedback'] ?? '';
            $answer->feedbackformat = FORMAT_HTML;
            $DB->insert_record('question_answers', $answer);
        }
    }
}
