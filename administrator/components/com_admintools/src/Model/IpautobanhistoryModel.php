<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;

#[\AllowDynamicProperties]
class IpautobanhistoryModel extends AdminModel
{
	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		throw new \RuntimeException('You can not edit or create Automatically IP Blocking History entries through the end user interface.');
	}
}