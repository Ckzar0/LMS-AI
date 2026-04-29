<?php
/**
 * Ferramenta de Substituição de Imagens e Tabelas - v5.3 (Centered Compact Layout)
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$courseid = optional_param('courseid', 0, PARAM_INT);
$pageid = optional_param('pageid', 0, PARAM_INT);
$placeholder_id = optional_param('placeholder_id', '', PARAM_RAW); 
$newimg = optional_param('newimg', '', PARAM_RAW);
$deleteimg = optional_param('deleteimg', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/wsmanageactivities/fix_images.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title("Gerir Imagens e Tabelas");
$PAGE->set_heading("🖼️ Ajustar Conteúdo Visual");

echo $OUTPUT->header();

$img_base_dir = __DIR__ . '/extracted_images/';
$public_assets_dir = $CFG->dirroot . '/course_assets/' . ($courseid ? $courseid . '/' : '');

/**
 * Lógica de Renumeração (NÃO TOCAR)
 */
function renumber_all_figures($courseid) {
    global $DB;
    $pages = $DB->get_records('page', ['course' => $courseid], 'id ASC');
    $count = 0;
    foreach ($pages as $p) {
        $content = $p->content;
        $pattern = '/<(figure|div)[^>]*class="[^"]*ailms-figure[^"]*"[^>]*>.*?<\/\1>/is';
        if (preg_match_all($pattern, $content, $blocks)) {
            foreach ($blocks[0] as $block) {
                $count++;
                if (preg_match('/class="ailms-img-caption"[^>]*>(.*?)<\/(?:figcaption|div)>/is', $block, $lm)) {
                    $old_caption_html = $lm[0];
                    $current_text = trim(strip_tags($lm[1]));
                    
                    // Limpeza ultra-agressiva recursiva
                    $clean_regex = '/^\s*(?:Figura|Figure|Fig\.?|Tabela|Table|Tab\.?)(?:\s*\d+)?\s*[:\-\d\s\.]*/i';
                    $clean_text = $current_text;
                    for($i=0; $i<3; $i++) {
                        $clean_text = preg_replace($clean_regex, '', trim($clean_text));
                    }
                    
                    $new_text = "Figura " . $count . (!empty($clean_text) ? " - " . $clean_text : "");
                    $tag = (stripos($old_caption_html, '<figcaption') !== false) ? 'figcaption' : 'div';
                    $style = (stripos($old_caption_html, '<figcaption') !== false) 
                             ? 'style="margin-top:15px; font-style:italic; font-weight:bold; color:#111; text-align:center;"'
                             : 'style="margin-top:15px; font-style:italic; font-weight:bold; color:#333;"';
                    $new_caption_html = '<' . $tag . ' class="ailms-img-caption" ' . $style . '>' . $new_text . '</' . $tag . '>';
                    $new_block = str_replace($old_caption_html, $new_caption_html, $block);
                    $content = str_replace($block, $new_block, $content);
                    $block = $new_block;
                }
            }
            $DB->set_field('page', 'content', $content, ['id' => $p->id]);
        }
    }
}

