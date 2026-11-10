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

namespace D3\DataWizardTasks\tests\integration;

use D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\Exports\InactiveCategories;
use D3\DataWizardTasks\Application\Model\Exports\KeyFigures;
use D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;
use Doctrine\DBAL\Connection;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProviderInterface;
use PDO;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;

/**
 * @covers \D3\DataWizardTasks\Application\Model\Exports\InactiveCategories
 * @covers \D3\DataWizardTasks\Application\Model\Exports\KeyFigures
 * @covers \D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars
 * @covers \D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars
 */
class NativePreparedStatementsTest extends d3ModCfgUnitTestCase
{
    private Connection $connection;
    private bool $originalEmulatedPrepares;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = ContainerFactory::getInstance()->getContainer()
            ->get(ConnectionProviderInterface::class)->get();

        $nativeConnection = $this->connection->getWrappedConnection();
        $this->assertInstanceOf(PDO::class, $nativeConnection);

        $this->originalEmulatedPrepares = $nativeConnection->getAttribute(PDO::ATTR_EMULATE_PREPARES);
        $nativeConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->getWrappedConnection()->setAttribute(
                PDO::ATTR_EMULATE_PREPARES,
                $this->originalEmulatedPrepares
            );
        }

        parent::tearDown();
    }

    /**
     * @dataProvider exportProfiles
     */
    public function testExportQueryRunsWithNativePreparedStatements(string $exportClass): void
    {
        /** @var \D3\DataWizard\Application\Model\ExportBase $export */
        $export = oxNew($exportClass);
        [$query, $parameters] = $export->getQuery();

        $result = $this->connection->executeQuery($query, $parameters);
        $result->free();
        $this->assertFalse($this->connection->getWrappedConnection()->getAttribute(PDO::ATTR_EMULATE_PREPARES));
    }

    public function exportProfiles(): array
    {
        return [
            'inactive categories' => [InactiveCategories::class],
            'key figures' => [KeyFigures::class],
            'CMS special characters' => [DestroyedWysiwygSpecialChars::class],
        ];
    }

    public function testInactiveCategoriesFiltersInactiveCategoriesAndActiveArticles(): void
    {
        [$query, $parameters] = oxNew(InactiveCategories::class)->getQuery();
        $this->assertSame(['categoryActive' => 0, 'articleActive' => 1], $parameters);
        $this->assertStringContainsString('oc.OXACTIVE = :categoryActive', $query);
        $this->assertStringContainsString('oa.OXACTIVE = :articleActive', $query);
        $this->assertStringContainsString('INNER JOIN', $query);
        $this->assertStringContainsString('COUNT(oa.oxid) as ', $query);
    }

    public function testExportColumnsUseBackendTranslations(): void
    {
        $oldLanguage = Registry::getLang();
        $language = $this->getMockBuilder(get_class($oldLanguage))->onlyMethods(['translateString'])->getMock();
        $language->method('translateString')->willReturnCallback(
            static fn ($id, $languageId = null, $admin = false) => ($admin ? 'admin_' : 'frontend_').$id
        );
        Registry::set(Language::class, $language);
        try {
            [$categoryQuery] = oxNew(InactiveCategories::class)->getQuery();
            foreach (['TREE', 'TITLE', 'COUNT'] as $suffix) {
                $this->assertStringContainsString('admin_D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_'.$suffix, $categoryQuery);
            }
            [$cmsQuery] = oxNew(DestroyedWysiwygSpecialChars::class)->getQuery();
            foreach (['TITLE', 'LOADID'] as $suffix) {
                $this->assertStringContainsString('admin_D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_'.$suffix, $cmsQuery);
            }
        } finally {
            Registry::set(Language::class, $oldLanguage);
        }
    }

    public function testCmsRepairCanBePreparedWithoutExecutingTheUpdate(): void
    {
        [$query, $parameters] = oxNew(FixWysiwygSpecialChars::class)->getQuery();
        $result = $this->connection->executeQuery('EXPLAIN '.$query, $parameters);
        $this->assertNotEmpty($result->fetchAllAssociative());
    }
}
