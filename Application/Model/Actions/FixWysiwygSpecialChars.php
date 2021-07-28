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

namespace D3\DataWizardTasks\Application\Model\Actions;

use D3\DataWizard\Application\Model\ActionBase;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\Registry;

class FixWysiwygSpecialChars extends ActionBase
{
    /**
     * @return string
     */
    public function getTitle() : string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_ACTIONS_FIXWYSIWYGSPECIALCHARS');
    }

    /**
     * @return array
     */
    public function getQuery() : array
    {
        $content            = oxNew(Content::class);
        $contentTableName   = $content->getCoreTableName();

        $currentLanguage = $content->getLanguage();
        $updateFields = [];
        $whereFields = [];
        foreach (array_keys(Registry::getLang()->getAllShopLanguageIds()) as $langId) {
            $content->setLanguage($langId);
            $fieldName = $content->getUpdateSqlFieldName('oxcontent');
            $updateFields[] = 'oc.'.$fieldName.' = REPLACE(oc.'.$fieldName.', :searchSpecialChars, :replaceSpecialChars)';
            $whereFields[] = $content->getUpdateSqlFieldName('oxcontent').' LIKE :whereSpecialChars';
        }
        $content->setLanguage($currentLanguage);

        return [
            "UPDATE
                ".$contentTableName." oc
                SET
                ".implode(', ', $updateFields)."
                WHERE ".implode(' OR ', $whereFields),
            [
                'searchSpecialChars'    => '-&gt;',
                'replaceSpecialChars'   => '->',
                'whereSpecialChars'     => '%-&gt;%'
            ]
        ];
    }
}