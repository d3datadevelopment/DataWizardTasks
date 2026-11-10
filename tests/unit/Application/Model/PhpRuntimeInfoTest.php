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
 * @coversNothing
 */
class PhpRuntimeInfoTest extends d3ModCfgUnitTestCase
{
    public function testProvidesRuntimeRowsWithoutSql(): void
    {
        $export = new PhpRuntimeInfo();
        [$rows, $fieldNames] = $export->getExportData(['']);

        $this->assertFalse($export->hasQuery());
        $this->assertSame(['Setting', 'Value'], $fieldNames);
        $this->assertNotEmpty($rows);
        $this->assertContains(['Setting' => 'PHP version', 'Value' => PHP_VERSION], $rows);
    }
}
