<?php

declare(strict_types=1);

namespace D3\DataWizardTasks\tests\unit\Application\Model;

use D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\Exports\InactiveCategories;
use D3\DataWizardTasks\Application\Model\Exports\PhpRuntimeInfo;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Language;

/**
 * @covers \D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars
 * @covers \D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars
 * @covers \D3\DataWizardTasks\Application\Model\Exports\InactiveCategories
 * @covers \D3\DataWizardTasks\Application\Model\Exports\PhpRuntimeInfo
 */
class TaskLabelsTest extends d3ModCfgUnitTestCase
{
    /** @dataProvider taskLabels */
    public function testTaskLabelsUseTheAdminTranslation(string $class, string $method, string $translationId): void
    {
        $originalLanguage = Registry::getLang();
        $language = $this->getMockBuilder(get_class($originalLanguage))->onlyMethods(['translateString'])->getMock();
        $language->expects($this->once())->method('translateString')->with($translationId, null, true)->willReturn('admin label');
        Registry::set(Language::class, $language);
        try {
            $task = oxNew($class);
            $this->assertSame('admin label', $task->$method());
        } finally {
            Registry::set(Language::class, $originalLanguage);
        }
    }

    public function taskLabels(): array
    {
        return [
            'CMS action title' => [FixWysiwygSpecialChars::class, 'getTitle', 'D3_DATAWIZARDTASKS_ACTIONS_FIXWYSIWYGSPECIALCHARS'],
            'CMS action description' => [FixWysiwygSpecialChars::class, 'getDescription', 'D3_DATAWIZARDTASKS_ACTIONS_FIXWYSIWYGSPECIALCHARS_DESC'],
            'CMS export title' => [DestroyedWysiwygSpecialChars::class, 'getTitle', 'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS'],
            'CMS export description' => [DestroyedWysiwygSpecialChars::class, 'getDescription', 'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_DESC'],
            'categories title' => [InactiveCategories::class, 'getTitle', 'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES'],
            'categories description' => [InactiveCategories::class, 'getDescription', 'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_DESC'],
            'PHP runtime title' => [PhpRuntimeInfo::class, 'getTitle', 'D3_DATAWIZARDTASKS_EXPORTS_PHP_RUNTIME_INFO'],
            'PHP runtime description' => [PhpRuntimeInfo::class, 'getDescription', 'D3_DATAWIZARDTASKS_EXPORTS_PHP_RUNTIME_INFO_DESC'],
        ];
    }
}
