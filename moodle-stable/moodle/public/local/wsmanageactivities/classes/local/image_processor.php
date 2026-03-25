<?php
namespace local_wsmanageactivities\local;

defined('MOODLE_INTERNAL') || die();

class image_processor {
    public static function process_placeholders($content, $contextid, $component, $filearea, $itemid, $image_folder = '', $course_id = 0) {
        global $CFG, $USER;
        
        // Determinar pasta pública por curso
        $course_assets_subfolder = ($course_id > 0) ? $course_id . '/' : '';
        $public_dir = $CFG->dirroot . '/public/course_assets/' . $course_assets_subfolder;
        $public_url = $CFG->wwwroot . '/course_assets/' . $course_assets_subfolder;
        
        if (!is_dir($public_dir)) mkdir($public_dir, 0777, true);
        
        // 1. PROCESSAR TABELAS (PLACEHOLDERS MANUAIS)
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
        preg_match_all('/\[\[IMG_P(\d+)_(\d+)\]\]/', $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) return $content;

        $subfolder = !empty($image_folder) ? trim($image_folder, '/') : '';
        $base_path = $CFG->dirroot . '/local/wsmanageactivities/extracted_images/' . ($subfolder ? $subfolder . '/' : '');

        // Obter todas as imagens disponíveis na pasta do curso
        $all_images = [];
        if (is_dir($base_path)) {
            $all_images = glob($base_path . "*.{jpg,jpeg,png}", GLOB_BRACE);
            sort($all_images);
        }

        foreach ($matches as $match) {
            $full_placeholder = $match[0];
            $page_num = (int)$match[1];
            $img_seq = (int)$match[2];

            $final_source = "";
            
            // Procura simplificada: tenta pelo número da página ou pela ordem
            $p_pad = str_pad($page_num, 3, '0', STR_PAD_LEFT);
            $p_pad_prev = str_pad($page_num - 1, 3, '0', STR_PAD_LEFT);
            $p_pad_next = str_pad($page_num + 1, 3, '0', STR_PAD_LEFT);
            $s_pad = str_pad($img_seq, 3, '0', STR_PAD_LEFT);
            
            $candidates = [
                $base_path . "img-$p_pad-$s_pad.jpg",      // Exato (P30_00 -> img-030-000)
                $base_path . "img-$p_pad_prev-$s_pad.jpg", // Shift -1 (P30_00 -> img-029-000)
                $base_path . "img-$p_pad_next-$s_pad.jpg", // Shift +1 (P30_00 -> img-031-000)
                $base_path . "img-$p_pad.jpg",             // Legado
                $base_path . "img-$p_pad_prev.jpg",
                $base_path . "img-" . ($page_num-1) . ".jpg"
            ];

            $mapping_file = $base_path . 'mapping.json';
            $mapping = file_exists($mapping_file) ? json_decode(file_get_contents($mapping_file), true) : [];

            foreach ($candidates as $c) {
                $fname = basename($c);
                
                // Se o otimizador mapeou este ficheiro para outro (duplicado)
                if (isset($mapping[$fname])) {
                    $c = $base_path . $mapping[$fname];
                }

                if (file_exists($c)) {
                    $final_source = $c;
                    break;
                }
            }

            if ($final_source && file_exists($final_source)) {
                $fname = basename($final_source);
                @copy($final_source, $public_dir . $fname);

                $img_url = $public_url . $fname;
                $img_tag = '<p dir="ltr" style="text-align: center;"><img src="' . $img_url . '" title="'.$full_placeholder.'" class="img-fluid" width="600" alt="Figura"></p>';
                $content = str_replace($full_placeholder, $img_tag, $content);
            } else {
                // IMPORTANTE: NÃO APAGAR O PLACEHOLDER SE FALHAR. 
                // Deixa-o visível como texto para o utilizador saber que falta uma imagem ali.
                $error_tag = '<span style="color:red; font-weight:bold;">[IMAGEM EM FALTA: ' . $full_placeholder . ']</span>';
                $content = str_replace($full_placeholder, $error_tag, $content);
            }
        }
        return $content;
    }
}
