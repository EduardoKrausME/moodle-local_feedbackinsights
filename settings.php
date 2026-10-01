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
 * Plugin settings.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_feedbackinsights', get_string('pluginname', 'local_feedbackinsights'));

    $settings->add(new admin_setting_configcheckbox(
        'local_feedbackinsights/persistanalyses',
        get_string('setting:persistanalyses', 'local_feedbackinsights'),
        get_string('setting:persistanalyses_desc', 'local_feedbackinsights'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_feedbackinsights/retentiondays',
        get_string('setting:retentiondays', 'local_feedbackinsights'),
        get_string('setting:retentiondays_desc', 'local_feedbackinsights'),
        30,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_feedbackinsights/batchsize',
        get_string('setting:batchsize', 'local_feedbackinsights'),
        get_string('setting:batchsize_desc', 'local_feedbackinsights'),
        60,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_feedbackinsights/maxresponses',
        get_string('setting:maxresponses', 'local_feedbackinsights'),
        get_string('setting:maxresponses_desc', 'local_feedbackinsights'),
        1000,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_feedbackinsights/maxchars',
        get_string('setting:maxchars', 'local_feedbackinsights'),
        get_string('setting:maxchars_desc', 'local_feedbackinsights'),
        4000,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_feedbackinsights/mintrendresponses',
        get_string('setting:mintrendresponses', 'local_feedbackinsights'),
        get_string('setting:mintrendresponses_desc', 'local_feedbackinsights'),
        12,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
