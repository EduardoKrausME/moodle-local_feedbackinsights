<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_feedbackinsights\privacy;

use context;
use context_course;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_feedbackinsights\service\analysis_repository;

/**
 * Privacy provider for local_feedbackinsights.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    core_userlist_provider {

    /**
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_feedbackinsights_an', [
            'courseid' => 'privacy:metadata:analysis:courseid',
            'source' => 'privacy:metadata:analysis:source',
            'sourceinstanceid' => 'privacy:metadata:analysis:sourceinstanceid',
            'questionids' => 'privacy:metadata:analysis:questionids',
            'createdby' => 'privacy:metadata:analysis:createdby',
            'resultsjson' => 'privacy:metadata:analysis:resultsjson',
            'timecreated' => 'privacy:metadata:analysis:timecreated',
        ], 'privacy:metadata:analysis');

        $collection->add_database_table('local_feedbackinsights_mem', [
            'analysisid' => 'privacy:metadata:member:analysisid',
            'sourceanswerid' => 'privacy:metadata:member:sourceanswerid',
            'userid' => 'privacy:metadata:member:userid',
            'themesjson' => 'privacy:metadata:member:themesjson',
        ], 'privacy:metadata:member');

        $collection->add_external_location_link('local_ai_bridge', [
            'responseid' => 'privacy:metadata:bridge:responseid',
            'questionid' => 'privacy:metadata:bridge:questionid',
            'text' => 'privacy:metadata:bridge:text',
        ], 'privacy:metadata:bridge');

        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_feedbackinsights_an} a ON a.courseid = ctx.instanceid
             LEFT JOIN {local_feedbackinsights_mem} m ON m.analysisid = a.id
                 WHERE ctx.contextlevel = :contextlevel
                   AND (a.createdby = :creator OR m.userid = :member)";
        return (new contextlist())->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'creator' => $userid,
            'member' => $userid,
        ]);
    }

    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_course) {
                continue;
            }

            $sql = "SELECT DISTINCT a.*
                      FROM {local_feedbackinsights_an} a
                 LEFT JOIN {local_feedbackinsights_mem} m ON m.analysisid = a.id
                     WHERE a.courseid = :courseid
                       AND (a.createdby = :creator OR m.userid = :member)
                  ORDER BY a.timecreated ASC";
            $records = $DB->get_records_sql($sql, [
                'courseid' => $context->instanceid,
                'creator' => $userid,
                'member' => $userid,
            ]);

            foreach ($records as $record) {
                $data = (object)[
                    'source' => $record->source,
                    'sourceinstanceid' => $record->sourceinstanceid,
                    'questionids' => json_decode($record->questionids, true),
                    'responsecount' => $record->responsecount,
                    'emptycount' => $record->emptycount,
                    'results' => json_decode($record->resultsjson, true),
                    'timecreated' => transform::datetime($record->timecreated),
                ];
                writer::with_context($context)->export_data([
                    get_string('pluginname', 'local_feedbackinsights'),
                    (string)$record->id,
                ], $data);
            }
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param context $context Parameter context.
     * @return void Return value.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_course) {
            return;
        }
        $analysisids = $DB->get_fieldset_select(
            'local_feedbackinsights_an',
            'id',
            'courseid = :courseid',
            ['courseid' => $context->instanceid]
        );
        analysis_repository::delete_ids($analysisids);
    }

    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_course) {
                continue;
            }
            $sql = "SELECT DISTINCT a.id
                      FROM {local_feedbackinsights_an} a
                 LEFT JOIN {local_feedbackinsights_mem} m ON m.analysisid = a.id
                     WHERE a.courseid = :courseid
                       AND (a.createdby = :creator OR m.userid = :member)";
            $ids = $DB->get_fieldset_sql($sql, [
                'courseid' => $context->instanceid,
                'creator' => $userid,
                'member' => $userid,
            ]);
            analysis_repository::delete_ids($ids);
        }
    }

    /**
     * Method get_users_in_context.
     *
     * @param userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_course) {
            return;
        }

        $userlist->add_from_sql(
            'createdby',
            'SELECT createdby
               FROM {local_feedbackinsights_an}
              WHERE courseid = :courseid',
            ['courseid' => $context->instanceid]
        );

        $userlist->add_from_sql(
            'userid',
            'SELECT m.userid
               FROM {local_feedbackinsights_mem} m
               JOIN {local_feedbackinsights_an} a ON a.id = m.analysisid
              WHERE a.courseid = :courseid
                AND m.userid IS NOT NULL',
            ['courseid' => $context->instanceid]
        );
    }

    /**
     * Method delete_data_for_users.
     *
     * @param approved_userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_course) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$usersql1, $params1] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'creator');
        $params1['courseid'] = $context->instanceid;
        $creatorids = $DB->get_fieldset_select(
            'local_feedbackinsights_an',
            'id',
            "courseid = :courseid AND createdby {$usersql1}",
            $params1
        );

        [$usersql2, $params2] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'member');
        $params2['courseid'] = $context->instanceid;
        $memberids = $DB->get_fieldset_sql(
            "SELECT DISTINCT a.id
               FROM {local_feedbackinsights_an} a
               JOIN {local_feedbackinsights_mem} m ON m.analysisid = a.id
              WHERE a.courseid = :courseid
                AND m.userid {$usersql2}",
            $params2
        );

        analysis_repository::delete_ids(
            array_values(array_unique(array_merge($creatorids, $memberids)))
        );
    }
}
