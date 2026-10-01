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

use local_feedbackinsights\model\response_record;

/**
 * Contract for response sources.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface provider {
    /**
     * Method get_key.
     *
     * @return string Return value.
     */
    public function get_key(): string;

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public function get_name(): string;

    /**
     * Method is_available.
     *
     * @return bool Return value.
     */
    public function is_available(): bool;

    /**
     * Method get_instances.
     *
     * @param int $courseid Parameter courseid.
     * @return array Return value.
     */
    public function get_instances(int $courseid): array;

    /**
     * Method get_questions.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @return array Return value.
     */
    public function get_questions(int $courseid, int $instanceid): array;

    /**
     * Method require_access.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @return void Return value.
     */
    public function require_access(int $courseid, int $instanceid): void;

    /**
     * Method get_responses.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @param array $questionids Parameter questionids.
     * @param int $datefrom Parameter datefrom.
     * @param int $dateuntil Parameter dateuntil.
     * @return array Return value.
     */
    public function get_responses(
        int $courseid,
        int $instanceid,
        array $questionids,
        int $datefrom,
        int $dateuntil
    ): array;

    /**
     * Method get_response.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @param int $responseid Parameter responseid.
     * @return ?response_record Return value.
     */
    public function get_response(int $courseid, int $instanceid, int $responseid): ?response_record;
}
