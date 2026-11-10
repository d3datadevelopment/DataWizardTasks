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

use D3\DataWizard\Application\Model\ActionBase;
use D3\DataWizard\Application\Model\ExportBase;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;
use LogicException;

class QueryCapabilityTest extends d3ModCfgUnitTestCase
{
    public function testExportQueryCapabilityCheckDoesNotBuildQuery(): void
    {
        $export = new class extends ExportBase {
            public function getTitle(): string { return 'test'; }
            public function getQuery(): array { throw new LogicException('getQuery must not be called'); }
        };

        $this->assertTrue($export->hasQuery());
    }

    public function testActionQueryCapabilityCheckDoesNotBuildQuery(): void
    {
        $action = new class extends ActionBase {
            public function getTitle(): string { return 'test'; }
            public function getQuery(): array { throw new LogicException('getQuery must not be called'); }
        };

        $this->assertTrue($action->hasQuery());
    }
}
