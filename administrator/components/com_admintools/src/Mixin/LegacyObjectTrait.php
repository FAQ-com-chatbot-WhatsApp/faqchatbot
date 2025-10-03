<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Mixin;

/** @noinspection PhpDeprecationInspection */
use Joomla\CMS\Object\CMSObject;

trait LegacyObjectTrait
{
	private function normalizePossibleCMSObject($item)
	{
		if (!is_object($item))
		{
			return $item;
		}

		/** @noinspection PhpDeprecationInspection */
		if (class_exists(CMSObject::class) && !$item instanceof CMSObject)
		{
			return $item;
		}

		/** @noinspection PhpDeprecationInspection */
		return (object) $item->getProperties();
	}
}