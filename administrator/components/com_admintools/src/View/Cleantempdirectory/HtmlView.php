<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Cleantempdirectory;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Model\CleantempdirectoryModel;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
	/**
	 * Do we have more processing to do?
	 *
	 * @var  bool
	 */
	public $more;

	public function display($tpl = null)
	{
		/** @var CleantempdirectoryModel $model */
		$model = $this->getModel();
		$this->more = !$model->getState('scanstate', false);

		$this->setLayout('default');

		$this->getDocument()
			->getWebAssetManager()
			->useScript('com_admintools.clean_tmp');

		parent::display($tpl);
	}
}