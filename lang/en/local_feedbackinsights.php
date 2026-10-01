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
 * local_feedbackinsights.php
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$string['analyse'] = 'Analyse responses';
$string['analysescleared'] = 'Saved analyses were deleted.';
$string['analysis'] = 'Feedback analysis';
$string['analysiscreated'] = 'Created {$a->date}; {$a->responses} non-empty responses analysed and {$a->empty} empty responses ignored.';
$string['analysissaved'] = 'Structured analysis saved as #{$a}. Raw prompt and raw AI response were not stored.';
$string['anonymous'] = 'Anonymous response';
$string['assignment:onlinetext'] = 'Online text submissions';
$string['backtoanalyses'] = 'Back to Feedback insights';
$string['changesource'] = 'Change source';
$string['clearconfirm'] = 'Delete all saved Feedback insights analyses and membership mappings for this course? Original Feedback and Assignment responses are not changed.';
$string['clearcourseanalyses'] = 'Delete saved analyses for this course';
$string['confidence'] = 'Semantic confidence';
$string['datefrom'] = 'From';
$string['dateuntil'] = 'Until';
$string['divergences'] = 'Divergences';
$string['emptyresponsesremoved'] = '{$a} empty response(s) were removed locally before AI analysis.';
$string['error:anonymousseparategroups'] = 'Anonymous Feedback cannot be analysed by a user restricted to separate groups because the plugin will not reidentify anonymous respondents to infer group membership.';
$string['error:daterange'] = 'The end date must not be earlier than the start date.';
$string['error:invalidquestion'] = 'One of the selected questions is not available in this source.';
$string['error:invalidsource'] = 'Invalid or unavailable response source.';
$string['error:malformedairesponse'] = 'The AI bridge returned a malformed response. No analysis was saved.';
$string['error:noresponses'] = 'No non-empty textual responses were found for this selection.';
$string['error:notextquestions'] = 'This source does not contain supported open-text questions.';
$string['error:responsenotfound'] = 'The original response no longer exists or is not accessible.';
$string['error:storedanalysis'] = 'The stored structured analysis is invalid.';
$string['error:toomanyresponses'] = 'This selection contains more than {$a} responses. Narrow the questions or period instead of silently sampling the data.';
$string['evidence'] = 'Evidence';
$string['intro'] = 'Group open-ended responses into traceable themes and recurring insights. Counts are calculated locally from validated response IDs; AI is used only for semantic grouping and summaries.';
$string['invalididsdiscarded'] = '{$a} invalid response ID(s) returned by AI were discarded before metrics were calculated.';
$string['nosources'] = 'No supported response source is available to you in this course.';
$string['nothemes'] = 'No reliable themes were returned for the selected responses.';
$string['originalresponse'] = 'Original response';
$string['pluginname'] = 'Feedback insights';
$string['privacy:metadata:analysis'] = 'Stores structured analyses created by authorised teaching staff.';
$string['privacy:metadata:analysis:courseid'] = 'Course containing the analysis.';
$string['privacy:metadata:analysis:createdby'] = 'User who requested the analysis.';
$string['privacy:metadata:analysis:questionids'] = 'Selected source question identifiers.';
$string['privacy:metadata:analysis:resultsjson'] = 'Structured derived themes, metrics and source response references.';
$string['privacy:metadata:analysis:source'] = 'Type of source analysed.';
$string['privacy:metadata:analysis:sourceinstanceid'] = 'Source activity instance identifier.';
$string['privacy:metadata:analysis:timecreated'] = 'Time the analysis was created.';
$string['privacy:metadata:bridge'] = 'Selected, normalised and redacted response text is sent through local_ai_bridge for semantic grouping. Moodle user IDs, names and source database IDs are not included in the prompt.';
$string['privacy:metadata:bridge:questionid'] = 'Selected source question identifier.';
$string['privacy:metadata:bridge:responseid'] = 'Artificial per-request response identifier.';
$string['privacy:metadata:bridge:text'] = 'Normalised response text with common direct identifiers redacted where possible.';
$string['privacy:metadata:member'] = 'Maps an analysis to source response identifiers and, for non-anonymous sources, respondent user IDs for privacy handling.';
$string['privacy:metadata:member:analysisid'] = 'Related analysis.';
$string['privacy:metadata:member:sourceanswerid'] = 'Source answer identifier.';
$string['privacy:metadata:member:themesjson'] = 'Theme keys assigned to this response.';
$string['privacy:metadata:member:userid'] = 'Respondent user ID when the source is not anonymous.';
$string['questionnumber'] = 'Question #{$a}';
$string['questions'] = 'Questions';
$string['recentanalyses'] = 'Recent saved analyses';
$string['recurringsuggestions'] = 'Recurring suggestions';
$string['responseid'] = 'Response #{$a}';
$string['responses'] = 'Responses';
$string['setting:batchsize'] = 'AI batch size';
$string['setting:batchsize_desc'] = 'Number of non-empty responses per semantic analysis request. Values are constrained to 10–100.';
$string['setting:maxchars'] = 'Maximum characters per response sent to AI';
$string['setting:maxchars_desc'] = 'Long responses are locally normalised and truncated to this size for the semantic prompt.';
$string['setting:maxresponses'] = 'Maximum responses per analysis';
$string['setting:maxresponses_desc'] = 'Fail safely and ask the teacher to narrow the selection instead of silently sampling when this limit is exceeded.';
$string['setting:mintrendresponses'] = 'Minimum responses for trends';
$string['setting:mintrendresponses_desc'] = 'Trend buckets are shown only when the selected period has at least this many non-empty responses and spans at least two days.';
$string['setting:persistanalyses'] = 'Persist structured analyses';
$string['setting:persistanalyses_desc'] = 'Store structured themes, counts and source response IDs so reports can be reopened. Raw prompts, raw AI responses and original response text are not stored by this plugin.';
$string['setting:retentiondays'] = 'Retention days';
$string['setting:retentiondays_desc'] = 'Saved analyses older than this are removed by the scheduled cleanup task.';
$string['source'] = 'Source';
$string['source:assignment'] = 'Assignment';
$string['source:feedback'] = 'Feedback';
$string['task:cleanup'] = 'Delete expired Feedback insights analyses';
$string['themes'] = 'Themes';
$string['trend'] = 'Trend';
