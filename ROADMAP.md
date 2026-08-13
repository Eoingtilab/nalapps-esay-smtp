# Roadmap

NalApps Easy SMTP started as a NalApps Commerce Core add-on and is now a
standalone plugin. The first goal is a stable, license-gated update path
that keeps existing SMTP configurations intact through the transition.

## Phase 1: Standalone launch

- Ship the standard admin/license/update/rollback/maintenance/system-info
  layers on top of the preserved SMTP settings contract.
- Register the product in EDD (download ID 533) and publish the first
  immutable GitHub Release.
- Confirm WordPress Plugin Check, WPCS, and the PHP 8.1–8.5 compatibility
  matrix all pass before general availability.

## Phase 2: Delivery diagnostics

- Add a lightweight send log (last N attempts, redacted) to help diagnose
  failed deliveries without storing full message contents.
- Surface common SMTP provider presets (e.g. common port/encryption
  combinations) to reduce setup errors.

## Phase 3: Broader mail provider support

- Evaluate API-based transactional email providers as an alternative to
  raw SMTP for sites that prefer it.
