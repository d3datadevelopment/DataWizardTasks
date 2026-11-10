[![deutsche Version](https://logos.oxidmodule.com/de2_xs.svg)](README.md)
[![english version](https://logos.oxidmodule.com/en2_xs.svg)](README.en.md)

# D³ Data Wizard Tasks for OXID eShop

This module contains useful sample tasks for the D3 `DataWizard` in the OXID eShop and acts as an implementation reference for own exports or tasks.

## Installation

In the console in the shop root (above source and vendor), execute the following command:

```bash
composer require d3/datawizardtasks
``` 

Activate DataWizard and this module in the shop administration area under "Extensions -> Modules". The examples for queryless exports and streaming renderers need a DataWizard version with `hasQuery()` and support for custom `StreamingRendererInterface` renderers respectively; the Composer constraint `^3.0` alone does not guarantee these capabilities.

## Included examples

- **Inactive categories:** lists inactive categories with directly assigned articles whose `OXACTIVE` flag is set.
- **Order figures by month:** counts orders and calculates an average basket value for the selected period. The end date is inclusive; the example query does not filter by shop or cancellation status. SQL results are numeric; presentation depends on the driver and export format.
- **CMS special characters:** an export finds content containing `-&gt;`; an action replaces that sequence with `->` in the language fields it processes.
- **Missing `oxartextends` records:** an action adds records for articles without a matching entry. Unknown required fields without defaults prevent query generation and execution. An unchecked-by-default option enables placeholders for supported data types; these may be inappropriate for the data. Unsupported types remain blocked.
- **PHP runtime information:** a queryless export reports selected PHP settings and extensions of the executing process. Its output can contain technical information.
- **Markdown:** a renderer exports records as a Markdown table (`.md`) using the format key `MARKDOWN`.

These profiles are examples and cannot account for every shop or module customization. Check data-changing actions and placeholders in a test environment before production use.

## Developing custom exports, actions, and export formats

DataWizard makes tasks available in the shop administration area. An export reads data and provides it in a supported format; an action changes data. This package contains examples of both. Put custom tasks in your own OXID module with Composer autoloading and a dependency on `d3/datawizard`, rather than in this example package.

The reference implementation is provided as an example only and is not intended to host custom extensions. Create a customer-specific plugin (OXID module) for custom exports and actions, and register your tasks there.

### Extend the task registry

The registry is `D3\DataWizard\Application\Model\Configuration`. Extend it through the OXID module chain and register tasks in `configure()`. Always call `parent::configure()` to preserve registrations from other modules. The included [Configuration](Modules/DataWizard/Application/Model/Configuration.php) shows the complete registration of the examples.

Example extension in your own module:

```php
namespace Acme\MyModule\Modules\DataWizard;

use D3\DataWizard\Application\Model\Configuration as Registry;
use Acme\MyModule\Export\ExampleExport;
use Acme\MyModule\Action\ExampleAction;

class Configuration extends Configuration_parent
{
    public function configure()
    {
        parent::configure();
        $this->registerExport(Registry::GROUP_SHOP, oxNew(ExampleExport::class));
        $this->registerAction(Registry::GROUP_ARTICLES, oxNew(ExampleAction::class));
    }
}
```

Add the extension to the `extend` array in your module's `metadata.php`:

```php
'extend' => [
    \D3\DataWizard\Application\Model\Configuration::class =>
        \Acme\MyModule\Modules\DataWizard\Configuration::class,
],
```

Use an existing `GROUP_*` constant or your own translatable group key. The registry derives task IDs from class names; registering the same class more than once in a group replaces the previous entry. Use separate task classes for different profiles. Activate your module and check the module chain in the shop administration area.

### Implement a task

An export extends `ExportBase`; an action extends `ActionBase`. Both implement at least `getTitle()` and `getQuery()`; `getDescription()` can explain the purpose and effects. Use translated text that shop operators can understand. `getQuery()` always returns an array containing SQL and parameters:

```php
public function getQuery(): array
{
    return [
        'SELECT OXID, OXTITLE FROM oxarticles WHERE OXACTIVE = :active',
        ['active' => 1],
    ];
}
```

This is the basic structure of an export. The base class expects a statement starting with `SELECT`. Column names become export headings. Parameters bind values; table and column names cannot be bound as values and must come from trusted sources and be quoted as identifiers using the database connection where needed. See [InactiveCategories](Application/Model/Exports/InactiveCategories.php) for a complete example.

An export can also provide data without SQL: [PhpRuntimeInfo](Application/Model/Exports/PhpRuntimeInfo.php) overrides `getExportData()` and returns false from `hasQuery()` so that no query preview is offered. Such exports do not use the regular streaming query path.

An action similarly returns a modifying statement and its parameters. The base class executes it using `executeStatement()` and records the affected row count; a statement starting with `SELECT` is rejected. See [FixWysiwygSpecialChars](Application/Model/Actions/FixWysiwygSpecialChars.php). Describe the scope and potential effects, especially when changing data across many records or shops.

### Register custom export formats

`D3\DataWizard\Application\Model\ExportRenderer\RendererBridge` manages the available export formats. The included [Markdown renderer](Application/Model/ExportRenderer/Markdown.php) and its [bridge extension](Modules/DataWizard/Application/Model/ExportRendererBridge.php) demonstrate integration. Implement additional formats, such as PDF, in the customer-specific module and register them through the OXID module chain. Override `getRendererList()` and retain the parent entries:

```php
namespace Acme\MyModule\Modules\DataWizard;

use Acme\MyModule\ExportRenderer\PdfRenderer;

class RendererBridge extends RendererBridge_parent
{
    public function getRendererList(): array
    {
        $renderers = parent::getRendererList();
        $renderers['ACME_PDF'] = oxNew(PdfRenderer::class);
        return $renderers;
    }
}
```

Also add the following entry to your `metadata.php` `extend` array:

```php
\D3\DataWizard\Application\Model\ExportRenderer\RendererBridge::class =>
    \Acme\MyModule\Modules\DataWizard\RendererBridge::class,
```

`PdfRenderer` is a class you implement yourself, not an included renderer. It implements `RendererInterface`: `getContent(iterable $rows, iterable $fieldNames): string` generates the file contents, `getFileExtension()` returns the filename extension, and `getTitleTranslationId()` returns the translation key for the format selector. Provide that key in your module's language files. The unique format key appears in the selector; `QUERY` is reserved for the SQL preview. Use simple, unique keys without special characters and avoid collisions with existing formats.

For streaming, a renderer additionally implements `StreamingRendererInterface`; [Markdown](Application/Model/ExportRenderer/Markdown.php) demonstrates both output paths. DataWizard also uses the streaming path for custom renderers, provided relevant export extension methods have not been overridden. Otherwise it uses the regular buffered path. Test both paths and memory usage with large exports.

The example renderer registers `MARKDOWN` and generates a Markdown table with the `.md` extension. Field names form the header; as with CSV, cells follow the order within each record, regardless of its keys. Each row must have the same number of cells as the header. No field names produce empty output; no records produce only the header and separator row.

Cells accept scalar values or `null`: `null` becomes an empty cell, and booleans become `true` or `false`. HTML and Markdown special characters are escaped, and line breaks become `<br>`. Tables and these line breaks require a Markdown viewer supporting them. Nested values cause a render error. This renderer is deliberately a simple integration example; JSON Lines is provided by the separate CLI extension `d3/datawizardcli`.

### Input, preview, and testing

Register FormManager fields using `registerFormElement()`, read and normalize submitted values, and validate them server-side before use. `run()` validates registered fields; implement business rules such as date ranges yourself and raise, for example, a `TaskException` with a useful message for invalid input. [KeyFigures](Application/Model/Exports/KeyFigures.php) demonstrates date fields, validation, and bound parameters. Give fields unique names because tasks share a form in the administration area.

After `init()`, the SQL preview calls `getQuery()` directly, without `run()` and its form validation. Put checks needed for safe query generation in the shared query path. The preview does not execute the query. Action-specific checks immediately before execution can also go in `executeAction()`; an override should call `parent::executeAction()` to execute the statement and record the result. [FixArtextendsItems](Application/Model/Actions/FixArtextendsItems.php) checks required fields both during query generation and before execution. If required fields cannot be initialized, the preview therefore displays an error instead of the query.

Test valid and invalid inputs, preview and actual execution, and empty and large result sets. For actions, first check affected records, repeatability, shop and language context, and error handling in a test environment. Assess permissions, privacy, and export format safety for the specific installation.

## Changelog

See [CHANGELOG](CHANGELOG.md) for further informations.

## Licence of this software (d3/datawizardtasks)
(status: 2021-07-27)

```
Copyright (c) D3 Data Development (Inh. Thomas Dartsch)

This software is distributed under the GNU GENERAL PUBLIC LICENSE version 3.
```

For full copyright and licensing information, please see the [LICENSE](LICENSE.md) file distributed with this source code.
