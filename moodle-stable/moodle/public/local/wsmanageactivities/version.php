<?php
/**
 * Plugin version and other meta-data are defined here.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_wsmanageactivities';
$plugin->version = 2026042201;
$plugin->release = 'v1.1.0 - Production Stable (Questions Association Fix)';
$plugin->requires = 2024100700;          // Moodle 5.1 (Outubro 2024)
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array();
$plugin->supported = [51, 52];          // Moodle 5.1 e 5.2 (Preview)
