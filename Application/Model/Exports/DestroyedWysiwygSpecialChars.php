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
        $connection         = $this->getConnection();

        $titleTitle = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_TITLE', null, true);
        $loadIdTitle = Registry::getLang()->translateString('D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_LOADID', null, true);

        $currentLanguage = $content->getLanguage();
        $tableAliases = [$contentTableName => 'oc'];
        $joins = [];
        $whereFields = [];
        try {
            foreach (array_keys(Registry::getLang()->getAllShopLanguageIds()) as $langId) {
                $languageTableName = getLangTableName($contentTableName, $langId);
                if (!isset($tableAliases[$languageTableName])) {
                    $alias = 'oc'.count($tableAliases);
                    $tableAliases[$languageTableName] = $alias;
                    $joins[] = 'LEFT JOIN '.$connection->quoteIdentifier($languageTableName).' '.$alias.
                        ' ON '.$alias.'.OXID = oc.OXID';
                }

                $content->setLanguage($langId);
                $fieldName = $tableAliases[$languageTableName].'.'.
                    $connection->quoteIdentifier($content->getUpdateSqlFieldName('oxcontent'));
                $whereFields[] = $fieldName.' LIKE :specialChars';
            }
        } finally {
            $content->setLanguage($currentLanguage);
        }

        return [
            "SELECT
                oc.OXID,
                oc.OXSHOPID,
                oc.OXLOADID as ".$connection->quoteIdentifier($loadIdTitle).",
                oc.OXTITLE as ".$connection->quoteIdentifier($titleTitle)."
                FROM ".$connection->quoteIdentifier($contentTableName)." oc
                ".implode("\n                ", $joins)."
                WHERE ".implode(' OR ', $whereFields),
            [
                'specialChars'      => '%-&gt;%'
            ]
        ];
    }
}
