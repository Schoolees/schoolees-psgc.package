# Automated Versioning & Packagist Publish

This repo uses Conventional Commits + Release Please for automatic versioning.

- Push commits to `main` using conventional format (example: `fix(seeder): respect resources_path`).
- GitHub Action `.github/workflows/release.yml` opens/updates a release PR.
- When that PR is merged, it creates a Git tag + GitHub Release automatically.
- After release creation, the workflow notifies Packagist to fetch the new tag.

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

