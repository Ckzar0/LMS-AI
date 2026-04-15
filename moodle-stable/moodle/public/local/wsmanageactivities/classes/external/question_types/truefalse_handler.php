<?php
/**
 * True/False Multilingual Handler - VERSÃO 2.0
 * 
 * MELHORIAS v2.0:
 * - Detecção automática do idioma do utilizador
 * - Suporte para múltiplos idiomas (PT, EN, ES, FR)
 * - Fallback inteligente para inglês se idioma não suportado
 * - Configuração via language strings do Moodle
 * 
 * @package    local_wsmanageactivities
 * @subpackage question_types
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    2.0 - 17 de Julho de 2025, 18:00
 */

namespace local_wsmanageactivities\external\question_types;

defined('MOODLE_INTERNAL') || die();

use stdClass;

class truefalse_handler {
    
    /**
     * Mapeamento de idiomas suportados
     */
    private static $supported_languages = [
        'pt' => ['true' => 'Verdadeiro', 'false' => 'Falso'],
        'pt_br' => ['true' => 'Verdadeiro', 'false' => 'Falso'],
        'en' => ['true' => 'True', 'false' => 'False'],
        'es' => ['true' => 'Verdadero', 'false' => 'Falso'],
        'fr' => ['true' => 'Vrai', 'false' => 'Faux'],
        'de' => ['true' => 'Wahr', 'false' => 'Falsch'],
        'it' => ['true' => 'Vero', 'false' => 'Falso']
    ];
    
    /**
     * Criar opções para questão True/False com idioma automático
     * 
     * @param stdClass $question Objeto da questão
     * @param bool $correctanswer Resposta correta (true/false)
     */
    public static function create_options($question, $correctanswer) {
        global $DB, $USER;
        
        // Obter idioma do utilizador atual
        $user_language = self::get_user_language();
        $labels = self::get_language_labels($user_language);
        
        // Criar resposta "Verdadeiro" (ou equivalente no idioma)
        $true_answer = new stdClass();
        $true_answer->question = $question->id;
        $true_answer->answer = $labels['true'];
        $true_answer->answerformat = 0;
        $true_answer->fraction = $correctanswer ? 1.0 : 0.0;
        $true_answer->feedback = self::generate_feedback($correctanswer, true, $user_language);
        $true_answer->feedbackformat = FORMAT_HTML;
        $true_answer_id = $DB->insert_record('question_answers', $true_answer);
        
        // Criar resposta "Falso" (ou equivalente no idioma)
        $false_answer = new stdClass();
        $false_answer->question = $question->id;
        $false_answer->answer = $labels['false'];
        $false_answer->answerformat = 0;
        $false_answer->fraction = $correctanswer ? 0.0 : 1.0;
        $false_answer->feedback = self::generate_feedback($correctanswer, false, $user_language);
        $false_answer->feedbackformat = FORMAT_HTML;
        $false_answer_id = $DB->insert_record('question_answers', $false_answer);
        
        // Configuração específica True/False
        $truefalse_config = new stdClass();
        $truefalse_config->question = $question->id;
        $truefalse_config->trueanswer = $true_answer_id;
        $truefalse_config->falseanswer = $false_answer_id;
        $truefalse_config->showstandardinstruction = 1;
        $DB->insert_record('question_truefalse', $truefalse_config);
        
        return [
            'true_answer_id' => $true_answer_id,
            'false_answer_id' => $false_answer_id,
            'language' => $user_language,
            'labels_used' => $labels
        ];
    }
    
    /**
     * Obter idioma do utilizador atual
     * 
     * @return string Código do idioma (ex: 'pt', 'en')
     */
    private static function get_user_language() {
        global $USER, $SESSION, $CFG;
        
        // Prioridade 1: Idioma definido pelo utilizador
        if (!empty($USER->lang)) {
            return self::normalize_language_code($USER->lang);
        }
        
        // Prioridade 2: Idioma da sessão
        if (!empty($SESSION->lang)) {
            return self::normalize_language_code($SESSION->lang);
        }
        
        // Prioridade 3: Idioma padrão do site
        if (!empty($CFG->lang)) {
            return self::normalize_language_code($CFG->lang);
        }
        
        // Prioridade 4: Browser language (se disponível)
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            return self::normalize_language_code($browser_lang);
        }
        
