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

/**
 * Metadata version
 */

use D3\DataWizard\Application\Model\Configuration as ConfigurationParent;
use D3\DataWizard\Application\Model\ExportRenderer\RendererBridge as RendererBridgeParent;
use D3\DataWizardTasks\Modules\DataWizard\Application\Model\Configuration;
use D3\DataWizardTasks\Modules\DataWizard\Application\Model\ExportRendererBridge;

$sMetadataVersion = '2.1';

$sModuleId = 'd3datawizardtasks';
$logo = '(D3) ';

/**
 * Module information
 */
$aModule = [
    'id'          => $sModuleId,
    'title'       => [
        'de'    => $logo.' Data Wizard - Exporte, Aktionen und Renderer',
        'en'    => $logo.' Data Wizard - exports, actions and renderers',
    ],
    'description' => [
        'de'    => '',
        'en'    => '',
    ],
    'thumbnail'   => 'picture.svg',
    'version'     => '2.0.0.0',
    'author'      => 'D&sup3; Data Development (Inh.: Thomas Dartsch)',
    'email'       => 'support@shopmodule.com',
    'url'         => 'https://www.oxidmodule.com/',
    'controllers' => [],
    'extend'      => [
        ConfigurationParent::class => Configuration::class,
        RendererBridgeParent::class => ExportRendererBridge::class,
    ],
    'events'      => [],
    'templates'   => [],
    'settings'    => [],
    'blocks'      => [],
];
