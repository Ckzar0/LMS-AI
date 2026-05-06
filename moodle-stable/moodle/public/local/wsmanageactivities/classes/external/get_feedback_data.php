<?php
namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_module;

class get_feedback_data extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    public static function execute($cmid) {
        global $DB;

        try {
            $params = self::validate_parameters(self::execute_parameters(), [
                'cmid' => $cmid
            ]);

            $cm = get_coursemodule_from_id('feedback', $params['cmid'], 0, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            self::validate_context($context);

            $feedback = $DB->get_record('feedback', ['id' => $cm->instance], '*', MUST_EXIST);
            
            // Get feedback items
            $items = $DB->get_records('feedback_item', ['feedback' => $feedback->id, 'template' => 0], 'position ASC');
            
            $processed_items = [];
            foreach ($items as $item) {
                if (empty($item->typ) || $item->typ === 'label') continue;

                $options = [];
                if ($item->typ === 'multichoice') {
                    // Parser robusto para a escala r>>>>>...<<<<<1
                    $presentation = $item->presentation;
                    $clean = str_replace(['r>>>>>', '<<<<<1'], '', $presentation);
                    $parts = explode('|', $clean);
                    foreach ($parts as $p) {
                        $options[] = trim($p);
                    }
                }

                $processed_items[] = [
                    'id' => (int)$item->id,
                    'name' => (string)$item->name,
                    'type' => (string)$item->typ,
                    'required' => (bool)$item->required,
                    'position' => (int)$item->position,
                    'options' => $options
                ];
            }

            return [
                'id' => (int)$feedback->id,
                'name' => (string)$feedback->name,
                'intro' => strip_tags($feedback->intro),
                'items' => $processed_items
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Feedback ID'),
            'name' => new external_value(PARAM_TEXT, 'Feedback name'),
            'intro' => new external_value(PARAM_TEXT, 'Feedback introduction'),
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Item ID'),
                    'name' => new external_value(PARAM_TEXT, 'Question text'),
                    'type' => new external_value(PARAM_TEXT, 'Question type (multichoice/textarea)'),
                    'required' => new external_value(PARAM_BOOL, 'Is required'),
                    'position' => new external_value(PARAM_INT, 'Position'),
                    'options' => new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'Option text'), 'List of options for multichoice', VALUE_OPTIONAL
                    )
                ])
            )
        ]);
    }
}
