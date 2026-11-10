<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://www.d3data.de
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <info@shopmodule.com>
 * @link      https://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\DataWizardTasks\Application\Model\Exports;

use D3\DataWizard\Application\Model\ExportBase;
use OxidEsales\Eshop\Core\Registry;

class PhpRuntimeInfo extends ExportBase
{
    public function hasQuery(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_PHP_RUNTIME_INFO', null, true);
    }

    public function getDescription(): string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_PHP_RUNTIME_INFO_DESC', null, true);
    }

    public function getQuery(): array
    {
        return ['', []];
    }

    public function getExportData(array $query): array
    {
        $settings = [
            'PHP version' => PHP_VERSION,
            'PHP SAPI' => PHP_SAPI,
            'Operating system' => PHP_OS_FAMILY,
            'Integer size' => (string) (PHP_INT_SIZE * 8).' bit',
            'Default timezone' => date_default_timezone_get(),
            'Loaded extensions' => implode(', ', get_loaded_extensions()),
            'Memory limit' => (string) ini_get('memory_limit'),
            'Maximum execution time' => (string) ini_get('max_execution_time'),
            'Maximum input variables' => (string) ini_get('max_input_vars'),
            'Maximum POST size' => (string) ini_get('post_max_size'),
            'Maximum upload size' => (string) ini_get('upload_max_filesize'),
            'Maximum file uploads' => (string) ini_get('max_file_uploads'),
        ];

        $rows = [];
        foreach ($settings as $name => $value) {
            $rows[] = ['Setting' => $name, 'Value' => $value];
        }

        return [$rows, ['Setting', 'Value']];
    }
}
