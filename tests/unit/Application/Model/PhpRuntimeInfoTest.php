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

namespace D3\DataWizardTasks\tests\unit\Application\Model;

use D3\DataWizardTasks\Application\Model\Exports\PhpRuntimeInfo;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;

/**
 * @covers \D3\DataWizardTasks\Application\Model\Exports\PhpRuntimeInfo
 */
class PhpRuntimeInfoTest extends d3ModCfgUnitTestCase
{
    public function testProvidesRuntimeRowsWithoutSql(): void
    {
        $export = new PhpRuntimeInfo();
        [$rows, $fieldNames] = $export->getExportData(['']);

        $this->assertFalse($export->hasQuery());
        $this->assertSame(['', []], $export->getQuery());
        $this->assertSame(['Setting', 'Value'], $fieldNames);
        $this->assertNotEmpty($rows);
        $this->assertContains(['Setting' => 'PHP version', 'Value' => PHP_VERSION], $rows);
        $this->assertContains(['Setting' => 'Integer size', 'Value' => (string) (PHP_INT_SIZE * 8).' bit'], $rows);
        $this->assertContains(['Setting' => 'Default timezone', 'Value' => date_default_timezone_get()], $rows);
        $this->assertContains(['Setting' => 'Loaded extensions', 'Value' => implode(', ', get_loaded_extensions())], $rows);
    }
}
