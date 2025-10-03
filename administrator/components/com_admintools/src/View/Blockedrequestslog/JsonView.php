<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Blockedrequestslog;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Model\BlockedrequestslogsModel;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

class JsonView extends BaseJsonView
{
	use ViewTaskBasedEventsTrait;

	public function onBeforeMain()
	{
		/** @var BlockedrequestslogsModel $model */
		$model = $this->getModel();

		echo json_encode($model->getItems());
	}
}