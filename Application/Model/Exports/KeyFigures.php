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

namespace D3\DataWizardTasks\Application\Model\Exports;

use D3\DataWizard\Application\Model\ExportBase;
use D3\DataWizard\Application\Model\Exceptions\TaskException;
use DateTimeImmutable;
use FormManager\Inputs\Date;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use FormManager\Factory as FormFactory;

class KeyFigures extends ExportBase
{
    public const STARTDATE_NAME = 'startdate';
    public const ENDDATE_NAME = 'enddate';

    protected ?string $startDateValue;
    protected ?string $endDateValue;

    /**
     * Shopkennzahlen
     */

    public function __construct()
    {
        $request = Registry::getRequest();
        $this->startDateValue = $this->normalizeDateValue(
            $request->getRequestEscapedParameter(self::STARTDATE_NAME),
            '1970-01-01'
        );
        $this->endDateValue = $this->normalizeDateValue(
            $request->getRequestEscapedParameter(self::ENDDATE_NAME),
            date('Y-m-d')
        );

        /** @var Date $startDate */
        $startDate = FormFactory::date(
            Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_STARTDATE', null, true),
            [
                'name'    => self::STARTDATE_NAME,
                'value'    => $this->startDateValue ?? '',
            ]
        );
        $this->registerFormElement($startDate);

        /** @var Date $endDate */
        $endDate = FormFactory::date(
            Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_ENDDATE', null, true),
            [
                'name'    => self::ENDDATE_NAME,
                'value'    => $this->endDateValue ?? '',
            ]
        );
        $this->registerFormElement($endDate);
    }

    private function normalizeDateValue($value, string $default): ?string
    {
        if ($value === null) {
            return $default;
        }
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? $default : $value;
    }

    private function parseDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES', null, true);
    }

    public function getDescription(): string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_DESC', null, true);
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        $startDate = $this->parseDate($this->startDateValue);
        $endDate = $this->parseDate($this->endDateValue);
        if ($startDate === null || $endDate === null) {
            throw new TaskException($this, Registry::getLang()->translateString(
                'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_INVALID_DATE',
                null,
                true
            ));
        }
        if ($startDate > $endDate) {
            throw new TaskException($this, Registry::getLang()->translateString(
                'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_INVALID_DATE_RANGE',
                null,
                true
            ));
        }

        $orderTable     = oxNew(Order::class)->getCoreTableName();
        $connection     = $this->getConnection();
        $ordersTitle    = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_ORDERSPERMONTH', null, true);
        $basketsTitle   = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_BASKETSIZE', null, true);
        $monthTitle     = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_MONTH', null, true);

        $endExclusive = $endDate->modify('+1 day')->format('Y-m-d');

        return [
            'SELECT
                DATE_FORMAT(oo.oxorderdate, "%Y-%m") as '.$connection->quoteIdentifier($monthTitle).', 
                COUNT(oo.oxid) AS '.$connection->quoteIdentifier($ordersTitle).', 
                ROUND(SUM(oo.OXTOTALBRUTSUM / oo.oxcurrate) / COUNT(oo.oxid), 2) as '.$connection->quoteIdentifier($basketsTitle).' 
            FROM '.$orderTable.' AS oo
            WHERE oo.oxorderdate >= :startDate AND oo.oxorderdate < :endExclusive
            GROUP BY DATE_FORMAT(oo.oxorderdate, "%Y-%m")
            ORDER BY DATE_FORMAT(oo.oxorderdate, "%Y-%m") DESC',
            [
                'startDate'     => $this->startDateValue,
                'endExclusive'  => $endExclusive,
            ],
        ];
    }
}
