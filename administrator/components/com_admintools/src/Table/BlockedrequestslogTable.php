<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Utilities\IpHelper;

/**
 * @property int    $id
 * @property string $logdate
 * @property string $ip
 * @property string $url
 * @property string $reason
 * @property string $extradata
 */
class BlockedrequestslogTable extends AbstractTable
{
	public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__admintools_log', 'id', $db, $dispatcher);
	}

	public function onBeforeCheck()
	{
		if (empty($this->logdate))
		{
			$this->logdate = (clone Factory::getDate())->toSql();
		}

		if (empty($this->ip))
		{
			$this->ip = IpHelper::getIp();
		}

		if (empty($this->url))
		{
			$this->url = Uri::getInstance()->toString();
		}

		if (empty($this->reason))
		{
			$this->reason = 'other';
		}
	}
}