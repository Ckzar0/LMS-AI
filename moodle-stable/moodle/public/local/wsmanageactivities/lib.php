<?php
// local/wsmanageactivities/lib.php

defined('MOODLE_INTERNAL') || die();

/**
 * Funções auxiliares partilhadas entre os managers
 */

/**
 * Processa ficheiros e substitui placeholders
 */
function wsmanageactivities_process_files($content, $files, $courseid, $activity_idx, $temp_upload_dir, $public_images_path) {
    global $CFG;
    
    foreach ($files as $file_info) {
        $filename = $file_info['filename'];
        $filepath = $file_info['path'];
        $placeholder = $file_info['placeholder'] ?? '';
        
        $possible_paths = [
            $temp_upload_dir . '/' . $filepath,
            $temp_upload_dir . '/' . basename($filepath),
            dirname($temp_upload_dir) . '/' . $filepath,
            $CFG->dirroot . '/' . $filepath,
            $filepath
        ];
        
        $found = false;
        $source_path = '';
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $found = true;
                $source_path = $path;
                break;
            }
        }
        
        if (!$found) {
            error_log("[YAML Upload] Ficheiro não encontrado: {$filepath}");
            continue;
        }
        
        $public_filename = "{$courseid}_{$activity_idx}_{$filename}";
        $dest_path = $public_images_path . '/' . $public_filename;
        
        if (!copy($source_path, $dest_path)) {
            error_log("[YAML Upload] Falha ao copiar ficheiro");
            continue;
        }
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $url = new moodle_url('/public_images/' . $public_filename);
        
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) {
            $html = html_writer::tag('img', '', [
                'src' => $url,
                'style' => 'max-width: 100%; height: auto;',
                'alt' => $filename
            ]);
        } elseif (in_array($ext, ['mp4', 'avi', 'mov'])) {
            $html = html_writer::tag('video', 
                html_writer::tag('source', '', ['src' => $url, 'type' => 'video/' . $ext]),
                ['controls' => true, 'style' => 'max-width: 100%;']
            );
        } elseif ($ext === 'pdf') {
            $html = html_writer::link($url, '📄 ' . $filename, ['target' => '_blank']);
        } else {
            $html = html_writer::link($url, '📎 ' . $filename);
        }
        
        $content = str_replace($placeholder, $html, $content);
    }
    
    return $content;
}

/**
 * Serve os ficheiros do plugin (imagens extraídas, etc.)
 */
function local_wsmanageactivities_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_wsmanageactivities', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        // Fallback: Tentar procurar na área de conteúdo geral do mod_page por compatibilidade
        $file = $fs->get_file($context->id, 'mod_page', 'content', 0, '/', $filename);
    }

    if (!$file) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Adiciona link na navegação primária (menu superior do Moodle 4.0+)
 *
 * @param \core\navigation\views\primary $navigation
 * @return void
 */
function local_wsmanageactivities_extend_navigation_primary($navigation) {
    global $CFG;

    if (is_siteadmin()) {
        $url = new moodle_url('/local/wsmanageactivities/courses.php');
        $navigation->add(
            'Fábrica de Cursos',
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_wsmanageactivities_factory'
        );
    }
}

/**
 * Adiciona link para a Fábrica de Cursos no menu de utilizador.
 *
 * @param navigation_node $navigation
 * @param stdClass $user
 * @param context $context
 * @return void
 */
function local_wsmanageactivities_extend_navigation_user($navigation, $user, $context) {
    if (is_siteadmin()) {
        $url = new moodle_url('/local/wsmanageactivities/courses.php');
        $navigation->add(
            '🚀 Fábrica de Cursos',
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'local_wsmanageactivities_user_link',
            new pix_icon('i/course', '')
        );
    }
}

/**
 * Adiciona navegação automática ao conteúdo
 */
function wsmanageactivities_add_navigation($content, $current_idx, $activities, $courseid, $is_enabled) {
    if (!$is_enabled) {
        return $content;
    }
    
    $next_idx = $current_idx + 1;
    
    if (strpos($content, '{{NAVIGATION_NEXT}}') !== false) {
        if ($next_idx < count($activities)) {
            $next_name = $activities[$next_idx]['name'] ?? 'Próxima Atividade';
            $button = '<div style="text-align: center; margin: 30px 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px;">
                <p style="color: white; margin-bottom: 15px;">Continue para a próxima atividade:</p>
                <a href="#" onclick="window.scrollTo(0,0); return false;" 
                   style="display: inline-block; padding: 12px 30px; background: white; color: #667eea; 
                   text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px;">
                   Próxima: ' . s($next_name) . ' →
                </a>
            </div>';
        } else {
            $course_url = new moodle_url('/course/view.php', ['id' => $courseid]);
            $button = '<div style="text-align: center; margin: 30px 0; padding: 20px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 10px;">
                <p style="color: white; margin-bottom: 15px; font-size: 18px;">🎉 Parabéns! Concluiu todas as atividades!</p>
                <a href="' . $course_url . '" 
                   style="display: inline-block; padding: 12px 30px; background: white; color: #28a745; 
                   text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px;">
                   Voltar ao Curso
                </a>
            </div>';
        }
        $content = str_replace('{{NAVIGATION_NEXT}}', $button, $content);
    }
    
    return $content;
}