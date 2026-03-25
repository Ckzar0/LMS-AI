<?php
/**
 * Portuguese language strings.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Gestão de Atividades via Web Services';
$string['privacy:metadata'] = 'O plugin de Gestão de Atividades via Web Services não armazena dados pessoais.';

// Capabilities
$string['wsmanageactivities:createpage'] = 'Criar atividades página via web service';
$string['wsmanageactivities:createquiz'] = 'Criar atividades quiz via web service';
$string['wsmanageactivities:managequestions'] = 'Gerir questões de quiz via web service';
$string['wsmanageactivities:viewmodules'] = 'Ver informações de módulos via web service';

// Web service descriptions
$string['createpage'] = 'Criar atividade página';
$string['createquiz'] = 'Criar atividade quiz';
$string['addquizquestions'] = 'Adicionar questões ao quiz';
$string['getmoduletypes'] = 'Obter tipos de módulos disponíveis';

// Error messages
$string['error:invalidcourseid'] = 'ID de curso inválido';
$string['error:invalidsection'] = 'Número de seção inválido';
$string['error:nopermission'] = 'Não tem permissão para realizar esta ação';
$string['error:modulenotfound'] = 'Tipo de módulo não encontrado';
$string['error:questioncreationfailed'] = 'Falha ao criar questão';
$string['error:quiznotfound'] = 'Quiz não encontrado';
$string['error:invalidquestiontype'] = 'Tipo de questão inválido';
$string['error:invalidmoduledata'] = 'Dados de módulo inválidos fornecidos';

// Success messages
$string['success:pagecreated'] = 'Atividade página criada com sucesso';
$string['success:quizcreated'] = 'Atividade quiz criada com sucesso';
$string['success:questionsadded'] = 'Questões adicionadas ao quiz com sucesso';

// Question types
$string['questiontype:multichoice'] = 'Escolha múltipla';
$string['questiontype:shortanswer'] = 'Resposta curta';
$string['questiontype:essay'] = 'Ensaio';
$string['questiontype:truefalse'] = 'Verdadeiro/Falso';

// Configuration
$string['defaultquizattempts'] = 'Tentativas padrão de quiz';
$string['defaultquizattempts_desc'] = 'Número padrão de tentativas permitidas para novos quizzes';
$string['defaultquizgrade'] = 'Nota padrão de quiz';
$string['defaultquizgrade_desc'] = 'Nota máxima padrão para novos quizzes';
$string['defaultquestionmark'] = 'Pontuação padrão de questão';
$string['defaultquestionmark_desc'] = 'Pontuação padrão para novas questões';
// Questões expandidas
$string['questiontype_multichoice'] = 'Questão de Múltipla Escolha';
$string['questiontype_shortanswer'] = 'Questão de Resposta Curta';
$string['questiontype_essay'] = 'Questão de Ensaio';
$string['questiontype_truefalse'] = 'Questão Verdadeiro/Falso';
$string['questiontype_numerical'] = 'Questão Numérica';
$string['questiontype_description'] = 'Descrição/Informação';

$string['questions_created'] = 'Questões criadas com sucesso';
$string['questions_failed'] = 'Algumas questões falharam ao criar';
$string['invalid_question_type'] = 'Tipo de questão inválido especificado';
$string['question_category_created'] = 'Categoria de questões criada automaticamente';
