<?php
/**
 * Página de Acesso Direto e Gestão de Cursos
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 AI LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();
$context = context_system::instance();

$PAGE->set_url(new moodle_url('/local/wsmanageactivities/courses.php'));
$PAGE->set_context($context);
$PAGE->set_title("Gestão de Cursos - AI LMS");
$PAGE->set_heading("Fábrica de Cursos - Meus Cursos Criados");

// Processar pedido de exclusão de curso
$deleteid = optional_param('delete', 0, PARAM_INT);
if ($deleteid > 1 && confirm_sesskey()) { // Não apagar o curso do site id=1
    try {
        delete_course($deleteid, false); // Apagar o curso (segunda opção é falso para não mostrar aviso, pois faremos manual)
        redirect(new moodle_url('/local/wsmanageactivities/courses.php'), "Curso eliminado com sucesso!", 2);
    } catch (Exception $e) {
        error_log("Erro ao eliminar curso: " . $e->getMessage());
    }
}

echo $OUTPUT->header();

global $DB, $CFG, $USER;

// Obter cursos (excluindo o curso do site id=1)
$courses = $DB->get_records('course', array(), 'id DESC', '*', 0, 50);

echo '<div style="max-width: 1100px; margin: 0 auto; padding: 20px; font-family: sans-serif;">';

// Cabeçalho com Botão de Criar
echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 6px solid #6B46C1;">';
echo '<div><h2 style="margin: 0; color: #2D3748;">Painel da Fábrica de Cursos</h2><p style="margin: 5px 0 0 0; color: #718096;">Gere e cria os teus conteúdos de forma rápida.</p></div>';
$upload_url = new moodle_url('/local/wsmanageactivities/upload.php');
echo '<a href="' . $upload_url . '" style="background: #6B46C1; color: white; padding: 14px 28px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 1.1em; transition: all 0.3s; box-shadow: 0 4px 6px rgba(107, 70, 193, 0.2);" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 12px rgba(107, 70, 193, 0.3)\';" onmouseout="this.style.transform=\'translateY(0)\';">🚀 Criar Novo Curso (AI JSON)</a>';
echo '</div>';

echo '<h3 style="color: #4A5568; margin-bottom: 25px; font-weight: 600;">📚 Cursos Disponíveis na Plataforma (' . (max(0, count($courses) - 1)) . ')</h3>';

if (empty($courses) || count($courses) <= 1) {
    echo '<div style="background: white; border: 2px dashed #E2E8F0; padding: 60px; text-align: center; border-radius: 16px; color: #718096;">';
    echo '<p style="font-size: 1.4em; font-weight: bold; margin-bottom: 10px;">Ainda não existem cursos criados.</p>';
    echo '<p>Os teus cursos gerados por inteligência artificial aparecerão aqui.</p>';
    echo '</div>';
} else {
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">';
    
    foreach ($courses as $course) {
        if ($course->id == 1) continue; // Pular o curso "Site"
        
        $course_url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        $edit_url = $CFG->wwwroot . '/course/edit.php?id=' . $course->id;
        $delete_url = new moodle_url('/local/wsmanageactivities/courses.php', ['delete' => $course->id, 'sesskey' => sesskey()]);
        $repair_url = new moodle_url('/local/wsmanageactivities/fix_images.php', ['courseid' => $course->id]);
        
        echo '<div style="background: white; border: 1px solid #EDF2F7; border-radius: 16px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); transition: transform 0.2s ease, box-shadow 0.2s ease; border-top: 6px solid #6B46C1;" onmouseover="this.style.transform=\'translateY(-5px)\'; this.style.boxShadow=\'0 10px 20px rgba(0,0,0,0.08)\';" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 10px rgba(0,0,0,0.03)\';">';
        echo '<h4 style="margin-top: 0; margin-bottom: 15px; color: #1A202C; font-size: 1.25em; line-height: 1.3; min-height: 3.5em; overflow: hidden;">' . htmlspecialchars($course->fullname) . '</h4>';
        echo '<div style="margin-bottom: 25px; background: #F7FAFC; padding: 10px; border-radius: 8px;">';
        echo '<p style="color: #4A5568; font-size: 0.85em; margin: 0;"><strong>ID:</strong> ' . $course->id . '</p>';
        echo '<p style="color: #4A5568; font-size: 0.85em; margin: 3px 0 0 0;"><strong>Código:</strong> ' . htmlspecialchars($course->shortname) . '</p>';
        echo '</div>';
        
        echo '<div style="display: flex; flex-direction: column; gap: 10px;">';
        echo '<a href="' . $course_url . '" style="background: #6B46C1; color: white; text-align: center; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 0.95em;">ENTRAR NO CURSO</a>';
        echo '<a href="' . $repair_url . '" style="background: #3182CE; color: white; text-align: center; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 0.85em;">🔧 REPARAR IMAGENS</a>';
        
        echo '<div style="display: flex; gap: 10px;">';
        echo '<a href="' . $edit_url . '" style="flex: 1; background: #E2E8F0; color: #4A5568; text-align: center; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 0.85em;">EDITAR</a>';
        
        $confirm_msg = "AVISO CRÍTICO:\n\nTem a certeza que deseja eliminar o curso: " . addslashes($course->fullname) . "?\n\nEsta ação irá apagar permanentemente todas as atividades, notas e ficheiros deste curso. Esta operação NÃO PODE SER DESFEITA!";
        echo '<a href="' . $delete_url . '" onclick="return confirm(\'' . $confirm_msg . '\')" style="flex: 1; background: #FFF5F5; color: #C53030; text-align: center; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 0.85em; border: 1px solid #FEB2B2; transition: all 0.2s;" onmouseover="this.style.background=\'#FEB2B2\'; this.style.color=\'white\';" onmouseout="this.style.background=\'#FFF5F5\'; this.style.color=\'#C53030\';">ELIMINAR</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
}

echo '</div>';

echo $OUTPUT->footer();
