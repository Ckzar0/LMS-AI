<?php
namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

class image_processor {
    private static $global_count = 0;

    public static function process_placeholders($content, $contextid, $component, $filearea, $itemid, $image_folder = '', $course_id = 0) {
        global $CFG, $DB;
        
        $course_assets_subfolder = ($course_id > 0) ? $course_id . '/' : '';
        $public_dir = $CFG->dirroot . '/course_assets/' . $course_assets_subfolder;
        $public_url = $CFG->wwwroot . '/course_assets/' . $course_assets_subfolder;
        if (!is_dir($public_dir)) mkdir($public_dir, 0777, true);

        // 1. CALCULAR O PONTO DE PARTIDA PARA NUMERAÇÃO CONTINUA
        if (self::$global_count == 0 && $course_id > 0 && $itemid > 0) {
            $previous_pages = $DB->get_records_sql(
                "SELECT content FROM {page} WHERE course = ? AND id < ? ORDER BY id ASC", 
                [$course_id, $itemid]
            );
            foreach ($previous_pages as $p) {
                self::$global_count += preg_match_all('/\[\[IMG_P\d+_\d+\]\]|class="ailms-figure/i', $p->content, $m);
            }
        }

        // 2. PROCESSAR IMAGENS
        // Suporta [[IMG_Pxx_yy]] ou [[IMG_Pxx_yy_descricao]]
        $pattern = '/\[\[IMG_P(\d+)_(\d+)(?:_([^\]]+))?\]\](?:\s*<br[^>]*>|\s+)*(?:<div class="ailms-img-caption">(.*?)<\/div>)?/is';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        $plugin_root = dirname(dirname(dirname(__FILE__)));
        $subfolder = !empty($image_folder) ? trim($image_folder, '/') : '';
        $base_path = $plugin_root . '/extracted_images/' . ($subfolder ? $subfolder . '/' : '');

        foreach ($matches as $match) {
            $full_match_text = $match[0]; 
            $p_num = $match[1]; $s_num = $match[2];
            $suffix = !empty($match[3]) ? $match[3] : "";
            $extracted_legend = !empty($match[4]) ? trim(strip_tags($match[4])) : "";
            
            // O placeholder completo serve como ID único
            $placeholder = !empty($suffix) ? '[[IMG_P' . $p_num . '_' . $s_num . '_' . $suffix . ']]' : '[[IMG_P' . $p_num . '_' . $s_num . ']]';

            self::$global_count++;
            
            // Gerar legenda: Prioridade para a legenda extraída, depois para o sufixo
            $clean_description = preg_replace('/^Figura(?:\s*\d+)?\s*[:\-\s]*/i', '', $extracted_legend);
            if (empty($clean_description) && !empty($suffix)) {
                $clean_description = str_replace('_', ' ', $suffix);
            }
            $final_legend = "Figura " . self::$global_count . (!empty($clean_description) ? " - " . $clean_description : "");

            $final_source = "";
            $p_pad = str_pad($p_num, 3, '0', STR_PAD_LEFT);
            $s_pad = str_pad($s_num, 3, '0', STR_PAD_LEFT);
            $candidates = [$base_path . "img-$p_pad-$s_pad.jpg", $base_path . "img-$p_pad-$s_pad.png", $base_path . "img-$p_pad.jpg"];

            foreach ($candidates as $c) { if (file_exists($c)) { $final_source = $c; break; } }

            if ($final_source && file_exists($final_source)) {
                $fname = basename($final_source);
                @copy($final_source, $public_dir . $fname);
                $img_url = $public_url . $fname;

                $img_tag = '<figure class="ailms-figure" data-placeholder="'.$placeholder.'">' .
                           '<img src="' . $img_url . '" data-legend="'.htmlspecialchars($final_legend).'" title="'.$placeholder.'" class="img-fluid" style="border-radius: 8px; max-width: 100%; height: auto;">' .
                           '<figcaption class="ailms-img-caption" style="margin-top:15px; font-style:italic; font-weight:bold; color:#111; text-align:center;">' . $final_legend . '</figcaption>' .
                           '</figure>';
            } else {
                $img_tag = '<div class="ailms-figure ailms-error-block" data-placeholder="'.$placeholder.'" style="background:#fee2e2; border:2px dashed #ef4444; padding:25px; border-radius:12px; margin:30px auto; text-align:center; max-width:90%;">' .
                             '<strong style="color:#b91c1c;">⚠️ IMAGEM EM FALTA: ' . $placeholder . '</strong>' .
                             '<div class="ailms-img-caption" style="margin-top:15px; font-style:italic; font-weight:bold; color:#333;">' . $final_legend . '</div>' .
                             '</div>';
            }
            $content = str_replace($full_match_text, $img_tag, $content);
        }

        // 3. PROCESSAR TABELAS
        // Suporta [[TABLE_Pxx]] ou [[TABLE_Pxx_descricao]]
        $pattern_tables = '/\[\[TABLE_P(\d+)(?:_([^\]]+))?\]\](?:\s*<br[^>]*>|\s+)*(?:(?:Tabela|Figura)\s*\d+[:\-\s]*(.*?)(?:\(|$|<\/div>))?/is';
        if (preg_match_all($pattern_tables, $content, $table_matches, PREG_SET_ORDER)) {
            foreach ($table_matches as $t_match) {
                $full_table_text = $t_match[0];
                $p_num = $t_match[1];
                $suffix = !empty($t_match[2]) ? $t_match[2] : "";
                $extracted_legend = !empty($t_match[3]) ? trim(strip_tags($t_match[3])) : "";
                
                $placeholder = !empty($suffix) ? '[[TABLE_P' . $p_num . '_' . $suffix . ']]' : '[[TABLE_P' . $p_num . ']]';
                
                self::$global_count++;
                $clean_description = !empty($extracted_legend) ? $extracted_legend : str_replace('_', ' ', $suffix);
                if (empty($clean_description)) $clean_description = "Dados Técnicos";
                
                $final_legend = "Tabela " . self::$global_count . " - " . $clean_description;
                
                $table_tag = '<div class="ailms-figure ailms-error-block" data-placeholder="'.$placeholder.'" style="background:#f0f9ff; border:2px dashed #0284c7; padding:25px; border-radius:12px; margin:30px auto; text-align:center; max-width:90%;">' .
                             '<strong style="color:#0369a1;">📊 TABELA EM FALTA (Manual Pág. '.$p_num.'): ' . $placeholder . '</strong>' .
                             '<div class="ailms-img-caption" style="margin-top:15px; font-style:italic; font-weight:bold; color:#333;">' . $final_legend . '</div>' .
                             '</div>';
                $content = str_replace($full_table_text, $table_tag, $content);
            }
        }

        return $content;
    }
}
