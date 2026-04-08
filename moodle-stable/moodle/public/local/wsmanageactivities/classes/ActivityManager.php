<?php
// local/wsmanageactivities/classes/ActivityManager.php

namespace local_wsmanageactivities;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php'); // Still needed for wsmanageactivities_add_navigation

class ActivityManager {
    private $courseid;
    private $output_callback;
    private $activity_ids = [];
    // private $public_images_path; // Not needed if using WS
    private $temp_upload_dir;
    private $activities_data; // Used for navigation data
    private $navigation_enabled;
    
    public function __construct($courseid, $activities_data, $temp_upload_dir, $navigation_enabled, $output_callback = null) {
        global $CFG;
        
        $this->courseid = $courseid;
        $this->activities_data = $activities_data;
        $this->temp_upload_dir = $temp_upload_dir;
        $this->navigation_enabled = $navigation_enabled;
        $this->output_callback = $output_callback ?? function($msg, $type = 'info') {};
        // $this->public_images_path = $CFG->dirroot . '/public_images'; // No longer needed
        
        // if (!file_exists($this->public_images_path)) {
        //     mkdir($this->public_images_path, 0755, true);
        // }
    }
    
    private function log($message, $type = 'info') {
        ($this->output_callback)($message, $type);
    }
    
    public function getActivityIds() {
        return $this->activity_ids;
    }
    
    /**
     * Criar todas as atividades
     */
    public function createActivities($activities) {
        if (empty($activities)) {
            $this->log("Nenhuma atividade para criar", 'info');
            return;
        }
        
        if (empty($this->courseid)) {
            throw new \Exception("Course ID não definido para criar atividades.");
        }

        $this->log("Criando " . count($activities) . " atividades para o curso ID {$this->courseid}...", 'info');
        
        foreach ($activities as $idx => $activity) {
            $type = $activity['type'] ?? 'page';
            $name = $activity['name'] ?? "Atividade {$idx}";
            $section = $activity['section'] ?? 0;
            
            $this->log("[{$idx}] Tipo: {$type} | Nome: {$name} | Secção: {$section}", 'info');
            
            try {
                switch ($type) {
                    case 'page':
                    case 'page_with_files':
                        $this->createPage($activity, $section, $idx);
                        break;
                    case 'quiz':
                    case 'random_quiz':
                        // Quizzes are handled by QuizManager
                        $this->log("Quiz será tratado pelo QuizManager", 'info');
                        break;
                    default:
                        $this->log("Tipo desconhecido: {$type}", 'warning');
                }
            } catch (\Exception $e) {
                $this->log("Erro na atividade [{$idx}]: " . $e->getMessage(), 'error');
            }
        }
    }
    
    /**
     * Criar página
     */
    public function createPage($activity, $section, $activity_idx) {
        $this->log("  Preparando dados da página (via WS)...", 'info');
        
        $name = $activity['name'];
        $content = $activity['content'] ?? '';
        $files_data = [];

        // Prepare files for WS call if any
        if (!empty($activity['files'])) {
            foreach ($activity['files'] as $file_info) {
                $filepath = $file_info['path'];
                $full_filepath = "{$this->temp_upload_dir}/{$filepath}"; // Files are in temp dir
                if (!file_exists($full_filepath)) {
                    // Try basename if direct path not found
                    $full_filepath = "{$this->temp_upload_dir}/" . basename($filepath);
                }

                if (file_exists($full_filepath)) {
                    $files_data[] = [
                        'filename' => $file_info['filename'] ?? basename($filepath),
                        'filecontent' => base64_encode(file_get_contents($full_filepath)),
                        'placeholder' => $file_info['placeholder'] ?? ''
                    ];
                } else {
                    $this->log("  Ficheiro {$filepath} não encontrado no diretório temporário.", 'warning');
                }
            }
        }

        // Apply internal navigation function from lib.php if enabled
        $content = wsmanageactivities_add_navigation(
            $content, 
            $activity_idx, 
            $this->activities_data, 
            $this->courseid, 
            $this->navigation_enabled
        );
        
        try {
            $result = $this->call_external_function('local_wsmanageactivities_create_page_with_files', [
                'courseid' => $this->courseid,
                'sectionnum' => $section,
                'name' => $name,
                'content' => $content,
                'files' => $files_data,
                // Assuming completiontype, prerequisitecmid, and options will be passed in $activity if needed
                'completiontype' => $activity['completiontype'] ?? 0,
                'prerequisitecmid' => $activity['prerequisitecmid'] ?? 0,
                'options' => [
                    'intro' => $activity['intro'] ?? '',
                    'visible' => $activity['visible'] ?? 1,
                ]
            ]);

            // The WS returns an object where the data is nested under 'data' property
            // and within that, 'success' and 'message' are accessed as array keys.
            if (!isset($result->data['success']) || !$result->data['success']) { 
                $this->log("DEBUG: Resposta do WS para local_wsmanageactivities_create_page_with_files: " . var_export($result, true), 'error');
                throw new \Exception("Falha ao criar página: " . ($result->data['message'] ?? 'Erro desconhecido'));
            }

            $cmid = $result->data['cm_id']; 
            $this->activity_ids[] = $cmid;
            $this->log("  Página criada via WS (CM: {$cmid})", 'success');
            
        } catch (\Exception $e) {
            throw new \Exception("Erro ao criar página via WS: " . $e->getMessage());
        }
    }

    /**
     * Chamada a Web Services externos
     * (Replicado do CourseManager para auto-suficiência ou refatorado para uma classe base)
     */
    protected function call_external_function($function, $params) {
        $result = \core_external\external_api::call_external_function(
            $function,
            $params,
            false
        );
        
        // If the result is an array, cast it to an object for consistent property access.
        if (is_array($result)) {
            $result = (object)$result;
        }

        // Removed: As a safeguard, if it's still not an object after casting, it's an unexpected type.
        // if (!is_object($result)) {
        //     error_log("Unexpected WS result type for function $function. Full result: " . var_export($result, true));
        //     throw new \Exception("WS Error: Unexpected result format for function $function. Check server logs for details.");
        // }

        // Now, all subsequent checks assume $result is an object (or will throw an error if not).
        if (isset($result->error) && $result->error) {
            if (property_exists($result, 'message')) {
                throw new \Exception("WS Error: " . $result->message);
            } else if ($result instanceof \Exception) {
                throw $result;
            } else if (property_exists($result, 'exception')) {
                if (is_object($result->exception) && property_exists($result->exception, 'message') && property_exists($result->exception, 'errorcode')) {
                    throw new \Exception("WS Error: Moodle Exception: " . $result->exception->message . " (Code: " . $result->exception->errorcode . ")");
                } else {
                    error_log("Unknown WS Error with 'exception' property for function $function. Full result: " . var_export($result, true));
                    throw new \Exception("WS Error: Unknown exception format for function $function. Check server logs for details.");
                }
            } else {
                error_log("Unknown WS Error for function $function. Full result: " . var_export($result, true));
                throw new \Exception("WS Error: An unknown error occurred with function $function. Check server logs for details.");
            }
        }
        
        return $result;
    }
}
