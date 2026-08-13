# Installing from GitHub

GitHub's default download ZIP may contain a root folder named `nalapps-easy-smtp-main`.

For WordPress plugin upload, the ZIP should contain this root folder instead:

```text
nalapps-easy-smtp/
```

Use the versioned Release asset (e.g. `nalapps-easy-smtp-1.0.0.zip`) from the
[Releases page](https://github.com/Eoingtilab/nalapps-esay-smtp/releases)
rather than the branch source ZIP — the Release asset is built with the
correct root folder and production-only dependencies.

If WordPress shows two copies of the plugin, deactivate both, delete the
duplicate `nalapps-easy-smtp-main` copy, and upload a correctly packaged ZIP.
