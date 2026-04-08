<?php
/**
 * Plugin settings and administration menu items.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 AI LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Adicionar link na categoria "Cursos" da administração
    $ADMIN->add('courses', new admin_externalpage(
        'local_wsmanageactivities_factory',
        'Fábrica de Cursos (AI)',
        new moodle_url('/local/wsmanageactivities/courses.php'),
        'moodle/site:config'
    ));
}
