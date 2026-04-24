<?php
namespace local_wsmanageactivities\importer;

class ActivityCreator {
    
    private static function clear_pending_transactions() {
        global $DB;
        while ($DB->is_transaction_started()) {
            try { $DB->force_transaction_rollback(); } catch (\Throwable $e) { break; }
        }
    }

    private static function force_string($val) {
        if (is_array($val) || is_object($val)) {
            $v = (array)$val;
            return $v['bank_name'] ?? $v['name'] ?? $v['text'] ?? $v[0] ?? json_encode($v);
        }
        return (string)$val;
    }

    public static function refresh_question_bank($course_id) {
        global $DB;
        try {
            $ctx = \context_system::instance();
            $event = \core\event\questions_imported::create(['context' => $ctx, 'other' => ['categoryid' => 0]]);
            $event->trigger();
        } catch (\Throwable $e) {}

        return (int)$info->coursemodule;
    }
    
    public static function create_page($course_id, $activity, $section = 1, $prerequisite_ids = []) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/modlib.php');
        self::clear_pending_transactions();
        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
        course_create_sections_if_missing($course, $section);
        $module = $DB->get_record('modules', ['name' => 'page'], '*', MUST_EXIST);
        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = 'page'; $moduleinfo->module = (int)$module->id; $moduleinfo->course = (int)$course_id; $moduleinfo->section = $section;
        $moduleinfo->name = self::force_string($activity['name'] ?? 'Página AI');
        $moduleinfo->intro = self::force_string($activity['intro'] ?? '');
        $moduleinfo->content = self::force_string($activity['content'] ?? '');
        $moduleinfo->contentformat = 1; $moduleinfo->display = 5; $moduleinfo->visible = 1; $moduleinfo->completion = 2; $moduleinfo->completionview = 1; $moduleinfo->cmidnumber = '';
        $moduleinfo->printintro = 1; $moduleinfo->printlastmodified = 1;
        
        // ADICIONAR RESTRIÇÕES DE ACESSO
        if (!empty($prerequisite_ids)) {
            $conditions = [];
            $showc = [];
            foreach ($prerequisite_ids as $cmid) {
                $conditions[] = (object)[
                    'type' => 'completion',
                    'cm' => (int)$cmid,
                    'e' => 1 // State: Complete
                ];
                $showc[] = true;
            }
            $availability = (object)['op' => '&', 'c' => $conditions, 'showc' => $showc];
            $moduleinfo->availability = json_encode($availability);
        }

        $info = \add_moduleinfo($moduleinfo, $course);
        rebuild_course_cache($course->id);
        
        // Processar imagens do PDF
        try {
            $page_id = (int)$info->instance;
            $context = \context_module::instance($info->coursemodule);
            
            // Tentar descobrir a pasta de imagens do JSON (ou o nome do ficheiro)
            $image_folder = $activity['image_folder'] ?? $activity['source_file'] ?? '';
            // Se vier o nome do ficheiro (ex: Redes_3_Cap_08.pdf), remover a extensão .pdf
            $image_folder = str_ireplace('.pdf', '', $image_folder);

            $new_content = \local_wsmanageactivities\local\image_processor::process_placeholders(
                $moduleinfo->content, 
                $context->id, 
                'mod_page', 
                'content', 
                0,
                $image_folder,
                $course_id
            );
            
            if ($new_content !== $moduleinfo->content) {
                $DB->set_field('page', 'content', $new_content, ['id' => $page_id]);
            }
        } catch (\Throwable $e) {
            // Log silencioso do erro de imagens
        }

