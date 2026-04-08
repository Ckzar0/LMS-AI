<?php
/**
 * Get Bank Statistics API - local_wsmanageactivities v22.0
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    22.0
 * @date       22 de Janeiro de 2025, 17:30
 */

namespace local_wsmanageactivities\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;
use moodle_exception;
use Exception;

class get_bank_statistics extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'category_name' => new external_value(PARAM_TEXT, 'Specific category name (optional)', VALUE_DEFAULT, ''),
            'include_question_types' => new external_value(PARAM_BOOL, 'Include question type breakdown', VALUE_DEFAULT, true)
        ]);
    }

    public static function execute($courseid, $category_name = '', $include_question_types = true) {
        global $DB;
        
        $params = self::validate_parameters(self::execute_parameters(), 
            compact('courseid', 'category_name', 'include_question_types'));
        
        // Validar curso e permissões
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/question:viewall', $context);
        
        try {
            $statistics = [];
            
            if (!empty($params['category_name'])) {
                // Estatísticas para categoria específica
                $category = $DB->get_record('question_categories', [
                    'contextid' => $context->id,
                    'name' => $params['category_name']
                ], '*', MUST_EXIST);
                
                $stats = self::get_category_statistics($category, $params['include_question_types']);
                $statistics[] = $stats;
                
            } else {
                // Estatísticas para todas as categorias
                $categories = $DB->get_records('question_categories', [
                    'contextid' => $context->id,
                    'parent' => ['>', 0] // Excluir categoria "Top"
                ], 'name ASC');
                
                foreach ($categories as $category) {
                    $stats = self::get_category_statistics($category, $params['include_question_types']);
                    if ($stats['question_count'] > 0) { // Só incluir categorias com questões
                        $statistics[] = $stats;
                    }
                }
            }
            
            // Calcular totais gerais
            $total_questions = array_sum(array_column($statistics, 'question_count'));
            $total_categories = count($statistics);
            
            return [
                'success' => true,
                'course_id' => (int)$params['courseid'],
                'context_id' => (int)$context->id,
                'total_categories_with_questions' => $total_categories,
                'total_questions_in_bank' => $total_questions,
                'average_questions_per_category' => $total_categories > 0 ? round($total_questions / $total_categories, 1) : 0,
                'category_statistics' => $statistics,
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Statistics retrieved successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'course_id' => (int)$params['courseid'],
                'context_id' => 0,
                'total_categories_with_questions' => 0,
                'total_questions_in_bank' => 0,
                'average_questions_per_category' => 0,
                'category_statistics' => [],
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    private static function get_category_statistics($category, $include_question_types) {
        global $DB;
        
        // Contagem total de questões
        $total_questions = $DB->count_records_sql("
            SELECT COUNT(DISTINCT q.id)
            FROM {question} q
            JOIN {question_versions} qv ON qv.questionid = q.id
            JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
            WHERE qbe.questioncategoryid = ?
            AND qv.status = 'ready'
        ", [$category->id]);
        
        $stats = [
            'category_id' => (int)$category->id,
            'category_name' => $category->name,
            'question_count' => (int)$total_questions
        ];
        
        if ($include_question_types && $total_questions > 0) {
            // Breakdown por tipo de questão
            $type_breakdown = $DB->get_records_sql("
                SELECT q.qtype, COUNT(DISTINCT q.id) as count
                FROM {question} q
                JOIN {question_versions} qv ON qv.questionid = q.id
                JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                WHERE qbe.questioncategoryid = ?
                AND qv.status = 'ready'
                GROUP BY q.qtype
                ORDER BY count DESC, q.qtype ASC
            ", [$category->id]);
            
            $question_types = [];
            foreach ($type_breakdown as $type) {
                $question_types[] = [
                    'type' => $type->qtype,
                    'count' => (int)$type->count,
                    'percentage' => round(($type->count / $total_questions) * 100, 1)
                ];
            }
            
            $stats['question_types'] = $question_types;
            $stats['most_common_type'] = !empty($question_types) ? $question_types[0]['type'] : 'none';
        }
        
        // Questões criadas recentemente (últimos 7 dias)
        $recent_questions = $DB->count_records_sql("
            SELECT COUNT(DISTINCT q.id)
            FROM {question} q
            JOIN {question_versions} qv ON qv.questionid = q.id
            JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
            WHERE qbe.questioncategoryid = ?
            AND qv.status = 'ready'
            AND q.timecreated > ?
        ", [$category->id, time() - (7 * 24 * 60 * 60)]);
        
        $stats['recent_questions_7days'] = (int)$recent_questions;
        $stats['suitability_for_random_quiz'] = self::assess_random_quiz_suitability($total_questions);
        
        return $stats;
    }
    
    private static function assess_random_quiz_suitability($question_count) {
        if ($question_count >= 20) {
            return 'excellent';
        } elseif ($question_count >= 10) {
            return 'good';
        } elseif ($question_count >= 5) {
            return 'fair';
        } elseif ($question_count >= 2) {
            return 'limited';
        } else {
            return 'insufficient';
        }
    }

public static function execute_returns() {
    return new external_single_structure([
        'success' => new external_value(PARAM_BOOL, 'Success flag'),
        'course_id' => new external_value(PARAM_INT, 'Course ID'),
        'context_id' => new external_value(PARAM_INT, 'Context ID'),
        'total_categories_with_questions' => new external_value(PARAM_INT, 'Categories with questions'),
        'total_questions_in_bank' => new external_value(PARAM_INT, 'Total questions in all banks'),
        'average_questions_per_category' => new external_value(PARAM_FLOAT, 'Average questions per category'),
        'category_statistics' => new external_multiple_structure(  // ✅ CORREÇÃO
            new external_single_structure([
                'category_id' => new external_value(PARAM_INT, 'Category ID'),
                'category_name' => new external_value(PARAM_TEXT, 'Category name'),
                'question_count' => new external_value(PARAM_INT, 'Number of questions'),
                'question_types' => new external_multiple_structure(  // ✅ CORREÇÃO
                    new external_single_structure([
                        'type' => new external_value(PARAM_TEXT, 'Question type'),
                        'count' => new external_value(PARAM_INT, 'Number of questions'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Percentage')
                    ]), 'Question type breakdown', VALUE_OPTIONAL
                ),
                'most_common_type' => new external_value(PARAM_TEXT, 'Most common question type', VALUE_OPTIONAL),
                'recent_questions_7days' => new external_value(PARAM_INT, 'Questions created in last 7 days', VALUE_OPTIONAL),
                'suitability_for_random_quiz' => new external_value(PARAM_TEXT, 'Suitability assessment', VALUE_OPTIONAL)
            ])
        ),
        'timestamp' => new external_value(PARAM_TEXT, 'Generation timestamp'),
        'message' => new external_value(PARAM_TEXT, 'Result message')
    ]);
}
}