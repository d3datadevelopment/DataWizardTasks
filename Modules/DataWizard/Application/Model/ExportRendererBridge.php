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

namespace D3\DataWizardTasks\Modules\DataWizard\Application\Model;

use D3\DataWizardTasks\Application\Model\ExportRenderer\JsonLines;

class ExportRendererBridge extends ExportRendererBridge_parent
{
    public const FORMAT_JSONL = 'JSONL';

    public function getRendererList(): array
    {
        return parent::getRendererList() + [self::FORMAT_JSONL => oxNew(JsonLines::class)];
    }
}