        // Fallback: inglês
        return 'en';
    }
    
    /**
     * Normalizar código de idioma
     * 
     * @param string $lang_code Código do idioma
     * @return string Código normalizado
     */
    private static function normalize_language_code($lang_code) {
        // Converter para minúsculas e obter apenas os primeiros 2-5 caracteres
        $lang_code = strtolower(trim($lang_code));
        
        // Mapeamentos especiais
        $special_mappings = [
            'pt_br' => 'pt_br',
            'pt-br' => 'pt_br',
            'pt_pt' => 'pt',
            'pt-pt' => 'pt',
            'en_us' => 'en',
            'en_gb' => 'en'
        ];
        
        if (isset($special_mappings[$lang_code])) {
            return $special_mappings[$lang_code];
        }
        
        // Para códigos simples, verificar se suportamos
        $simple_code = substr($lang_code, 0, 2);
        
        return $simple_code;
    }
    
    /**
     * Obter labels no idioma especificado
     * 
     * @param string $language Código do idioma
     * @return array Array com labels 'true' e 'false'
     */
    private static function get_language_labels($language) {
        // Tentar obter do Moodle language strings primeiro
        $moodle_labels = self::get_moodle_language_strings($language);
        if ($moodle_labels) {
            return $moodle_labels;
        }
        
        // Fallback para nosso mapeamento interno
        if (isset(self::$supported_languages[$language])) {
            return self::$supported_languages[$language];
        }
        
        // Fallback final: inglês
        return self::$supported_languages['en'];
    }
    
    /**
     * Tentar obter strings do sistema de idiomas do Moodle
     * 
     * @param string $language Código do idioma
     * @return array|null Array com labels ou null se não encontrou
     */
    private static function get_moodle_language_strings($language) {
        try {
            // Tentar obter strings do Moodle
            $string_manager = get_string_manager();
            
            // Verificar se existem strings específicas para True/False
            if ($string_manager->string_exists('true', 'qtype_truefalse', $language)) {
                return [
                    'true' => $string_manager->get_string('true', 'qtype_truefalse', null, $language),
                    'false' => $string_manager->get_string('false', 'qtype_truefalse', null, $language)
                ];
            }
            
            // Fallback para strings gerais
            if ($string_manager->string_exists('yes', 'core', $language) && 
                $string_manager->string_exists('no', 'core', $language)) {
                return [
                    'true' => $string_manager->get_string('yes', 'core', null, $language),
                    'false' => $string_manager->get_string('no', 'core', null, $language)
                ];
            }
            
        } catch (Exception $e) {
            // Se falhar, usar nosso sistema interno
            debugging('Erro ao obter strings do Moodle: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        
        return null;
    }
    
    /**
     * Gerar feedback personalizado no idioma correto
     * 
     * @param bool $correct_answer A resposta correta da questão
     * @param bool $selected_answer A resposta selecionada (true/false)
     * @param string $language Idioma para o feedback
     * @return string HTML do feedback
     */
    private static function generate_feedback($correct_answer, $selected_answer, $language) {
        $is_correct = ($correct_answer === $selected_answer);
        $labels = self::get_language_labels($language);
        
        // Obter strings de feedback no idioma correto
        $feedback_strings = self::get_feedback_strings($language);
        
        if ($is_correct) {
            return sprintf(
                '<div class="alert alert-success" style="color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid; border-radius: 0.25rem;">
                    <i class="icon fa fa-check-circle" aria-hidden="true"></i>
                    <strong>%s</strong> %s
                </div>',
                $feedback_strings['correct_title'],
                $feedback_strings['correct_message']
            );
        } else {
            $correct_text = $correct_answer ? $labels['true'] : $labels['false'];
            return sprintf(
                '<div class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid; border-radius: 0.25rem;">
                    <i class="icon fa fa-times-circle" aria-hidden="true"></i>
                    <strong>%s</strong> %s <strong>%s</strong>.
                    <br><small>%s</small>
                </div>',
                $feedback_strings['incorrect_title'],
                $feedback_strings['incorrect_message'],
                $correct_text,
                $feedback_strings['review_suggestion']
            );
        }
    }
    
    /**
     * Obter strings de feedback no idioma especificado
     * 
     * @param string $language Código do idioma
     * @return array Strings de feedback
     */
    private static function get_feedback_strings($language) {
        $feedback_strings = [
            'pt' => [
                'correct_title' => 'Correto!',
                'correct_message' => 'A sua resposta está correta.',
                'incorrect_title' => 'Incorreto.',
                'incorrect_message' => 'A resposta correta é',
                'review_suggestion' => 'Reveja o conteúdo e tente novamente.'
            ],
            'pt_br' => [
                'correct_title' => 'Correto!',
                'correct_message' => 'Sua resposta está correta.',
                'incorrect_title' => 'Incorreto.',
                'incorrect_message' => 'A resposta correta é',
                'review_suggestion' => 'Revise o conteúdo e tente novamente.'
            ],
            'en' => [
                'correct_title' => 'Correct!',
                'correct_message' => 'Your answer is correct.',
                'incorrect_title' => 'Incorrect.',
                'incorrect_message' => 'The correct answer is',
                'review_suggestion' => 'Please review the content and try again.'
            ],
            'es' => [
                'correct_title' => '¡Correcto!',
                'correct_message' => 'Su respuesta es correcta.',
                'incorrect_title' => 'Incorrecto.',
                'incorrect_message' => 'La respuesta correcta es',
                'review_suggestion' => 'Revise el contenido e inténtelo de nuevo.'
            ],
            'fr' => [
                'correct_title' => 'Correct !',
                'correct_message' => 'Votre réponse est correcte.',
                'incorrect_title' => 'Incorrect.',
                'incorrect_message' => 'La bonne réponse est',
                'review_suggestion' => 'Veuillez revoir le contenu et réessayer.'
            ]
        ];
        
        // Fallback para inglês se idioma não suportado
        return isset($feedback_strings[$language]) ? 
               $feedback_strings[$language] : 
               $feedback_strings['en'];
    }
    
    /**
     * Validar e converter resposta correta (com suporte multilingual)
     * 
     * @param mixed $correctanswer Valor da resposta
     * @param string $user_language Idioma do utilizador (opcional)
     * @return bool Valor booleano da resposta
     */
    public static function parse_correct_answer($correctanswer, $user_language = null) {
        if ($user_language === null) {
            $user_language = self::get_user_language();
        }
        
        // Converter para string e normalizar
        $value = strtolower(trim($correctanswer));
        
        // Obter valores aceitos para o idioma
        $labels = self::get_language_labels($user_language);
        $true_values = [
            // Valores do idioma atual
            strtolower($labels['true']),
            // Valores universais
            'true', '1', 'yes', 'sim', 'sí', 'oui', 'ja', 'si',
            // Valores específicos por idioma
            'verdadeiro', 'verdadero', 'vrai', 'wahr', 'vero'
        ];
        
        $false_values = [
            // Valores do idioma atual
            strtolower($labels['false']),
            // Valores universais
            'false', '0', 'no', 'não', 'nao', 'non', 'nein',
            // Valores específicos por idioma
            'falso', 'falsch', 'faux'
        ];
        
        if (in_array($value, $true_values)) {
            return true;
        } elseif (in_array($value, $false_values)) {
            return false;
        } else {
            // Fallback para conversão booleana padrão
            return filter_var($correctanswer, FILTER_VALIDATE_BOOLEAN);
        }
    }
    
    /**
     * Obter informações de debug sobre idioma
     * 
     * @return array Informações de debug
     */
    public static function get_language_debug_info() {
        global $USER, $SESSION, $CFG;
        
        return [
            'detected_language' => self::get_user_language(),
            'user_lang' => $USER->lang ?? 'not_set',
            'session_lang' => $SESSION->lang ?? 'not_set',
            'site_default_lang' => $CFG->lang ?? 'not_set',
            'browser_lang' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'not_available',
            'supported_languages' => array_keys(self::$supported_languages),
            'labels_for_current_lang' => self::get_language_labels(self::get_user_language())
        ];
    }
    
    /**
     * Função para testar diferentes idiomas (apenas para debug/desenvolvimento)
     * 
     * @param string $test_language Idioma para testar
     * @return array Resultado do teste
     */
    public static function test_language($test_language) {
        $labels = self::get_language_labels($test_language);
        $feedback_strings = self::get_feedback_strings($test_language);
        
        return [
            'language' => $test_language,
            'normalized' => self::normalize_language_code($test_language),
            'labels' => $labels,
            'feedback_strings' => $feedback_strings,
            'is_supported' => isset(self::$supported_languages[$test_language])
        ];
    }
}