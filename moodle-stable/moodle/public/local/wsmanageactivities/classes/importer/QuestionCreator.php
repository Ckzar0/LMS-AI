<?php
namespace local_wsmanageactivities\importer;

class QuestionCreator {

    private static function force_string($val) {
        if (is_array($val) || is_object($val)) {
            $v = (array)$val;
            return $v['text'] ?? $v['name'] ?? $v['bank_name'] ?? $v[0] ?? json_encode($v);
        }
        return (string)$val;
    }

    public static function create_question($category_id, $context_id, $data) {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->libdir . '/questionlib.php');

        // Normalizar tipo de questão
        $qtype = self::force_string($data['qtype'] ?? $data['type'] ?? 'multichoice');
        $qtype = strtolower(trim($qtype));
        
        // Mapeamento de tipos IA -> Moodle
        if (in_array($qtype, ['multichoice', 'multiple choice', 'multiple_choice', 'multiplechoice'])) $qtype = 'multichoice';
        elseif (in_array($qtype, ['truefalse', 'true/false', 'true_false', 'boolean'])) $qtype = 'truefalse';
        elseif (in_array($qtype, ['match', 'matching', 'associacao'])) $qtype = 'match';
        elseif (in_array($qtype, ['shortanswer', 'short_answer', 'resposta_curta'])) $qtype = 'shortanswer';
        else $qtype = 'multichoice';

        $qdata = new \stdClass();
        $qdata->categoryid = $category_id; 
        $qdata->category = $category_id;
        $qdata->contextid = $context_id;
        $qdata->name = (string)($data['name'] ?? 'Questão AI ' . time());
        
        $qtext = $data['questiontext'] ?? $data['text'] ?? '';
        $qdata->questiontext = ['text' => self::force_string($qtext), 'format' => 1];
        
        // Centralizar feedback da IA no General Feedback (Bloco único abaixo da questão)
        $qdata->generalfeedback = ['text' => self::force_string($data['generalfeedback'] ?? $data['feedback'] ?? ''), 'format' => 1];
        
        $qdata->defaultmark = (float)($data['mark'] ?? 1.0);
        $qdata->penalty = 0.3333333;
        $qdata->status = 'ready'; 
        
        // Limpar feedbacks combinados (deixar para o Moodle gerir os ícones/labels nativos)
        $qdata->correctfeedback = ['text' => '', 'format' => 1];
        $qdata->partiallycorrectfeedback = ['text' => '', 'format' => 1];
        $qdata->incorrectfeedback = ['text' => '', 'format' => 1];
        $qdata->shownumcorrect = 1;

        $config = $data['config'] ?? $data;
        
        if ($qtype === 'multichoice') {
            $qdata->qtype = 'multichoice'; $qdata->single = 1; $qdata->shuffleanswers = 1; $qdata->answernumbering = 'abc';
            if (!empty($config['answers'])) {
                foreach ($config['answers'] as $ans) {
                    $qdata->fraction[] = (float)($ans['fraction'] ?? ($ans['correct'] ? 1.0 : 0.0));
                    $qdata->answer[] = ['text' => self::force_string($ans['text'] ?? ''), 'format' => 1];
                    // REMOVIDO: feedback por resposta para evitar duplicação de ícones
                    $qdata->feedback[] = ['text' => '', 'format' => 1];
                }
            }
        } elseif ($qtype === 'truefalse') {
            $qdata->qtype = 'truefalse';
            $correct = isset($config['correctanswer']) ? $config['correctanswer'] : (isset($config['correct_answer']) ? $config['correct_answer'] : true);
            if (is_string($correct)) { $correct = (strtolower($correct) === 'true' || $correct === '1'); }
            $qdata->correctanswer = $correct ? 1 : 0;
            
            // REMOVIDO: feedback por resposta para evitar duplicação
            $qdata->feedbacktrue = ['text' => '', 'format' => 1];
            $qdata->feedbackfalse = ['text' => '', 'format' => 1];
        } elseif ($qtype === 'match') {
            $qdata->qtype = 'match';
            $qdata->shuffleanswers = 1;
            
            if (!empty($config['subquestions'])) {
                foreach ($config['subquestions'] as $sub) {
                    $qdata->subquestions[] = ['text' => self::force_string($sub['questiontext'] ?? $sub['text'] ?? ''), 'format' => 1];
                    $qdata->subanswers[] = self::force_string($sub['answertext'] ?? $sub['answer'] ?? '');
                }
            }
        }

        try {
            $qtypeobj = \question_bank::get_qtype($qtype);
            
            // Garantir que todos os campos estão prontos para o save_question
            $qdata->category = $category_id;
            $qdata->contextid = $context_id;
            
            // Chamar o método oficial de salvamento
            $question = $qtypeobj->save_question($qdata, $qdata);

            if ($question && isset($question->id)) {
                return $question->id;
            }
        } catch (\Throwable $e) {
            // Log silencioso do erro para evitar quebra de fluxo
        }
        
        return null;
    }
}
