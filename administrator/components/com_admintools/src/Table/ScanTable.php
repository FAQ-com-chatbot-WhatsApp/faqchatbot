<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

/**
 * @property int    $id
 * @property string $comment
 * @property string $scanstart
 * @property string $scanend
 * @property string $status
 * @property string $origin
 * @property int    $totalfiles
 */
class ScanTable extends AbstractTable
{
	public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__admintools_scans', 'id', $db, $dispatcher);
	}

	protected function onAfterDelete($result, $pk)
	{
		if (!$result)
		{
			return;
		}

		$db    = $this->getDbo();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->quoteName('#__admintools_scanalerts'))
			->where($db->quoteName('scan_id') . ' = :scan_id')
			->bind(':scan_id', $pk);

		$db->setQuery($query)->execute();
	}
}