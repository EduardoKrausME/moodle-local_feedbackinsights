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

/**
 * Library callbacks for local_feedbackinsights.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add Feedback insights to course navigation for authorised staff.
 *
 * @param navigation_node $navigation Course navigation node.
 * @param stdClass $course Course record.
 * @param context_course $context Course context.
 */
function local_feedbackinsights_extend_navigation_course($navigation, $course, $context): void {
    if (!has_capability('local/feedbackinsights:view', $context)) {
        return;
    }

    $url = new moodle_url('/local/feedbackinsights/index.php', ['courseid' => $course->id]);
    $navigation->add(
        get_string('pluginname', 'local_feedbackinsights'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_feedbackinsights',
        new pix_icon('i/report', '')
    );
}
