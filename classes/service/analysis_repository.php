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

namespace local_feedbackinsights\service;

use local_feedbackinsights\model\response_record;

/**
 * Persistence for structured analyses.
 *
 * Raw prompts, raw AI responses and original answer text are never stored here.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class analysis_repository {
    /**
     * Method save.
     *
     * @param array $metadata Parameter metadata.
     * @param array $result Parameter result.
     * @param array $responses Parameter responses.
     * @return int Return value.
     */
    public static function save(array $metadata, array $result, array $responses): int {
        global $DB, $USER;

        $now = time();
        $record = (object)[
            'courseid' => $metadata['courseid'],
            'source' => $metadata['source'],
            'sourceinstanceid' => $metadata['sourceinstanceid'],
            'questionids' => json_encode(array_values($metadata['questionids'])),
            'datefrom' => $metadata['datefrom'],
            'dateuntil' => $metadata['dateuntil'],
            'createdby' => $USER->id,
            'responsecount' => count($responses),
            'emptycount' => $metadata['emptycount'],
            'invalididcount' => $metadata['invalididcount'],
            'resultsjson' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timecreated' => $now,
        ];

        $transaction = $DB->start_delegated_transaction();
        $analysisid = (int)$DB->insert_record('local_feedbackinsights_an', $record);

        $themebyresponse = [];
        foreach ($result['themes'] as $theme) {
            foreach ($theme['source_response_ids'] as $responseid) {
                $themebyresponse[$responseid][] = $theme['key'];
            }
        }

        foreach ($responses as $response) {
            $DB->insert_record('local_feedbackinsights_mem', (object)[
                'analysisid' => $analysisid,
                'sourceanswerid' => $response->id,
                'userid' => $response->anonymous ? null : $response->userid,
                'themesjson' => json_encode(array_values(array_unique($themebyresponse[$response->id] ?? []))),
                'timecreated' => $now,
            ]);
        }

        $transaction->allow_commit();
        return $analysisid;
    }

    /**
     * Method get.
     *
     * @param int $analysisid Parameter analysisid.
     * @param int $courseid Parameter courseid.
     * @return object Return value.
     */
    public static function get(int $analysisid, int $courseid): object {
        global $DB;

        return $DB->get_record(
            'local_feedbackinsights_an',
            ['id' => $analysisid, 'courseid' => $courseid],
            '*',
            MUST_EXIST
        );
    }

    /**
     * Method list_for_course.
     *
     * @param int $courseid Parameter courseid.
     * @param int $limit Parameter limit.
     * @return array Return value.
     */
    public static function list_for_course(int $courseid, int $limit = 20): array {
        global $DB;

        return $DB->get_records(
            'local_feedbackinsights_an',
            ['courseid' => $courseid],
            'timecreated DESC',
            '*',
            0,
            $limit
        );
    }

    /**
     * Method delete.
     *
     * @param int $analysisid Parameter analysisid.
     * @return void Return value.
     */
    public static function delete(int $analysisid): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_feedbackinsights_mem', ['analysisid' => $analysisid]);
        $DB->delete_records('local_feedbackinsights_an', ['id' => $analysisid]);
        $transaction->allow_commit();
    }

    /**
     * Method delete_course.
     *
     * @param int $courseid Parameter courseid.
     * @return void Return value.
     */
    public static function delete_course(int $courseid): void {
        global $DB;

        $analysisids = $DB->get_fieldset_select(
            'local_feedbackinsights_an',
            'id',
            'courseid = :courseid',
            ['courseid' => $courseid]
        );
        self::delete_ids($analysisids);
    }

    /**
     * Method delete_older_than.
     *
     * @param int $threshold Parameter threshold.
     * @return void Return value.
     */
    public static function delete_older_than(int $threshold): void {
        global $DB;

        $analysisids = $DB->get_fieldset_select(
            'local_feedbackinsights_an',
            'id',
            'timecreated < :threshold',
            ['threshold' => $threshold]
        );
        self::delete_ids($analysisids);
    }

    /**
     * Method delete_ids.
     *
     * @param array $analysisids Parameter analysisids.
     * @return void Return value.
     */
    public static function delete_ids(array $analysisids): void {
        global $DB;

        if (!$analysisids) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal(
            array_map('intval', $analysisids),
            SQL_PARAMS_NAMED,
            'analysis'
        );
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records_select('local_feedbackinsights_mem', "analysisid {$insql}", $params);
        $DB->delete_records_select('local_feedbackinsights_an', "id {$insql}", $params);
        $transaction->allow_commit();
    }
}
