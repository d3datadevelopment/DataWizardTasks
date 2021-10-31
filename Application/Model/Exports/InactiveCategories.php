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
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Object2Category;
use OxidEsales\Eshop\Core\Registry;

class InactiveCategories extends ExportBase
{
    /**
     * Kategorien -deaktiviert, mit aktiven Artikeln
     */

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES', null, true);
    }

    /**
     * @return array
     */
    public function getQuery() : array
    {
        $categoryTableName          = oxNew(Category::class)->getCoreTableName();
        $object2categoryTableName   = oxNew(Object2Category::class)->getCoreTableName();
        $articleTableName           = oxNew(Article::class)->getCoreTableName();

        $treeTitle  = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_TREE', null, true);
        $titleTitle = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_TITLE', null, true);
        $countTitle = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_COUNT', null, true);

        return [
            "SELECT
                oc.OXID,
                oc.OXSHOPID,
                oc.oxtitle as :titleTitle,
                (
                    SELECT GROUP_CONCAT(oxtitle ORDER BY oxleft ASC SEPARATOR ' > ') 
                    from ".$categoryTableName." 
                    WHERE OXLEFT < oc.oxleft AND OXRIGHT > oc.oxright AND OXROOTID = oc.OXROOTID AND OXSHOPID = oc.OXSHOPID
                ) as :treeTitle,
                COUNT(oa.oxid) as :countTitle
                FROM ".$categoryTableName." oc
                LEFT JOIN ".$object2categoryTableName." o2c ON oc.OXID = o2c.OXCATNID
                LEFT JOIN ".$articleTableName." oa ON o2c.OXOBJECTID = oa.OXID
                WHERE oc.OXACTIVE = :categoryActive AND oa.OXACTIVE = :articleActive
                GROUP BY oc.oxid
                ORDER BY oc.oxleft ASC",
            [
                'categoryActive'    => 0,
                'articleActive'     => 1,
                'titleTitle'        => $titleTitle,
                'treeTitle'         => $treeTitle,
                'countTitle'        => $countTitle
            ]
        ];
    }
}