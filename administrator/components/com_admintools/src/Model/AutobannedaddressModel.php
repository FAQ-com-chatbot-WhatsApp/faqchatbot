<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Mixin\RunPluginsTrait;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;

#[\AllowDynamicProperties]
class AutobannedaddressModel extends AdminModel
{
	use RunPluginsTrait;

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		throw new \RuntimeException('You can not edit or create Automatically Blocked IP addresses through the end user interface.');
	}

	public function delete(&$pks)
	{
		$table = $this->getTable();

		// Include the plugins for the delete events.
		PluginHelper::importPlugin($this->events_map['delete']);

		// Iterate the items to delete each one.
		foreach ($pks as $i => $pk)
		{
			try
			{
				$isTableLoaded = $table->load($pk);
				/** @noinspection PhpDeprecationInspection qualified access will work when getError is removed */
				$tableErrorMessage = method_exists($table, 'getError') ? $table->getError() : '';
			}
			catch (\Exception $e)
			{
				$isTableLoaded = false;
				$tableErrorMessage = $e->getMessage();
			}

			if (!$isTableLoaded)
			{
				if (!method_exists($this, 'setError'))
				{
					throw new \RuntimeException($tableErrorMessage);
				}

				/** @noinspection PhpDeprecationInspection only called when deprecated code is not removed */
				$this->setError($tableErrorMessage);

				return false;
			}

			if (!$this->canDelete($table))
			{
				// Prune items that you can't change.
				unset($pks[$i]);

				/** @noinspection PhpDeprecationInspection qualified access will work when getError is removed */
				$error = method_exists($this, 'getError') ? $this->getError() : '';

				if ($error)
				{
					Log::add($error, Log::WARNING, 'jerror');
				}
				else
				{
					Log::add(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), Log::WARNING, 'jerror');
				}

				return false;
			}

			$context = $this->option . '.' . $this->name;

			// Trigger the before delete event.
			try
			{
				$result     = $this->triggerPluginEvent($this->event_before_delete, [$context, $table]);
				/** @noinspection PhpDeprecationInspection qualified access will work when getError is removed */
				$tableError = method_exists($table, 'getError') ? $table->getError() : '';
			}
			catch (\Exception $e)
			{
				$result     = [false];
				$tableError = $e->getMessage();
			}

			if (\in_array(false, $result, true))
			{
				if (!method_exists($this, 'setError'))
				{
					throw new \RuntimeException($tableError);
				}

				/** @noinspection PhpDeprecationInspection only called when deprecated code is not removed */
				$this->setError($tableError);

				return false;
			}

			try
			{
				$isDeleted   = $table->delete($pk);
				/** @noinspection PhpDeprecationInspection qualified access will work when getError is removed */
				$errorString = method_exists($table, 'getError') ? $table->getError() : '';
			}
			catch (\Exception $e)
			{
				$isDeleted   = false;
				$errorString = $e->getMessage();
			}

			if (!$isDeleted)
			{
				if (!method_exists($this, 'setError'))
				{
					throw new \RuntimeException($tableError);
				}

				/** @noinspection PhpDeprecationInspection only called when deprecated code is not removed */
				$this->setError($errorString);

				return false;
			}

			// Trigger the after event.
			$this->triggerPluginEvent($this->event_after_delete, [$context, $table]);
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}


}