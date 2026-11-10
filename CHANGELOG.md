# Changelog

All notable changes to this project are documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Document how to register custom exports, actions and export renderers in a customer-specific module.
- Allow operators to opt in to type-specific placeholder values for supported required `oxartextends` fields.
- Add a PHP runtime information export without SQL and a Markdown table renderer as integration examples.
- Add PHPUnit configuration and tests for the new examples, query capability and native prepared statements.
- Add Infection configuration, a bootstrap for OXID parent classes and documented mutation-testing exceptions.
- Add PHP CS Fixer configuration for the package.

### Changed

- Replace the unreleased JSON Lines renderer example with a streaming Markdown table renderer (`MARKDOWN`, `.md`); JSON Lines is now provided by `d3/datawizardcli`.
- Remove the 30-month limit from the key figures export.
- Return numeric SQL results for order counts and rounded average basket values in the key figures export.
- Simplify category joins in the inactive categories export.

### Fixed

- Validate key figures date inputs and include the entire selected end date.
- Support CMS content stored in separate language tables in the special-character export and repair action.
- Respect column types and database defaults when repairing missing `oxartextends` records; reject unsupported required fields during query generation and before execution.
- Quote SQL aliases in example exports.

## [2.0.0.0] - 2024-05-13

### Added

- Add backend and Smarty translations.
- Add module artwork and a vector icon.

### Changed

- Make the module installable in OXID eShop 7.
- Update module metadata and package description.

### Removed

- Remove the obsolete check for FormManager versions below 5.

## [1.1.0.0] - 2021-11-11

### Changed

- Retrieve translations from the administration area.
- Initialize form elements with default values for generating prefilled link arguments.
- Add compatibility with DataWizard 2.x.
- Hide exports with form elements when the installed FormManager version is unsupported.

## [1.0.2.0] - 2021-07-29

### Changed

- Improve the description of the WYSIWYG special-character tasks.

## [1.0.1.0] - 2021-07-28

### Added

- Add tasks to find and fix special-character issues caused by the WYSIWYG editor.

## [1.0.0.0] - 2021-07-27

### Added

- Extract exports and actions from DataWizard Core into this package.
