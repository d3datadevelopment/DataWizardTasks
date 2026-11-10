<?php

declare(strict_types=1);

namespace D3\DataWizardTasks\tests\integration;

use D3\DataWizard\Application\Model\Configuration;
use D3\DataWizard\Application\Model\ExportRenderer\RendererBridge;
use D3\DataWizardTasks\Application\Model\Actions\FixArtextendsItems;
use D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\ExportRenderer\Markdown;
use D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\Exports\InactiveCategories;
use D3\DataWizardTasks\Application\Model\Exports\KeyFigures;
use D3\DataWizardTasks\Application\Model\Exports\PhpRuntimeInfo;
use D3\DataWizardTasks\Modules\DataWizard\Application\Model\Configuration as TasksConfiguration;
use D3\DataWizardTasks\Modules\DataWizard\Application\Model\ExportRendererBridge;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;

class TaskRegistrationTest extends d3ModCfgUnitTestCase
{
    /**
     * @covers \D3\DataWizardTasks\Modules\DataWizard\Application\Model\Configuration
     */
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

    /**
     * @covers \D3\DataWizardTasks\Modules\DataWizard\Application\Model\ExportRendererBridge
     */
    public function testActivatedModuleRetainsBuiltInRenderersAndAddsMarkdown(): void
    {
        $bridge = oxNew(RendererBridge::class);
        if (!$bridge instanceof ExportRendererBridge) {
            $this->markTestSkipped('Activate the DataWizardTasks module to test the OXID module chain.');
        }

        $this->assertInstanceOf(Markdown::class, $bridge->getRenderer('MARKDOWN'));
        $this->assertArrayHasKey(RendererBridge::FORMAT_CSV, $bridge->getRendererList());
        $this->assertArrayHasKey(RendererBridge::FORMAT_JSON, $bridge->getRendererList());
    }
}
