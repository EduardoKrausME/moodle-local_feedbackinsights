# local_feedbackinsights

`local_feedbackinsights` is a Moodle 4.5+ local plugin that analyses open-ended responses and turns them into traceable
themes and useful insights without reducing feedback to a word cloud.

## Requirements

- Moodle 4.5+
- PHP supported by the target Moodle release
- `local_ai_bridge >= 2026093001`
- AI purpose configured in the bridge: `feedbackinsights-analysis`

The plugin never calls OpenAI, Gemini, Claude, Ollama, Whisper or any provider directly. Every semantic request goes
exclusively through:

```php
\local_ai_bridge\api::generate('feedbackinsights-analysis', $messages);
```

No API key, endpoint, provider or model setting exists in this plugin.

## Initial sources

- `mod_feedback`: textfield and textarea items. Anonymous Feedback remains anonymous; the provider deliberately
  sets `userid = null` and never tries to reconstruct identity.
- Assignment online text: available only when the current user can grade that Assignment and explicitly selects it as
  the source.

The source layer is extensible through `\local_feedbackinsights\source\provider`. Another plugin can register providers
from its `lib.php`:

```php
function local_example_feedbackinsights_source_providers(): array {
    return [\local_example\feedbackinsights\questionnaire_provider::class];
}
```

That is the intended integration point for `mod_questionnaire`: support can be added when it is installed without
coupling this plugin to a third-party schema.

## Processing model

The deterministic layer runs first. PHP selects the source, enforces source capabilities and group access, counts
records, removes empty answers, normalises HTML to plain text, applies a configurable size limit, creates artificial
request IDs and removes Moodle identity metadata from the prompt. Common direct identifiers such as e-mail addresses and
phone-like strings are masked where possible.

The AI receives only objects such as:

```json
{
  "id": "R000017",
  "question_id": "Q002",
  "question": "What should we improve in this course?",
  "text": "The practical examples helped, but ..."
}
```

It may group themes, describe patterns, identify recurring suggestions and point out divergences. It is explicitly
instructed not to infer emotion, mental health, personality, intent, student quality or engagement, and it cannot create
an individual score.

The AI is also forbidden from creating counts. It returns memberships using artificial IDs; PHP validates every returned
ID against the request whitelist and discards invalid IDs. Theme counts, percentages and time buckets are then
calculated deterministically in PHP.

For large selections, responses are analysed in batches. A second bridge call consolidates provisional theme summaries
only; student answer text is not re-sent during consolidation.

## Persistence and privacy

Persistence is configurable. When enabled, the plugin stores structured themes, deterministic metrics, source response
IDs and membership mappings. It does **not** store the raw prompt, raw AI response or original source answer text.

Examples shown in reports are links back to the original source response and are loaded only when the current user has
both `local/feedbackinsights:viewresponses` and the source-specific capability. Anonymous Feedback never exposes or
stores a respondent user ID.

The plugin implements Moodle Privacy API metadata, export, deletion and user-list support. If a non-anonymous user's
response participated in a saved analysis, deleting that user's plugin data deletes the affected derived analysis rather
than attempting to surgically preserve AI-derived context.

Administrators can disable persistence and set retention. A scheduled task removes expired analyses, and authorised
teachers/managers can delete all saved analyses for a course without modifying original Feedback or Assignment
responses.

## Capabilities

- `local/feedbackinsights:view`
- `local/feedbackinsights:analyse`
- `local/feedbackinsights:viewresponses`
- `local/feedbackinsights:deleteanalyses`

No capability is granted to the student archetype.

## Tests

The PHPUnit suite covers:

- malformed AI JSON;
- invalid returned IDs;
- deterministic counts and trend buckets;
- teacher/student permission boundaries;
- anonymous response persistence;
- Privacy API deletion.

CI installs `local_ai_bridge`, runs Moodle Plugin CI checks and invokes `EduardoKrausME/moodle-plugin-validate`.

## Installation

Place the plugin in:

```text
local/feedbackinsights
```

Then run Moodle upgrade and configure `feedbackinsights-analysis` in `local_ai_bridge` for the relevant tenant/role.

## License

GNU GPL v3 or later.
