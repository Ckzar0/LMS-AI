<?php

namespace local_wsmanageactivities\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class update_course_section extends external_api {

    /**
     * Retorna os parâmetros esperados para a função `update_course_section`.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID do curso.'),
            'sectionnum' => new external_value(PARAM_INT, 'Número da secção a atualizar (0 para secção 0, 1 para secção 1, etc.).'),
            'sectionname' => new external_value(PARAM_TEXT, 'Novo nome para a secção.'),
        ]);
    }

    /**
     * Atualiza o nome de uma secção de curso.
     *
     * @param int $courseid ID do curso.
     * @param int $sectionnum Número da secção.
     * @param string $sectionname Novo nome da secção.
     * @return array Confirmação de sucesso.
     * @throws moodle_exception Se o curso ou a secção não forem encontrados, ou se o utilizador não tiver permissões.
     */
    public static function execute($courseid, $sectionnum, $sectionname) {
        global $DB;

        self::validate_parameters(self::execute_parameters(), compact('courseid', 'sectionnum', 'sectionname'));

        $context = context_course::instance($courseid, MUST_EXIST);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        // Verifique se a seção existe para o curso especificado
        $course_section = $DB->get_record('course_sections',
            ['course' => $courseid, 'section' => $sectionnum], '*', MUST_EXIST);

        // Atualize o nome da seção
        $course_section->name = $sectionname;
        $course_section->timemodified = time(); // Atualiza a data de modificação
        $DB->update_record('course_sections', $course_section);

        // Atualizar o cache do curso para que a alteração seja visível imediatamente
        \core_course_external::update_course_sections_cache($courseid);


        return ['success' => true, 'message' => "Secção {$sectionnum} do curso {$courseid} atualizada para '{$sectionname}'."];
    }

    /**
     * Retorna a descrição da estrutura de dados de retorno da função `update_course_section`.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True se a operação foi bem-sucedida.'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem de status da operação.'),
        ]);
    }
}

