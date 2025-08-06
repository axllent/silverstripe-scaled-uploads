# Changelog

Notable changes to this project will be documented in this file.

## [2.3.8]

- Add option to increase memory limit for processing images on upload

## [2.3.7]

- Add support for Silverstripe 6
- Use `TEMP_PATH` instead of `TEMP_FOLDER`

## [2.3.6]

- Fix: Handle all exif rotation states

## [2.3.5]

- PHP 8.2 compatibility

## [2.3.4]

- Add support for Silverstripe 5

## [2.2.3]

- Prevent warning on PHP 8.1

## [2.2.2]

- Fix for `legacy_filenames` - Do not delete original after scaling if `legacy_filenames: true`

## [2.2.1]

- Use `Configurable` instead of `Config`

## [2.2.0]

- Add custom folder configuration options

## [2.1.1]

- Switch to silverstripe-vendormodule

## [2.1.0]

- Write modified file properly
- Update configs

## [2.0.1]

- Support new Image InterventionBackend
- No longer rely on GD

## [2.0.0]

- Support for Silverstripe 4
- Rewrite with new Asset backend
- Extend Upload rather than Image
- Add option to scale only height or width
- Option to force resampling on all images
- Remove default yaml config
- Test if function `exif_read_data` exists

## [1.0.2]

- Test if function `exif_read_data` exists

## [1.0.1]

- Fix typo in README.md
- Add CHANGELOG & docs

## [1.0.0]

- Adopt semantic versioning releases
- Release versions
