# Changelog

This changelog documents only the two most recent **published** releases of `local_feedbackinsights`, verified on October 24, 2026 (America/Sao_Paulo). Each entry reflects the net changes between a published release tag and its immediately preceding published tag. The October 20 `-core_text` commit occurred after release `1.1.4` and is intentionally excluded.

## [1.1.4] - 2026-10-15

### Release metadata
- Updated `version.php` from internal version `2026101400`, release `1.1.3`, to internal version `2026101500`, release `1.1.4`.
- This is the **only** file changed between these published tags; no application code, CI configuration or functionality was modified.

**Full comparison:** [1.1.3...1.1.4](https://github.com/EduardoKrausME/moodle-local_feedbackinsights/compare/1.1.3...1.1.4)

## [1.1.3] - 2026-10-14

### Security and blind marking
- Updated the Assignment response provider to hide respondent user IDs while blind marking is enabled and identities have not been revealed, except for graders who have the explicit `mod/assign:viewblinddetails` capability.
- Applied the identity check to both individual-response and response-list retrieval, returning anonymous records with null user IDs where identities must remain hidden.
- Rechecked identity visibility when saving an analysis, preventing an ID retrieved earlier from being stored if visibility was revoked or blind marking was re-enabled while external AI processing was in progress.
- Added `RISK_PERSONAL` to the `local/feedbackinsights:analyse` and `local/feedbackinsights:viewresponses` capabilities.
- Added regression tests covering hidden and revealed Assignment respondent identities and permission changes between extraction and storage; adjusted the privacy deletion test fixture to avoid assuming Assignment permissions.

### External AI processing and privacy transparency
- Corrected the Moodle Privacy API external-recipient metadata to describe the configured AI provider reached through `local_ai_bridge`, including question labels and response text.
- Clarified in English and Brazilian Portuguese privacy strings that Moodle user IDs and source database IDs are not passed as separate prompt fields, while personal information typed into response text or question labels can still reach the configured provider.
- Documented the limits of automated masking: email addresses and Brazilian-format telephone numbers are masked, but names and other personal information are not necessarily removed.
- Updated the README to explain blind-marking identity protection and the need to disclose and appropriately configure external AI processing.

### CI and release packaging documentation
- Updated the PostgreSQL CI service image from version 16 to 17.
- Updated the MariaDB CI service image from version 10 to 11.4, including the container environment and `healthcheck.sh --connect --innodb_initialized` readiness check.
- Added README guidance to use an explicitly attached, Moodle-installable `local_feedbackinsights_<version>.zip` release asset with the `feedbackinsights/` root directory instead of a GitHub auto-generated source ZIP. The historical comparison also removes a redundant `LICENCE` file while retaining the root `LICENSE`.
- Intermediate commits addressed release archive packaging, but the final published-tag comparison contains **no net change** to `.github/workflows/release.yml`.

### Release metadata
- Updated `version.php` from internal version `2026101200`, release `1.1.2`, to internal version `2026101400`, release `1.1.3`.

**Full comparison:** [1.1.2...1.1.3](https://github.com/EduardoKrausME/moodle-local_feedbackinsights/compare/1.1.2...1.1.3)
