<?php

declare(strict_types=1);

namespace D3\DataWizardTasks\tests\integration;

use D3\DataWizardTasks\Application\Model\Actions\FixArtextendsItems;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProviderInterface;

/** @covers \D3\DataWizardTasks\Application\Model\Actions\FixArtextendsItems */
class ArtExtendsSchemaTest extends d3ModCfgUnitTestCase
{
    public function testFieldListUsesActualDatabaseColumnNames(): void
    {
        $this->assertTrue((new \ReflectionMethod(FixArtextendsItems::class, 'getArtExtendsColumns'))->isProtected());
        $connection = ContainerFactory::getInstance()->getContainer()
            ->get(ConnectionProviderInterface::class)->get();
        $columns = $connection->executeQuery('SHOW FULL COLUMNS FROM '.$connection->quoteIdentifier('oxartextends'))
            ->fetchAllAssociative();
        $expected = [];
        foreach ($columns as $column) {
            $column = array_change_key_case($column, CASE_LOWER);
            if (strtolower($column['field']) !== 'oxid') {
                $expected[] = strtolower($column['field']);
            }
        }

        $this->assertNotEmpty($expected);
        $this->assertSame($expected, array_values(oxNew(FixArtextendsItems::class)->getArtExtendsFields()));
    }
}
