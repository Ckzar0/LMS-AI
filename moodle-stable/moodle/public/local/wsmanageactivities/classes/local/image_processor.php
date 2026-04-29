<?php
/**
 * @package    local_wsmanageactivities
 * @copyright  2024 BMad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Classe para processamento de imagens e tabelas no conteúdo do Moodle.
 */
class image_processor {

    /** @var int Contador global para numeração sequencial de figuras/tabelas */
    private static $global_count = 0;

    /**
     * Processa os placeholders [[IMG_Pxx_yy]] e [[TABLE_Pxx]] no conteúdo.
     */
    public static function process_placeholders($content, $contextid, $component, $filearea, $itemid, $image_folder = '', $course_id = 0) {
        global $CFG, $DB;

        if (empty($content)) {
            return $content;
        }

        // 1. SINCRONIZAR CONTADOR GLOBAL (Soma o que já existe nas páginas anteriores)
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
        
        $plugin_root = dirname(dirname(dirname(__FILE__)));
        $subfolder = !empty($image_folder) ? trim($image_folder, '/') : '';
        $base_path = $plugin_root . '/extracted_images/' . ($subfolder ? $subfolder . '/' : '');

        $content = preg_replace_callback($pattern, function($match) use ($base_path, $CFG, $course_id) {
            self::$global_count++;
            $p_num = $match[1]; $s_num = $match[2];
            $suffix = !empty($match[3]) ? $match[3] : "";
            $extracted_legend = !empty($match[4]) ? trim(strip_tags($match[4])) : "";
            
            // Limpeza de legendas
            $clean_regex = '/^\s*(?:Figura|Figure|Fig\.?|Tabela|Table|Tab\.?)\s*\d*[:\-\s\.]*/iu';
            $clean_desc = preg_replace($clean_regex, '', $extracted_legend);
            if (empty($clean_desc) && !empty($suffix)) $clean_desc = str_replace('_', ' ', $suffix);
            $clean_desc = trim($clean_desc);

            $final_legend = "Figura " . self::$global_count . (!empty($clean_desc) ? " - " . $clean_desc : "");
            
            // ID LIMPO para o data-placeholder (Sem os colchetes para evitar aninhamento)
            $placeholder_id = "IMG_P{$p_num}_{$s_num}" . ($suffix ? "_{$suffix}" : "");

            // Caminhos de Imagem
            $p_pad = str_pad($p_num, 3, '0', STR_PAD_LEFT);
            $s_pad = str_pad($s_num, 3, '0', STR_PAD_LEFT);
            $img_name = "img-$p_pad-$s_pad.jpg";
            $target_path = $base_path . $img_name;
            $img_url = "";

            if (file_exists($target_path)) {
                $assets_sub = ($course_id > 0) ? $course_id . '/' : '';
                $public_dir = $CFG->dirroot . '/course_assets/' . $assets_sub;
                if (!is_dir($public_dir)) mkdir($public_dir, 0777, true);
                @copy($target_path, $public_dir . $img_name);
                $img_url = $CFG->wwwroot . '/course_assets/' . $assets_sub . $img_name;
            }

            if ($img_url) {
                return '<figure class="ailms-figure" data-placeholder="'.$placeholder_id.'" style="margin: 25px auto; text-align: center; display: block; clear: both;">' .
                       '<img src="' . $img_url . '" data-legend="'.htmlspecialchars($final_legend).'" class="img-fluid" style="border-radius: 8px; max-width: 100%; height: auto; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">' .
                       '<figcaption class="ailms-img-caption" style="margin-top:12px; font-style:italic; font-weight:600; color:#111; text-align:center;">' . $final_legend . '</figcaption>' .
                       '</figure>';
            } else {
                return '<div class="ailms-figure ailms-error-block" data-placeholder="'.$placeholder_id.'" style="background:#fee2e2; border:2px dashed #ef4444; padding:25px; border-radius:12px; margin:20px auto; text-align:center; clear: both;">' .
                             '<strong style="color:#b91c1c;">⚠️ IMAGEM EM FALTA: [[' . $placeholder_id . ']]</strong>' .
                             '<div class="ailms-img-caption" style="margin-top:10px; font-weight:bold; text-align:center;">' . $final_legend . '</div>' .
                             '</div>';
            }
        }, $content);

        return $content;
    }
}
