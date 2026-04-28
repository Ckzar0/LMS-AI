<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// LER O PROMPT INTEGRAL DO FICHEIRO
$prompt_path = __DIR__ . '/master_prompt.md';
$full_prompt_content = file_exists($prompt_path) ? file_get_contents($prompt_path) : "Erro: Ficheiro de prompt não encontrado.";

$PAGE->set_url(new moodle_url('/local/wsmanageactivities/upload.php'));
$PAGE->set_context($context);
$PAGE->set_title("AI LMS - Fábrica de Cursos");

echo $OUTPUT->header();

echo '<div style="max-width: 800px; margin: 0 auto; padding: 20px;">';
$back_url = new moodle_url('/local/wsmanageactivities/courses.php');
echo '<div style="margin-bottom: 20px;"><a href="' . $back_url . '" style="text-decoration: none; color: #6B46C1; font-weight: bold;">← Voltar para Meus Cursos</a></div>';

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'upload' && confirm_sesskey()) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/local/wsmanageactivities/classes/CourseManager.php');
    require_once($CFG->dirroot . '/local/wsmanageactivities/classes/QuestionBankManager.php');
    require_once($CFG->dirroot . '/local/wsmanageactivities/classes/importer/QuestionCreator.php');
    require_once($CFG->dirroot . '/local/wsmanageactivities/classes/importer/ActivityCreator.php');
    
    echo '<div style="background:#000;color:#0f0;padding:20px;font-family:monospace;max-height:600px;overflow-y:auto;border-radius:8px;">';
    
    try {
        if (!isset($_FILES['course_structure'])) throw new Exception("Sem ficheiro selecionado.");
        $f = $_FILES['course_structure'];
        $json_content = file_get_contents($f['tmp_name']);
        $data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao ler JSON: " . json_last_error_msg());
        }
        
        if (empty($data)) {
            throw new Exception("O ficheiro JSON está vazio ou não contém dados válidos.");
        }
        
        echo "🚀 A processar: {$f['name']}\n";
        echo "────────────────────────────────────────\n\n";
        
        $name = $data['course_name'] ?? $data['name'] ?? $data['course']['name'] ?? 'Curso Gerado';
        $shortname = $data['course_shortname'] ?? $data['shortname'] ?? 'IA_' . time();
        $global_folder = $data['source_file'] ?? $data['image_folder'] ?? '';
        
        // --- AUTOMAÇÃO DE EXTRAÇÃO DE PDF (VERSÃO DOCKER-READY) ---
        if (!empty($global_folder)) {
            $pdf_name = str_ireplace('.pdf', '', $global_folder);
            
            // Procurar na pasta Cursos dentro do projeto (visível pelo Docker)
            // No Moodle 5.1, o plugin está em public/local/, por isso a pasta Cursos está 3 níveis acima
            $pdf_path_docker = $CFG->dirroot . "/../Cursos/" . $pdf_name . ".pdf";
            $target_dir = __DIR__ . "/extracted_images/" . $pdf_name;
            
            if (file_exists($pdf_path_docker)) {
                echo "📦 PDF localizado: $pdf_name.pdf\n⏳ A extrair imagens... (Aguarde)\n";
                
                // Limpar pasta anterior para garantir extração fresca
                if (is_dir($target_dir)) {
                    exec("rm -rf \"$target_dir\"/*");
                } else {
                    mkdir($target_dir, 0777, true);
                }

                // Extração Ultra-Rápida e Inteligente
                echo "⏳ A extrair imagens... (Aguarde)\n";
                exec("pdfimages -p -j \"$pdf_path_docker\" \"$target_dir/img\" 2>&1", $exec_out);

                // --- CHAMAR OTIMIZADOR PYTHON ---
                $py_script = __DIR__ . "/optimize_images.py";
                if (file_exists($py_script)) {
                    echo "🧹 A otimizar imagens e remover duplicados...\n";
                    exec("python3 \"$py_script\" \"$target_dir\" 2>&1", $py_out);
                    foreach($py_out as $line) echo "   $line\n";
                }
                
                $final_count = count(glob("$target_dir/*.jpg"));
                echo "   ✅ Extração inteligente concluída ($final_count imagens).\n\n";
            }
        }

        $course_manager = new \local_wsmanageactivities\CourseManager();
        $course_id = $course_manager->process_course(['name' => $name, 'shortname' => $shortname]);
        echo "✅ Curso Criado: [ID: $course_id] | [Código: $shortname] | [Nome: $name]\n\n";
        
        // As questões serão processadas dentro do Quiz para evitar erros de contexto
        if (!empty($data['activities'])) {
            echo "🎯 A criar Atividades e Conteúdos...\n";
            $all_cms = [];
            $success_count = 0;
            $error_count = 0;
            $last_quiz_cm = null;

            foreach ($data['activities'] as $i => $activity) {
                global $DB;
                $n = $i + 1;
                $t = $activity['type'] ?? 'page';
                
                // Injetar pasta global se a atividade não tiver uma local
                if (empty($activity['image_folder']) && empty($activity['source_file'])) {
                    $activity['image_folder'] = $global_folder;
                }
                
                try {
                    if ($t === 'page') {
                        $cm = \local_wsmanageactivities\importer\ActivityCreator::create_page($course_id, $activity);
                        echo "   [$n] Página: {$activity['name']} (CM: $cm)\n";
                        $all_cms[] = ['type' => 'page', 'cm_id' => $cm];
                        $success_count++;
                    } elseif ($t === 'quiz') {
                        // PASSAMOS O JSON COMPLETO PARA O QUIZ EXTRAIR O BANCO SE PRECISAR
                        $cm = \local_wsmanageactivities\importer\ActivityCreator::create_quiz($course_id, $activity, $data);
                        echo "   [$n] Quiz: {$activity['name']} (CM: $cm)\n";
                        $all_cms[] = ['type' => 'quiz', 'cm_id' => $cm];
                        $success_count++;
                        $last_quiz_cm = $cm;
                    }
                } catch (\Throwable $e) {
                    echo "   ❌ Erro no item $n: " . $e->getMessage() . "\n";
                    $error_count++;
                }
            }
            
            echo "\n🧭 A configurar Navegação Simples...\n";
            for ($idx = 0; $idx < count($all_cms); $idx++) {
                $cm_id = $all_cms[$idx]['cm_id'];
                $type = $all_cms[$idx]['type'];
                $prev_cm = ($idx > 0) ? $all_cms[$idx - 1]['cm_id'] : null;
                $next_cm = ($idx < count($all_cms) - 1) ? $all_cms[$idx + 1]['cm_id'] : null;
                
                if ($type === 'page') {
                    \local_wsmanageactivities\importer\ActivityCreator::add_navigation_to_page($cm_id, $prev_cm, $next_cm);
                } else {
                    \local_wsmanageactivities\importer\ActivityCreator::add_navigation_to_quiz($cm_id, $prev_cm, $next_cm);
                }
                
                // RESTRIÇÕES REMOVIDAS: O curso fica todo desbloqueado
            }

            if ($last_quiz_cm) {
                echo "\n🎓 A configurar Conclusão Automática do Curso...\n";
                \local_wsmanageactivities\importer\ActivityCreator::set_course_completion($course_id, $last_quiz_cm);
            }
            
            echo "📋 A criar Inquérito de Satisfação (Feedback)...\n";
            $feedback_cm = \local_wsmanageactivities\importer\ActivityCreator::create_feedback($course_id);
            if ($feedback_cm && $last_quiz_cm) {
                // Esconder o feedback totalmente (hide_completely = true) até o quiz ser feito
                \local_wsmanageactivities\importer\ActivityCreator::add_completion_restriction($feedback_cm, $last_quiz_cm, true);
            }
            
            echo "\n══════════════════════════════════════\n";
            echo "📊 RESUMO: $success_count Sucessos | $error_count Erros\n";
            echo "══════════════════════════════════════\n";
        }
        
        // REFRESCAR BANCO DE QUESTÕES PARA VISIBILIDADE
        \local_wsmanageactivities\importer\ActivityCreator::refresh_question_bank($course_id);
        
        purge_all_caches();
        echo "\n✅ PROCESSO CONCLUÍDO!\n";
        $url = $CFG->wwwroot . "/course/view.php?id=" . $course_id;
        echo "🔗 Ver curso: <a href='$url' style='color:#0f0;text-decoration:underline;'>$url</a>\n";
        
    } catch (Exception $e) {
        echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    }
    echo '</div>';
}
?>

