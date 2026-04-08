<?php
// local/wsmanageactivities/classes/QuizManager.php

namespace local_wsmanageactivities;

defined('MOODLE_INTERNAL') || die();

class QuizManager {
    private $courseid;
    private $output_callback;
    private $activity_ids = [];
    private $created_quizzes = []; // Keep track of created quizzes if needed later
    
    public function __construct($courseid, $output_callback = null) {
        $this->courseid = $courseid;
        $this->output_callback = $output_callback ?? function($msg, $type = 'info') {};
    }
    
    private function log($message, $type = 'info') {
        ($this->output_callback)($message, $type);
    }
    
    public function getActivityIds() {
        return $this->activity_ids;
    }
    
    /**
     * Criar quizzes
     */
    public function createQuizzes($activities) {
        if (empty($activities)) {
            $this->log("Nenhum quiz para criar", 'info');
            return;
        }

        if (empty($this->courseid)) {
            throw new \Exception("Course ID não definido para criar quizzes.");
        }
        
        $this->log("Processando quizzes para o curso ID {$this->courseid}...", 'info');
        
        foreach ($activities as $idx => $activity) {
            $type = $activity['type'] ?? 'page';
            
            if ($type === 'quiz' || $type === 'random_quiz') {
                try {
                    $this->createQuiz($activity, $activity['section'] ?? 0, $idx);
                } catch (\Exception $e) {
                    $this->log("Erro no quiz [{$idx}]: " . $e->getMessage(), 'error');
                }
            }
        }
    }
    
    /**
     * Criar quiz via Web Service
     */
    private function createQuiz($activity_data, $section_num, $activity_idx) {
        $name = $activity_data['name'] ?? "Quiz {$activity_idx}";
        $this->log("  Preparando quiz: {$name} (via WS)...", 'info');

        try {
            // Parameters for local_wsmanageactivities_create_quiz
            $quiz_params = [
                'courseid' => $this->courseid,
                'sectionnum' => $section_num,
                'name' => $name,
                'intro' => $activity_data['intro'] ?? '',
                'grade' => $activity_data['grade'] ?? 10.0,
                'attempts' => $activity_data['attempts'] ?? 0,
                'timelimit' => $activity_data['timelimit'] ?? 0,
                'timeopen' => $activity_data['timeopen'] ?? 0,
                'timeclose' => $activity_data['timeclose'] ?? 0,
                'visible' => $activity_data['visible'] ?? true,
                'completiontype' => $activity_data['completiontype'] ?? 1,
                'sequential' => $activity_data['sequential'] ?? true,
                'prerequisitecmid' => $activity_data['prerequisitecmid'] ?? 0,
                'gradepass' => $activity_data['gradepass'] ?? null,
                // Pass review options if provided, otherwise the WS defaults will be used
                'reviewattempt' => $activity_data['reviewattempt'] ?? null,
                'reviewcorrectness' => $activity_data['reviewcorrectness'] ?? null,
                'reviewmarks' => $activity_data['reviewmarks'] ?? null,
                'reviewspecificfeedback' => $activity_data['reviewspecificfeedback'] ?? null,
                'reviewgeneralfeedback' => $activity_data['reviewgeneralfeedback'] ?? null,
                'reviewrightanswer' => $activity_data['reviewrightanswer'] ?? null,
                'reviewoverallfeedback' => $activity_data['reviewoverallfeedback'] ?? null,
                'reviewmaxmarks' => $activity_data['reviewmaxmarks'] ?? null,
            ];

            $create_quiz_result = $this->call_external_function('local_wsmanageactivities_create_quiz', $quiz_params);

            // The WS returns an object where the data is nested under 'data' property
            // and within that, 'success', 'id', and 'instance' are accessed as array keys.
            if (!isset($create_quiz_result->data['success']) || !$create_quiz_result->data['success']) { 
                // Add specific debug log for this WS call
                $this->log("DEBUG: Resposta do WS para local_wsmanageactivities_create_quiz: " . var_export($create_quiz_result, true), 'error');
                throw new \Exception("Falha ao criar quiz: " . ($create_quiz_result->data['message'] ?? 'Erro desconhecido'));
            }

            $cmid = $create_quiz_result->data['id']; 
            $quiz_instance_id = $create_quiz_result->data['instance']; 
            $this->activity_ids[] = $cmid;
            $this->created_quizzes[] = [
                'instance_id' => $quiz_instance_id,
            ];
            $this->log("  Quiz criado via WS (CM: {$cmid})", 'success');
            
        } catch (\Exception $e) {
            throw new \Exception("Erro ao criar quiz via WS: " . $e->getMessage());
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
