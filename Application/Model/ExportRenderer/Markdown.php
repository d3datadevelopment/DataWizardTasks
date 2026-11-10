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

namespace D3\DataWizardTasks\Application\Model\ExportRenderer;

use D3\DataWizard\Application\Model\Exceptions\RenderException;
use D3\DataWizard\Application\Model\ExportRenderer\StreamWriter;
use D3\DataWizard\Application\Model\ExportRenderer\StreamingRendererInterface;

class Markdown implements StreamingRendererInterface
{
    public function getContent(iterable $rows, iterable $fieldNames): string
    {
        $content = '';
        foreach ($this->lines($rows, $fieldNames) as $line) {
            $content .= $line;
        }
        return $content;
    }

    public function writeContent($stream, iterable $rows, iterable $fieldNames): void
    {
        foreach ($this->lines($rows, $fieldNames) as $line) {
            StreamWriter::write($stream, $line);
        }
    }

    private function lines(iterable $rows, iterable $fieldNames): \Generator
    {
        $headers = is_array($fieldNames) ? $fieldNames : iterator_to_array($fieldNames, false);
        if ($headers === []) {
            return;
        }

        yield $this->row($headers);
        yield '| '.implode(' | ', array_fill(0, count($headers), '---'))." |\n";
        foreach ($rows as $row) {
            $cells = is_array($row) ? $row : iterator_to_array($row, false);
            if (count($cells) !== count($headers)) {
                throw oxNew(RenderException::class, 'Markdown row does not match the number of columns', []);
            }
            yield $this->row($cells);
        }
    }

    private function row(array $cells): string
    {
        return '| '.implode(' | ', array_map([$this, 'cell'], $cells))." |\n";
    }

    private function cell(mixed $value): string
    {
        if ($value !== null && !is_scalar($value)) {
            throw oxNew(RenderException::class, 'Markdown cells must be scalar or null', []);
        }
        $text = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Escape cell content, then add only our own markup for line breaks.
        return strtr($text, [
            '\\' => '\\\\', '|' => '\\|', '`' => '\\`', '*' => '\\*',
            '_' => '\\_', '[' => '\\[', ']' => '\\]', '~' => '\\~',
            "\r\n" => '<br>', "\r" => '<br>', "\n" => '<br>',
        ]);
    }

    public function getFileExtension(): string
    {
        return 'md';
    }

    public function getTitleTranslationId(): string
    {
        return 'D3_DATAWIZARDTASKS_EXPORT_FORMAT_MARKDOWN';
    }
}
