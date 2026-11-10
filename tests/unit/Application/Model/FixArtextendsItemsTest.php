<?php

declare(strict_types=1);

namespace D3\DataWizardTasks\tests\unit\Application\Model;

use D3\DataWizard\Application\Model\Exceptions\TaskException;
use D3\DataWizardTasks\Application\Model\Actions\FixArtextendsItems;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;
use Doctrine\DBAL\Connection;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Language;

/** @covers \D3\DataWizardTasks\Application\Model\Actions\FixArtextendsItems */
class FixArtextendsItemsTest extends d3ModCfgUnitTestCase
{
    public function testSchemaInspectionRemainsAnExtensionPoint(): void
    {
        $method = new \ReflectionMethod(FixArtextendsItems::class, 'getArtExtendsColumns');
        $this->assertTrue($method->isProtected());
    }

    public function testSubclassCanProvideSchemaColumns(): void
    {
        $action = new class extends FixArtextendsItems {
            protected function getArtExtendsColumns(): array
            {
                return [
                    ['field' => 'OXID'],
                    ['field' => 'CUSTOM_COLUMN'],
                ];
            }
        };

        $this->assertSame(['custom_column'], array_values($action->getArtExtendsFields()));
    }

    private function action(array $columns, bool $fallback = false): FixArtextendsItems
    {
        $oldRequest = Registry::getRequest();
        $request = $this->getMockBuilder(Request::class)->onlyMethods(['getRequestEscapedParameter'])->getMock();
        $request->method('getRequestEscapedParameter')->willReturn($fallback ? 'on' : null);
        Registry::set(Request::class, $request);
        try {
            $action = $this->getMockBuilder(FixArtextendsItems::class)
                ->onlyMethods(['getArtExtendsColumns', 'getConnection'])->getMock();
        } finally {
            Registry::set(Request::class, $oldRequest);
        }
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn ($name) => '`'.$name.'`');
        $action->method('getConnection')->willReturn($connection);
        $action->method('getArtExtendsColumns')->willReturn($columns);
        return $action;
    }

    private function column(string $name, string $type, ?string $default = null, string $nullable = 'NO', string $extra = ''): array
    {
        return ['field' => $name, 'type' => $type, 'default' => $default, 'null' => $nullable, 'extra' => $extra];
    }

    public function testDefaultQueryUsesOnlyRequiredLongDescriptions(): void
    {
        $action = $this->action([
            $this->column('OXID', 'char(32)'),
            $this->column('OXLONGDESC_1', 'longtext'),
            $this->column('OPTIONAL', 'varchar(20)', null, 'YES'),
            $this->column('DEFAULTED', 'int', '1'),
            $this->column('GENERATED', 'int', null, 'NO', 'STORED GENERATED'),
        ]);
        [$query, $parameters] = $action->getQuery();
        $this->assertStringStartsWith('INSERT INTO oxartextends', $query);
        $this->assertStringContainsString('INSERT INTO oxartextends (`OXID`, `OXLONGDESC_1`)', $query);
        $this->assertStringContainsString("SELECT oa.OXID, ''", $query);
        $this->assertStringContainsString('FROM oxarticles AS oa LEFT JOIN oxartextends AS arx ON oa.oxid = arx.oxid WHERE arx.oxid IS NULL', $query);
        $this->assertStringNotContainsString('OPTIONAL', $query);
        $this->assertStringNotContainsString('DEFAULTED', $query);
        $this->assertStringNotContainsString('GENERATED', $query);
        $this->assertSame([], $parameters);
    }

    public function testUnknownRequiredColumnBlocksPreviewAndExecution(): void
    {
        $action = $this->action([$this->column('CUSTOM_FIELD', 'varchar(20)')]);
        foreach (['getQuery', 'executeAction'] as $method) {
            try {
                $method === 'getQuery' ? $action->getQuery() : $action->executeAction(['DELETE FROM oxartextends', []]);
                $this->fail('Expected the required field check to reject '.$method);
            } catch (TaskException $exception) {
                $this->assertStringContainsString('CUSTOM_FIELD', $exception->getMessage());
            }
        }
    }

    /** @dataProvider fallbackValues */
    public function testOptInAddsTypeSpecificValue(string $type, string $value): void
    {
        $action = $this->action([$this->column('CUSTOM_FIELD', $type)], true);
        [$query] = $action->getQuery();
        $this->assertStringContainsString('`CUSTOM_FIELD`', $query);
        $this->assertStringContainsString('SELECT oa.OXID, '.$value, $query);
    }

    public function fallbackValues(): array
    {
        return [
            'text' => ['varchar(20)', "''"],
            'number' => ['decimal(10,2)', '0'],
            'date' => ['date', "'1970-01-01'"],
            'datetime' => ['datetime', "'1970-01-02 00:00:00'"],
            'time' => ['time', "'00:00:00'"],
            'year' => ['year', '1970'],
            'uppercase text' => ['VARCHAR(20)', "''"],
            'uppercase integer' => ['INT', '0'],
            'uppercase date' => ['DATE', "'1970-01-01'"],
            'uppercase datetime' => ['DATETIME', "'1970-01-02 00:00:00'"],
            'uppercase time' => ['TIME', "'00:00:00'"],
            'uppercase year' => ['YEAR', '1970'],
        ];
    }

    public function testUnsupportedTypeRemainsBlockedWhenFallbackIsEnabled(): void
    {
        $action = $this->action([$this->column('CUSTOM_ENUM', "enum('a','b')")], true);
        $this->expectException(TaskException::class);
        $action->getQuery();
    }

    public function testSupportedRepairExecutesAndReportsAffectedRows(): void
    {
        $action = $this->getMockBuilder(FixArtextendsItems::class)
            ->onlyMethods(['getArtExtendsColumns', 'getConnection'])->getMock();
        $action->method('getArtExtendsColumns')->willReturn([
            $this->column('OXID', 'char(32)'),
            $this->column('OXLONGDESC', 'longtext'),
        ]);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('executeStatement')
            ->with('UPDATE oxartextends SET OXID = OXID', [])->willReturn(2);
        $action->method('getConnection')->willReturn($connection);

        $action->executeAction(['UPDATE oxartextends SET OXID = OXID', []]);
        $this->assertSame(2, $action->getActionResult()->getAffectedRows());
    }

    public function testFieldListNormalizesNamesAndExcludesPrimaryKey(): void
    {
        $action = $this->action([
            $this->column('OXID', 'char(32)'),
            $this->column('OXLONGDESC', 'longtext'),
            $this->column('CustomField', 'int'),
        ]);
        $this->assertSame(['oxlongdesc', 'customfield'], array_values($action->getArtExtendsFields()));
    }

    public function testFallbackCheckboxReflectsExplicitChoice(): void
    {
        foreach ([false, true] as $enabled) {
            $action = $this->action([], $enabled);
            $fields = $action->getFormElements();
            $this->assertCount(1, $fields);
            $this->assertSame('d3_fixartextends_fallback', $fields[0]->getAttribute('name'));
            $this->assertSame($enabled ? true : null, $fields[0]->getValue());
        }
    }

    public function testTitleAndFallbackLabelUseBackendTranslations(): void
    {
        $oldLanguage = Registry::getLang();
        $language = $this->getMockBuilder(get_class($oldLanguage))->onlyMethods(['translateString'])->getMock();
        $language->method('translateString')->willReturnCallback(
            static fn ($id, $languageId = null, $admin = false) => ($admin ? 'admin_' : 'frontend_').$id
        );
        Registry::set(Language::class, $language);
        try {
            $action = $this->action([]);
            $this->assertSame('admin_D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS', $action->getTitle());
            $this->assertStringContainsString(
                'admin_D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS_FALLBACK',
                (string) $action->getFormElements()[0]
            );
        } finally {
            Registry::set(Language::class, $oldLanguage);
        }
    }

    public function testRequiredFieldAfterIgnoredColumnsStillBlocksRepair(): void
    {
        $action = $this->action([
            $this->column('OXID', 'char(32)'),
            $this->column('OPTIONAL', 'text', null, 'yes'),
            $this->column('GENERATED', 'int', null, 'NO', 'stored generated'),
            $this->column('REQUIRED', 'int'),
        ]);
        $this->expectException(TaskException::class);
        $this->expectExceptionMessage('REQUIRED');
        $action->getQuery();
    }

    /** @dataProvider unsupportedLongDescriptionFields */
    public function testOnlyRecognizedLongDescriptionTextFieldsAreInitialized(string $field, string $type): void
    {
        $action = $this->action([$this->column($field, $type)]);
        $this->expectException(TaskException::class);
        $action->getQuery();
    }

    public function unsupportedLongDescriptionFields(): array
    {
        return [
            'prefix' => ['CUSTOM_OXLONGDESC', 'text'],
            'suffix' => ['OXLONGDESC_EXTRA', 'text'],
            'wrong type' => ['OXLONGDESC', 'int'],
            'unrelated text' => ['CUSTOM_FIELD', 'text'],
        ];
    }

    public function testPrimaryKeyAndDatabaseManagedColumnsAreNotInitialized(): void
    {
        $action = $this->action([
            $this->column('OXID', 'char(32)'),
            $this->column('OPTIONAL', 'text', null, 'yes'),
            $this->column('GENERATED', 'int', null, 'NO', 'stored generated'),
        ], true);
        [$query] = $action->getQuery();
        $this->assertStringContainsString('INSERT INTO oxartextends (`OXID`) SELECT oa.OXID', $query);
        $this->assertStringNotContainsString('`OPTIONAL`', $query);
        $this->assertStringNotContainsString('`GENERATED`', $query);
    }

    public function testNoPrimaryKeyInFieldListPreservesFirstColumn(): void
    {
        $action = $this->action([
            $this->column('FIRST_FIELD', 'int'),
            $this->column('SECOND_FIELD', 'text'),
        ]);
        $this->assertSame(['first_field', 'second_field'], array_values($action->getArtExtendsFields()));
    }

    public function testLongDescriptionWithUppercaseDatabaseTypeIsInitialized(): void
    {
        $action = $this->action([$this->column('OXLONGDESC', 'LONGTEXT')]);
        [$query] = $action->getQuery();
        $this->assertStringContainsString('`OXLONGDESC`', $query);
        $this->assertStringContainsString("SELECT oa.OXID, ''", $query);
    }

    public function testFailedRepairClearsPreviousResult(): void
    {
        $action = $this->getMockBuilder(FixArtextendsItems::class)
            ->onlyMethods(['getArtExtendsColumns', 'getConnection'])->getMock();
        $action->method('getArtExtendsColumns')->willReturnOnConsecutiveCalls(
            [$this->column('OXID', 'char(32)')],
            [$this->column('REQUIRED', 'int')]
        );
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('executeStatement')->willReturn(2);
        $action->method('getConnection')->willReturn($connection);
        $action->executeAction(['UPDATE oxartextends SET OXID = OXID', []]);
        $this->assertNotNull($action->getActionResult());

        try {
            $action->executeAction(['UPDATE oxartextends SET OXID = OXID', []]);
            $this->fail('Expected an unsupported required field to be rejected');
        } catch (TaskException $exception) {
            $this->assertNull($action->getActionResult());
        }
    }
}