// --- LÓGICA DE PROCESSAMENTO ---
if ($pageid && confirm_sesskey() && $placeholder_id) {
    $page = $DB->get_record('page', ['id' => $pageid], '*', MUST_EXIST);
    $final_url = "";
    $public_assets_url = $CFG->wwwroot . '/course_assets/' . ($courseid ? $courseid . '/' : '');

    if (!empty($_FILES['uploadimg']['name'])) {
        $file = $_FILES['uploadimg'];
        if (!is_dir($public_assets_dir)) mkdir($public_assets_dir, 0777, true);
        $fname = "up_" . time() . "_" . clean_param($file['name'], PARAM_FILE);
        if (move_uploaded_file($file['tmp_name'], $public_assets_dir . $fname)) $final_url = $public_assets_url . $fname;
    } elseif ($newimg) {
        $source = $img_base_dir . $newimg;
        if (file_exists($source)) {
            if (!is_dir($public_assets_dir)) mkdir($public_assets_dir, 0777, true);
            $fname = basename($newimg);
            @copy($source, $public_assets_dir . $fname);
            $final_url = $public_assets_url . $fname;
        }
    }

    $pattern_find = '/<(figure|div)[^>]*data-placeholder="' . preg_quote($placeholder_id, '/') . '"[^>]*>.*?<\/\1>/is';
    if (preg_match($pattern_find, $page->content, $m)) {
        $old_block = $m[0];
        
        if ($deleteimg) {
            $new_block = "";
        } elseif ($final_url) {
            $legend = "";
            if (preg_match('/class="ailms-img-caption"[^>]*>(.*?)<\/(?:figcaption|div)>/is', $old_block, $lm)) {
                $legend = trim(strip_tags($lm[1]));
                // Limpeza agressiva: remove variações de "Figura X" ou "Tabela X"
                $clean_regex = '/^\s*(?:Figura|Figure|Fig\.?|Tabela|Table|Tab\.?)\s*[:\-\d\s\.]*/i';
                $legend = preg_replace($clean_regex, '', $legend);
                $legend = preg_replace($clean_regex, '', $legend);
            }
            $new_block = '<figure class="ailms-figure" data-placeholder="'.$placeholder_id.'" style="text-align: center; margin: 30px auto; max-width: 90%; border: 1px solid #ddd; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">' .
                           '<img src="' . $final_url . '" data-legend="'.htmlspecialchars($legend).'" class="img-fluid" style="border-radius: 8px; max-width: 100%; height: auto;">' .
                           '<figcaption class="ailms-img-caption" style="margin-top:15px; font-style:italic; font-weight:bold; color:#111; text-align:center;">' . trim($legend) . '</figcaption>' .
                           '</figure>';
        } else {
            // Se clicar em atualizar sem nova imagem, apenas avisamos e continuamos para renderizar o layout
            echo $OUTPUT->notification("Nenhuma nova imagem selecionada. Mantendo original.", 'notifyproblem');
            $new_block = $old_block;
        }

        if ($new_block !== $old_block || $deleteimg) {
            $new_content = str_replace($old_block, $new_block, $page->content);
            $DB->set_field('page', 'content', $new_content, ['id' => $pageid]);
            renumber_all_figures($courseid);
            echo $OUTPUT->notification("Ação concluída!", 'notifysuccess');
            rebuild_course_cache($page->course, true);
        }
    }
}

// --- INTERFACE ---
// Container centralizador mais estreito (1000px)
echo '<div style="max-width:1000px; margin:0 auto;">';

