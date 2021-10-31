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

declare(strict_types=1);

namespace D3\DataWizardTasks\Application\Model\Actions;

use D3\DataWizard\Application\Model\ActionBase;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;

class FixArtextendsItems extends ActionBase
{
    /**
     * fehlende oxartextends-Einträge nachtragen
     */

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS', null, true);
    }

    /**
     * @return array
     */
    public function getQuery() : array
    {
        $aDefaultValueFields = array(
            'oxtimestamp'   => "''",
        );

        $aNonArtExtendsFields = array(
            'oxid'  => 'oxarticles.oxid',
        );

        $aArtExtendsFields = array_fill_keys($this->getArtExtendsFields(), "''");
        $aMergedFields = array_merge($aNonArtExtendsFields, $aArtExtendsFields);
        $aQueryFields = array_diff_key($aMergedFields, $aDefaultValueFields);

        $sArtExtendsFields = implode(', ', array_keys($aQueryFields));

        $select = "SELECT ".implode(', ', $aQueryFields).
            " FROM oxarticles".
            " LEFT JOIN oxartextends AS arx ON oxarticles.oxid = arx.oxid".
            " WHERE arx.oxid IS NULL";

        $query = "INSERT INTO oxartextends ($sArtExtendsFields) ".
            $select;

        return [$query, []];
    }

    /**
     * @return array
     */
    public function getArtExtendsFields(): array
    {
        /** @var $oArtExtends MultiLanguageModel */
        $oArtExtends = oxNew(BaseModel::class);
        $oArtExtends->init('oxartextends', false);

        $aFieldNames = $oArtExtends->getFieldNames();

        if (false == $aFieldNames) {
            $oArtExtends->disableLazyLoading();
            $aFieldNames = $oArtExtends->getFieldNames();
        }

        unset($aFieldNames[array_search('oxid', $aFieldNames)]);

        return $aFieldNames;
    }
}