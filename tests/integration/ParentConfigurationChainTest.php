<?php

declare(strict_types=1);

namespace D3\DataWizardTasks\tests\integration;

use D3\DataWizard\Application\Model\Configuration as BaseConfiguration;
use D3\DataWizardTasks\Modules\DataWizard\Application\Model\Configuration as TasksConfiguration;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;

class ParentConfigurationFixture extends BaseConfiguration
{
    public bool $parentConfigured = false;

    public function configure()
    {
        parent::configure();
        $this->parentConfigured = true;
    }
}

/** @covers \D3\DataWizardTasks\Modules\DataWizard\Application\Model\Configuration */
class ParentConfigurationChainTest extends d3ModCfgUnitTestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testConfigurationInvokesPreviousModuleInChain(): void
    {
        $parentClass = 'D3\\DataWizardTasks\\Modules\\DataWizard\\Application\\Model\\Configuration_parent';
        if (class_exists($parentClass, false) || class_exists(TasksConfiguration::class, false)) {
            $this->markTestSkipped('The OXID module chain has already been initialized in this process.');
        }
        class_alias(ParentConfigurationFixture::class, $parentClass);

        $configuration = new TasksConfiguration();
        $this->assertTrue($configuration->parentConfigured);
        $this->assertNotEmpty($configuration->getActionGroups());
    }
}
