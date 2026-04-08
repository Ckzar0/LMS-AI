<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php'); // For course_update_section()

class local_wsmanageactivities_externallib extends external_api {

    /**
     * Updates the name of course sections.
     * @param int $courseid The course ID.
     * @param array $sections An array of section data, each with 'section' (0-indexed section number) and 'name'.
     * @return array Status of the update.
     */
    public static function update_sections($courseid, $sections) {
        global $DB;

        // Parameter validation
        self::validate_parameters(self::update_sections_parameters(), [
            'courseid' => $courseid,
            'sections' => $sections
        ]);

        $result = ['status' => 'success', 'message' => []];

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        foreach ($sections as $sectiondata) {
            $sectionnumber = $sectiondata['section'];
            $sectionname = $sectiondata['name'];

            // Moodle sections are 0-indexed in DB, but often 1-indexed in UI/some functions
            // get_course_section takes 0-indexed section numbers
            $sectionrecord = get_course_section_cm($sectionnumber, $courseid, false);

            if ($sectionrecord) {
                // Update the section name.
                $sectionrecord->name = $sectionname;
                
                // Use core Moodle function to update section
                $transaction = $DB->start_delegated_transaction();
                try {
                    course_update_section($course, $sectionrecord, true); // true for update name, summary etc.
                    $transaction->allow_commit();
                    $result['message'][] = "Section $sectionnumber (ID: {$sectionrecord->id}) updated with name: '$sectionname'.";
                } catch (Exception $e) {
                    $transaction->rollback($e);
                    $result['status'] = 'error';
                    $result['message'][] = "Error updating section $sectionnumber (ID: {$sectionrecord->id}) with name '$sectionname': " . $e->getMessage();
                    // Don't exit, try to update other sections.
                }

            } else {
                $result['status'] = 'error';
                $result['message'][] = "Section $sectionnumber not found in course $courseid.";
            }
        }

        return $result;
    }

    /**
     * Describes the parameters for update_sections.
     * @return external_function_parameters
     */
    public static function update_sections_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course ID.'),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'section' => new external_value(PARAM_INT, 'The 0-indexed section number.'),
                    'name' => new external_value(PARAM_TEXT, 'The new name for the section.')
                ]),
                'An array of section data to update.'
            ),
        ]);
    }

    /**
     * Describes the return values for update_sections.
     * @return external_function_parameters
     */
    public static function update_sections_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Overall status of the update (success/error).'),
            'message' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Details about each section update.'),
                'Array of messages for each section operation.'
            )
        ]);
    }

}

