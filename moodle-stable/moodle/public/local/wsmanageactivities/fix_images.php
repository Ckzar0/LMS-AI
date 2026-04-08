<?php
/**
 * Ferramenta de Substituição de Imagens e Tabelas - v3.1 (Complete Fix)
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$courseid = optional_param('courseid', 0, PARAM_INT);
$pageid = optional_param('pageid', 0, PARAM_INT);
$oldimg_full = optional_param('oldimg', '', PARAM_RAW); // Pode ser a tag inteira ou URL
$newimg = optional_param('newimg', '', PARAM_RAW);
$subfolder = optional_param('subfolder', '', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/local/wsmanageactivities/fix_images.php'), ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title("Gerir Imagens e Tabelas");
$PAGE->set_heading("🖼️ Ajustar Conteúdo Visual");

echo $OUTPUT->header();

$img_base_dir = __DIR__ . '/extracted_images/';
// Mover assets para fora da pasta public para contornar bloqueios do Moodle 5.1
$public_assets_dir = $CFG->dirroot . '/course_assets/' . ($courseid ? $courseid . '/' : '');

// --- LÓGICA DE PROCESSAMENTO ---
if ($pageid && confirm_sesskey()) {
    $page = $DB->get_record('page', ['id' => $pageid], '*', MUST_EXIST);

    $final_url = "";
    // A URL aponta agora para a raiz do servidor web
    $public_assets_url = $CFG->wwwroot . '/course_assets/' . ($courseid ? $courseid . '/' : '');


    // 1. Processar Upload Direto
    if (!empty($_FILES['uploadimg']['name'])) {
        $file = $_FILES['uploadimg'];
        $name = clean_param($file['name'], PARAM_FILE);
        $final_filename = "up_" . time() . "_" . $name;
        
        // Garantir que o diretório existe dentro do container
        if (!is_dir($public_assets_dir)) {
            mkdir($public_assets_dir, 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $public_assets_dir . $final_filename)) {
            // URL RELATIVO PARA O NAVEGADOR
            $final_url = $public_assets_url . $final_filename;
        } else {
            echo $OUTPUT->notification("Erro ao mover ficheiro para: " . $public_assets_dir, 'notifyproblem');
        }
    } 
    // 2. Processar Galeria
    elseif ($newimg) {
        $source = $img_base_dir . $newimg;
        if (file_exists($source)) {
            if (!is_dir($public_assets_dir)) mkdir($public_assets_dir, 0777, true);
            $fname = basename($newimg);
            copy($source, $public_assets_dir . $fname);
            $final_url = $public_assets_url . $fname;
        }
    }

    if ($final_url) {
        // Se estivermos a substituir um placeholder laranja (ailms-placeholder-box), 
        // temos de trocar o div inteiro por uma tag img limpa
        $new_img_tag = '<p dir="ltr" style="text-align: center;"><img src="'.$final_url.'" class="img-fluid" width="600" height="auto"></p>';
        
        // Tentar encontrar o bloco pai no conteúdo
        if (strpos($page->content, $oldimg_full) !== false) {
            $new_content = str_replace($oldimg_full, $new_img_tag, $page->content);
        } else {
            // Fallback para URL simples se não encontrar o bloco completo
            $new_content = str_replace($oldimg_full, $final_url, $page->content);
        }
        
        $DB->set_field('page', 'content', $new_content, ['id' => $pageid]);
        echo $OUTPUT->notification("Substituído com sucesso!", 'notifysuccess');
        rebuild_course_cache($page->course, true);
        $page->content = $new_content; // Atualizar para exibição imediata abaixo
    }
}

?>
<style>
    .img-card { display: flex; align-items: stretch; gap: 20px; background: #fff; border: 1px solid #d1d5db; margin-bottom: 25px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .preview-current { width: 300px; display: flex; flex-direction: column; align-items: center; background: #f9fafb; border-radius: 8px; padding: 10px; }
    .preview-current img { max-width: 100%; max-height: 200px; border: 1px solid #eee; }
    .actions { flex-grow: 1; display: flex; flex-direction: column; gap: 15px; }
    .box-upload { background: #eff6ff; padding: 15px; border-radius: 8px; border: 1px dashed #3b82f6; }
    .box-gallery { background: #f3f4f6; padding: 15px; border-radius: 8px; }
    .btn-apply { background: #059669; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; width: fit-content; }
    .btn-apply:hover { background: #047857; }
    .preview-new-container { margin-top: 10px; text-align: center; background: #fff; padding: 10px; border-radius: 6px; display: none; border: 1px solid #ddd; }
    .preview-new-container img { max-width: 200px; max-height: 150px; }
    .label-pretendia { background: #fee2e2; color: #b91c1c; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; margin-bottom: 10px; display: block; width: 100%; text-align: center; }
</style>

<script>
function showNewPreview(select, id) {
    const container = document.getElementById('new_container_' + id);
    const img = document.getElementById('new_img_' + id);
    if (select.value) {
        const url = '<?php echo $CFG->wwwroot; ?>/local/wsmanageactivities/extracted_images/' + select.value;
        img.src = url;
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}
function previewLocalFile(input, id) {
    const container = document.getElementById('new_container_' + id);
    const img = document.getElementById('new_img_' + id);
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            container.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php
echo '<div style="max-width: 1100px; margin: 0 auto; padding: 20px;">';
$back_url = new moodle_url('/local/wsmanageactivities/courses.php');
echo '<div style="margin-bottom: 20px;"><a href="' . $back_url . '" style="text-decoration: none; color: #6B46C1; font-weight: bold;">← Voltar para Meus Cursos</a></div>';

if (!$courseid) {
    echo '<h3>Selecione um curso para gerir:</h3>';
    $courses = $DB->get_records('course', [], 'id DESC', 'id, fullname', 0, 20);
    foreach ($courses as $c) { 
        if ($c->id > 1) {
            $c_url = new moodle_url('/local/wsmanageactivities/fix_images.php', ['courseid' => $c->id]);
            echo '<div style="margin-bottom:10px;"><a href="'.$c_url.'" style="font-size:1.1em; font-weight:bold;">📁 '.$c->fullname.'</a></div>';
        }
    }
} else {
    echo '<h2>Curso: '.$DB->get_field('course', 'fullname', ['id' => $courseid]).'</h2>';
    $pages = $DB->get_records('page', ['course' => $courseid]);
    
    // LISTA DE IMAGENS AGRUPADA
    $all_available = [];
    if (is_dir($img_base_dir)) {
        $it = new RecursiveDirectoryIterator($img_base_dir);
        foreach (new RecursiveIteratorIterator($it) as $file) {
            if ($file->isFile() && preg_match('/\.(jpg|jpeg|png)$/i', $file->getFilename())) {
                $f_folder = trim(str_replace($img_base_dir, '', $file->getPath()), '/');
                $all_available[] = ['name' => $file->getFilename(), 'folder' => $f_folder];
            }
        }
    }
    // Ordenar para que a pasta do curso atual apareça primeiro (opcional)
    sort($all_available);

    foreach ($pages as $page) {
        // Encontrar: 
        // 1. Placeholders laranja (ailms-placeholder-box)
        // 2. Imagens normais
        // 3. Spans de erro de imagem em falta
        preg_match_all('/<div class="ailms-placeholder-box"[^>]*>.*?<\/div>/is', $page->content, $div_matches);
        preg_match_all('/<img[^>]+src="([^"]+)"[^>]*>/i', $page->content, $img_matches, PREG_SET_ORDER);
        preg_match_all('/<span[^>]+style="color:red;[^>]*>\[IMAGEM EM FALTA: (.*?)\]<\/span>/is', $page->content, $error_matches, PREG_SET_ORDER);

        $items_to_fix = [];
        // Adicionar placeholders primeiro
        foreach ($div_matches[0] as $div_html) {
            $label = "Placeholder de Tabela";
            $hint = "";
            if (preg_match('/\[SUBSTITUIR POR PRINT DA TABELA - (PÁG \d+)\]/', $div_html, $lm)) {
                $label = $lm[0];
                $hint = "Esta tabela deve ser extraída da página " . $lm[1] . " do manual original.";
            }
            $items_to_fix[] = ['type' => 'placeholder', 'html' => $div_html, 'label' => $label, 'hint' => $hint];
        }
        
        // Adicionar erros de "Imagem em Falta"
        foreach ($error_matches as $em) {
            $items_to_fix[] = ['type' => 'error', 'html' => $em[0], 'label' => "FALTA: " . $em[1], 'hint' => "A IA solicitou a imagem do placeholder " . $em[1] . ", mas não foi encontrada na extração automática."];
        }

        // Adicionar imagens normais
        foreach ($img_matches as $im) {
            $full_img_tag = $im[0];
            $img_url_found = $im[1];
            
            $label = 'Imagem do Curso';
            $hint = "Imagem sem legenda detetada.";
            
            // 1. Tentar extrair do atributo data-legend (que o image_processor agora preenche com a legenda completa)
            if (preg_match('/data-legend\s*=\s*["\']([^"]+)["\']/i', $full_img_tag, $pm)) {
                $hint = htmlspecialchars_decode($pm[1]);
            } 
            // 2. Fallback para alt
            elseif (preg_match('/alt\s*=\s*["\']([^"]+)["\']/i', $full_img_tag, $pm) && !in_array($pm[1], ['Imagem do Curso', 'Figura'])) {
                $hint = $pm[1];
            }
            // 3. Fallback para title (contém o placeholder técnico)
            elseif (preg_match('/title\s*=\s*["\']([^"]+)["\']/i', $full_img_tag, $pm)) {
                $hint = "Ref: " . $pm[1];
            }
            
            $items_to_fix[] = ['type' => 'image', 'html' => $full_img_tag, 'url' => $img_url_found, 'label' => $label, 'hint' => $hint];
        }


        if (empty($items_to_fix)) continue;

        echo '<div style="background: #f3f4f6; padding: 20px; border-radius: 12px; margin-bottom: 40px; border: 1px solid #e5e7eb;">';
        echo '<h3 style="margin-top:0;">📄 Página: '.s($page->name).'</h3>';
        
        $idx = 0;
        foreach ($items_to_fix as $item) {
            $idx++;
            $unique_id = "item_{$page->id}_{$idx}";
            
            // Extrair o que a IA pediu (do alt ou title se for imagem, ou da label se for placeholder)
            $requested_label = $item['label'];
            if ($item['type'] === 'image') {
                if (preg_match('/\[\[IMG_P\d+_\d+\]\]/i', $item['html'], $pm)) $requested_label = "Imprescindível: " . $pm[0];
            }

            echo '<div class="img-card">';
            // LADO ESQUERDO: PREVIEW ATUAL
            echo '<div class="preview-current">';
            echo '<span class="label-pretendia" style="background:#3b82f6; color:white;">' . $requested_label . '</span>';
            if ($item['type'] === 'placeholder' || $item['type'] === 'error') {
                echo '<img src="https://placehold.co/300x200?text=Aguardando+Substituicao" style="opacity:0.5;">';
            } else {
                echo '<img src="'.$item['url'].'" onerror="this.src=\'https://placehold.co/300x200?text=Erro+ao+Carregar\';">';
            }
            echo '</div>';

            // LADO DIREITO: AÇÕES
            echo '<div class="actions">';
            if (!empty($item['hint'])) {
                echo '<div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 13px; color: #92400e;">';
                echo '💡 <strong>Legenda do Sistema:</strong> ' . s($item['hint']);
                echo '</div>';
            }
            echo '<form method="POST" enctype="multipart/form-data">';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
            echo '<input type="hidden" name="pageid" value="'.$page->id.'">';
            echo '<input type="hidden" name="oldimg" value="'.s($item['html']).'">';

            echo '<div class="box-upload">';
            echo '<strong>📤 Carregar imagem (Computador):</strong><br>';
            echo '<input type="file" name="uploadimg" accept="image/*" onchange="previewLocalFile(this, \''.$unique_id.'\')" style="margin-top:10px;">';
            echo '</div>';

            echo '<div style="text-align:center; font-weight:bold; color:#999; font-size:12px;">-- OU --</div>';

            echo '<div class="box-gallery">';
            echo '<strong>🖼️ Escolher da Galeria do PDF:</strong><br>';
            echo '<select name="newimg" onchange="showNewPreview(this, \''.$unique_id.'\');" style="width:100%; margin-top:5px; padding:5px;">';
            echo '<option value="">-- Selecione uma imagem --</option>';
            foreach ($all_available as $avail) {
                $val = ($avail['folder'] ? $avail['folder'] . '/' : '') . $avail['name'];
                echo '<option value="'.$val.'">['.($avail['folder']?:'Raiz').'] '.$avail['name'].'</option>';
            }
            echo '</select>';
            echo '</div>';

            // PREVIEW DA NOVA ESCOLHA
            echo '<div id="new_container_'.$unique_id.'" class="preview-new-container">';
            echo '<span style="font-size:10px; color:#059669; font-weight:bold;">Nova Imagem Selecionada:</span><br>';
            echo '<img id="new_img_'.$unique_id.'" src="">';
            echo '</div>';

            echo '<button type="submit" class="btn-apply">✅ APLICAR E SUBSTITUIR</button>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
}
echo '</div>';
echo $OUTPUT->footer();