        return (int)$info->coursemodule;
    }
    
    public static function create_quiz($course_id, $activity, $json_data = null, $section = 1, $prerequisite_ids = []) {
        global $DB, $CFG, $USER;
        require_once(__DIR__ . '/QuestionCreator.php');
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/questionlib.php');
        self::clear_pending_transactions();
        
        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
        course_create_sections_if_missing($course, $section);
        
        $module = $DB->get_record('modules', ['name' => 'quiz'], '*', MUST_EXIST);
        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = 'quiz'; $moduleinfo->module = (int)$module->id; $moduleinfo->course = (int)$course_id; $moduleinfo->section = $section;
        $moduleinfo->name = self::force_string($activity['name'] ?? 'Quiz AI');
        $moduleinfo->intro = self::force_string($activity['intro'] ?? '');
        $moduleinfo->introformat = 1;
        $moduleinfo->preferredbehaviour = 'deferredfeedback';
        $moduleinfo->attempts = (int)($activity['max_attempts'] ?? 0); 
        $moduleinfo->grademethod = 1; $moduleinfo->decimalpoints = 2;
        $moduleinfo->grade = (float)($activity['grade'] ?? 20.0);
        $moduleinfo->reviewattempt = 4368; $moduleinfo->reviewcorrectness = 4368; $moduleinfo->reviewmarks = 4368;
        $moduleinfo->reviewspecificfeedback = 4368; $moduleinfo->reviewgeneralfeedback = 4368; $moduleinfo->reviewrightanswer = 4368; $moduleinfo->reviewoverallfeedback = 4368;
        $moduleinfo->visible = 1; $moduleinfo->completion = 2; $moduleinfo->completionusegrade = 1; $moduleinfo->completionpass = 1;
        $moduleinfo->gradepass = (float)($activity['passing_score'] ?? 15.0);
        $moduleinfo->cmidnumber = ''; $moduleinfo->timeopen = 0; $moduleinfo->timeclose = 0; 
        $moduleinfo->timelimit = (int)($activity['timelimit'] ?? 0);
        
        // Novos campos para Moodle 5.x (OBRIGATÓRIOS)
        $moduleinfo->questionsperpage = 1; $moduleinfo->shuffleanswers = 1; $moduleinfo->sumgrades = 0;
        $moduleinfo->navmethod = 'free'; $moduleinfo->overduehandling = 'autoabandon'; $moduleinfo->browsersecurity = '-';
        $moduleinfo->quizpassword = ''; $moduleinfo->subnet = ''; $moduleinfo->delay1 = 0; $moduleinfo->delay2 = 0;
        $moduleinfo->showuserpicture = 0; $moduleinfo->showblocks = 0; $moduleinfo->completionminattempts = 0;
        $moduleinfo->allowofflineattempts = 0;
        $moduleinfo->reviewmaxmarks = 4368;
        $moduleinfo->timemodified = time();
        $moduleinfo->timecreated = time();

        // ADICIONAR RESTRIÇÕES DE ACESSO (O Quiz só aparece se as páginas anteriores estiverem completas)
        if (!empty($prerequisite_ids)) {
            $conditions = [];
            $showc = [];
            foreach ($prerequisite_ids as $cmid) {
                $conditions[] = (object)[
                    'type' => 'completion',
                    'cm' => (int)$cmid,
                    'e' => 1 // State: Complete (visto verde)
                ];
                $showc[] = true; // Mostrar a restrição ao aluno
            }
            
            $availability = (object)[
                'op' => '&', // ALL conditions must be met
                'c' => $conditions,
                'showc' => $showc
            ];
            $moduleinfo->availability = json_encode($availability);
        }

        try {
            $info = \add_moduleinfo($moduleinfo, $course);
            rebuild_course_cache($course->id);
            \context_helper::reset_caches();
            $quiz_context = \context_module::instance($info->coursemodule);
        } catch (\Throwable $e) {
            // echo "      ❌ Erro fatal ao criar Quiz: " . $e->getMessage() . "\n";
            throw $e;
        }

        $quiz = $DB->get_record('quiz', ['id' => $info->instance], '*', MUST_EXIST);

        // FORÇAR REVISÃO E CONCLUSÃO (Moodle às vezes ignora no add_moduleinfo)
        $DB->set_field('quiz', 'reviewattempt', 4368, ['id' => $quiz->id]);
        $DB->set_field('quiz', 'reviewcorrectness', 4368, ['id' => $quiz->id]);
        $DB->set_field('quiz', 'reviewmarks', 4368, ['id' => $quiz->id]);
        $DB->set_field('quiz', 'reviewspecificfeedback', 0, ['id' => $quiz->id]);
        $DB->set_field('quiz', 'reviewgeneralfeedback', 4368, ['id' => $quiz->id]);
        $DB->set_field('quiz', 'reviewrightanswer', 0, ['id' => $quiz->id]);
        $DB->set_field('quiz', 'reviewoverallfeedback', 0, ['id' => $quiz->id]);
        $DB->set_field('quiz', 'reviewmaxmarks', 4368, ['id' => $quiz->id]);

        // 1. OBTER CATEGORIA PADRÃO DO QUIZ
        $default_category = \question_get_default_category($quiz_context->id);
        // echo "      📂 Banco de Questões do Quiz: {$default_category->name} (ID: {$default_category->id})\n";

        // 2. IMPORTAR QUESTÕES PARA ESTA CATEGORIA
        if (!empty($json_data['question_banks'])) {
            $q_count = 0;
            foreach ($json_data['question_banks'] as $bank) {
                if (!empty($bank['questions'])) {
                    foreach ($bank['questions'] as $q_data) {
                        $qid = \local_wsmanageactivities\importer\QuestionCreator::create_question($default_category->id, $quiz_context->id, $q_data);
                        if ($qid) $q_count++;
                    }
                }
            }
            // echo "      ✅ Criadas $q_count questões diretamente no Banco do Quiz.\n";
        }

        // 3. VINCULAR QUESTÕES ALEATÓRIAS AO QUIZ
        try {
            $count_limit = (int)($activity['questions_from_bank']['count'] ?? 10);
            
            $sql_all = "SELECT qbe.id as entryid 
                        FROM {question_bank_entries} qbe 
                        JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id 
                        WHERE qbe.questioncategoryid = ? AND qv.status = 'ready'
                        GROUP BY qbe.id 
                        ORDER BY RAND()"; 
            $entries = $DB->get_records_sql($sql_all, [$default_category->id], 0, $count_limit);
            
            if ($entries) {
                $slot_num = 1;
                foreach ($entries as $entry) { 
                    $slot = new \stdClass();
                    $slot->quizid = (int)$quiz->id; $slot->slot = $slot_num; $slot->page = $slot_num;
                    $slot->maxmark = 1.0; $slot->requireprevious = 0;
                    $slot->id = $DB->insert_record('quiz_slots', $slot);

                    $ref = new \stdClass();
                    $ref->usingcontextid = (int)$quiz_context->id;
                    $ref->component = 'mod_quiz'; $ref->questionarea = 'slot'; $ref->itemid = (int)$slot->id;
                    $ref->questionbankentryid = (int)$entry->entryid; $ref->version = null;
                    $DB->insert_record('question_references', $ref);
                    $slot_num++;
                }
                
                if (class_exists('\mod_quiz\quiz_settings')) {
                    $quizobj = \mod_quiz\quiz_settings::create($quiz->id);
                    \mod_quiz\grade_calculator::create($quizobj)->recompute_quiz_sumgrades();
                }
                // echo "      ✅ Associadas " . ($slot_num-1) . " perguntas ao Quiz.\n";
            }
        } catch (\Throwable $e) {
            // echo "      ❌ Erro na associação de questões: " . $e->getMessage() . "\n";
        }
        return $info->coursemodule;
    }

    public static function set_course_completion($course_id, $quiz_cm_id) {
        global $DB;
        try {
            // 1. Ativar conclusão no curso
            $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
            if (!$course->enablecompletion) {
                $course->enablecompletion = 1;
                $DB->update_record('course', $course);
            }

            // 2. Definir critério: Aprovação no Quiz
            $quiz_instance = $DB->get_field('course_modules', 'instance', ['id' => $quiz_cm_id]);
            
            $criteria = new \stdClass();
            $criteria->course = $course_id;
            $criteria->criteriatype = 4; // COMPLETION_CRITERIA_TYPE_ACTIVITY
            $criteria->module = 'quiz';
            $criteria->moduleinstance = $quiz_instance;
            
            if (!$DB->record_exists('course_completion_criteria', ['course' => $course_id, 'moduleinstance' => $quiz_instance])) {
                $DB->insert_record('course_completion_criteria', $criteria);
            }
            
            // 3. Definir método de agregação (Geralmente 1 = ALL)
            if (!$DB->record_exists('course_completion_aggr_methd', ['course' => $course_id, 'criteriatype' => null])) {
                $aggr = new \stdClass();
                $aggr->course = $course_id;
                $aggr->criteriatype = null;
                $aggr->method = 1;
                $DB->insert_record('course_completion_aggr_methd', $aggr);
            }
        } catch (\Throwable $e) {
            // echo "      ⚠️ Erro ao configurar conclusão de curso: " . $e->getMessage() . "\n";
        }
    }

    public static function create_feedback($course_id) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/modlib.php');
        
        try {
            $module = $DB->get_record('modules', ['name' => 'feedback'], '*', MUST_EXIST);
            $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
            
            $moduleinfo = new \stdClass();
            $moduleinfo->modulename = 'feedback';
            $moduleinfo->module = (int)$module->id;
            $moduleinfo->course = (int)$course_id;
            $moduleinfo->section = 1;
            $moduleinfo->name = 'Avaliação da Formação';
            $moduleinfo->intro = 'A sua opinião é fundamental para melhorarmos as nossas formações.';
            $moduleinfo->introformat = 1;
            $moduleinfo->anonymous = 1; 
            $moduleinfo->publish_stats = 1;
            $moduleinfo->multiple_submit = 0;
            $moduleinfo->completionsubmit = 1; 
            $moduleinfo->visible = 1;
            $moduleinfo->completion = 2;
            $moduleinfo->timemodified = time();
            $moduleinfo->page_after_submit = ''; 
            $moduleinfo->site_after_submit = '';
            
            $info = \add_moduleinfo($moduleinfo, $course);
            
            // Importar itens do XML simplificado
            $xml_path = $CFG->dirroot . '/local/wsmanageactivities/feedback_formacao.xml';
            if (file_exists($xml_path)) {
                self::import_feedback_xml($info->instance, $xml_path);
            }
            
            return $info->coursemodule;
        } catch (\Throwable $e) {
            // echo "      ⚠️ Erro ao criar atividade de Feedback: " . $e->getMessage() . "\n";
            return null;
        }
    }

    private static function import_feedback_xml($feedback_id, $xml_path) {
        global $DB;
        $xml = simplexml_load_file($xml_path);
        if (!$xml) return;
        
        $pos = 1;
        foreach ($xml->ITEMS->ITEM as $item) {
            $newitem = new \stdClass();
            $newitem->feedback = $feedback_id;
            $newitem->template = 0;
            $newitem->name = (string)$item->ITEMTEXT;
            $newitem->label = (string)$item->ITEMLABEL;
            $newitem->typ = (string)$item->attributes()['TYPE'];
            $newitem->presentation = (string)$item->PRESENTATION;
            $newitem->hasvalue = ($newitem->typ === 'textarea') ? 0 : 1;
            $newitem->position = $pos++;
            $newitem->required = (int)$item->attributes()['REQUIRED'];
            $newitem->dependitem = 0;
            $newitem->dependvalue = '';
            $newitem->options = '';
            
            $DB->insert_record('feedback_item', $newitem);
        }
    }

    public static function add_navigation_to_page($cm_id, $prev, $next) { self::add_nav($cm_id, 'page', $prev, $next); }
    public static function add_navigation_to_quiz($cm_id, $prev, $next) { self::add_nav($cm_id, 'quiz', $prev, $next); }
    
    private static function add_nav($cm_id, $type, $prev_cm_id = null, $next_cm_id = null) {
        global $DB, $CFG;
        try {
            $cm = $DB->get_record('course_modules', ['id' => $cm_id], '*', MUST_EXIST);
            $record = $DB->get_record($type, ['id' => $cm->instance], '*', MUST_EXIST);
            
            $nav = '<div class="ailms-nav-container" style="margin-top:40px; padding:20px; border-top:2px solid #eee; display:flex; justify-content:space-between; align-items:center;">';
            
            if ($prev_cm_id) {
                $prev_url = $CFG->wwwroot . "/mod/" . $DB->get_field('modules', 'name', ['id' => $DB->get_field('course_modules', 'module', ['id' => $prev_cm_id])]) . "/view.php?id=" . $prev_cm_id;
                $nav .= '<a href="'.$prev_url.'" style="padding:10px 20px; background:#f3f4f6; color:#333; text-decoration:none; border-radius:5px;">⬅️ Anterior</a>';
            } else {
                $nav .= '<span></span>';
            }

            if ($next_cm_id) {
                $next_url = $CFG->wwwroot . "/mod/" . $DB->get_field('modules', 'name', ['id' => $DB->get_field('course_modules', 'module', ['id' => $next_cm_id])]) . "/view.php?id=" . $next_cm_id;
                $nav .= '<a href="'.$next_url.'" style="padding:10px 20px; background:#2563eb; color:white; text-decoration:none; border-radius:5px; font-weight:bold;">Próximo ➡️</a>';
            } else {
                $nav .= '<span></span>';
            }
            
            $nav .= '</div>';
            
            if ($type === 'page') $record->content .= $nav; else $record->intro .= $nav;
            $DB->update_record($type, $record);
        } catch (\Throwable $e) {}
    }
    public static function add_completion_restriction($cm_id, $prev_cm_id, $hide_completely = false) {
        global $DB;
        if (!$prev_cm_id) return;
        try {
            $cm = $DB->get_record('course_modules', ['id' => $cm_id], '*', MUST_EXIST);
            // e:1 = Completa | showc: visibilidade se bloqueada
            $showc = $hide_completely ? false : true;
            $cm->availability = json_encode(['op' => '&', 'c' => [['type' => 'completion', 'cm' => (int)$prev_cm_id, 'e' => 1]], 'showc' => [$showc]]);
            $DB->update_record('course_modules', $cm);
            
            \rebuild_course_cache($cm->course, true);
        } catch (\Throwable $e) {}
    }
}
