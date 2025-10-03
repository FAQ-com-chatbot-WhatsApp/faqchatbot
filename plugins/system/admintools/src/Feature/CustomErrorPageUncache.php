<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;

class CustomErrorPageUncache extends Base
{
	public function isEnabled()
	{
		return true;
	}

	public function onPageCacheSetCaching()
	{
		// Were we explicitly requested to show the Block view?
		if (!$this->app->getSession()->get('com_admintools.block', false))
		{
			return true;
		}

		return false;
	}
}