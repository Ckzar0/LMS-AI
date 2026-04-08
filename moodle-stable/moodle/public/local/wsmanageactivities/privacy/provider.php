<?php
/**
 * Privacy provider for local_wsmanageactivities.
 *
 * @package    local_wsmanageactivities
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wsmanageactivities\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for the Web Services Activity Management plugin.
 *
 * This plugin does not store any personal data itself, but it creates
 * activities that may contain personal data through the standard Moodle
 * activity modules.
 */
class provider implements 
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
    
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
        // This plugin doesn't store personal data directly, but it creates activities
        // that may contain personal data through standard Moodle modules.
        
        $collection->add_external_location_link(
            'created_activities',
            [
                'userid' => 'privacy:metadata:created_activities:userid',
                'courseid' => 'privacy:metadata:created_activities:courseid', 
                'activitytype' => 'privacy:metadata:created_activities:activitytype',
                'activityname' => 'privacy:metadata:created_activities:activityname',
                'timecreated' => 'privacy:metadata:created_activities:timecreated'
            ],
            'privacy:metadata:created_activities'
        );
        
        // Log data (if debugging is enabled)
        $collection->add_external_location_link(
            'debug_logs',
            [
                'userid' => 'privacy:metadata:debug_logs:userid',
                'action' => 'privacy:metadata:debug_logs:action',
                'data' => 'privacy:metadata:debug_logs:data',
                'timestamp' => 'privacy:metadata:debug_logs:timestamp'
            ],
            'privacy:metadata:debug_logs'
        );
        
        return $collection;
    }
    
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // This plugin doesn't store personal data directly in its own tables.
        // Any personal data is stored in the activities it creates, which are
        // handled by their respective privacy providers.
        
        $contextlist = new contextlist();
        
        // We don't add any contexts because this plugin doesn't store
        // personal data directly.
        
        return $contextlist;
    }
    
    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        // This plugin doesn't store personal data directly, so no users to add.
    }
    
    /**
     * Export personal data for the given approved_contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // This plugin doesn't store personal data directly, so nothing to export.
        // The activities created by this plugin will be exported by their
        // respective privacy providers (mod_page, mod_quiz, etc.).
    }
    
    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // This plugin doesn't store personal data directly, so nothing to delete.
        // The activities created by this plugin will be handled by their
        // respective privacy providers.
    }
    
    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // This plugin doesn't store personal data directly, so nothing to delete.
    }
    
    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // This plugin doesn't store personal data directly, so nothing to delete.
    }
}