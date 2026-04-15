<?php
/**
 * External API tests for local_wsmanageactivities.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

use externallib_advanced_testcase;
use context_course;
use local_wsmanageactivities\external\create_page;
use local_wsmanageactivities\external\create_quiz;
use local_wsmanageactivities\external\add_quiz_questions;
use local_wsmanageactivities\external\get_module_types;

/**
 * Tests for external API functions.
 */
class external_test extends externallib_advanced_testcase {
    
    /** @var stdClass Course object */
    private $course;
    
    /** @var stdClass User object */
    private $user;
    
    /** @var context_course Course context */
    private $context;
    
    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        
        $this->resetAfterTest(true);
        
        // Create course
        $this->course = $this->getDataGenerator()->create_course([
            'fullname' => 'Test Course',
            'shortname' => 'TEST',
            'numsections' => 5
        ]);
        
        $this->context = context_course::instance($this->course->id);
        
        // Create user with editing teacher role
        $this->user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'editingteacher');
        
        $this->setUser($this->user);
    }
    
    /**
     * Test create_page basic functionality.
     */
    public function test_create_page_basic() {
        // Test data
        $courseid = $this->course->id;
        $sectionnum = 1;
        $name = 'Test Page';
        $content = '<h1>Test Content</h1><p>This is a test page.</p>';
        $options = [
            'intro' => 'Test introduction',
            'visible' => true
        ];
        
        // Execute function
        $result = create_page::execute($courseid, $sectionnum, $name, $content, $options);
        
        // Assertions
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['id']);
        $this->assertGreaterThan(0, $result['instance']);
        $this->assertEquals($name, $result['name']);
        $this->assertEquals($sectionnum, $result['section']);
        $this->assertTrue($result['visible']);
        $this->assertEmpty($result['warnings']);
        
        // Verify page was created in database
        global $DB;
        $page = $DB->get_record('page', ['id' => $result['instance']]);
        $this->assertNotFalse($page);
        $this->assertEquals($name, $page->name);
        
        // Verify course module was created
        $cm = $DB->get_record('course_modules', ['id' => $result['id']]);
        $this->assertNotFalse($cm);
        $this->assertEquals($this->course->id, $cm->course);
    }
    
    /**
     * Test create_page with advanced options.
     */
    public function test_create_page_with_options() {
        $courseid = $this->course->id;
        $sectionnum = 2;
        $name = 'Advanced Test Page';
        $content = '<h2>Advanced Content</h2>';
        $options = [
            'intro' => 'Advanced introduction',
            'introformat' => FORMAT_HTML,
            'visible' => true,
            'groupmode' => SEPARATEGROUPS,
            'completion' => [
                'completionview' => true,
                'completionexpected' => time() + 86400
            ]
        ];
        
        $result = create_page::execute($courseid, $sectionnum, $name, $content, $options);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($name, $result['name']);
        
        // Verify completion settings
        global $DB;
        $cm = $DB->get_record('course_modules', ['id' => $result['id']]);
        $this->assertEquals(COMPLETION_TRACKING_MANUAL, $cm->completion);
        $this->assertEquals(1, $cm->completionview);
        $this->assertEquals(SEPARATEGROUPS, $cm->groupmode);
    }
    
    /**
     * Test create_quiz basic functionality.
     */
    public function test_create_quiz_basic() {
        $courseid = $this->course->id;
        $sectionnum = 1;
        $name = 'Test Quiz';
        $config = [
            'intro' => 'Quiz introduction',
            'grade' => 10.0,
            'attempts' => 3,
            'timelimit' => 1800
        ];
        
        $result = create_quiz::execute($courseid, $sectionnum, $name, $config);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['id']);
        $this->assertGreaterThan(0, $result['instance']);
        $this->assertEquals($name, $result['name']);
        $this->assertEquals(10.0, $result['grade']);
        $this->assertEquals(3, $result['attempts']);
        $this->assertEquals(0, $result['questions_added']);
        $this->assertEmpty($result['warnings']);
        
        // Verify quiz was created
        global $DB;
        $quiz = $DB->get_record('quiz', ['id' => $result['instance']]);
        $this->assertNotFalse($quiz);
        $this->assertEquals($name, $quiz->name);
        $this->assertEquals(10.0, $quiz->grade);
        $this->assertEquals(3, $quiz->attempts);
        $this->assertEquals(1800, $quiz->timelimit);
    }
    
    /**
     * Test create_quiz with questions.
     */
    public function test_create_quiz_with_questions() {
        $courseid = $this->course->id;
        $sectionnum = 2;
        $name = 'Quiz with Questions';
        $config = [
            'intro' => 'Quiz with questions',
            'grade' => 20.0,
            'attempts' => 2
        ];
        $questions = [
            [
                'type' => 'multichoice',
                'name' => 'MC Question',
                'questiontext' => 'What is 2+2?',
                'mark' => 2.0,
                'questiondata' => json_encode([
                    'answers' => [
                        ['text' => '4', 'fraction' => 1],
                        ['text' => '3', 'fraction' => 0],
                        ['text' => '5', 'fraction' => 0]
                    ]
                ])
            ],
            [
                'type' => 'truefalse',
                'name' => 'TF Question',
                'questiontext' => 'The sky is blue?',
                'mark' => 1.0,
                'questiondata' => json_encode(['correctanswer' => true])
            ]
        ];
        
        $result = create_quiz::execute($courseid, $sectionnum, $name, $config, $questions);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['questions_added']);
        
        // Verify questions were added
        global $DB;
        $quiz = $DB->get_record('quiz', ['id' => $result['instance']]);
        $quizquestions = $DB->get_records('quiz_slots', ['quizid' => $quiz->id]);
        $this->assertCount(2, $quizquestions);
    }
    
    /**
     * Test add_quiz_questions functionality.
     */
    public function test_add_quiz_questions() {
        // First create a quiz
        $courseid = $this->course->id;
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $courseid,
            'name' => 'Test Quiz for Questions'
        ]);
        
        $questions = [
            [
                'type' => 'shortanswer',
                'name' => 'SA Question',
                'questiontext' => 'What is the capital of Portugal?',
                'mark' => 3.0,
                'questiondata' => json_encode([
                    'answers' => [['text' => 'Lisboa', 'fraction' => 1]]
                ])
            ],
            [
                'type' => 'essay',
                'name' => 'Essay Question',
                'questiontext' => 'Describe your experience with Moodle.',
                'mark' => 5.0
            ]
        ];
        
        $result = add_quiz_questions::execute($quiz->cmid, $questions, 'cmid');
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($quiz->cmid, $result['quizid']);
        $this->assertEquals($quiz->id, $result['quiz_instance']);
        $this->assertEquals(2, $result['questions_requested']);
        $this->assertEquals(2, $result['questions_added']);
        $this->assertEmpty($result['warnings']);
    }
    
    /**
     * Test get_module_types functionality.
     */
    public function test_get_module_types() {
        $courseid = $this->course->id;
        
        // Test all modules
        $result = get_module_types::execute($courseid, 'all');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('modules', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('supported_count', $result);
        $this->assertArrayHasKey('plugin_info', $result);
        $this->assertGreaterThan(0, $result['total_count']);
        $this->assertEmpty($result['warnings']);
        
        // Check that page and quiz are in the list
        $modulenames = array_column($result['modules'], 'name');
        $this->assertContains('page', $modulenames);
        $this->assertContains('quiz', $modulenames);
        
        // Test supported only
        $supportedresult = get_module_types::execute($courseid, 'supported');
        $this->assertLessThanOrEqual($result['total_count'], $supportedresult['total_count']);
        $this->assertEquals($supportedresult['supported_count'], $supportedresult['total_count']);
    }
    
    /**
     * Test permissions and error handling.
     */
    public function test_permissions_and_errors() {
        // Create user without permissions
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, 'student');
        $this->setUser($user);
        
        // Try to create page without permission
        $result = create_page::execute(
            $this->course->id, 
            1, 
            'Unauthorized Page', 
            'Content'
        );
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertEquals(0, $result['id']);
        
        // Test with invalid course ID
        $this->setUser($this->user); // Back to teacher
        $result = create_page::execute(999999, 1, 'Test', 'Content');
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['warnings']);
    }
    
    /**
     * Test parameter validation.
     */
    public function test_parameter_validation() {
        // Test empty name
        $result = create_page::execute($this->course->id, 1, '', 'Content');
        $this->assertFalse($result['success']);
        
        // Test empty content
        $result = create_page::execute($this->course->id, 1, 'Test', '');
        $this->assertFalse($result['success']);
        
        // Test invalid section
        $result = create_page::execute($this->course->id, -1, 'Test', 'Content');
        $this->assertFalse($result['success']);
    }
    
    /**
     * Test question type validation.
     */
    public function test_question_type_validation() {
        // Create quiz first
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $this->course->id,
            'name' => 'Validation Test Quiz'
        ]);
        
        // Test invalid question type
        $questions = [
            [
                'type' => 'invalid_type',
                'name' => 'Invalid Question',
                'questiontext' => 'This should fail'
            ]
        ];
        
        $result = add_quiz_questions::execute($quiz->cmid, $questions);
        
        // Should succeed but add 0 questions due to invalid type
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['questions_added']);
        $this->assertEquals(1, $result['questions_requested']);
    }
    
    /**
     * Test HTML content sanitization.
     */
    public function test_html_sanitization() {
        $courseid = $this->course->id;
        $sectionnum = 1;
        $name = 'HTML Test Page';
        $content = '<h1>Safe Content</h1><script>alert("unsafe");</script><p>More content</p>';
        
        $result = create_page::execute($courseid, $sectionnum, $name, $content);
        
        $this->assertTrue($result['success']);
        
        // Verify content was sanitized (script tag removed)
        global $DB;
        $page = $DB->get_record('page', ['id' => $result['instance']]);
        $this->assertStringNotContainsString('<script>', $page->content);
        $this->assertStringContainsString('<h1>Safe Content</h1>', $page->content);
    }
    
    /**
     * Test availability conditions.
     */
    public function test_availability_conditions() {
        $courseid = $this->course->id;
        $sectionnum = 1;
        $name = 'Conditional Page';
        $content = 'This page has availability conditions';
        $options = [
            'availability' => '{"op":"&","c":[{"type":"date","d":">=","t":' . (time() + 86400) . '}],"showc":[]}'
        ];
        
        $result = create_page::execute($courseid, $sectionnum, $name, $content, $options);
        
        $this->assertTrue($result['success']);
        
        // Verify availability was set
        global $DB;
        $cm = $DB->get_record('course_modules', ['id' => $result['id']]);
        $this->assertNotNull($cm->availability);
        $this->assertStringContainsString('date', $cm->availability);
    }
}