if (!$courseid) {
    echo '<h2>Selecione um Curso:</h2>';
    $courses = $DB->get_records('course', [], 'fullname ASC');
    foreach ($courses as $c) {
        if ($c->id == 1) continue;
        $url = new moodle_url('/local/wsmanageactivities/fix_images.php', ['courseid' => $c->id]);
        echo '<div style="margin-bottom:10px;"><a href="'.$url.'" style="font-weight:bold; font-size:1.1em;">📁 '.s($c->fullname).'</a></div>';
    }
} else {
    echo '<h2>Curso: '.s($DB->get_field('course', 'fullname', ['id' => $courseid])).'</h2>';
    $pages = $DB->get_records('page', ['course' => $courseid]);
    
    $all_available = [];
    if (is_dir($img_base_dir)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($img_base_dir));
        foreach ($it as $file) {
            if ($file->isFile() && preg_match('/\.(jpg|jpeg|png)$/i', $file->getFilename())) {
                $all_available[] = ['name' => $file->getFilename(), 'folder' => trim(str_replace($img_base_dir, '', $file->getPath()), '/')];
            }
        }
    }
    sort($all_available);

    foreach ($pages as $page) {
        preg_match_all('/<(figure|div)[^>]*class="[^"]*ailms-figure[^"]*"[^>]*>.*?<\/\1>/is', $page->content, $blocks);
        if (empty($blocks[0])) continue;

        echo '<div style="background:#f4f4f4; padding:25px; border:1px solid #ddd; margin-bottom:40px; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">';
        echo '<h3 style="margin-top:0; color:#1a73e8; border-bottom:2px solid #e1e4e8; padding-bottom:10px; margin-bottom:20px;">📄 Página: '.s($page->name).'</h3>';
        
        foreach ($blocks[0] as $idx => $block_html) {
            $placeholder = "";
            if (preg_match('/data-placeholder="([^"]+)"/i', $block_html, $pm)) $placeholder = $pm[1];
            if (!$placeholder) continue;

            $unique_id = "item_{$page->id}_{$idx}";
            
            echo '<table style="width:100%; border-collapse:collapse; background:#fff; border:1px solid #ccc; margin-bottom:25px; border-radius:10px; overflow:hidden; table-layout:fixed;">';
            echo '<tr>';
            
            // Preview
            echo '<td style="width:320px; padding:15px; border-right:1px solid #eee; vertical-align:middle; background:#fafafa; text-align:center;">';
            if (stripos($block_html, 'ailms-error-block') !== false) {
                if (stripos($placeholder, 'TABLE') !== false) {
                    echo '<div style="color:#0369a1; font-weight:bold; background:#e0f2fe; padding:20px; border-radius:8px; border:1px dashed #0ea5e9;">📊 TABELA EM FALTA<br><small style="opacity:0.7;">'.$placeholder.'</small></div>';
                } else {
                    echo '<div style="color:#d32f2f; font-weight:bold; background:#ffebee; padding:20px; border-radius:8px; border:1px dashed #f44336;">🖼️ IMAGEM EM FALTA<br><small style="opacity:0.7;">'.$placeholder.'</small></div>';
                }
            } else {
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $block_html, $im)) {
                    echo '<img src="'.$im[1].'" style="max-width:100%; max-height:250px; height:auto; border-radius:6px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">';
                } else {
                    echo "Sem Preview";
                }
            }
            echo '</td>';

            // Ações
            echo '<td style="padding:20px; vertical-align:top;">';
            
            $legend = "";
            if (preg_match('/class="ailms-img-caption"[^>]*>(.*?)<\//is', $block_html, $lm)) $legend = trim(strip_tags($lm[1]));
            echo '<div style="background:#fffde7; padding:12px; border:1px solid #fff59d; border-radius:8px; margin-bottom:15px; font-size:13px; color:#5d4037; line-height:1.4;">';
            echo '💡 <strong>Legenda do Sistema:</strong><br>' . ($legend ? s($legend) : 'Sem legenda');
            echo '</div>';

            echo '<form method="POST" enctype="multipart/form-data" style="margin:0;">';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'"><input type="hidden" name="pageid" value="'.$page->id.'"><input type="hidden" name="placeholder_id" value="'.s($placeholder).'">';
            
            echo '<div style="background:#f8f9fa; padding:12px; border-radius:8px; margin-bottom:10px; border:1px solid #eceff1;"><strong>📤 Upload Local:</strong><br><input type="file" name="uploadimg" style="margin-top:5px; font-size:12px;"></div>';
            echo '<div style="background:#f8f9fa; padding:12px; border-radius:8px; margin-bottom:10px; border:1px solid #eceff1;"><strong>🖼️ Galeria Extração:</strong><br><select name="newimg" style="width:100%; margin-top:5px; padding:5px; border-radius:4px; border:1px solid #cfd8dc;" onchange="previewNew(this, \''.$unique_id.'\')"><option value="">-- Selecionar --</option>';
            foreach ($all_available as $img) echo '<option value="'.s($img['folder'].'/'.$img['name']).'">['.s($img['folder']).'] '.s($img['name']).'</option>';
            echo '</select></div>';
            
            echo '<div id="new_container_'.$unique_id.'" style="display:none; margin-top:10px; border:2px dashed #4caf50; padding:10px; text-align:center; background:#e8f5e9; border-radius:8px;"><span style="color:#2e7d32; font-size:11px; font-weight:bold;">✨ Nova Seleção:</span><br><img id="new_img_'.$unique_id.'" src="" style="max-width:100%; max-height:120px; border-radius:4px; margin-top:5px;"></div>';

            echo '<div style="margin-top:20px; display:flex; gap:10px;">';
            echo '<button type="submit" style="background:#1b5e20; color:#fff; padding:12px 20px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; flex:2;">✅ ATUALIZAR</button> ';
            echo '<button type="submit" name="deleteimg" value="1" style="background:#b71c1c; color:#fff; padding:12px 20px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; flex:1;" onclick="return confirm(\'Apagar permanentemente?\')">🗑️ APAGAR</button>';
            echo '</div>';
            echo '</form>';
            
            echo '</td></tr></table>';
        }
        echo '</div>';
    }
}
echo '</div>'; // Fecha container centralizador
?>
<script>
function previewNew(sel, id) {
    const container = document.getElementById('new_container_' + id);
    const img = document.getElementById('new_img_' + id);
    if (sel.value) {
        img.src = '<?php echo $CFG->wwwroot; ?>/local/wsmanageactivities/extracted_images/' + sel.value;
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}
</script>
<?php
echo $OUTPUT->footer();
