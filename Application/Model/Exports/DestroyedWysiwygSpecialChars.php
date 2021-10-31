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
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\Registry;

class DestroyedWysiwygSpecialChars extends ExportBase
{
    /**
     * @return string
     */
    public function getTitle() : string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS', null, true);
    }

    public function getDescription() : string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_DESC', null, true);
    }

    /**
     * @return array
     */
    public function getQuery() : array
    {
        $content            = oxNew(Content::class);
        $contentTableName   = $content->getCoreTableName();

        $titleTitle = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_TITLE', null, true);
        $loadIdTitle = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_LOADID', null, true);

        $currentLanguage = $content->getLanguage();
        $whereFields = [];
        foreach (array_keys(Registry::getLang()->getAllShopLanguageIds()) as $langId) {
            $content->setLanguage($langId);
            $whereFields[] = $content->getUpdateSqlFieldName('oxcontent').' LIKE :specialChars';
        }
        $content->setLanguage($currentLanguage);

        return [
            "SELECT
                oc.OXID,
                oc.OXSHOPID,
                oc.OXLOADID as :loadId,
                oc.OXTITLE as :titleTitle
                FROM ".$contentTableName." oc
                WHERE ".implode(' OR ', $whereFields)."
                GROUP BY oc.oxloadid",
            [
                'specialChars'      => '%-&gt;%',
                'loadId'            => $loadIdTitle,
                'titleTitle'        => $titleTitle
            ]
        ];
    }
}