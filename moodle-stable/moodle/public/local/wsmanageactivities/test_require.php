<?php
require_once(__DIR__ . '/../../config.php');
echo "CFG->dirroot = " . $CFG->dirroot . "<br>";
$path = $CFG->dirroot . '/local/wsmanageactivities/classes/CourseManager.php';
echo "Trying to load: " . $path . "<br>";
echo "File exists: " . (file_exists($path) ? 'YES' : 'NO') . "<br>";
echo "Is readable: " . (is_readable($path) ? 'YES' : 'NO') . "<br>";
require_once($path);
echo "SUCCESS!<br>";
