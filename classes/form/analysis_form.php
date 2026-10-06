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

namespace local_feedbackinsights\form;

use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../../../lib/formslib.php');

/**
 * Analysis parameter form.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class analysis_form extends moodleform {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $source = $this->_customdata['source'];
        $sourceid = $this->_customdata['sourceid'];
        $questions = $this->_customdata['questions'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'source', $source);
        $mform->setType('source', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'sourceid', $sourceid);
        $mform->setType('sourceid', PARAM_INT);

        $select = $mform->addElement(
            'select',
            'questionids',
            get_string('questions', 'local_feedbackinsights'),
            $questions,
            ['size' => min(12, max(3, count($questions)))]
        );
        $select->setMultiple(true);
        $mform->addRule('questionids', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_selector', 'datefrom', get_string('datefrom', 'local_feedbackinsights'));
        $mform->addElement('date_selector', 'dateuntil', get_string('dateuntil', 'local_feedbackinsights'));
        $mform->setDefault('datefrom', strtotime('-90 days'));
        $mform->setDefault('dateuntil', time());

        $this->add_action_buttons(true, get_string('analyse', 'local_feedbackinsights'));
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if ((int)$data['datefrom'] > (int)$data['dateuntil']) {
            $errors['dateuntil'] = get_string('error:daterange', 'local_feedbackinsights');
        }
        if (empty($data['questionids'])) {
            $errors['questionids'] = get_string('required');
        }
        return $errors;
    }
}
