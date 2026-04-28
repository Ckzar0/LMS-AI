<?php
namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

class image_processor {
    private static $global_count = 0;

    public static function process_placeholders($content, $contextid, $component, $filearea, $itemid, $image_folder = '', $course_id = 0) {
        global $CFG, $DB;
        
        if (empty($content)) return $content;

        // 1. SINCRONIZAR CONTADOR GLOBAL (Curso Inteiro)
        self::$global_count = 0;
        if ($course_id > 0) {
            $previous_pages = $DB->get_records_sql(
                "SELECT content FROM {page} WHERE course = ? AND id < ? ORDER BY id ASC", 
                [$course_id, $itemid]
            );
            foreach ($previous_pages as $p) {
                self::$global_count += preg_match_all('/class="[^"]*ailms-figure/i', $p->content, $m);
            }
        }

        // 2. PROCESSAR IMAGENS
        $pattern = '/\[\[IMG_P?(\d+)_(\d+)(?:_([^\]]+))?\]\](?:\s*<br[^>]*>|\s+)*(?:<div class="ailms-img-caption">(.*?)<\/div>)?/is';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        $plugin_root = dirname(dirname(dirname(__FILE__)));
        $subfolder = !empty($image_folder) ? trim($image_folder, '/') : '';
        $base_path = $plugin_root . '/extracted_images/' . ($subfolder ? $subfolder . '/' : '');
        
        foreach ($matches as $match) {
            $full_match_text = $match[0]; 
            if (strpos($full_match_text, 'class="ailms-figure"') !== false) continue;

            self::$global_count++;
            $p_num = $match[1]; $s_num = $match[2];
            $suffix = !empty($match[3]) ? $match[3] : "";
            $extracted_legend = !empty($match[4]) ? trim(strip_tags($match[4])) : "";
            
            // Limpeza: Ignorar qualquer prefixo vindo do texto
            $clean_regex = '/^\s*(?:Figura|Figure|Fig\.?|Tabela|Table|Tab\.?)\s*\d*[:\-\s\.]*/iu';
            $clean_desc = preg_replace($clean_regex, '', $extracted_legend);
            if (empty($clean_desc) && !empty($suffix)) $clean_desc = preg_replace($clean_regex, '', str_replace('_', ' ', $suffix));
            $clean_desc = trim($clean_desc);

            // Legenda Final: "Figura X - [Descrição]"
            $final_legend = "Figura " . self::$global_count . (!empty($clean_desc) ? " - " . $clean_desc : "");
            $placeholder = "[[IMG_P{$p_num}_{$s_num}" . ($suffix ? "_{$suffix}" : "") . "]]";

            // Localizar Imagem
            $p_pad = str_pad($p_num, 3, '0', STR_PAD_LEFT);
            $s_pad = str_pad($s_num, 3, '0', STR_PAD_LEFT);
            $img_name = "img-$p_pad-$s_pad.jpg";
            $target_path = $base_path . $img_name;
            $final_url = "";

            if (file_exists($target_path)) {
                $assets_sub = ($course_id > 0) ? $course_id . '/' : '';
                $public_dir = $CFG->dirroot . '/course_assets/' . $assets_sub;
                if (!is_dir($public_dir)) mkdir($public_dir, 0777, true);
                @copy($target_path, $public_dir . $img_name);
                $final_url = $CFG->wwwroot . '/course_assets/' . $assets_sub . $img_name;
            }

            if ($final_url) {
                $img_tag = '<figure class="ailms-figure" data-placeholder="'.$placeholder.'" style="margin: 25px auto; text-align: center;">' .
                           '<img src="' . $final_url . '" data-legend="'.htmlspecialchars($final_legend).'" class="img-fluid" style="border-radius: 8px; max-width: 100%;">' .
                           '<figcaption class="ailms-img-caption" style="margin-top:12px; font-style:italic; font-weight:bold; color:#111;">' . $final_legend . '</figcaption>' .
                           '</figure>';
            } else {

                $img_tag = '<div class="ailms-figure ailms-error-block" data-placeholder="'.$placeholder.'" style="background:#fee2e2; border:2px dashed #ef4444; padding:20px; border-radius:10px; margin:20px auto; text-align:center;">' .
                             '<strong style="color:#b91c1c;">⚠️ IMAGEM EM FALTA: ' . $placeholder . '</strong>' .
                             '<div class="ailms-img-caption" style="margin-top:10px; font-weight:bold;">' . $final_legend . '</div>' .
                             '</div>';
            }
            $content = str_replace($full_match_text, $img_tag, $content);
        }

        // 3. PROCESSAR TABELAS (Contagem Global)
        $pattern_tables = '/\[\[TABLE_P?(\d+)(?:_([^\]]+))?\]\](?:\s*<br[^>]*>|\s+)*(?:(?:Tabela|Figura)\s*\d+[:\-\s]*(.*?)(?:\(|$|<\/div>))?/is';
        preg_match_all($pattern_tables, $content, $t_matches, PREG_SET_ORDER);
        foreach ($t_matches as $t_match) {
            if (strpos($t_match[0], 'ailms-figure') !== false) continue;
            self::$global_count++;
            $p_num = $t_match[1];
            $final_legend = "Tabela " . self::$global_count;
            $table_tag = '<div class="ailms-figure ailms-error-block" data-placeholder="[[TABLE_P'.$p_num.']]" style="background:#f0f9ff; border:2px dashed #0284c7; padding:20px; border-radius:10px; margin:20px auto; text-align:center;">' .
                         '<strong style="color:#0369a1;">📊 TABELA EM FALTA (Pág. '.$p_num.')</strong>' .
                         '<div class="ailms-img-caption" style="margin-top:10px; font-weight:bold;">' . $final_legend . '</div>' .
                         '</div>';
            $content = str_replace($t_match[0], $table_tag, $content);
        }

        return $content;
    }
}
