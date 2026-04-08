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
        $pattern = '/\[\[IMG_P(\d+)_(\d+)\]\](?:\s*<br[^>]*>|\s+)*(?:<div class="ailms-img-caption">(.*?)<\/div>)?/is';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        if (empty($matches)) return $content;

        $plugin_root = dirname(dirname(dirname(__FILE__)));
        $subfolder = !empty($image_folder) ? trim($image_folder, '/') : '';
        $base_path = $plugin_root . '/extracted_images/' . ($subfolder ? $subfolder . '/' : '');

        foreach ($matches as $match) {
            $full_match_text = $match[0]; 
            $p_num = $match[1]; $s_num = $match[2];
            $extracted_legend = !empty($match[3]) ? trim(strip_tags($match[3])) : "";
            $placeholder = '[[IMG_P' . $p_num . '_' . $s_num . ']]';

            self::$global_count++;
            
            // Gerar legenda limpa
            $clean_description = preg_replace('/^Figura(?:\s*\d+)?\s*[:\-\s]*/i', '', $extracted_legend);
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
        return $content;
    }
}
