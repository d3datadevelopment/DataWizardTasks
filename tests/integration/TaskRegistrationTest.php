<?php

declare(strict_types=1);

namespace D3\DataWizardTasks\tests\integration;

use D3\DataWizard\Application\Model\Configuration;
use D3\DataWizard\Application\Model\ExportRenderer\RendererBridge;
use D3\DataWizardTasks\Application\Model\Actions\FixArtextendsItems;
use D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\ExportRenderer\JsonLines;
use D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\Exports\InactiveCategories;
use D3\DataWizardTasks\Application\Model\Exports\KeyFigures;
use D3\DataWizardTasks\Application\Model\Exports\PhpRuntimeInfo;
use D3\DataWizardTasks\Modules\DataWizard\Application\Model\Configuration as TasksConfiguration;
use D3\DataWizardTasks\Modules\DataWizard\Application\Model\ExportRendererBridge;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;

/**
 * @covers \D3\DataWizardTasks\Modules\DataWizard\Application\Model\Configuration
 * @covers \D3\DataWizardTasks\Modules\DataWizard\Application\Model\ExportRendererBridge
 */
class TaskRegistrationTest extends d3ModCfgUnitTestCase
{
    public function testActivatedModuleRegistersTasksInTheirGroups(): void
    {
        $configuration = oxNew(Configuration::class);
        if (!$configuration instanceof TasksConfiguration) {
            $this->markTestSkipped('Activate the DataWizardTasks module to test the OXID module chain.');
        }

        foreach ([
            [Configuration::GROUP_ARTICLES, FixArtextendsItems::class, true],
            [Configuration::GROUP_CMS, FixWysiwygSpecialChars::class, true],
            [Configuration::GROUP_CATEGORY, InactiveCategories::class, false],
            [Configuration::GROUP_SHOP, KeyFigures::class, false],
            [Configuration::GROUP_SHOP, PhpRuntimeInfo::class, false],
            [Configuration::GROUP_CMS, DestroyedWysiwygSpecialChars::class, false],
        ] as [$group, $class, $isAction]) {
            $tasks = $isAction ? $configuration->getActionsByGroup($group) : $configuration->getExportsByGroup($group);
            $this->assertNotEmpty(array_filter($tasks, static fn ($task) => $task instanceof $class), $class);
        }
    }

    public function testActivatedModuleRetainsBuiltInRenderersAndAddsJsonLines(): void
    {
        $bridge = oxNew(RendererBridge::class);
        if (!$bridge instanceof ExportRendererBridge) {
            $this->markTestSkipped('Activate the DataWizardTasks module to test the OXID module chain.');
        }

        $this->assertInstanceOf(JsonLines::class, $bridge->getRenderer('JSONL'));
        $this->assertArrayHasKey(RendererBridge::FORMAT_CSV, $bridge->getRendererList());
        $this->assertArrayHasKey(RendererBridge::FORMAT_JSON, $bridge->getRendererList());
    }
}
