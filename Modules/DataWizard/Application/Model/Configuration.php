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

namespace D3\DataWizardTasks\Modules\DataWizard\Application\Model;

use D3\DataWizard\Application\Model\Configuration as ConfigurationParent;
use D3\DataWizardTasks\Application\Model\Actions\FixArtextendsItems;
use D3\DataWizardTasks\Application\Model\Actions\FixWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\Exports\DestroyedWysiwygSpecialChars;
use D3\DataWizardTasks\Application\Model\Exports\InactiveCategories;
use D3\DataWizardTasks\Application\Model\Exports\KeyFigures;

class Configuration extends Configuration_parent
{
    public function configure()
    {
        parent::configure();

        $this->registerAction( ConfigurationParent::GROUP_ARTICLES, oxNew( FixArtextendsItems::class));
        $this->registerAction( ConfigurationParent::GROUP_CMS, oxNew( FixWysiwygSpecialChars::class));

        $this->registerExport( ConfigurationParent::GROUP_CATEGORY, oxNew( InactiveCategories::class));
        $this->registerExport( ConfigurationParent::GROUP_SHOP, oxNew( KeyFigures::class));
        $this->registerExport( ConfigurationParent::GROUP_CMS, oxNew( DestroyedWysiwygSpecialChars::class));
    }
}