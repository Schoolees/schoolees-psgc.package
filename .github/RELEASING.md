# Automated Versioning & Packagist Publish

This repo uses Conventional Commits + Release Please for automatic versioning.

- Push commits to `main` using conventional format (example: `fix(seeder): respect resources_path`).
- GitHub Action `.github/workflows/release.yml` opens/updates a release PR.
- When that PR is merged, it creates a Git tag + GitHub Release automatically.
- After release creation, `.github/workflows/release.yml` notifies Packagist immediately.
- `.github/workflows/packagist-sync.yml` also runs on `release.published`, can be re-run manually via `workflow_dispatch`, and now fails if Packagist still does not expose the released tag after polling.

## Normal Release Flow
1. Merge conventional commits into `main`.
2. Wait for Release Please to open or update the release PR.
3. Merge the release PR.
4. Release Please creates the tag and GitHub Release.
5. Packagist is notified automatically.

## Manual Recovery
- If the Git tag or GitHub Release exists but Packagist is stale, run the `Packagist Sync` workflow manually from GitHub Actions.
- If `Packagist Sync` fails after notification, inspect the workflow log and the current `https://repo.packagist.org/p2/schoolees/laravel-psgc.json` payload to confirm whether the new tag was indexed.
- If Release Please cannot open or merge the release PR, fix the token or workflow permissions first; do not create ad-hoc version commits unless you intentionally want a manual release.

## Required GitHub Secrets
- `RELEASE_PLEASE_TOKEN`: fine-grained PAT (or bot PAT) with repository `Contents (Read/Write)` and `Pull requests (Read/Write)`
- `PACKAGIST_USERNAME`: your Packagist username
- `PACKAGIST_TOKEN`: API token from Packagist account settings

If Packagist secrets are missing, release/tag creation still works, but Packagist notification is skipped.

## Required GitHub Repository Settings
- `Settings -> Actions -> General -> Workflow permissions`: set to `Read and write permissions`
- Enable `Allow GitHub Actions to create and approve pull requests`

If `Allow GitHub Actions to create and approve pull requests` is disabled by org policy, `RELEASE_PLEASE_TOKEN` is required.
The workflow validates this token first and fails with a clear error if credentials are invalid.
