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
use D3\DataWizardTasks\Application\Model\ExportRenderer\Markdown;
use D3\ModCfg\Tests\unit\d3ModCfgUnitTestCase;

/** @covers \D3\DataWizardTasks\Application\Model\ExportRenderer\Markdown */
class MarkdownTest extends d3ModCfgUnitTestCase
{
    /** @dataProvider tableProvider */
    public function testBufferedAndStreamingOutput(array $rows, array $headers, string $expected): void
    {
        $renderer = new Markdown();
        $generator = static function (array $values): \Generator {
            foreach ($values as $value) {
                yield $value;
            }
        };
        $this->assertSame($expected, $renderer->getContent($generator($rows), $generator($headers)));
        $stream = fopen('php://temp', 'w+b');
        try {
            $renderer->writeContent($stream, $generator($rows), $generator($headers));
            rewind($stream);
            $this->assertSame($expected, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public static function tableProvider(): array
    {
        return [
            'empty' => [[], [], ''],
            'headers only' => [[], ['name'], "| name |\n| --- |\n"],
            'values in row order' => [
                [['id' => 1, 'name' => 'Ä/one'], ['id' => 2, 'name' => 'two']],
                ['ID', 'Name'], "| ID | Name |\n| --- | --- |\n| 1 | Ä/one |\n| 2 | two |\n",
            ],
            'scalar values' => [
                [[null, false, true, 0, 1.25]], ['a', 'b', 'c', 'd', 'e'],
                "| a | b | c | d | e |\n| --- | --- | --- | --- | --- |\n|  | false | true | 0 | 1.25 |\n",
            ],
            'html and line breaks' => [
                [["<script>&\"'\r\nsecond\rthird\nfourth"]], ['<header>'],
                "| &lt;header&gt; |\n| --- |\n| &lt;script&gt;&amp;&quot;&#039;<br>second<br>third<br>fourth |\n",
            ],
            'markdown syntax' => [
                [['a|b\\c`d*e_f[g]~']], ['a|b'],
                "| a\\|b |\n| --- |\n| a\\|b\\\\c\\`d\\*e\\_f\\[g\\]\\~ |\n",
            ],
        ];
    }

    /** @dataProvider invalidRowsProvider */
    public function testRejectsInvalidRows(array $row, bool $streaming): void
    {
        $stream = fopen('php://temp', 'w+b');
        try {
            $this->expectException(RenderException::class);
            if ($streaming) {
                (new Markdown())->writeContent($stream, [$row], ['column']);
            } else {
                (new Markdown())->getContent([$row], ['column']);
            }
        } finally {
            fclose($stream);
        }
    }

    public static function invalidRowsProvider(): array
    {
        return [
            [[], false], [[], true],
            [[1, 2], false], [[1, 2], true],
            [[['nested']], false], [[['nested']], true],
        ];
    }

    public function testStreamReceivesRowsBeforeTheNextRowIsRequested(): void
    {
        $stream = fopen('php://temp', 'w+b');
        try {
            $rows = (function () use ($stream): \Generator {
                yield new \ArrayIterator(['first']);
                $this->assertSame(strlen("| name |\n| --- |\n| first |\n"), ftell($stream));
                yield ['second'];
            })();
            (new Markdown())->writeContent($stream, $rows, ['name']);
            rewind($stream);
            $this->assertSame("| name |\n| --- |\n| first |\n| second |\n", stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    /** @dataProvider outputModeProvider */
    public function testRepeatedIteratorKeysDoNotDiscardColumns(bool $streaming): void
    {
        // Generators may repeat keys, e.g. when combining iterables with yield from.
        $headers = (static function (): \Generator {
            yield from ['First'];
            yield from ['Second'];
        })();
        $cells = (static function (): \Generator {
            yield from ['one'];
            yield from ['two'];
        })();
        $renderer = new Markdown();
        $expected = "| First | Second |\n| --- | --- |\n| one | two |\n";

        if (!$streaming) {
            $this->assertSame($expected, $renderer->getContent([$cells], $headers));
            return;
        }

        $stream = fopen('php://temp', 'w+b');
        try {
            $renderer->writeContent($stream, [$cells], $headers);
            rewind($stream);
            $this->assertSame($expected, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public static function outputModeProvider(): array
    {
        return ['buffered' => [false], 'streaming' => [true]];
    }

    public function testMetadata(): void
    {
        $renderer = new Markdown();
        $this->assertSame('md', $renderer->getFileExtension());
        $this->assertSame('D3_DATAWIZARDTASKS_EXPORT_FORMAT_MARKDOWN', $renderer->getTitleTranslationId());
    }
}