<div class="ailms-panel" style="max-width: 1000px; margin: 20px auto; font-family: sans-serif; color: #333;">
    <div style="background: #1a73e8; color: white; padding: 20px; border-radius: 8px 8px 0 0;">
        <h2 style="margin:0;">🛠️ AI LMS - Painel de Configuração de Curso</h2>
    </div>

    <div style="background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 8px 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">⏱️ Duração / Profundidade:</label>
                    <select id="course_depth" class="form-control" style="width:100%; padding:10px;">
                        <option value="Resumo Executivo">Resumo Executivo - Direto ao essencial</option>
                        <option value="Profissional" selected>Profissional - Equilibrado (Padrão)</option>
                        <option value="Especialista Técnico">Especialista Técnico - Deep Dive Exaustivo</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">🏆 Dificuldade do Quiz:</label>
                    <select id="quiz_diff" class="form-control" style="width:100%; padding:10px;">
                        <option value="easy">Fácil - Verificação de conceitos básicos</option>
                        <option value="medium" selected>Média - Aplicação prática e análise</option>
                        <option value="hard">Difícil - Casos complexos e pensamento crítico</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">❓ Número de Questões:</label>
                    <input type="number" id="num_questions" value="20" min="5" max="50" class="form-control" style="width:100%; padding:10px;">
                </div>
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">⏲️ Duração do Quiz (min):</label>
                    <input type="number" id="quiz_duration" value="30" min="5" max="120" class="form-control" style="width:100%; padding:10px;">
                </div>
            </div>

            <div style="background: #fdfdfd; border: 1px dashed #ccc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h4 style="margin-top:0; color:#1a73e8;">📋 Passo 1: Copie o Prompt Master Integral</h4>
                <p style="font-size:0.9em; color:#666;">Este prompt é carregado diretamente do ficheiro mestre sem qualquer resumo.</p>
                <textarea id="dynamic_prompt" readonly style="width:100%; height:300px; padding:15px; font-family:monospace; font-size:12px; border:1px solid #eee; background:#fff; line-height:1.4;"></textarea>
                <button type="button" onclick="copyPrompt()" style="margin-top:10px; padding:10px 20px; cursor:pointer; background:#1a73e8; color:white; border:none; border-radius:4px; font-weight:bold;">📄 Copiar Prompt Completo</button>
            </div>

            <div style="background: #e8f0fe; padding: 20px; border-radius: 8px;">
                <h4 style="margin-top:0; color:#1a73e8;">📤 Passo 2: Upload do JSON Gerado</h4>
                <input type="file" name="course_structure" accept=".json" required style="width:100%; padding:10px; background:white; border:1px solid #adc6ff; border-radius:4px;">
            </div>

            <button type="submit" style="display:block; width:100%; margin-top:30px; padding:20px; background:#1a73e8; color:white; border:none; border-radius:8px; font-size:18px; font-weight:bold; cursor:pointer; box-shadow: 0 4px 0 #0d47a1;">
                🚀 Gerar Curso no Moodle
            </button>
        </form>
    </div>
