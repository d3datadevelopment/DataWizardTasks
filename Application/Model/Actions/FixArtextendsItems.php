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
use D3\DataWizard\Application\Model\Exceptions\TaskException;
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
        $connection = $this->getConnection();
        $queryFields = [$connection->quoteIdentifier('OXID') => 'oa.OXID'];
        $unsupportedFields = [];

        foreach ($this->getArtExtendsColumns() as $column) {
            $fieldName = $column['field'];
            $extra = $column['extra'];
            if (strtolower($fieldName) === 'oxid') {
                continue;
            }

            // Let the database supply defaults, NULLs and generated values.
            if ($column['default'] !== null || strtoupper($column['null']) === 'YES' ||
                stripos($extra, 'auto_increment') !== false ||
                preg_match('/\b(?:VIRTUAL|STORED|PERSISTENT)\b/i', $extra)) {
                continue;
            }

            if (preg_match('/^oxlongdesc(?:_[0-9]+)?$/i', $fieldName) &&
                preg_match('/^(?:tinytext|text|mediumtext|longtext)\b/i', $column['type'])) {
                $queryFields[$connection->quoteIdentifier($fieldName)] = "''";
            } else {
                $unsupportedFields[] = $fieldName;
            }
        }

        if ($unsupportedFields !== []) {
            throw new TaskException($this, sprintf(
                Registry::getLang()->translateString('D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS_REQUIRED_FIELDS', null, true),
                implode(', ', $unsupportedFields)
            ));
        }

        $sArtExtendsFields = implode(', ', array_keys($queryFields));

        $select = "SELECT ".implode(', ', $queryFields).
            " FROM oxarticles AS oa".
            " LEFT JOIN oxartextends AS arx ON oa.oxid = arx.oxid".
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
        $aFieldNames = array_map('strtolower', array_column($this->getArtExtendsColumns(), 'field'));

        $oxidKey = array_search('oxid', $aFieldNames, true);
        if ($oxidKey !== false) {
            unset($aFieldNames[$oxidKey]);
        }

        return $aFieldNames;
    }

    protected function getArtExtendsColumns(): array
    {
        $connection = $this->getConnection();
        $columns = $connection->executeQuery(
            'SHOW FULL COLUMNS FROM '.$connection->quoteIdentifier('oxartextends')
        )->fetchAllAssociative();

        return array_map(static function (array $column): array {
            return array_change_key_case($column, CASE_LOWER);
        }, $columns);
    }
}
