<?php

declare(strict_types=1);

namespace D3\DataWizardTasks\tests\integration;

use D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\UtilsObject;
use RuntimeException;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProviderInterface;

/**
 * @covers \D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars
 * @covers \D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars
 */
class CmsSpecialCharactersTest extends d3ModCfgUnitTestCase
{
    public function testDetectionAndRepairCoverTheSameConfiguredLanguageFields(): void
    {
        $connection = ContainerFactory::getInstance()->getContainer()
            ->get(ConnectionProviderInterface::class)->get();
        $content = oxNew(Content::class);
        $originalLanguage = $content->getLanguage();
        $table = $content->getCoreTableName();
        $aliases = [$table => 'oc'];
        $fields = [];

        try {
            foreach (array_keys(Registry::getLang()->getAllShopLanguageIds()) as $languageId) {
                $languageTable = getLangTableName($table, $languageId);
                if (!isset($aliases[$languageTable])) {
                    $aliases[$languageTable] = 'oc'.count($aliases);
                }
                $content->setLanguage($languageId);
                $fields[] = $aliases[$languageTable].'.'.$connection->quoteIdentifier(
                    $content->getUpdateSqlFieldName('oxcontent')
                );
            }
        } finally {
            $content->setLanguage($originalLanguage);
        }

        $this->assertNotEmpty($fields);
        [$exportQuery, $exportParameters] = oxNew(DestroyedWysiwygSpecialChars::class)->getQuery();
        [$actionQuery, $actionParameters] = oxNew(FixWysiwygSpecialChars::class)->getQuery();

        $this->assertStringStartsWith('SELECT', ltrim($exportQuery));
        $this->assertStringContainsString('FROM '.$connection->quoteIdentifier($table).' oc', $exportQuery);
        $this->assertStringStartsWith('UPDATE', ltrim($actionQuery));
        $this->assertStringContainsString('UPDATE'."\n".'                '.$connection->quoteIdentifier($table).' oc', $actionQuery);
        $this->assertStringContainsString('SET', $actionQuery);
        $this->assertStringContainsString('WHERE', $actionQuery);

        foreach ($fields as $field) {
            $this->assertStringContainsString($field.' LIKE :specialChars', $exportQuery);
            $this->assertStringContainsString($field.' LIKE :whereSpecialChars', $actionQuery);
            $this->assertStringContainsString($field.' = REPLACE('.$field.', :searchSpecialChars, :replaceSpecialChars)', $actionQuery);
        }
        $this->assertSame(['specialChars' => '%-&gt;%'], $exportParameters);
        $this->assertSame([
            'searchSpecialChars' => '-&gt;',
            'replaceSpecialChars' => '->',
            'whereSpecialChars' => '%-&gt;%',
        ], $actionParameters);
    }

    public function testSeparateLanguageTableIsJoinedOnlyOnce(): void
    {
        $oldLanguage = Registry::getLang();
        $language = $this->getMockBuilder(get_class($oldLanguage))
            ->onlyMethods(['getAllShopLanguageIds', 'getMultiLangTables'])->getMock();
        $perTable = (int) Registry::getConfig()->getConfigParam('iLangPerTable');
        $perTable = $perTable > 1 ? $perTable : 8;
        $language->method('getAllShopLanguageIds')->willReturn([0 => 'base', $perTable => 'extra']);
        $language->method('getMultiLangTables')->willReturn(['oxcontents']);

        Registry::set(Language::class, $language);
        try {
            $connection = ContainerFactory::getInstance()->getContainer()
                ->get(ConnectionProviderInterface::class)->get();
            $table = getLangTableName('oxcontents', $perTable);
            $this->assertNotSame('oxcontents', $table);
            $join = 'LEFT JOIN '.$connection->quoteIdentifier($table).' oc1 ON oc1.OXID = oc.OXID';
            [$exportQuery] = oxNew(DestroyedWysiwygSpecialChars::class)->getQuery();
            [$actionQuery] = oxNew(FixWysiwygSpecialChars::class)->getQuery();
            $this->assertSame(1, substr_count($exportQuery, $join));
            $this->assertSame(1, substr_count($actionQuery, $join));
            $base = $connection->quoteIdentifier('oxcontents').' oc';
            $this->assertMatchesRegularExpression('/'.preg_quote($base, '/').'\s+'.preg_quote($join, '/').'/', $exportQuery);
            $this->assertMatchesRegularExpression('/'.preg_quote($base, '/').'\s+'.preg_quote($join, '/').'/', $actionQuery);
            $this->assertLessThan(strpos($exportQuery, 'WHERE'), strpos($exportQuery, $join));
            $this->assertLessThan(strpos($actionQuery, 'SET'), strpos($actionQuery, $join));
            $this->assertLessThan(strpos($actionQuery, 'WHERE'), strpos($actionQuery, $join));
            $this->assertStringContainsString('oc1.', $exportQuery);
            $this->assertStringContainsString('oc1.', $actionQuery);
        } finally {
            Registry::set(Language::class, $oldLanguage);
        }
    }

    /** @dataProvider cmsTasks */
    public function testContentLanguageIsRestoredWhenQueryConstructionFails(string $taskClass): void
    {
        $originalLanguage = Registry::getLang();
        $language = $this->getMockBuilder(get_class($originalLanguage))
            ->onlyMethods(['getAllShopLanguageIds'])->getMock();
        $language->method('getAllShopLanguageIds')->willReturn([0 => 'base']);
        $content = $this->getMockBuilder(Content::class)->disableOriginalConstructor()
            ->onlyMethods(['getCoreTableName', 'getLanguage', 'setLanguage', 'getUpdateSqlFieldName'])->getMock();
        $content->method('getCoreTableName')->willReturn('oxcontents');
        $content->method('getLanguage')->willReturn(3);
        $calls = [];
        $content->method('setLanguage')->willReturnCallback(static function ($id) use (&$calls) {
            $calls[] = $id;
        });
        $content->method('getUpdateSqlFieldName')->willThrowException(new RuntimeException('query failed'));

        Registry::set(Language::class, $language);
        UtilsObject::setClassInstance(Content::class, $content);
        try {
            try {
                oxNew($taskClass)->getQuery();
                $this->fail('Expected query construction to fail.');
            } catch (RuntimeException $exception) {
                $this->assertSame('query failed', $exception->getMessage());
                $this->assertSame([0, 3], $calls);
            }
        } finally {
            UtilsObject::resetClassInstances();
            Registry::set(Language::class, $originalLanguage);
        }
    }

    public function cmsTasks(): array
    {
        return [
            'action' => [FixWysiwygSpecialChars::class],
            'export' => [DestroyedWysiwygSpecialChars::class],
        ];
    }
}
