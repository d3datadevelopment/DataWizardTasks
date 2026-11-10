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

namespace D3\DataWizardTasks\tests\unit\Application\Model\ExportRenderer;

use D3\DataWizard\Application\Model\Exceptions\RenderException;
use D3\DataWizardTasks\Application\Model\ExportRenderer\JsonLines;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;

class JsonLinesTest extends d3ModCfgUnitTestCase
{
    public function testRendersGeneratorAsOneJsonObjectPerLine(): void
    {
        $renderer = new JsonLines();
        $rows = (static function (): \Generator {
            yield ['name' => 'Ä/one'];
            yield ['name' => 'two'];
        })();

        $this->assertSame(
            "{\"name\":\"Ä/one\"}\n{\"name\":\"two\"}\n",
            $renderer->getContent($rows, ['name'])
        );
    }

    public function testWritesGeneratorDirectlyToStream(): void
    {
        $renderer = new JsonLines();
        $rows = (static function (): \Generator {
            yield ['id' => 1];
            yield ['id' => 2];
        })();
        $stream = fopen('php://temp', 'w+b');

        try {
            $renderer->writeContent($stream, $rows, ['id']);
            rewind($stream);

            $this->assertSame("{\"id\":1}\n{\"id\":2}\n", stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testEmptyIterableProducesEmptyOutput(): void
    {
        $this->assertSame('', (new JsonLines())->getContent([], []));
    }

    public function testInvalidJsonRaisesRenderException(): void
    {
        $this->expectException(RenderException::class);

        (new JsonLines())->getContent([['value' => "\xB1\x31"]], ['value']);
    }
}
