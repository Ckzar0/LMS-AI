<?php
/**
 * Web service definitions for local_wsmanageactivities
 * Versão: 23.0 - Adicionada API de Navegação Automática
 * Data: 25 de Janeiro de 2025, 18:15
 *
 * NOVA FUNCIONALIDADE v23.0:
 * - update_navigation: Adicionar navegação automática entre atividades
 * 
 * FUNCIONALIDADES v22.0:
 * - create_question_category: Criar categorias de questões
 * - add_questions_to_bank: Adicionar questões aos bancos
 * - add_random_questions_to_quiz: Configurar questões aleatórias
 * - get_question_categories: Listar categorias (auxiliar)
 * - get_bank_statistics: Estatísticas dos bancos (auxiliar)
 * 
 * COMPATIBILIDADE TOTAL: Todas as funções existentes mantidas
 * 
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // ==========================================
    // FUNÇÕES EXISTENTES (mantidas v13.2)
    // ==========================================
    'local_wsmanageactivities_create_page' => [
        'classname'   => 'local_wsmanageactivities\\external\\create_page',
        'methodname'  => 'execute',
        'description' => 'Create a new page activity',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_create_quiz' => [
        'classname'   => 'local_wsmanageactivities\\external\\create_quiz',
        'methodname'  => 'execute',
        'description' => 'Create a new quiz activity',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_add_quiz_questions' => [
        'classname'   => 'local_wsmanageactivities\\external\\add_quiz_questions',
        'methodname'  => 'execute',
        'description' => 'Add questions to a quiz (all types: multichoice, truefalse, numerical, shortanswer, matching)',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/question:add',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_get_module_types' => [
        'classname'   => 'local_wsmanageactivities\\external\\get_module_types',
        'methodname'  => 'execute',
        'description' => 'Get available module types',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'moodle/course:view',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_create_page_with_files' => [
        'classname'   => 'local_wsmanageactivities\\external\\create_page_with_files',
        'methodname'  => 'execute',
        'description' => 'Create a new page activity with automatic file upload and integration',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities,moodle/files:uploadfiles',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_upload_and_attach_files' => [
        'classname'   => 'local_wsmanageactivities\\external\\upload_and_attach_files',
        'methodname'  => 'execute',
        'description' => 'Upload files and attach to specific context',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/files:uploadfiles',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_configure_sequential_workflow' => [
        'classname'   => 'local_wsmanageactivities\\external\\configure_sequential_workflow',
        'methodname'  => 'execute',
        'description' => 'Configure sequential workflow with completion tracking and activity dependencies',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:manageactivities',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],

    // ==========================================
    // NOVA FUNÇÃO - ATUALIZAR SECÇÃO DE CURSO
    // ==========================================
    'local_wsmanageactivities_update_course_section' => [
        'classname'   => 'local_wsmanageactivities\\external\\update_course_section',
        'methodname'  => 'execute',
        'description' => 'Update the name of a course section',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:update',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    // ==========================================
    // NOVA FUNÇÃO v23.0 - NAVEGAÇÃO AUTOMÁTICA
    // ==========================================
    'local_wsmanageactivities_update_navigation' => [
        'classname'   => 'local_wsmanageactivities\\external\\update_navigation',
        'methodname'  => 'execute',
        'description' => 'Update activity content to add automatic navigation buttons between activities',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/page:view',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_create_course_with_content' => [
        'classname'   => 'local_wsmanageactivities\\external\\create_course_with_content',
        'methodname'  => 'execute',
        'description' => 'Create a complete course with content in a single call',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:create',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    // ==========================================
    // FUNÇÕES v22.0 - BANCO DE QUESTÕES
    // ==========================================
    'local_wsmanageactivities_create_question_category' => [
        'classname'   => 'local_wsmanageactivities\\external\\create_question_category',
        'methodname'  => 'execute',
        'description' => 'Create a question category for question banks',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/question:managecategory',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],

    'local_wsmanageactivities_create_qbank_module' => [
        'classname'   => 'local_wsmanageactivities\\external\\create_qbank_module',
        'methodname'  => 'execute',
        'description' => 'Create Question Bank module',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:manageactivities'
    ],
    
    'local_wsmanageactivities_add_questions_to_bank' => [
        'classname'   => 'local_wsmanageactivities\\external\\add_questions_to_bank',
        'methodname'  => 'execute',
        'description' => 'Add multiple questions to a question bank category',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/question:add',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_add_random_questions_to_quiz' => [
        'classname'   => 'local_wsmanageactivities\\external\\add_random_questions_to_quiz',
        'methodname'  => 'execute',
        'description' => 'Add random questions from a category to a quiz',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/quiz:manage',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_get_question_categories' => [
        'classname'   => 'local_wsmanageactivities\\external\\get_question_categories',
        'methodname'  => 'execute',
        'description' => 'Get question categories for a course',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'moodle/question:viewall',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_wsmanageactivities_get_bank_statistics' => [
        'classname'   => 'local_wsmanageactivities\\external\\get_bank_statistics',
        'methodname'  => 'execute',
        'description' => 'Get statistics about question banks in a course',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'moodle/question:viewall',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
];

// ==========================================
// DEFINIR SERVIÇOS
// ==========================================
$services = [
    'WS Manage Activities Service' => [
        'functions' => [
            // Funções básicas de atividades (existentes)
            'local_wsmanageactivities_create_page',
            'local_wsmanageactivities_create_quiz',
            'local_wsmanageactivities_add_quiz_questions',
            'local_wsmanageactivities_get_module_types',
            
            // Funções de gestão de ficheiros (existentes)
            'local_wsmanageactivities_create_page_with_files',
            'local_wsmanageactivities_upload_and_attach_files',
            
            // Função de workflow sequencial (existente)
            'local_wsmanageactivities_configure_sequential_workflow',

            'local_wsmanageactivities_create_course_with_content',

            // Funções de gestão de secções
            'local_wsmanageactivities_update_course_section',
            
            // NOVA FUNÇÃO v23.0 - NAVEGAÇÃO AUTOMÁTICA
            'local_wsmanageactivities_update_navigation',
            
            // FUNÇÕES v22.0 - BANCO DE QUESTÕES
            'local_wsmanageactivities_create_question_category',
            'local_wsmanageactivities_add_questions_to_bank',
            'local_wsmanageactivities_add_random_questions_to_quiz',
            'local_wsmanageactivities_get_question_categories',
            'local_wsmanageactivities_get_bank_statistics',
            
            // Funções core necessárias
            'core_course_create_courses',
            'core_course_get_courses',
            'core_course_get_contents',
            'core_webservice_get_site_info'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'wsmanageactivities_v23',
        'downloadfiles' => 1,
        'uploadfiles' => 1
    ]
];
