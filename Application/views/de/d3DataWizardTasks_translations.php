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

$sLangName = "Deutsch";
// -------------------------------
// RESOURCE IDENTITFIER = STRING
// -------------------------------
return [

//Navigation
    'charset'                                                       => 'UTF-8',

    'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES'                 => 'deaktivierte Kategorien, mit aktiven Artikeln',
    'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_TREE'            => 'Baum',
    'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_TITLE'           => 'Titel',
    'D3_DATAWIZARDTASKS_EXPORTS_INACTIVECATEGORIES_COUNT'           => 'Anzahl',

    'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS'       => 'Texte mit zerstörten Sonderzeichen',
    'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_DESC'  => 'Dies wird typischerweise beim Speichern von Texten im WYSIWYG-Editor hervorgerufen und kann zu undefinierten Fehlern im Shopablauf führen.',
    'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_LOADID' => 'Lade-ID',
    'D3_DATAWIZARDTASKS_EXPORTS_DESTROYEDWYSIWYGSPECIALCHARS_TITLE' => 'Titel',

    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES'                         => 'Bestellungskennzahlen nach Monat',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_STARTDATE'         => 'Startdatum (optional)',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_FIELD_ENDDATE'           => 'Enddatum (optional)',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_INVALID_DATE'            => 'Bitte gültige Datumswerte im Format JJJJ-MM-TT eingeben.',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_INVALID_DATE_RANGE'      => 'Das Startdatum darf nicht nach dem Enddatum liegen.',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_ORDERSPERMONTH'          => 'Bestellungen pro Monat',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_BASKETSIZE'              => 'Warenkorbhöhe',
    'D3_DATAWIZARDTASKS_EXPORTS_KEYFIGURES_MONTH'                   => 'Monat',
    'D3_DATAWIZARDTASKS_EXPORTS_PHP_RUNTIME_INFO'                   => 'PHP-Laufzeitinformationen (ohne SQL)',
    'D3_DATAWIZARDTASKS_EXPORTS_PHP_RUNTIME_INFO_DESC'              => 'Demonstriert einen Export, der seine Daten direkt aus PHP bezieht. Die Abfragevorschau ist für diesen Export nicht verfügbar.',
    'D3_DATAWIZARDTASKS_EXPORT_FORMAT_MARKDOWN'                        => 'Markdown-Tabelle',

    'D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS'                 => 'fehlende oxartextends-Einträge nachtragen',
    'D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS_REQUIRED_FIELDS' => 'Die Reparatur wurde nicht ausgeführt. Für folgende Pflichtfelder in oxartextends ohne Datenbankstandardwert ist keine Initialisierung definiert: %s.',
    'D3_DATAWIZARDTASKS_ACTIONS_FIXARTEXTENDSITEMS_FALLBACK'       => 'Fehlende Pflichtfelder mit typabhängigen Platzhalterwerten befüllen. Diese Werte können fachlich unpassend sein.',
    'D3_DATAWIZARDTASKS_ACTIONS_FIXWYSIWYGSPECIALCHARS'             => 'Texte mit zerstörten Sonderzeichen korrigieren',
];
