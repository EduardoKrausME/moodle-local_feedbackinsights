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

namespace local_feedbackinsights\task;

use core\task\scheduled_task;
use local_feedbackinsights\service\analysis_repository;

/**
 * Remove expired persisted analyses.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cleanup_analyses extends scheduled_task {
    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public function get_name(): string {
        return get_string('task:cleanup', 'local_feedbackinsights');
    }

    /**
     * Method execute.
     *
     * @return void Return value.
     */
    public function execute(): void {
        $days = max(1, (int)(get_config('local_feedbackinsights', 'retentiondays') ?: 30));
        analysis_repository::delete_older_than(time() - ($days * DAYSECS));
    }
}
