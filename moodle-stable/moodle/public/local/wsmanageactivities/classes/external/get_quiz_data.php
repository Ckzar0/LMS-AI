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

/**
 * External API to get quiz questions and options
 */
class get_quiz_data extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    public static function execute($cmid) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        // 1. Get Quiz Instance
        $cm = get_coursemodule_from_id('quiz', $params['cmid'], 0, false, MUST_EXIST);
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
        
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // 2. Get Questions linked to this quiz (Moodle 5.x Structure)
        $sql = "SELECT q.*, slot.slot, slot.page
                FROM {question} q
                JOIN {question_versions} qv ON qv.questionid = q.id
                JOIN {question_references} qr ON qr.questionbankentryid = qv.questionbankentryid
                JOIN {quiz_slots} slot ON slot.id = qr.itemid
                WHERE slot.quizid = ? 
                  AND qr.component = 'mod_quiz' 
                  AND qr.questionarea = 'slot'
                ORDER BY slot.slot ASC";
        
        $questions_res = $DB->get_records_sql($sql, [$quiz->id]);
        $questions = [];

        foreach ($questions_res as $q) {
            $options = [];
            $subquestions = [];
            
            if ($q->qtype === 'match') {
                // Get subquestions and their correct answers
                $subqs = $DB->get_records('qtype_match_subquestions', ['questionid' => $q->id], 'id ASC');
                $all_right_answers = [];
                foreach ($subqs as $sq) {
                    if (!empty($sq->answertext)) {
                        $all_right_answers[] = strip_tags($sq->answertext);
                    }
                    $subquestions[] = [
                        'id' => $sq->id,
                        'text' => strip_tags($sq->questiontext),
                        'correct_answer' => strip_tags($sq->answertext)
                    ];
                }
                // Options for matching are the list of all unique correct answers
                $unique_answers = array_unique($all_right_answers);
                foreach ($unique_answers as $idx => $ans) {
                    $options[] = [
                        'id' => $idx,
                        'text' => $ans,
                        'is_correct' => true // In match, all provided options are "correct" for some subquestion
                    ];
                }
            } else {
                // Standard logic for other types (multichoice, etc.)
                $answers = $DB->get_records('question_answers', ['question' => $q->id], 'id ASC');
                foreach ($answers as $ans) {
                    $options[] = [
                        'id' => $ans->id,
                        'text' => strip_tags($ans->answer),
                        'is_correct' => ($ans->fraction > 0)
                    ];
                }
            }

            $questions[] = [
                'id' => $q->id,
                'slot' => $q->slot,
                'type' => $q->qtype,
                'text' => strip_tags($q->questiontext),
                'options' => $options,
                'subquestions' => $subquestions
            ];
        }

        return [
            'id' => $quiz->id,
            'name' => $quiz->name,
            'intro' => strip_tags($quiz->intro),
            'questions' => $questions,
            'passgrade' => $quiz->sumgrades * 0.75 // Default 75%
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Quiz ID'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'intro' => new external_value(PARAM_TEXT, 'Quiz introduction'),
            'passgrade' => new external_value(PARAM_FLOAT, 'Passing grade'),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Question ID'),
                    'slot' => new external_value(PARAM_INT, 'Slot number'),
                    'type' => new external_value(PARAM_TEXT, 'Question type'),
                    'text' => new external_value(PARAM_TEXT, 'Question text'),
                    'options' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Option ID'),
                            'text' => new external_value(PARAM_TEXT, 'Option text'),
                            'is_correct' => new external_value(PARAM_BOOL, 'Is this the correct answer')
                        ])
                    ),
                    'subquestions' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Sub-question ID'),
                            'text' => new external_value(PARAM_TEXT, 'Sub-question text'),
                            'correct_answer' => new external_value(PARAM_TEXT, 'Correct answer for this sub-question')
                        ]), 'List of sub-questions for matching types', VALUE_OPTIONAL
                    )
                ])
            )
        ]);
    }
}
