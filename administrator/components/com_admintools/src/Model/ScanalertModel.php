<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\LegacyObjectTrait;
use Akeeba\Component\AdminTools\Administrator\Table\ScanTable;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

#[\AllowDynamicProperties]
class ScanalertModel extends AdminModel
{
	use LegacyObjectTrait;

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_admintools.scanalert',
			'scanalert',
			[
				'control'   => 'jform',
				'load_data' => $loadData,
			]
		) ?: false;

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		/** @var CMSApplication $app */
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_admintools.edit.scanalert.data', []);
		$pk   = (int) $this->getState($this->getName() . '.id');
		$item = ($pk ? (object) $this->normalizePossibleCMSObject($this->getItem()) : false) ?: [];

		$data = $data ?: $item;
		$data = (object) array_merge((array)$item, (array)$data);

		if ($scan_id = ((array)$data)['scan_id'])
		{
			/** @var ScanModel $scanModel */
			$scanModel = $this->getMVCFactory()->createModel('Scan', 'Administrator');
			/** @var ScanTable $scan */
			$scan = $scanModel->getTable('Scan', 'Administrator');
			$scan->load($scan_id);

			if (is_array($data))
			{
				$data['scandate'] = $scan->scanstart;
			}
			else
			{
				$data->scandate = $scan->scanstart;
			}
		}

		$this->preprocessData('com_admintools.scanalert', $data);

		return $data;
	}

	protected function canDelete($record)
	{
		// You can never delete scan alert records. Delete the scan itself to delete all of its scan alerts records.
		return false;
	}
}