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
use JsonException;

class JsonLines implements StreamingRendererInterface
{
    public function getContent(iterable $rows, iterable $fieldNames): string
    {
        $lines = [];
        try {
            foreach ($rows as $row) {
                $lines[] = json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (JsonException $exception) {
            throw oxNew(RenderException::class, $exception->getMessage(), []);
        }

        return $lines === [] ? '' : implode("\n", $lines)."\n";
    }

    public function writeContent($stream, iterable $rows, iterable $fieldNames): void
    {
        try {
            foreach ($rows as $row) {
                $line = json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                StreamWriter::write($stream, $line."\n");
            }
        } catch (JsonException $exception) {
            throw oxNew(RenderException::class, $exception->getMessage(), []);
        }
    }

    public function getFileExtension(): string
    {
        return 'jsonl';
    }

    public function getTitleTranslationId(): string
    {
        return 'D3_DATAWIZARDTASKS_EXPORT_FORMAT_JSONL';
    }
}
