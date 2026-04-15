<?php
/**
 * Create Page With Files - v20.0 (FINAL HARMONIZADO)
 */
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/externallib.php');
require_once($GLOBALS['CFG']->dirroot . '/course/lib.php');
require_once($GLOBALS['CFG']->dirroot . '/mod/page/lib.php');
require_once($GLOBALS['CFG']->dirroot . '/lib/filelib.php');
require_once(__DIR__ . '/file_management/file_uploader.php');
require_once(__DIR__ . '/file_management/content_processor.php');

use external_api, external_function_parameters, external_value, external_single_structure, external_multiple_structure, external_warnings, context_course, context_module, moodle_exception, stdClass, Exception;

class create_page_with_files extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number'),
            'name' => new external_value(PARAM_TEXT, 'Page name'),
            'content' => new external_value(PARAM_RAW, 'Page content with placeholders'),
            'files' => new external_multiple_structure(
                new external_single_structure([
                    'filename' => new external_value(PARAM_TEXT, 'File name'),
                    'filecontent' => new external_value(PARAM_RAW, 'Base64 encoded file content')
                ]),
                'Files to upload and attach', VALUE_DEFAULT, []
            ),
            // ALTERAÇÃO 1 de 2: Descrição do parâmetro atualizada para clareza.
            'completiontype' => new external_value(PARAM_INT, 'Tipo de conclusão (0=nenhum, 1=automático por visualização)', VALUE_DEFAULT, 0),
            'prerequisitecmid' => new external_value(PARAM_INT, 'Course Module ID of the prerequisite activity', VALUE_DEFAULT, 0),
            'options' => new external_single_structure([
                'intro' => new external_value(PARAM_RAW, 'Page introduction', VALUE_OPTIONAL),
                'visible' => new external_value(PARAM_INT, 'Page visibility', VALUE_DEFAULT, 1)
            ], 'Additional options', VALUE_DEFAULT, [])
        ]);
    }

    public static function execute($courseid, $sectionnum, $name, $content, $files = [], $completiontype = 0, $prerequisitecmid = 0, $options = []) {
        global $DB, $CFG;
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'sectionnum', 'name', 'content', 'files', 'completiontype', 'prerequisitecmid', 'options'));
        try {
            $transaction = $DB->start_delegated_transaction();
            $page_result = self::create_basic_page_and_module($params);
            if (!$page_result['success']) {
                throw new moodle_exception('Failed to create page module: ' . $page_result['error']);
            }
            $page_id = $page_result['page_id'];
            $cm_id = $page_result['cm_id'];
            $file_urls = [];
            if (!empty($params['files'])) {
                $file_result = self::process_files($params['files'], $cm_id);
                if (!$file_result['success']) { throw new moodle_exception('Failed to process files: ' . $file_result['error']); }
                $file_urls = $file_result['file_urls'];
            }
            $processed_content = str_replace('@@PLUGINFILE@@', (new \moodle_url('/webservice/pluginfile.php', ['file' => '']))->out(false), $content);

            self::update_page_content($page_id, $processed_content);
            rebuild_course_cache($params['courseid'], true);
            $transaction->allow_commit();
            return ['success' => true, 'page_id' => $page_id, 'cm_id' => $cm_id, 'files_processed' => count($file_urls), 'page_url' => (new \moodle_url('/mod/page/view.php', ['id' => $cm_id]))->out(false), 'file_urls' => $file_urls, 'warnings' => [] ];
        } catch (Exception $e) {
            if (isset($transaction)) { $transaction->rollback($e); }
            return ['success' => false, 'warnings' => [['item' => 'page', 'message' => $e->getMessage()]]];
        }
    }

    private static function create_basic_page_and_module($params) {
        global $DB;
        try {
            $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
            $context = context_course::instance($course->id);
            self::validate_context($context);
            require_capability('mod/page:addinstance', $context);
            $module = $DB->get_record('modules', ['name' => 'page', 'visible' => 1], '*', MUST_EXIST);
            $section = $DB->get_record('course_sections', ['course' => $params['courseid'], 'section' => $params['sectionnum']]);
            if (!$section) {
                $section = new stdClass(); $section->course = $params['courseid']; $section->section = $params['sectionnum']; $section->name = null; $section->summary = ''; $section->summaryformat = FORMAT_HTML; $section->sequence = ''; $section->visible = 1; $section->availabilityjson = null; $section->timemodified = time();
                $section->id = $DB->insert_record('course_sections', $section);
            }
            $page = new stdClass();
            $page->course = $course->id;
            $page->name = $params['name'];
            $page->intro = $params['options']['intro'] ?? '';
            $page->introformat = FORMAT_HTML;
            $page->content = '';
            $page->contentformat = FORMAT_HTML;
            $page->timemodified = time();
            $page->timecreated = time();
	$page->displayoptions = serialize(['printintro' => '0', 'printlastmodified' => '0']);
            $page->id = $DB->insert_record('page', $page);
            $cm = new stdClass();
            $cm->course = $course->id;
            $cm->module = $module->id;
            $cm->instance = $page->id;
            $cm->section = $section->id;
            $cm->visible = $params['options']['visible'] ?? 1;

            // ALTERAÇÃO 2 de 2: Lógica de conclusão implementada.
            // As duas propriedades (completion e completionview) são agora definidas em conjunto.
            if (!empty($params['completiontype']) && $params['completiontype'] == 1) {
                // Define conclusão automática (2) e exige visualização (1).
                $cm->completion = 2;
                $cm->completionview = 1;
            } else {
                // Nenhum acompanhamento de conclusão.
                $cm->completion = 0;
                $cm->completionview = 0;
            }

            if (!empty($params['prerequisitecmid'])) {
                $availability_rules = ['op' => '&', 'c' => [['type' => 'completion', 'cm' => $params['prerequisitecmid'], 'e' => 1]], 'showc' => [true]];
                $cm->availability = json_encode($availability_rules);
            } else {
                $cm->availability = null;
            }
            
            // A propriedade 'completionview' foi removida desta linha porque já é tratada acima.
            $cm->idnumber = ''; $cm->added = time(); $cm->score = 0; $cm->indent = 0; $cm->visibleoncoursepage = $cm->visible; $cm->visibleold = $cm->visible; $cm->groupmode = 0; $cm->groupingid = 0; $cm->completiongradeitemnumber = null; $cm->completionpassgrade = 0; $cm->completionexpected = 0; $cm->showdescription = 0;
            
            $cm->id = $DB->insert_record('course_modules', $cm);
            $sequence = $section->sequence;
            if (!empty($sequence)) { $sequence .= ',' . $cm->id; } else { $sequence = $cm->id; }
            $DB->set_field('course_sections', 'sequence', $sequence, ['id' => $section->id]);
            return ['success' => true, 'page_id' => $page->id, 'cm_id' => $cm->id];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private static function process_files($files, $cm_id) { /* ...código auxiliar... */ return ['success' => true, 'file_urls' => []]; }
    private static function update_page_content($page_id, $content) { /* ...código auxiliar... */ }
    
    public static function execute_returns() {
        return new external_single_structure([ 'success' => new external_value(PARAM_BOOL), 'page_id' => new external_value(PARAM_INT), 'cm_id' => new external_value(PARAM_INT), 'files_processed' => new external_value(PARAM_INT), 'page_url' => new external_value(PARAM_URL), 'file_urls' => new external_multiple_structure( new external_single_structure([ 'filename' => new external_value(PARAM_TEXT), 'url' => new external_value(PARAM_URL), 'size' => new external_value(PARAM_INT), 'mimetype' => new external_value(PARAM_TEXT) ])), 'warnings' => new external_warnings() ]);
    }
}