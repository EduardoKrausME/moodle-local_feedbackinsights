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

namespace local_feedbackinsights\model;

/**
 * Normalised source response.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class response_record {
    /** @var int Source answer identifier. */
    public readonly int $id;

    /** @var string Source question identifier. */
    public readonly string $questionid;

    /** @var string Original answer text. */
    public readonly string $text;

    /** @var int Answer timestamp. */
    public readonly int $timestamp;

    /** @var int|null Respondent user id, or null for anonymous sources. */
    public readonly ?int $userid;

    /** @var bool Whether the source explicitly treats this response as anonymous. */
    public readonly bool $anonymous;

    /**
     * Constructor.
     *
     * @param int $id Source answer identifier.
     * @param string $questionid Source question identifier.
     * @param string $text Original answer text.
     * @param int $timestamp Answer timestamp.
     * @param int|null $userid Respondent user id, or null for anonymous sources.
     * @param bool $anonymous Whether the source explicitly treats this response as anonymous.
     */
    public function __construct(
        int $id,
        string $questionid,
        string $text,
        int $timestamp,
        ?int $userid,
        bool $anonymous = false
    ) {
        $this->id = $id;
        $this->questionid = $questionid;
        $this->text = $text;
        $this->timestamp = $timestamp;
        $this->userid = $userid;
        $this->anonymous = $anonymous;
    }
}
