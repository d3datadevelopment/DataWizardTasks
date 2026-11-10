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
use FormManager\Factory as FormFactory;
use OxidEsales\Eshop\Core\Registry;

class FixArtextendsItems extends ActionBase
{
    private const FALLBACK_FIELD = 'd3_fixartextends_fallback';
    private bool $useFallbackValues;

    public function __construct()
    {
        $this->useFallbackValues = Registry::getRequest()->getRequestEscapedParameter(self::FALLBACK_FIELD) === 'on';
        $checkbox = FormFactory::checkbox(
            Registry::getLang()->translateString('D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS_FALLBACK', null, true),
            ['name' => self::FALLBACK_FIELD]
        );
        $checkbox->setValue($this->useFallbackValues);
        $this->registerFormElement($checkbox);
    }
    /**
     * fehlende oxartextends-Einträge nachtragen
     */

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return Registry::getLang()->translateString('D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS', null, true);
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        $columns = $this->getArtExtendsColumns();
        $this->assertSupportedRequiredFields($columns);

        $connection = $this->getConnection();
        $queryFields = [$connection->quoteIdentifier('OXID') => 'oa.OXID'];

        foreach ($columns as $column) {
            $fieldName = $column['field'];
            if (strtolower($fieldName) === 'oxid') {
                continue;
            }

            if ($this->requiresInitialization($column)) {
                $value = $this->initialValue($column);
                if ($value !== null) {
                    $queryFields[$connection->quoteIdentifier($fieldName)] = $value;
                }
            }
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

    public function executeAction(array $query): void
    {
        $this->clearActionResult();
        $this->assertSupportedRequiredFields($this->getArtExtendsColumns());

        parent::executeAction($query);
    }

    private function assertSupportedRequiredFields(array $columns): void
    {
        $unsupportedFields = [];
        foreach ($columns as $column) {
            $fieldName = $column['field'];
            if (strtolower($fieldName) === 'oxid' || !$this->requiresInitialization($column)) {
                continue;
            }
            if ($this->initialValue($column) === null) {
                $unsupportedFields[] = $fieldName;
            }
        }

        if ($unsupportedFields !== []) {
            throw new TaskException($this, sprintf(
                Registry::getLang()->translateString('D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS_REQUIRED_FIELDS', null, true),
                implode(', ', $unsupportedFields)
            ));
        }
    }

    private function requiresInitialization(array $column): bool
    {
        return $column['default'] === null && strtoupper($column['null']) !== 'YES' &&
            stripos($column['extra'], 'auto_increment') === false &&
            !preg_match('/\b(?:VIRTUAL|STORED|PERSISTENT)\b/i', $column['extra']);
    }

    private function initialValue(array $column): ?string
    {
        $type = $column['type'];
        if (preg_match('/^oxlongdesc(?:_[0-9]+)?$/i', $column['field']) &&
            preg_match('/^(?:tinytext|text|mediumtext|longtext)\b/i', $type)) {
            return "''";
        }
        if (!$this->useFallbackValues) {
            return null;
        }
        if (preg_match('/^(?:char|varchar|tinytext|text|mediumtext|longtext|binary|varbinary|tinyblob|blob|mediumblob|longblob)\b/i', $type)) {
            return "''";
        }
        if (preg_match('/^(?:tinyint|smallint|mediumint|int|integer|bigint|decimal|numeric|float|double|real|bit|bool|boolean)\b/i', $type)) {
            return '0';
        }
        if (preg_match('/^date\b/i', $type)) {
            return "'1970-01-01'";
        }
        if (preg_match('/^(?:datetime|timestamp)\b/i', $type)) {
            return "'1970-01-02 00:00:00'";
        }
        if (preg_match('/^time\b/i', $type)) {
            return "'00:00:00'";
        }
        if (preg_match('/^year\b/i', $type)) {
            return '1970';
        }
        return null;
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
