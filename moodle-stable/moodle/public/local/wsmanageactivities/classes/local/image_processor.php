<?php
namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

class image_processor {
    private static $global_count = 0;

    public static function process_placeholders($content, $contextid, $component, $filearea, $itemid, $image_folder = '', $course_id = 0) {
        global $CFG, $DB;
        
        if (empty($content)) return $content;

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

        $pattern = '/(?<!data-placeholder=")(\[\[IMG_P?(\d+)_(\d+)(?:_([^\]]+))?\]\])(?:\s*<br[^>]*>|\s+)*(?:<div class="ailms-img-caption">(.*?)<\/div>)?/is';
        
        $plugin_root = dirname(dirname(dirname(__FILE__)));
        $subfolder = !empty($image_folder) ? trim($image_folder, '/') : '';
        $base_path = $plugin_root . '/extracted_images/' . ($subfolder ? $subfolder . '/' : '');
        
        $mapping = [];
        if (file_exists($base_path . 'mapping.json')) {
            $mapping = json_decode(file_get_contents($base_path . 'mapping.json'), true) ?: [];
        }

        $content = preg_replace_callback($pattern, function($match) use ($base_path, $mapping, $CFG, $course_id) {
            self::$global_count++;
            
            $full_placeholder = $match[1];
            $p_num = $match[2]; 
            $s_num_raw = $match[3]; // Mantemos como string (ex: "00")
            $s_num_int = (int)$s_num_raw;
            $suffix = !empty($match[4]) ? $match[4] : "";
            $extracted_legend = !empty($match[5]) ? trim(strip_tags($match[5])) : "";
            
            $clean_regex = '/^\s*(?:Figura|Figure|Fig\.?|Tabela|Table|Tab\.?)\s*\d*[:\-\s\.]*/iu';
            $clean_desc = preg_replace($clean_regex, '', $extracted_legend);
            if (empty($clean_desc) && !empty($suffix)) $clean_desc = str_replace('_', ' ', $suffix);
            $clean_desc = trim($clean_desc);

            $final_legend = "Figura " . self::$global_count . (!empty($clean_desc) ? " - " . $clean_desc : "");
            $clean_id = "IMG_P{$p_num}_{$s_num_raw}" . ($suffix ? "_{$suffix}" : "");

            // --- PROCURA INTELIGENTE (FUZZY) ---
            $p_pad = str_pad($p_num, 3, '0', STR_PAD_LEFT);
            $final_source = "";

            // Tentar índices próximos (exato, -1, +1)
            $to_test = [$s_num_int, $s_num_int - 1, $s_num_int + 1, 0, 1, 2];
            foreach (array_unique($to_test) as $idx) {
                if ($idx < 0) continue;
                $s_pad = str_pad($idx, 3, '0', STR_PAD_LEFT);
                $cname = "img-$p_pad-$s_pad.jpg";
                
                if (file_exists($base_path . $cname)) { $final_source = $base_path . $cname; break; }
                if (isset($mapping[$cname]) && file_exists($base_path . $mapping[$cname])) { $final_source = $base_path . $mapping[$cname]; break; }
            }

            // Fallback: Qualquer imagem daquela página
            if (!$final_source) {
                $page_files = glob($base_path . "img-$p_pad-*.jpg");
                if ($page_files) $final_source = $page_files[0];
            }

            if ($final_source) {
                $assets_sub = ($course_id > 0) ? $course_id . '/' : '';
                $public_dir = $CFG->dirroot . '/course_assets/' . $assets_sub;
                if (!is_dir($public_dir)) mkdir($public_dir, 0777, true);
                
                $img_name = basename($final_source);
                @copy($final_source, $public_dir . $img_name);
                $img_url = $CFG->wwwroot . '/course_assets/' . $assets_sub . $img_name;

                return '<figure class="ailms-figure" data-placeholder="'.$clean_id.'" style="margin: 25px auto; text-align: center; display: block; clear: both;">' .
                       '<img src="' . $img_url . '" data-legend="'.htmlspecialchars($final_legend).'" class="img-fluid" style="border-radius: 8px; max-width: 100%; height: auto; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">' .
                       '<figcaption class="ailms-img-caption" style="margin-top:12px; font-style:italic; font-weight:600; color:#111; text-align:center;">' . $final_legend . '</figcaption>' .
                       '</figure>';
            } else {
                return '<div class="ailms-figure ailms-error-block" data-placeholder="'.$clean_id.'" style="background:#fee2e2; border:2px dashed #ef4444; padding:20px; border-radius:10px; margin:20px auto; text-align:center; clear: both;">' .
                             '<strong style="color:#b91c1c;">⚠️ IMAGEM EM FALTA: [[' . $clean_id . ']]</strong>' .
                             '<div class="ailms-img-caption" style="margin-top:10px; font-weight:bold; text-align:center;">' . $final_legend . '</div>' .
                             '</div>';
            }
        }, $content);

        // 3. PROCESSAR TABELAS
        $pattern_tables = '/(?<!data-placeholder=")(\[\[TABLE_P?(\d+)(?:_([^\]]+))?\]\])(?:\s*<br[^>]*>|\s+)*(?:(?:Tabela|Figura)\s*\d+[:\-\s]*(.*?)(?:\(|$|<\/div>))?/is';
        $content = preg_replace_callback($pattern_tables, function($match) {
            self::$global_count++;
            $p_num = $match[2];
            $suffix = !empty($match[3]) ? $match[3] : "";
            $placeholder_id = "TABLE_P{$p_num}" . ($suffix ? "_{$suffix}" : "");
            
            $final_legend = "Tabela " . self::$global_count;
            return '<div class="ailms-figure ailms-error-block" data-placeholder="'.$placeholder_id.'" style="background:#f0f9ff; border:2px dashed #0284c7; padding:20px; border-radius:12px; margin:30px auto; text-align:center; clear: both;">' .
                         '<strong style="color:#0369a1;">📊 TABELA EM FALTA (Pág. '.$p_num.')</strong>' .
                         '<div class="ailms-img-caption" style="margin-top:10px; font-weight:bold; text-align:center;">' . $final_legend . '</div>' .
                         '</div>';
        }, $content);

        return $content;
    }
}
