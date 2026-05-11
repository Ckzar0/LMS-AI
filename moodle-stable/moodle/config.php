<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dbtype    = "mariadb";
$CFG->dblibrary = "native";
$CFG->dbhost    = "db";
$CFG->dbname    = "moodle";
$CFG->dbuser    = "moodle";
$CFG->dbpass    = "m@0dl3ing";
$CFG->prefix    = "m_";
$CFG->dboptions = array("dbcollation" => "utf8mb4_unicode_ci");

// Lógica de URL Dinâmica corrigida:
// 1. Se o FrontEnd aceder via rede interna (webserver), mantemos 'http://webserver'
// 2. Se o Browser aceder via host, usamos 'http://localhost:8080'
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'webserver') {
    $CFG->wwwroot = 'http://webserver';
} else {
    $CFG->wwwroot = 'http://localhost:8080';
}

$CFG->dataroot  = "/var/www/moodledata";
$CFG->admin     = "admin";
$CFG->directorypermissions = 0777;
require_once(__DIR__ . "/lib/setup.php");
$CFG->debug = (E_ALL | E_STRICT); $CFG->debugdisplay = 1;