</div>

<script>
// CARREGAR O TEXTO INTEGRAL DO PHP PARA O JAVASCRIPT
const rawMasterPrompt = <?php echo json_encode($full_prompt_content); ?>;

function updatePrompt() {
    let depth = document.getElementById('course_depth').value;
    let diff = document.getElementById('quiz_diff').value;
    let numQuestions = parseInt(document.getElementById('num_questions').value) || 20;
    let bankSize = numQuestions + 10;
    
    // Substituir placeholders {{DURATION}}, {{DIFFICULTY}}, {{NUM_QUESTIONS}}, {{BANK_SIZE}}
    let finalPrompt = rawMasterPrompt
        .replace(/{{DURATION}}/g, depth)
        .replace(/{{DIFFICULTY}}/g, diff)
        .replace(/{{NUM_QUESTIONS}}/g, numQuestions)
        .replace(/{{BANK_SIZE}}/g, bankSize);

    document.getElementById('dynamic_prompt').value = finalPrompt;
}

function copyPrompt() {
    let copyText = document.getElementById("dynamic_prompt");
    copyText.select();
    document.execCommand("copy");
    alert("Prompt Master Integral Copiado! Agora cole-o na sua IA.");
}

document.getElementById('course_depth').addEventListener('change', updatePrompt);
document.getElementById('quiz_diff').addEventListener('change', updatePrompt);
document.getElementById('num_questions').addEventListener('input', updatePrompt);
updatePrompt();
</script>

<?php
echo $OUTPUT->footer();
