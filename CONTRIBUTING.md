# Contributing

NalApps Easy SMTP follows **NalApps WordPress Plugin Standard v4.6.0**.

Before proposing a change:

- Preserve the existing `nalapps_easy_smtp_settings` option and the AES-256-CBC encrypted-password contract unless the change explicitly requires a migration.
- Use the `NES` namespace/prefix and avoid global identifiers.
- Require capability + nonce for state-changing admin actions.
- Validate/sanitize input and escape output by context.
- Never include the SMTP password or API key in exports, snapshots, system information, or logs.
- Do not commit credentials, license keys, customer data, backups, dumps, or real `.env` values.
- Keep telemetry off unless an explicit opt-in design is approved.
- Keep remote calls bounded by timeout, SSL verification, validation, and cache policy.
- Update documentation before the final version bump.
- Treat existing version tags and release assets as immutable.

Release changes must keep the plugin header version, `NES_VERSION`, `readme.txt` Stable tag, GitHub tag/release, and EDD product version aligned.
