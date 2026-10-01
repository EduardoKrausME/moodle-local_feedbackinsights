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

use core_text;
use local_feedbackinsights\model\response_record;
use local_feedbackinsights\source\provider;
use moodle_exception;

/**
 * Analysis orchestration.
 *
 * Deterministic extraction, normalisation, counting, ID validation and metrics happen in PHP.
 * AI is limited to semantic grouping and summaries.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class analysis_service {
    /**
     * Property client.
     *
     * @var ai_client
     */
    private ai_client $client;

    /**
     * Method __construct.
     *
     * @param ?ai_client $client Parameter client.
     */
    public function __construct(?ai_client $client = null) {
        $this->client = $client ?? new ai_client();
    }

    /**
     * Method run.
     *
     * @param provider $provider Parameter provider.
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @param array $questionids Parameter questionids.
     * @param int $datefrom Parameter datefrom.
     * @param int $dateuntil Parameter dateuntil.
     * @return array Return value.
     */
    public function run(
        provider $provider,
        int $courseid,
        int $instanceid,
        array $questionids,
        int $datefrom,
        int $dateuntil
    ): array {
        $provider->require_access($courseid, $instanceid);
        $rawresponses = $provider->get_responses($courseid, $instanceid, $questionids, $datefrom, $dateuntil);

        $maxresponses = max(1, (int)(get_config('local_feedbackinsights', 'maxresponses') ?: 1000));
        if (count($rawresponses) > $maxresponses) {
            throw new moodle_exception('error:toomanyresponses', 'local_feedbackinsights', '', $maxresponses);
        }

        [$responses, $emptycount] = $this->normalise_nonempty($rawresponses);
        if (!$responses) {
            throw new moodle_exception('error:noresponses', 'local_feedbackinsights');
        }

        $questionlabels = $provider->get_questions($courseid, $instanceid);
        $questiontokens = [];
        foreach (array_values($questionids) as $index => $questionid) {
            $questiontokens[(string)$questionid] = sprintf('Q%03d', $index + 1);
        }

        $pseudomap = [];
        $prepared = [];
        $maxchars = max(500, (int)(get_config('local_feedbackinsights', 'maxchars') ?: 4000));
        foreach ($responses as $index => $response) {
            $pseudoid = sprintf('R%06d', $index + 1);
            $pseudomap[$pseudoid] = $response;
            $text = anonymizer::redact($response->text);
            if (core_text::strlen($text) > $maxchars) {
                $text = core_text::substr($text, 0, $maxchars) . '…';
            }
            $prepared[] = [
                'id' => $pseudoid,
                'question_id' => $questiontokens[$response->questionid] ?? 'Q000',
                'question' => $questionlabels[$response->questionid] ?? '',
                'text' => $text,
            ];
        }

        $batchsize = min(100, max(10, (int)(get_config('local_feedbackinsights', 'batchsize') ?: 60)));
        $batches = array_chunk($prepared, $batchsize);
        $batchpayloads = [];
        $invalididcount = 0;

        foreach ($batches as $batch) {
            $batchresult = $this->client->analyse_batch($batch);
            $batchpayloads[] = $batchresult['payload'];
            $invalididcount += $batchresult['invalidids'];
        }

        $payload = count($batchpayloads) === 1
            ? $batchpayloads[0]
            : $this->merge_batches($batchpayloads);

        $mintrendresponses = max(4, (int)(get_config('local_feedbackinsights', 'mintrendresponses') ?: 12));
        $result = metrics::enrich($payload, $pseudomap, $datefrom, $dateuntil, $mintrendresponses);

        return [
            'result' => $result,
            'responses' => $responses,
            'emptycount' => $emptycount,
            'invalididcount' => $invalididcount,
        ];
    }

    /**
     * Method normalise_nonempty.
     *
     * @param array $responses Parameter responses.
     * @return array Return value.
     */
    private function normalise_nonempty(array $responses): array {
        $result = [];
        $empty = 0;

        foreach ($responses as $response) {
            $text = text_normalizer::normalize($response->text);
            if ($text === '') {
                $empty++;
                continue;
            }
            $result[] = new response_record(
                $response->id,
                $response->questionid,
                $text,
                $response->timestamp,
                $response->anonymous ? null : $response->userid,
                $response->anonymous
            );
        }
        return [$result, $empty];
    }

    /**
     * Method merge_batches.
     *
     * @param array $batchpayloads Parameter batchpayloads.
     * @return array Return value.
     */
    private function merge_batches(array $batchpayloads): array {
        $provisional = [];
        $lookup = [];
        $suggestions = [];
        $divergences = [];

        foreach ($batchpayloads as $batchindex => $payload) {
            foreach ($payload['themes'] as $themeindex => $theme) {
                $id = 'B' . ($batchindex + 1) . 'T' . ($themeindex + 1);
                $lookup[$id] = $theme;
                $provisional[] = [
                    'id' => $id,
                    'title' => $theme['title'],
                    'description' => $theme['description'],
                    'confidence' => $theme['confidence'],
                ];
            }
            $suggestions = array_merge($suggestions, $payload['suggestions'] ?? []);
            $divergences = array_merge($divergences, $payload['divergences'] ?? []);
        }

        if (!$provisional) {
            return [
                'themes' => [],
                'suggestions' => $suggestions,
                'divergences' => $divergences,
            ];
        }

        $merged = $this->client->consolidate($provisional);
        $validprovisional = array_fill_keys(array_keys($lookup), true);
        $themes = [];

        foreach ($merged['themes'] as $index => $theme) {
            if (!is_array($theme) || !is_array($theme['provisional_ids'] ?? null)) {
                continue;
            }
            $ids = array_values(array_unique(array_filter(
                array_map('strval', $theme['provisional_ids']),
                static fn(string $id): bool => isset($validprovisional[$id])
            )));
            if (!$ids) {
                continue;
            }

            $responseids = [];
            $exampleids = [];
            $confidence = [];
            foreach ($ids as $id) {
                $responseids = array_merge($responseids, $lookup[$id]['response_ids']);
                $exampleids = array_merge($exampleids, $lookup[$id]['example_ids']);
                $confidence[] = $lookup[$id]['confidence'];
            }

            $themes[] = [
                'key' => 'T' . ($index + 1),
                'title' => clean_param((string)($theme['title'] ?? ''), PARAM_TEXT),
                'description' => clean_param((string)($theme['description'] ?? ''), PARAM_TEXT),
                'response_ids' => array_values(array_unique($responseids)),
                'example_ids' => array_slice(array_values(array_unique($exampleids)), 0, 3),
                'confidence' => isset($theme['confidence']) && is_numeric($theme['confidence'])
                    ? max(0.0, min(1.0, (float)$theme['confidence']))
                    : (array_sum($confidence) / max(1, count($confidence))),
            ];
        }

        return [
            'themes' => $themes,
            'suggestions' => $suggestions,
            'divergences' => $divergences,
        ];
    }
}
