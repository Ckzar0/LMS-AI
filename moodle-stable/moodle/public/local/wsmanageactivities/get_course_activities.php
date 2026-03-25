<?php
/**
 * Webservice para retornar lista ordenada de atividades de um curso
 * Usado pela navegação dinâmica para funcionar após backup/restore
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);

// Obter informações do curso
$modinfo = get_fast_modinfo($courseid);
$cms = $modinfo->get_cms();

$activities = [];

foreach ($cms as $cm) {
    // Apenas pages e quizzes com navegação
    if ($cm->modname === 'page' || $cm->modname === 'quiz') {
        $activities[] = [
            'cmid' => $cm->id,
            'modname' => $cm->modname,
            'name' => $cm->name,
            'section' => $cm->sectionnum
        ];
    }
}

// Ordenar por secção e depois por ordem dentro da secção
usort($activities, function($a, $b) {
    if ($a['section'] === $b['section']) {
        // Mesma secção, manter ordem original
        return 0;
    }
    return $a['section'] - $b['section'];
});

// Retornar JSON
header('Content-Type: application/json');
echo json_encode($activities);

