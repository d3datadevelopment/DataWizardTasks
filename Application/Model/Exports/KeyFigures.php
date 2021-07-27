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
use FormManager\Inputs\Date;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use FormManager\Factory as FormFactory;

class KeyFigures extends ExportBase
{
    const STARTDATE_NAME = 'startdate';
    const ENDDATE_NAME = 'enddate';

    /**
     * Shopkennzahlen
     */

    public function __construct()
    {
        /** @var Date $startDate */
        $startDateValue = Registry::getRequest()->getRequestEscapedParameter(self::STARTDATE_NAME);
        $startDate = FormFactory::date(
            Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_STARTDATE'),
            [
                'name'    => self::STARTDATE_NAME,
                'value'    => $startDateValue
            ]
        );
        $this->registerFormElement($startDate);

        /** @var Date $endDate */
        $endDateValue = Registry::getRequest()->getRequestEscapedParameter(self::ENDDATE_NAME);
        $endDate = FormFactory::date(
            Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_ENDDATE'),
            [
                'name'    => self::ENDDATE_NAME,
                'value'    => $endDateValue
            ]
        );
        $this->registerFormElement($endDate);
    }

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES');
    }

    /**
     * @return array
     */
    public function getQuery() : array
    {
        $orderTable     = oxNew(Order::class)->getCoreTableName();
        $ordersTitle    = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_ORDERSPERMONTH');
        $basketsTitle   = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_BASKETSIZE');
        $monthTitle     = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_MONTH');

        $startDateValue = Registry::getRequest()->getRequestEscapedParameter(self::STARTDATE_NAME) ?: '1970-01-01';
        $endDateValue   = Registry::getRequest()->getRequestEscapedParameter(self::ENDDATE_NAME) ?: date('Y-m-d');

        return [
            'SELECT
                DATE_FORMAT(oo.oxorderdate, "%Y-%m") as :monthTitle, 
                FORMAT(COUNT(oo.oxid), 0) AS :ordersTitle, 
                FORMAT(SUM(oo.OXTOTALBRUTSUM / oo.oxcurrate) / COUNT(oo.oxid), 2) as :basketsTitle 
            FROM '.$orderTable.' AS oo
            WHERE oo.oxorderdate >= :startDate AND oo.oxorderdate <= :endDate
            GROUP BY DATE_FORMAT(oo.oxorderdate, "%Y-%m")
            ORDER BY DATE_FORMAT(oo.oxorderdate, "%Y-%m") DESC
            LIMIT 30',
            [
                'startDate'     => $startDateValue,
                'endDate'       => $endDateValue,
                'monthTitle'    => $monthTitle,
                'ordersTitle'   => $ordersTitle,
                'basketsTitle'  => $basketsTitle
            ]
        ];
    }
}