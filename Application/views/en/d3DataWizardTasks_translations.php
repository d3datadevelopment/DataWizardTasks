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

$sLangName = "English";
// -------------------------------
// RESOURCE IDENTITFIER = STRING
// -------------------------------
return [

//Navigation
    'charset'                                                       => 'UTF-8',

    'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES'                 => 'deactivated categories, with active articles',
    'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_TREE'            => 'tree',
    'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_TITLE'           => 'title',
    'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_COUNT'           => 'count',

    'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS'       => 'texts with destroyed special characters',
    'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_DESC'  => 'This is typically caused when saving texts in the WYSIWYG editor and can lead to undefined errors in the shop flow.',
    'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_LOADID'=> 'load ID',
    'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_TITLE' => 'title',

    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES'                         => 'Order key figures by month',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_STARTDATE'         => 'start date (optional)',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_ENDDATE'           => 'end date (optional)',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_INVALID_DATE'            => 'Please enter valid dates in YYYY-MM-DD format.',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_INVALID_DATE_RANGE'      => 'The start date must not be after the end date.',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_ORDERSPERMONTH'          => 'orders per month',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_BASKETSIZE'              => 'shopping cart value',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_MONTH'                   => 'month',
    'D3_DATAWIZARDTASKS_EXPORTS_PHP_RUNTIME_INFO'                   => 'PHP runtime information (no SQL)',
    'D3_DATAWIZARDTASKS_EXPORTS_PHP_RUNTIME_INFO_DESC'              => 'Demonstrates an export that provides its data directly from PHP. SQL query preview is unavailable for this export.',

    'D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS'                 => 'add missing oxartextends entries',
    'D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS_REQUIRED_FIELDS' => 'The repair was not executed. No initialization is defined for these required oxartextends fields without database defaults: %s.',
    'D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS_FALLBACK'       => 'Fill missing required fields with type-specific placeholder values. These values may not be appropriate for your data.',
    'D3_DATAWIZARDTASKS_ACTIONS_FIXWYSIWYGSPECIALCHARS'             => 'fix texts with destroyed special characters',
];
