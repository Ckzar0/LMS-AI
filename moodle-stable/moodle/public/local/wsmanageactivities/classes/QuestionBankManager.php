<?php
namespace local_wsmanageactivities;

use local_wsmanageactivities\importer\QuestionCreator;

class QuestionBankManager {

    public function process_bank($course_id, $bank_data) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/questionlib.php');

        // LIMPEZA E REPARAÇÃO DE CONTEXTOS (CRÍTICO PARA MOODLE 5)
        \context_helper::reset_caches();
        \context_helper::build_all_paths(true); 
        $course_context = \context_course::instance($course_id);
        
        // Localizar a categoria 'top' do curso de forma manual (Compatível Moodle 5)
        $top_category = $DB->get_record('question_categories', ['contextid' => $course_context->id, 'parent' => 0]);
        
        if (!$top_category) {
            $top_category = new \stdClass();
            $top_category->name = 'top';
            $top_category->contextid = $course_context->id;
            $top_category->parent = 0;
            $top_category->info = '';
            $top_category->stamp = md5(uniqid(rand(), true));
            $top_category->id = $DB->insert_record('question_categories', $top_category);
        }

        // Criar ou obter a categoria para o banco de questões (filha da top)
        $course_name = $DB->get_field('course', 'fullname', ['id' => $course_id]);
        $bank_name = $bank_data['name'] ?? "Banco AI - " . $course_name;
        
        $category = $DB->get_record('question_categories', [
            'contextid' => $course_context->id, 
            'name' => $bank_name
        ]);

        if (!$category) {
            $category = new \stdClass();
            $category->name = $bank_name;
            $category->contextid = $course_context->id;
            $category->info = 'Questões importadas via AI.';
            $category->infoformat = 1;
            $category->parent = (int)$top_category->id; // Garantir que não é NULL
            $category->sortorder = 999;
            $category->stamp = md5(uniqid(rand(), true));
            $category->id = $DB->insert_record('question_categories', $category);
            echo "      📁 Criada nova categoria: $bank_name (ID: {$category->id}) no Contexto: {$course_context->id}\n";
        } else {
            echo "      📁 Usando categoria existente: $bank_name (ID: {$category->id})\n";
        }

        if (!empty($bank_data['questions'])) {
            $q_count = 0;
            foreach ($bank_data['questions'] as $q_data) {
                $qid = \local_wsmanageactivities\importer\QuestionCreator::create_question($category->id, $course_context->id, $q_data);
                if ($qid) $q_count++;
            }
            echo "      ✅ Criadas $q_count questões na categoria ID: {$category->id}\n";
        }

        // Limpar caches de contexto novamente após a criação
        \context_helper::reset_caches();

        return $category->id;
    }
}
