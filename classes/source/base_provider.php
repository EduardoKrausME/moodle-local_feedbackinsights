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

namespace local_feedbackinsights\source;

use context_module;
use stdClass;

/**
 * Common source helpers.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_provider implements provider {
    /**
     * Get a course module for an instance.
     *
     * @param string $modname Module name.
     * @param int $instanceid Instance id.
     * @param int $courseid Course id.
     * @return stdClass
     */
    protected function get_cm(string $modname, int $instanceid, int $courseid): stdClass {
        return get_coursemodule_from_instance($modname, $instanceid, $courseid, false, MUST_EXIST);
    }

    /**
     * Return user IDs accessible to the current user when separate groups apply.
     *
     * Null means no user restriction is required. An empty array means no group members are accessible.
     *
     * @param stdClass $cm Course module.
     * @param context_module $context Module context.
     * @return int[]|null
     */
    protected function get_group_limited_userids(stdClass $cm, context_module $context): ?array {
        global $DB;

        if (groups_get_activity_groupmode($cm) !== SEPARATEGROUPS ||
            has_capability('moodle/site:accessallgroups', $context)) {
            return null;
        }

        $groups = groups_get_activity_allowed_groups($cm);
        if (!$groups) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(array_keys($groups), SQL_PARAMS_NAMED, 'grp');
        $records = $DB->get_records_select('groups_members', "groupid {$insql}", $params, '', 'id,userid');
        return array_values(array_unique(array_map(static fn($record): int => (int)$record->userid, $records)));
    }
}
