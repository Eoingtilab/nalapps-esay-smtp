# NalApps Easy SMTP Release Acceptance

Release is PASS only when all applicable items are verified.

- [ ] `plugin-profile.json` matches the implementation.
- [ ] Plugin version, `NES_VERSION`, readme Stable tag, Git tag and Release agree.
- [ ] `NES_STANDARD_VERSION` and `nalapps-standard-manifest.json` match the adopted NalApps stable tag.
- [ ] License screen shows the free/active status with no serial key entry.
- [ ] SMTP password and API key are excluded from diagnostics and data export.
- [ ] WordPress Plugins update and internal `지금 업데이트` both use an installable GitHub Release package URL (no EDD/license dependency).
- [ ] Pre-update code backup and settings snapshot are created before plugin replacement.
- [ ] Rollback is available and creates a pre-rollback backup first.
- [ ] Settings export/import works, always strips `password_enc` and `api_key_enc`, and import creates a pre-import snapshot.
- [ ] Provider quick-setup (Brevo/Mailgun/SendGrid) autofills correctly by logo click and by dropdown, and API-key mode sends through the matching provider HTTP API.
- [ ] Uninstall defaults to preserve; delete-all runs only after explicit owner opt-in.
- [ ] System information contains no secret, license key, or SMTP credential.
- [ ] `phpmailer_init` wiring is verified with an active SMTP configuration and a real test send.
- [ ] PHP 8.1–8.5 syntax matrix, WPCS, WordPress Plugin Check and dependency audit pass.
- [ ] Public repository secret/customer-data gate passes.
- [ ] Production ZIP root is `nalapps-easy-smtp/`.
- [ ] Production ZIP contains all maintenance runtime files (`includes/`, `assets/`, `uninstall.php`), with dev-only tooling (WPCS/PHPCS) excluded.
- [ ] Existing installs upgrading from the `nalapps-commerce-addon-easy-smtp` channel keep their SMTP settings and encrypted password intact.
- [ ] Final old-version → new-version update is exercised on a disposable WordPress site.
