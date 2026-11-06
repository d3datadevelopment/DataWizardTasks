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
    public function getTaskId(): string
    {
        return 'd3_fix_wysiwyg';
    }

    public function getGroup(): string
    {
        return \D3\DataWizard\Application\Model\Configuration::GROUP_CMS;
    }

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_ACTIONS_FIXWYSIWYGSPECIALCHARS', null, true);
    }

    /**
     * @return array
     */
    public function getQuery() : array
    {
        $content            = oxNew(Content::class);
        $contentTableName   = $content->getCoreTableName();
        $connection         = $this->getConnection();

        $currentLanguage = $content->getLanguage();
        $tableAliases = [$contentTableName => 'oc'];
        $joins = [];
        $updateFields = [];
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
                $updateFields[] = $fieldName.' = REPLACE('.$fieldName.', :searchSpecialChars, :replaceSpecialChars)';
                $whereFields[] = $fieldName.' LIKE :whereSpecialChars';
            }
        } finally {
            $content->setLanguage($currentLanguage);
        }

        return [
            "UPDATE
                ".$connection->quoteIdentifier($contentTableName)." oc
                ".implode("\n                ", $joins)."
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
