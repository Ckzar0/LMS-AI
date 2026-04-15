<?php
/**
 * @package    local_wsmanageactivities
 * @copyright  2024 BMad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities;


class CourseManager {

    /**
     * Cria ou localiza um curso com base nos dados do JSON.
     */
    public function process_course($data) {
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/course/lib.php');

        // Aceitar tanto 'code' como 'shortname'
        $base_shortname = $data['shortname'] ?? $data['code'] ?? 'CURSO';
        // Aceitar tanto 'name' como 'fullname'
        $fullname  = $data['fullname'] ?? $data['name'] ?? 'Sem Nome';
        // Aceitar tanto 'category' como 'category_id'
        $category  = $data['category_id'] ?? $data['category'] ?? 1;
        // Aceitar tanto 'description' como 'summary'
        $summary = $data['summary'] ?? $data['description'] ?? '';

        // Gerar shortname único
        $shortname = $base_shortname;
        $counter = 1;
        while ($DB->record_exists('course', ['shortname' => $shortname])) {
            $shortname = $base_shortname . '_' . $counter;
            $counter++;
            if ($counter > 100) {
                $shortname = $base_shortname . '_' . time();
                break;
            }
        }

        $course_data = new \stdClass();
        $course_data->fullname = $fullname;
        $course_data->shortname = $shortname;
        $course_data->category = $category;
        $course_data->summary = $summary;
        $course_data->format = 'topics';
        $course_data->showgrades = 1;
        $course_data->newsitems = 5;
        $course_data->maxbytes = 0;
        $course_data->showreports = 0;
        $course_data->visible = 1;
        $course_data->enablecompletion = 1; // CRÍTICO: Ativar acompanhamento de conclusão

        $course = create_course($course_data);
        
        error_log("CourseManager - Curso criado: ID {$course->id}, Shortname: {$shortname}");
        
        return $course->id;
    }
}
