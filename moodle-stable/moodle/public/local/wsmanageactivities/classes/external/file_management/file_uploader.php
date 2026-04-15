<?php
/**
 * File Upload Handler para local_wsmanageactivities (VERSÃO CORRIGIDA)
 * Versão: 1.1
 * Data: 16 de Julho de 2025, 20:25
 * 
 * CORREÇÃO: Removido token hardcoded, agora usa File API diretamente
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\external\file_management;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/accesslib.php');

use context;
use context_user;
use context_module;
use moodle_url;
use stdClass;
use Exception;

class file_uploader {
    
    /**
     * Upload de ficheiro para draft area (VERSÃO CORRIGIDA)
     * Usa File API diretamente em vez de web service
     * 
     * @param string $filename Nome do ficheiro
     * @param string $filecontent Conteúdo base64
     * @return array Resultado do upload
     */
    public static function upload_to_draft_area($filename, $filecontent) {
        global $USER;
        
        try {
            // Obter draft ID usando File API diretamente
            $draft_id = file_get_unused_draft_itemid();
            
            if (!$draft_id) {
                throw new Exception('Não foi possível obter draft ID');
            }
            
            // Preparar ficheiro temporário
            $temp_file = self::create_temp_file($filecontent);
            
            // Upload usando File API diretamente
            $upload_result = self::upload_to_draft_direct($temp_file, $filename, $draft_id);
            
            // Limpeza
            unlink($temp_file);
            
            return [
                'success' => true,
                'draft_id' => $draft_id,
                'filename' => $filename,
                'upload_result' => $upload_result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload direto para draft area usando File API
     * 
     * @param string $temp_file Path do ficheiro temporário
     * @param string $filename Nome do ficheiro
     * @param int $draft_id Draft item ID
     * @return array Resultado do upload
     */
    private static function upload_to_draft_direct($temp_file, $filename, $draft_id) {
        global $USER;
        
        try {
            $fs = get_file_storage();
            $user_context = context_user::instance($USER->id);
            
            // Preparar informações do ficheiro
            $file_record = [
                'contextid' => $user_context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draft_id,
                'filepath' => '/',
                'filename' => $filename,
                'userid' => $USER->id,
                'timecreated' => time(),
                'timemodified' => time()
            ];
            
            // Criar ficheiro na draft area
            $stored_file = $fs->create_file_from_pathname($file_record, $temp_file);
            
            return [
                'success' => true,
                'file_id' => $stored_file->get_id(),
                'filename' => $stored_file->get_filename(),
                'filesize' => $stored_file->get_filesize()
            ];
            
        } catch (Exception $e) {
            throw new Exception('Upload direto falhou: ' . $e->getMessage());
        }
    }
    
    /**
     * Criar ficheiro temporário a partir de conteúdo base64
     * 
     * @param string $filecontent Conteúdo base64
     * @return string Path do ficheiro temporário
     */
    private static function create_temp_file($filecontent) {
        $temp_file = tempnam(sys_get_temp_dir(), 'moodle_upload_');
        
        $decoded_content = base64_decode($filecontent);
        if ($decoded_content === false) {
            throw new Exception('Conteúdo base64 inválido');
        }
        
        if (file_put_contents($temp_file, $decoded_content) === false) {
            throw new Exception('Não foi possível criar ficheiro temporário');
        }
        
        return $temp_file;
    }
    
    /**
     * Mover ficheiros de draft area para área final
     * 
     * @param int $draft_id Draft item ID
     * @param int $context_id Context ID de destino
     * @param string $component Componente (ex: mod_page)
     * @param string $filearea Área de ficheiros (ex: content)
     * @param int $itemid Item ID (geralmente 0)
     * @return array URLs dos ficheiros movidos
     */
    public static function move_to_final_area($draft_id, $context_id, $component, $filearea, $itemid = 0) {
        global $DB, $USER;
        
        try {
            $fs = get_file_storage();
            $context = context::instance_by_id($context_id);
            
            // Obter ficheiros da draft area
            $draft_files = $fs->get_area_files(
                context_user::instance($USER->id)->id,
                'user',
                'draft',
                $draft_id,
                'filename',
                false
            );
            
            $final_urls = [];
            
            foreach ($draft_files as $file) {
                // Preparar dados para área final
                $file_record = [
                    'contextid' => $context_id,
                    'component' => $component,
                    'filearea' => $filearea,
                    'itemid' => $itemid,
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                    'userid' => $USER->id,
                    'timecreated' => time(),
                    'timemodified' => time()
                ];
                
                // Criar ficheiro na área final
                $final_file = $fs->create_file_from_storedfile($file_record, $file);
                
                // Gerar URL final
                $url = moodle_url::make_pluginfile_url(
                    $context_id,
                    $component,
                    $filearea,
                    $itemid,
                    $file->get_filepath(),
                    $file->get_filename()
                );
                
                $final_urls[] = [
                    'filename' => $file->get_filename(),
                    'url' => $url->out(),
                    'size' => $file->get_filesize(),
                    'mimetype' => $file->get_mimetype()
                ];
            }
            
            // Limpar draft area
            self::cleanup_draft_area($draft_id);
            
            return $final_urls;
            
        } catch (Exception $e) {
            return [
                'error' => 'Erro ao mover ficheiros: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Limpar draft area usando File API diretamente
     * 
     * @param int $draft_id Draft item ID
     * @return bool Sucesso da limpeza
     */
    private static function cleanup_draft_area($draft_id) {
        global $USER;
        
        try {
            $fs = get_file_storage();
            $user_context = context_user::instance($USER->id);
            
            // Eliminar ficheiros da draft area
            $fs->delete_area_files($user_context->id, 'user', 'draft', $draft_id);
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validar tipo de ficheiro
     * 
     * @param string $filename Nome do ficheiro
     * @return bool Ficheiro é válido
     */
    public static function validate_file_type($filename) {
        $allowed_extensions = [
            'png', 'jpg', 'jpeg', 'gif', 'svg',
            'pdf', 'doc', 'docx', 'txt', 'rtf',
            'zip', 'rar', '7z',
            'mp4', 'avi', 'mov',
            'mp3', 'wav', 'ogg'
        ];
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowed_extensions);
    }
    
    /**
     * Obter informações sobre ficheiro
     * 
     * @param string $filecontent Conteúdo base64
     * @return array Informações do ficheiro
     */
    public static function get_file_info($filecontent) {
        $decoded = base64_decode($filecontent);
        if ($decoded === false) {
            return ['error' => 'Conteúdo base64 inválido'];
        }
        
        $temp_file = tempnam(sys_get_temp_dir(), 'info_');
        file_put_contents($temp_file, $decoded);
        
        $info = [
            'size' => filesize($temp_file),
            'mime_type' => mime_content_type($temp_file),
            'size_human' => self::format_bytes(filesize($temp_file))
        ];
        
        unlink($temp_file);
        
        return $info;
    }
    
    /**
     * Formatar bytes em formato legível
     * 
     * @param int $bytes Tamanho em bytes
     * @return string Tamanho formatado
     */
    private static function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}