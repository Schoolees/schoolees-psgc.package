# Changelog

## [1.1.2](https://github.com/Schoolees/schoolees-psgc.package/compare/v1.1.1...v1.1.2) (2026-08-24)


### Bug Fixes

* **api:** correct filter and pagination input handling ([fe9c66c](https://github.com/Schoolees/schoolees-psgc.package/commit/fe9c66c93cea26e191b07eaf47d9e60626de5a24))
* **api:** correct filter and pagination input handling ([e1aecaf](https://github.com/Schoolees/schoolees-psgc.package/commit/e1aecafe23bef2afd244dcdf5947d31fd3a2857a))


### Performance Improvements

* **db:** index name on provinces, cities, and barangays ([6285c11](https://github.com/Schoolees/schoolees-psgc.package/commit/6285c11301a9fbfd72d51b4c1ecacccd13122cc8))

## [1.1.1](https://github.com/Schoolees/schoolees-psgc.package/compare/v1.1.0...v1.1.1) (2026-07-31)


### Bug Fixes

* **data:** zero-pad PSGC codes for Regions I-IX ([ee29b3e](https://github.com/Schoolees/schoolees-psgc.package/commit/ee29b3e460f9d45cdc6b8049b94fcb7e5899a391))
* **models:** stop generating invalid PHP in make:psgc-models ([823f83e](https://github.com/Schoolees/schoolees-psgc.package/commit/823f83e0de173f73b4c922afa770dd05a9d32833))
* PSGC audit findings (invalid generated PHP, code padding, dead routes, LIKE escaping) ([6da334f](https://github.com/Schoolees/schoolees-psgc.package/commit/6da334fb3571557347506e1c5f1e0b8818c87de2))
* **routes:** remove unused routes/api.php and correct stale references ([8b18f95](https://github.com/Schoolees/schoolees-psgc.package/commit/8b18f954ba2eccf11b576f726d7fa94cbdafecd1))
* **search:** escape LIKE wildcards in PSGC filters ([0c9bb6e](https://github.com/Schoolees/schoolees-psgc.package/commit/0c9bb6e3a884a906c3d55d6d55b99670804e75dd))

## [1.1.0](https://github.com/Schoolees/schoolees-psgc.package/compare/v1.0.2...v1.1.0) (2026-04-18)


### Features

* **api:** unify route behavior and add a generic pagination mode ([4983bd5](https://github.com/Schoolees/schoolees-psgc.package/commit/4983bd54959f98d03c401968afe5396b0fb72492))


### Bug Fixes

* **package:** harden install, generation, seeding, and release recovery ([d89de83](https://github.com/Schoolees/schoolees-psgc.package/commit/d89de837d8765842f6294b4664052f74d3731cc0))
* **release:** fail when Packagist stays stale after a sync ([ada48e0](https://github.com/Schoolees/schoolees-psgc.package/commit/ada48e09ef7847d7cb7565898f9e6eb56b0aaf75))

## [1.0.2](https://github.com/Schoolees/schoolees-psgc.package/compare/v1.0.1...v1.0.2) (2026-04-18)


### Bug Fixes

* **api:** stop exposing internal exception messages ([10039a9](https://github.com/Schoolees/schoolees-psgc.package/commit/10039a921fde6a49e98d34f526dd385a80c00f60))

## 1.0.1 (2026-04-19)


### Bug Fixes

* **compat:** keep the PSGC package installable on Laravel 13 ([71c4e23](https://github.com/Schoolees/schoolees-psgc.package/commit/71c4e23))

## 1.0.0 (2026-02-17)


### Features

* **package:** harden query handling and add test scaffold ([c39267e](https://github.com/Schoolees/schoolees-psgc.package/commit/c39267eb0689c0be0fe68b534ceb562a38c1cee0))


### Bug Fixes

* **ci:** add release token preflight validation ([b4d6931](https://github.com/Schoolees/schoolees-psgc.package/commit/b4d6931ed573ccbdec559271b257e17ad3588d2c))
* **ci:** configure release permissions and bootstrap sha ([43bccbf](https://github.com/Schoolees/schoolees-psgc.package/commit/43bccbf7c326a9b79cafecd05a747ed750a466dd))
* **ci:** use release token for release-please PR creation ([8283ff8](https://github.com/Schoolees/schoolees-psgc.package/commit/8283ff8e0bb60be88a4b4101d5e17117612f803e))


### Miscellaneous Chores

* **release:** reset baseline for v1.0.0 ([84f4c96](https://github.com/Schoolees/schoolees-psgc.package/commit/84f4c9662324bd046f0ed9fd90eec7780debf21a))

## [0.2.0](https://github.com/Schoolees/schoolees-psgc.package/compare/v0.1.0...v0.2.0) (2026-02-17)


### Features

* **package:** harden query handling and add test scaffold ([c39267e](https://github.com/Schoolees/schoolees-psgc.package/commit/c39267eb0689c0be0fe68b534ceb562a38c1cee0))


### Bug Fixes

* **ci:** add release token preflight validation ([b4d6931](https://github.com/Schoolees/schoolees-psgc.package/commit/b4d6931ed573ccbdec559271b257e17ad3588d2c))
* **ci:** configure release permissions and bootstrap sha ([43bccbf](https://github.com/Schoolees/schoolees-psgc.package/commit/43bccbf7c326a9b79cafecd05a747ed750a466dd))
* **ci:** use release token for release-please PR creation ([8283ff8](https://github.com/Schoolees/schoolees-psgc.package/commit/8283ff8e0bb60be88a4b4101d5e17117612f803e))
