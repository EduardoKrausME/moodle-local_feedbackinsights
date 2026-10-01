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

use local_ai_bridge\api;
use moodle_exception;

/**
 * Single integration point with local_ai_bridge.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_client {
    /** @var string */
    private const PURPOSE = 'feedbackinsights-analysis';

    /**
     * Method analyse_batch.
     *
     * @param array $responses Parameter responses.
     * @return array Return value.
     */
    public function analyse_batch(array $responses): array {
        $validids = array_column($responses, 'id');
        $messages = [
            ['role' => 'system', 'content' => $this->system_instruction()],
            [
                'role' => 'user',
                'content' => json_encode(
                    ['responses' => $responses],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ],
        ];

        $response = api::generate(self::PURPOSE, $messages);
        $parsed = ai_response_parser::parse($response->text);
        return ai_response_parser::validate_ids($parsed, $validids);
    }

    /**
     * Method consolidate.
     *
     * @param array $provisionalthemes Parameter provisionalthemes.
     * @return array Return value.
     */
    public function consolidate(array $provisionalthemes): array {
        $messages = [
            [
                'role' => 'system',
                'content' => 'Consolidate semantically equivalent provisional themes. Return JSON only in the form ' .
                    '{"themes":[{"title":"...","description":"...","provisional_ids":["B1T1"],"confidence":0.0}]}. ' .
                    'Use only provisional_ids supplied by the user. Do not create counts. Do not infer emotion, mental health, ' .
                    'personality, intent, student quality, engagement, or individual scores.',
            ],
            [
                'role' => 'user',
                'content' => json_encode(
                    ['themes' => $provisionalthemes],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ],
        ];
        $response = api::generate(self::PURPOSE, $messages);
        $text = trim($response->text);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $text, $matches)) {
            $text = trim($matches[1]);
        }
        $payload = json_decode($text, true);
        if (!is_array($payload) || !isset($payload['themes']) || !is_array($payload['themes'])) {
            throw new moodle_exception('error:malformedairesponse', 'local_feedbackinsights');
        }
        return $payload;
    }

    /**
     * Method system_instruction.
     *
     * @return string Return value.
     */
    private function system_instruction(): string {
        return <<<'TEXT'
You analyse open-ended course feedback and return useful themes, not word clouds.
The input contains artificial response IDs and text. Never infer or classify emotion, mental health, personality,\nintent, student quality, engagement, or any individual score. Do not attempt to identify a person.
Group responses into meaningful themes, recurring suggestions and relevant divergences.\nA response may belong to more than one theme when justified.
Never invent a count. Never return any ID that does not appear in the input.\nEvidence must be represented only by response IDs; do not quote long passages.
Return JSON only with this exact top-level shape:
{
  "themes": [
    {
      "key": "T1",
      "title": "short title",
      "description": "concise explanation of the pattern",
      "response_ids": ["R000001"],
      "example_ids": ["R000001"],
      "confidence": 0.0
    }
  ],
  "suggestions": [
    {"title":"short title","description":"recurring suggestion","response_ids":["R000001"]}
  ],
  "divergences": [
    {"title":"short title","description":"meaningful disagreement or divergent experience","response_ids":["R000001"]}
  ]
}
Confidence is semantic confidence from 0 to 1, not a student score.\nCounts are calculated later by PHP and must not appear in your output.
TEXT;
    }
}
