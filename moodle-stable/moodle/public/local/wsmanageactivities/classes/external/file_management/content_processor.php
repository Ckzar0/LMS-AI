<?php
/**
 * Content Processor para local_wsmanageactivities
 * Versão: 1.0
 * Data: 16 de Julho de 2025, 18:30
 * 
 * Processamento de conteúdo HTML com injeção automática de URLs de ficheiros
 * Suporta placeholders e integração transparente com atividades Moodle
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\external\file_management;

defined('MOODLE_INTERNAL') || die();

class content_processor {
    
    /**
     * Substituir placeholders no conteúdo por URLs reais
     * 
     * @param string $content Conteúdo HTML com placeholders
     * @param array $file_mappings Mapeamento placeholder => URL
     * @return string Conteúdo processado
     */
    public static function replace_placeholders($content, $file_mappings) {
        if (empty($file_mappings) || !is_array($file_mappings)) {
            return $content;
        }
        
        foreach ($file_mappings as $placeholder => $url) {
            if (is_string($placeholder) && is_string($url)) {
                $content = str_replace($placeholder, $url, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Injeção automática de imagens usando padrão {{IMAGE_N}}
     * 
     * @param string $content Conteúdo HTML
     * @param array $file_urls Array de URLs dos ficheiros
     * @return string Conteúdo com imagens injetadas
     */
    public static function auto_inject_images($content, $file_urls) {
        if (empty($file_urls) || !is_array($file_urls)) {
            return $content;
        }
        
        // Procurar por placeholders como {{IMAGE_0}}, {{IMAGE_1}}, etc.
        $pattern = '/\{\{IMAGE_(\d+)\}\}/';
        
        $content = preg_replace_callback($pattern, function($matches) use ($file_urls) {
            $index = (int)$matches[1];
            
            if (isset($file_urls[$index])) {
                $url = $file_urls[$index]['url'];
                $filename = $file_urls[$index]['filename'];
                
                // Gerar tag IMG com atributos apropriados
                return self::generate_image_tag($url, $filename);
            }
            
            // Se não encontrou ficheiro, manter placeholder
            return $matches[0];
        }, $content);
        
        return $content;
    }
    
    /**
     * Injeção automática de ficheiros usando padrão {{FILE_N}}
     * 
     * @param string $content Conteúdo HTML
     * @param array $file_urls Array de URLs dos ficheiros
     * @return string Conteúdo com links injetados
     */
    public static function auto_inject_files($content, $file_urls) {
        if (empty($file_urls) || !is_array($file_urls)) {
            return $content;
        }
        
        // Procurar por placeholders como {{FILE_0}}, {{FILE_1}}, etc.
        $pattern = '/\{\{FILE_(\d+)\}\}/';
        
        $content = preg_replace_callback($pattern, function($matches) use ($file_urls) {
            $index = (int)$matches[1];
            
            if (isset($file_urls[$index])) {
                $url = $file_urls[$index]['url'];
                $filename = $file_urls[$index]['filename'];
                $size = isset($file_urls[$index]['size']) ? $file_urls[$index]['size'] : 0;
                
                // Gerar link com informações do ficheiro
                return self::generate_file_link($url, $filename, $size);
            }
            
            // Se não encontrou ficheiro, manter placeholder
            return $matches[0];
        }, $content);
        
        return $content;
    }
    
    /**
     * Processamento completo de conteúdo com todos os tipos de placeholders
     * 
     * @param string $content Conteúdo HTML original
     * @param array $file_urls Array de URLs dos ficheiros
     * @param array $custom_mappings Mapeamentos personalizados adicionais
     * @return string Conteúdo completamente processado
     */
    public static function process_content($content, $file_urls = [], $custom_mappings = []) {
        // Aplicar mapeamentos personalizados primeiro
        if (!empty($custom_mappings)) {
            $content = self::replace_placeholders($content, $custom_mappings);
        }
        
        // Aplicar injeção automática de imagens
        $content = self::auto_inject_images($content, $file_urls);
        
        // Aplicar injeção automática de ficheiros
        $content = self::auto_inject_files($content, $file_urls);
        
        // Limpeza final
        $content = self::cleanup_content($content);
        
        return $content;
    }
    
    /**
     * Gerar tag IMG com atributos apropriados
     * 
     * @param string $url URL da imagem
     * @param string $filename Nome do ficheiro
     * @return string Tag IMG HTML
     */
    private static function generate_image_tag($url, $filename) {
        $alt_text = self::generate_alt_text($filename);
        
        return sprintf(
            '<img src="%s" alt="%s" title="%s" style="max-width: 100%%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" class="moodle-auto-image">',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($alt_text, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($filename, ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Gerar link para ficheiro com informações
     * 
     * @param string $url URL do ficheiro
     * @param string $filename Nome do ficheiro
     * @param int $size Tamanho do ficheiro em bytes
     * @return string Link HTML
     */
    private static function generate_file_link($url, $filename, $size = 0) {
        $icon = self::get_file_icon($filename);
        $size_text = $size > 0 ? self::format_bytes($size) : '';
        $extension = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
        
        $link_html = sprintf(
            '<div class="moodle-file-link" style="display: inline-block; margin: 8px 0; padding: 8px 12px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
                <span class="file-icon" style="margin-right: 8px;">%s</span>
                <a href="%s" target="_blank" style="text-decoration: none; color: #0066cc; font-weight: 500;">%s</a>
                <span class="file-info" style="margin-left: 8px; font-size: 0.9em; color: #666;">
                    %s%s
                </span>
            </div>',
            $icon,
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'),
            $extension ? "($extension)" : '',
            $size_text ? " • $size_text" : ''
        );
        
        return $link_html;
    }
    
    /**
     * Gerar texto alternativo para imagens
     * 
     * @param string $filename Nome do ficheiro
     * @return string Texto alternativo
     */
    private static function generate_alt_text($filename) {
        $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        
        // Converter underscores e hífens em espaços
        $alt = str_replace(['_', '-'], ' ', $name_without_ext);
        
        // Capitalizar palavras
        $alt = ucwords($alt);
        
        return $alt;
    }
    
    /**
     * Obter ícone para tipo de ficheiro
     * 
     * @param string $filename Nome do ficheiro
     * @return string Ícone HTML
     */
    private static function get_file_icon($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $icons = [
            'pdf' => '📄',
            'doc' => '📝',
            'docx' => '📝',
            'txt' => '📄',
            'rtf' => '📄',
            'xls' => '📊',
            'xlsx' => '📊',
            'ppt' => '📊',
            'pptx' => '📊',
            'zip' => '📦',
            'rar' => '📦',
            '7z' => '📦',
            'mp4' => '🎬',
            'avi' => '🎬',
            'mov' => '🎬',
            'mp3' => '🎵',
            'wav' => '🎵',
            'ogg' => '🎵',
            'png' => '🖼️',
            'jpg' => '🖼️',
            'jpeg' => '🖼️',
            'gif' => '🖼️',
            'svg' => '🖼️'
        ];
        
        return isset($icons[$extension]) ? $icons[$extension] : '📁';
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
    
    /**
     * Limpeza final do conteúdo
     * 
     * @param string $content Conteúdo HTML
     * @return string Conteúdo limpo
     */
    private static function cleanup_content($content) {
        // Remover placeholders não utilizados
        $content = preg_replace('/\{\{(IMAGE|FILE)_\d+\}\}/', '', $content);
        
        // Remover espaços desnecessários
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Remover quebras de linha excessivas
        $content = preg_replace('/\n\s*\n/', '\n', $content);
        
        return trim($content);
    }
    
    /**
     * Extrair placeholders existentes no conteúdo
     * 
     * @param string $content Conteúdo HTML
     * @return array Lista de placeholders encontrados
     */
    public static function extract_placeholders($content) {
        $placeholders = [];
        
        // Procurar por placeholders IMAGE
        preg_match_all('/\{\{IMAGE_(\d+)\}\}/', $content, $image_matches);
        if (!empty($image_matches[0])) {
            foreach ($image_matches[0] as $index => $match) {
                $placeholders[] = [
                    'type' => 'image',
                    'placeholder' => $match,
                    'index' => (int)$image_matches[1][$index]
                ];
            }
        }
        
        // Procurar por placeholders FILE
        preg_match_all('/\{\{FILE_(\d+)\}\}/', $content, $file_matches);
        if (!empty($file_matches[0])) {
            foreach ($file_matches[0] as $index => $match) {
                $placeholders[] = [
                    'type' => 'file',
                    'placeholder' => $match,
                    'index' => (int)$file_matches[1][$index]
                ];
            }
        }
        
        return $placeholders;
    }
    
    /**
     * Validar se conteúdo tem placeholders válidos
     * 
     * @param string $content Conteúdo HTML
     * @param int $file_count Número de ficheiros disponíveis
     * @return array Resultado da validação
     */
    public static function validate_placeholders($content, $file_count) {
        $placeholders = self::extract_placeholders($content);
        $issues = [];
        
        foreach ($placeholders as $placeholder) {
            if ($placeholder['index'] >= $file_count) {
                $issues[] = [
                    'type' => 'missing_file',
                    'placeholder' => $placeholder['placeholder'],
                    'index' => $placeholder['index'],
                    'message' => "Ficheiro {$placeholder['index']} não existe (apenas {$file_count} ficheiros disponíveis)"
                ];
            }
        }
        
        return [
            'valid' => empty($issues),
            'placeholders_found' => count($placeholders),
            'issues' => $issues
        ];
    }
    
    /**
     * Gerar conteúdo de demonstração com placeholders
     * 
     * @param int $image_count Número de imagens
     * @param int $file_count Número de ficheiros
     * @return string Conteúdo HTML de demonstração
     */
    public static function generate_demo_content($image_count = 2, $file_count = 1) {
        $content = "<h2>Demonstração de Conteúdo com Ficheiros</h2>\n";
        $content .= "<p>Este é um exemplo de como integrar ficheiros no conteúdo:</p>\n\n";
        
        // Adicionar imagens
        for ($i = 0; $i < $image_count; $i++) {
            $content .= "<h3>Imagem " . ($i + 1) . "</h3>\n";
            $content .= "<p>Veja a imagem abaixo:</p>\n";
            $content .= "{{IMAGE_{$i}}}\n\n";
        }
        
        // Adicionar ficheiros
        for ($i = 0; $i < $file_count; $i++) {
            $content .= "<h3>Recurso " . ($i + 1) . "</h3>\n";
            $content .= "<p>Faça o download do ficheiro:</p>\n";
            $content .= "{{FILE_{$i}}}\n\n";
        }
        
        $content .= "<p><em>Nota: Os placeholders serão substituídos automaticamente pelas URLs dos ficheiros carregados.</em></p>";
        
        return $content;
    }
}
