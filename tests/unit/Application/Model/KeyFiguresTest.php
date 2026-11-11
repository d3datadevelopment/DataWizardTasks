<?php

declare(strict_types=1);

namespace D3\DataWizardTasks\tests\unit\Application\Model;

use D3\DataWizard\Application\Model\Exceptions\TaskException;
use D3\DataWizardTasks\Application\Model\Exports\KeyFigures;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;
use Doctrine\DBAL\Connection;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Language;

/** @covers \D3\DataWizardTasks\Application\Model\Exports\KeyFigures */
class KeyFiguresTest extends d3ModCfgUnitTestCase
{
    private function export($start, $end): KeyFigures
    {
        $oldRequest = Registry::getRequest();
        $request = $this->getMockBuilder(Request::class)->onlyMethods(['getRequestEscapedParameter'])->getMock();
        $request->method('getRequestEscapedParameter')->willReturnCallback(
            static fn ($name) => $name === KeyFigures::STARTDATE_NAME ? $start : $end
        );
        Registry::set(Request::class, $request);
        try {
            $export = $this->getMockBuilder(KeyFigures::class)->onlyMethods(['getConnection'])->getMock();
        } finally {
            Registry::set(Request::class, $oldRequest);
        }
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn ($name) => '`'.$name.'`');
        $export->method('getConnection')->willReturn($connection);
        return $export;
    }

    public function testInclusiveEndDateAndUnformattedNumericResults(): void
    {
        [$query, $parameters] = $this->export('2024-02-01', '2024-02-29')->getQuery();
        $this->assertSame(['startDate' => '2024-02-01', 'endExclusive' => '2024-03-01'], $parameters);
        $this->assertStringContainsString('COUNT(oo.oxid)', $query);
        $this->assertStringContainsString('ROUND(SUM(', $query);
        $this->assertStringNotContainsString('FORMAT(COUNT', $query);
        $this->assertStringNotContainsString('LIMIT 30', $query);
    }

    /** @dataProvider invalidDates */
    public function testInvalidDateRangesAreRejected($start, $end): void
    {
        $export = $this->export($start, $end);
        $this->expectException(TaskException::class);
        $export->getQuery();
    }

    public function invalidDates(): array
    {
        return [
            'invalid calendar date' => ['2023-02-29', '2023-03-01'],
            'invalid format' => ['01.01.2024', '2024-02-01'],
            'reversed range' => ['2024-03-01', '2024-02-01'],
            'non-string' => [['2024-01-01'], '2024-02-01'],
            'prefix' => ['x2024-01-01', '2024-02-01'],
            'suffix' => ['2024-01-01x', '2024-02-01'],
        ];
    }

    public function testSingleDayAndWhitespaceAreHandled(): void
    {
        $export = $this->export(' 2024-02-29 ', '2024-02-29');
        [$query, $parameters] = $export->getQuery();
        $this->assertSame(['startDate' => '2024-02-29', 'endExclusive' => '2024-03-01'], $parameters);
        $this->assertStringContainsString('COUNT(oo.oxid) AS', $query);
        $fields = $export->getFormElements();
        $this->assertCount(2, $fields);
        $this->assertSame('startdate', $fields[0]->getAttribute('name'));
        $this->assertSame('enddate', $fields[1]->getAttribute('name'));
        $this->assertSame('2024-02-29', $fields[0]->getValue());
        $this->assertSame('2024-02-29', $fields[1]->getValue());
    }

    public function testFormAndQueryUseAdminTranslations(): void
    {
        $oldLanguage = Registry::getLang();
        $language = $this->getMockBuilder(get_class($oldLanguage))->onlyMethods(['translateString'])->getMock();
        $language->method('translateString')->willReturnCallback(
            static fn ($id, $languageId = null, $admin = false) => ($admin ? 'admin_' : 'frontend_').$id
        );
        Registry::set(Language::class, $language);
        try {
            $export = $this->export('2024-01-01', '2024-01-31');
            $this->assertSame('admin_D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES', $export->getTitle());
            $this->assertSame('admin_D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_DESC', $export->getDescription());
            $fields = $export->getFormElements();
            $this->assertStringContainsString('admin_D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_STARTDATE', (string) $fields[0]);
            $this->assertStringContainsString('admin_D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_ENDDATE', (string) $fields[1]);
            [$query] = $export->getQuery();
            foreach (['ORDERSPERMONTH', 'BASKETSIZE', 'MONTH'] as $suffix) {
                $this->assertStringContainsString('admin_D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_'.$suffix, $query);
            }
        } finally {
            Registry::set(Language::class, $oldLanguage);
        }
    }

    /** @dataProvider invalidDateMessages */
    public function testInvalidDateMessagesUseAdminTranslations($start, $end, string $expectedId): void
    {
        $oldLanguage = Registry::getLang();
        $language = $this->getMockBuilder(get_class($oldLanguage))->onlyMethods(['translateString'])->getMock();
        $language->method('translateString')->willReturnCallback(
            static fn ($id, $languageId = null, $admin = false) => ($admin ? 'admin_' : 'frontend_').$id
        );
        Registry::set(Language::class, $language);
        try {
            $export = $this->export($start, $end);
            $this->expectException(TaskException::class);
            $this->expectExceptionMessage('admin_'.$expectedId);
            $export->getQuery();
        } finally {
            Registry::set(Language::class, $oldLanguage);
        }
    }

    public function invalidDateMessages(): array
    {
        return [
            'invalid date' => ['invalid', '2024-01-01', 'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_INVALID_DATE'],
            'reversed range' => ['2024-02-01', '2024-01-01', 'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_INVALID_DATE_RANGE'],
        ];
    }

    public function testNonStringInputIsRejectedWithoutDeprecations(): void
    {
        $export = $this->export(['unexpected'], '2024-01-01');
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ($severity === E_DEPRECATED) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
            return false;
        });
        try {
            $this->expectException(TaskException::class);
            $export->getQuery();
        } finally {
            restore_error_handler();
        }
    }
}
