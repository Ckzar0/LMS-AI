<?php
namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

class image_processor {
    public static function process_placeholders($content, $contextid, $component, $filearea, $itemid, $image_folder = '', $course_id = 0) {
        global $CFG;
        
        $course_assets_subfolder = ($course_id > 0) ? $course_id . '/' : '';
        $public_dir = $CFG->dirroot . '/course_assets/' . $course_assets_subfolder;
        $public_url = $CFG->wwwroot . '/course_assets/' . $course_assets_subfolder;
        
        if (!is_dir($public_dir)) mkdir($public_dir, 0777, true);
        
        // 1. PROCESSAR TABELAS
        preg_match_all('/\[\[TABLE_P(\d+)\]\]/', $content, $table_matches, PREG_SET_ORDER);
        foreach ($table_matches as $t_match) {
            $full_placeholder = $t_match[0];
            $page_num = $t_match[1];
            $img_url = $CFG->wwwroot . '/course_assets/placeholder_table.jpg';
            $img_tag = '<div class="ailms-placeholder-box" style="text-align: center; border: 2px dashed #ff9800; padding: 15px; margin: 10px 0; border-radius: 10px;">' .
                       '<img src="' . $img_url . '" alt="TABLE_P' . $page_num . '" class="img-fluid" style="max-width:300px; opacity:0.5;">' .
                       '<br><span style="color: #ff9800; font-weight: bold;">[SUBSTITUIR POR PRINT DA TABELA - PÁG ' . $page_num . ']</span>' .
                       '</div>';
            $content = str_replace($full_placeholder, $img_tag, $content);
        }

        // 2. PROCESSAR IMAGENS
        // Regex para capturar placeholder e a div de legenda opcional
        $pattern = '/\[\[IMG_P(\d+)_(\d+)\]\](?:\s*<br[^>]*>|\s+)*(?:<div class="ailms-img-caption">([^<]+)<\/div>)?/is';
        
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) return $content;

        $subfolder = !empty($image_folder) ? trim($image_folder, '/') : '';
        $base_path = $CFG->dirroot . '/local/wsmanageactivities/extracted_images/' . ($subfolder ? $subfolder . '/' : '');

        if (!is_dir($base_path) && $course_id > 0) {
            $fallback_path = $CFG->dirroot . '/local/wsmanageactivities/extracted_images/' . $course_id . '/';
            if (is_dir($fallback_path)) $base_path = $fallback_path;
        }

        // Usamos uma variável global para manter a contagem de imagens ao longo de todo o curso
        // Isso garante que se a página 1 tem imagens 1-3, a página 2 começa na 4.
        static $global_img_count = 0;

        foreach ($matches as $match) {
            $full_match_text = $match[0]; 
            $p_num = $match[1];
            $s_num = $match[2];
            $extracted_legend = !empty($match[3]) ? trim($match[3]) : "";
            
            $full_placeholder = '[[IMG_P' . $p_num . '_' . $s_num . ']]';
            $final_source = "";
            $p_pad = str_pad($p_num, 3, '0', STR_PAD_LEFT);
            $p_pad_prev = str_pad($p_num - 1, 3, '0', STR_PAD_LEFT);
            $p_pad_next = str_pad($p_num + 1, 3, '0', STR_PAD_LEFT);
            $s_pad = str_pad($s_num, 3, '0', STR_PAD_LEFT);
            
            $candidates = [$base_path . "img-$p_pad-$s_pad.jpg", $base_path . "img-$p_pad_prev-$s_pad.jpg", $base_path . "img-$p_pad_next-$s_pad.jpg", $base_path . "img-$p_pad.jpg"];

            foreach ($candidates as $c) {
                if (file_exists($c)) { $final_source = $c; break; }
            }

            if ($final_source && file_exists($final_source)) {
                $fname = basename($final_source);
                if (!is_dir($public_dir)) mkdir($public_dir, 0777, true);
                @copy($final_source, $public_dir . $fname);

                $img_url = $public_url . $fname;
                $global_img_count++;
                
                // --- LÓGICA DE LEGENDA SEQUENCIAL ---
                // 1. Limpar "Figura X" que a IA possa ter escrito (para não duplicar)
                $clean_description = preg_replace('/^Figura\s*\d+\s*[:\-\s]*/i', '', $extracted_legend);
                
                // 2. Criar a legenda oficial com o número correto do contador global
                $display_legend = "Figura " . $global_img_count . (!empty($clean_description) ? " - " . $clean_description : "");

                $img_tag = '<figure class="ailms-figure" style="text-align: center; margin: 30px auto; max-width: 90%; border: 1px solid #ddd; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">' .
                           '<img src="' . $img_url . '" alt="'.htmlspecialchars($display_legend).'" data-legend="'.htmlspecialchars($display_legend).'" title="'.$full_placeholder.'" class="img-fluid" style="border-radius: 6px; max-width: 100%; height: auto;">' .
                           '<figcaption class="ailms-img-caption" style="margin-top: 18px; font-style: italic; color: #111; font-size: 1.1em; font-weight: bold; border-top: 2px solid #eee; padding-top: 15px; font-family: sans-serif;">' . $display_legend . '</figcaption>' .
                           '</figure>';
                
                // SUBSTITUIÇÃO CIRÚRGICA: Apenas a primeira ocorrência encontrada (limit = 1)
                $quoted_match = preg_quote($full_match_text, '/');
                $content = preg_replace('/' . $quoted_match . '/', $img_tag, $content, 1);
            } else {
                $error_tag = '<div style="background:#fee2e2; border:1px solid #ef4444; color:#b91c1c; padding:15px; border-radius:8px; margin:20px 0; text-align:center;">' .
                             '<strong>⚠️ IMAGEM EM FALTA:</strong> ' . $full_placeholder . '<br>' .
                             '<div class="ailms-img-caption" style="font-size:0.9em; opacity:0.8;">' . $extracted_legend . '</div>' .
                             '</div>';
                $quoted_match = preg_quote($full_match_text, '/');
                $content = preg_replace('/' . $quoted_match . '/', $error_tag, $content, 1);
            }
        }
        return $content;
    }
}
