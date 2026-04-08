<?php
/**
 * Update Navigation API - Sistema de Navegação Automática Moodle
 * 
 * API para adicionar botões de navegação automática entre atividades,
 * mantendo retrocompatibilidade total com cursos existentes.
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    1.0
 * @date       25 de Janeiro de 2025, 18:00
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;
use moodle_exception;
use stdClass;
use Exception;

class update_navigation extends external_api {

    /**
     * Definir parâmetros de entrada
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'activity_id' => new external_value(PARAM_INT, 'ID da atividade a atualizar'),
            'activity_module' => new external_value(PARAM_TEXT, 'Tipo da atividade: page ou quiz'),
            'next_activity_id' => new external_value(PARAM_INT, 'ID da próxima atividade (0 se última)'),
            'next_activity_module' => new external_value(PARAM_TEXT, 'Tipo da próxima: page ou quiz', VALUE_DEFAULT, ''),
            'button_text' => new external_value(PARAM_TEXT, 'Texto do botão', VALUE_DEFAULT, 'CONTINUAR →'),
            'button_style' => new external_value(PARAM_TEXT, 'Estilo: primary ou success', VALUE_DEFAULT, 'primary'),
            'placeholder' => new external_value(PARAM_TEXT, 'Placeholder a substituir', VALUE_DEFAULT, '{{NAVIGATION_NEXT}}')
        ]);
    }

    /**
     * Executar a atualização de navegação
     */
    public static function execute($activity_id, $activity_module, $next_activity_id, 
                                  $next_activity_module = '', $button_text = 'CONTINUAR →', 
                                  $button_style = 'primary', $placeholder = '{{NAVIGATION_NEXT}}') {
        global $DB;
        
        // Validar parâmetros
        $params = self::validate_parameters(self::execute_parameters(), 
            compact('activity_id', 'activity_module', 'next_activity_id', 
                   'next_activity_module', 'button_text', 'button_style', 'placeholder'));
        
        try {
            // Validar tipo de módulo
            if (!in_array($params['activity_module'], ['page', 'quiz'])) {
                throw new moodle_exception('Invalid activity module type');
            }
            
            // Para quiz, não fazemos nada (decisão de design)
            if ($params['activity_module'] === 'quiz') {
                return [
                    'success' => true,
                    'updated' => false,
                    'message' => 'Navigation not added to quiz (by design)',
                    'activity_id' => $params['activity_id'],
                    'activity_type' => $params['activity_module'],
                    'navigation_added' => false,
                    'button_html' => ''
                ];
            }
            
            // Obter a atividade página
            $page = $DB->get_record('page', ['id' => $params['activity_id']], '*', MUST_EXIST);
            
            // Obter contexto e validar permissões
            $cm = get_coursemodule_from_instance('page', $page->id, $page->course, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            self::validate_context($context);
            require_capability('mod/page:view', $context);
            
            // Se não há próxima atividade (última), remover placeholder se existir
            if ($params['next_activity_id'] == 0) {
                // Verificar se o placeholder existe
                if (strpos($page->content, $params['placeholder']) !== false) {
                    // Remover o placeholder
                    $new_content = str_replace($params['placeholder'], '', $page->content);
                    
                    // Atualizar no banco de dados
                    // $DB->set_field('page', 'content', $new_content, ['id' => $page->id]);
                    // $DB->set_field('page', 'timemodified', time(), ['id' => $page->id]);
                    
                    // Limpar caches
                    rebuild_course_cache($page->course, true);
                    
                    return [
                        'success' => true,
                        'updated' => true,
                        'message' => 'Last activity - placeholder removed',
                        'activity_id' => $params['activity_id'],
                        'activity_type' => $params['activity_module'],
                        'navigation_added' => false,
                        'button_html' => '',
                        'placeholder_removed' => true
                    ];
                }
                
                return [
                    'success' => true,
                    'updated' => false,
                    'message' => 'Last activity - no placeholder found',
                    'activity_id' => $params['activity_id'],
                    'activity_type' => $params['activity_module'],
                    'navigation_added' => false,
                    'button_html' => ''
                ];
            }
            
            // Verificar se o placeholder existe no conteúdo
            if (strpos($page->content, $params['placeholder']) === false) {
                return [
                    'success' => true,
                    'updated' => false,
                    'message' => 'Placeholder not found in content',
                    'activity_id' => $params['activity_id'],
                    'activity_type' => $params['activity_module'],
                    'navigation_added' => false,
                    'button_html' => '',
                    'placeholder_searched' => $params['placeholder']
                ];
            }
            
            // Obter informações da próxima atividade
            if (empty($params['next_activity_module'])) {
                // Tentar detectar o tipo automaticamente
                $params['next_activity_module'] = self::detect_activity_type($params['next_activity_id']);
            }
            
            // Obter CM da próxima atividade para construir URL
            $next_cm_id = self::get_cm_id_from_instance($params['next_activity_id'], $params['next_activity_module']);
            
            if (!$next_cm_id) {
                throw new moodle_exception('Could not find course module for next activity');
            }
            
            // Gerar HTML do botão
            $button_html = self::generate_navigation_button(
                $next_cm_id,
                $params['next_activity_module'],
                $params['button_text'],
                $params['button_style']
            );
            
            // Substituir placeholder pelo botão
            $new_content = str_replace($params['placeholder'], $button_html, $page->content);
            
            // Atualizar no banco de dados
            // $DB->set_field('page', 'content', $new_content, ['id' => $page->id]);
            // $DB->set_field('page', 'timemodified', time(), ['id' => $page->id]);
            
            // Limpar caches
            rebuild_course_cache($page->course, true);
            
            return [
                'success' => true,
                'updated' => true,
                'message' => 'Navigation button added successfully',
                'activity_id' => $params['activity_id'],
                'activity_type' => $params['activity_module'],
                'next_activity_id' => $params['next_activity_id'],
                'next_activity_type' => $params['next_activity_module'],
                'navigation_added' => true,
                'button_html' => $button_html,
                'button_text' => $params['button_text'],
                'button_style' => $params['button_style']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'updated' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'activity_id' => $params['activity_id'],
                'activity_type' => $params['activity_module'],
                'navigation_added' => false,
                'button_html' => '',
                'error_details' => [
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ]
            ];
        }
    }
    
    /**
     * Gerar HTML do botão de navegação
     */
    private static function generate_navigation_button($next_cm_id, $next_module, $button_text, $button_style) {
        global $CFG;
        
        // Definir cores baseadas no estilo
        $bg_color = ($button_style === 'success') ? '#28a745' : '#6B46C1';
        $hover_color = ($button_style === 'success') ? '#218838' : '#5a3db3';
        
        // Construir URL da próxima atividade
        $next_url = $CFG->wwwroot . '/mod/' . $next_module . '/view.php?id=' . $next_cm_id;
        
        // Template HTML do botão com completion tracking
        $button_html = '
<div style="text-align: center; margin: 40px 0; padding: 20px 0;">
    <button onclick="
        // Marcar atividade como completa se completion tracking estiver ativo
        if (typeof M !== \'undefined\' && M.core_completion && M.core_completion.toggle_completion) {
            try {
                M.core_completion.toggle_completion();
            } catch(e) {
                console.log(\'Completion tracking não disponível\');
            }
        }
        
        // Pequeno delay para garantir que completion foi registrado
        setTimeout(function() {
            window.location.href = \'' . $next_url . '\';
        }, 300);
        
        // Feedback visual
        this.innerHTML = \'Carregando...\';
        this.disabled = true;
        " 
        style="
            background: ' . $bg_color . '; 
            color: white; 
            border: none; 
            padding: 18px 60px; 
            font-size: 16px; 
            font-weight: 700; 
            letter-spacing: 2px; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-transform: uppercase;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        "
        onmouseover="this.style.background=\'' . $hover_color . '\'; this.style.transform=\'scale(1.05)\'; this.style.boxShadow=\'0 6px 12px rgba(0, 0, 0, 0.15)\';"
        onmouseout="this.style.background=\'' . $bg_color . '\'; this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 4px 6px rgba(0, 0, 0, 0.1)\';">
        ' . htmlspecialchars($button_text) . '
    </button>
</div>';
        
        return $button_html;
    }
    
    /**
     * Detectar tipo de atividade automaticamente
     */
    private static function detect_activity_type($instance_id) {
        global $DB;
        
        // Tentar encontrar em páginas
        if ($DB->record_exists('page', ['id' => $instance_id])) {
            return 'page';
        }
        
        // Tentar encontrar em quizzes
        if ($DB->record_exists('quiz', ['id' => $instance_id])) {
            return 'quiz';
        }
        
        // Adicionar outros tipos conforme necessário
        
        throw new moodle_exception('Could not detect activity type for instance ' . $instance_id);
    }
    
    /**
     * Obter CM ID a partir do instance ID e tipo
     */
    private static function get_cm_id_from_instance($instance_id, $module_type) {
        global $DB;
        
        // Obter o ID do módulo
        $module = $DB->get_record('modules', ['name' => $module_type], 'id', MUST_EXIST);
        
        // Obter o course module
        $cm = $DB->get_record('course_modules', [
            'module' => $module->id,
            'instance' => $instance_id
        ], 'id');
        
        return $cm ? $cm->id : false;
    }
    
    /**
     * Definir estrutura de retorno
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Se a operação foi bem-sucedida'),
            'updated' => new external_value(PARAM_BOOL, 'Se o conteúdo foi atualizado'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem de status'),
            'activity_id' => new external_value(PARAM_INT, 'ID da atividade'),
            'activity_type' => new external_value(PARAM_TEXT, 'Tipo da atividade'),
            'next_activity_id' => new external_value(PARAM_INT, 'ID da próxima atividade', VALUE_OPTIONAL),
            'next_activity_type' => new external_value(PARAM_TEXT, 'Tipo da próxima atividade', VALUE_OPTIONAL),
            'navigation_added' => new external_value(PARAM_BOOL, 'Se navegação foi adicionada'),
            'button_html' => new external_value(PARAM_RAW, 'HTML do botão gerado'),
            'button_text' => new external_value(PARAM_TEXT, 'Texto do botão', VALUE_OPTIONAL),
            'button_style' => new external_value(PARAM_TEXT, 'Estilo do botão', VALUE_OPTIONAL),
            'placeholder_searched' => new external_value(PARAM_TEXT, 'Placeholder procurado', VALUE_OPTIONAL),
            'error_details' => new external_single_structure([
                'line' => new external_value(PARAM_INT, 'Linha do erro', VALUE_OPTIONAL),
                'file' => new external_value(PARAM_TEXT, 'Arquivo do erro', VALUE_OPTIONAL)
            ], 'Detalhes do erro', VALUE_OPTIONAL)
        ]);
    }
}