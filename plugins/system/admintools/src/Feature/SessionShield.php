<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use ReflectionProperty;

class SessionShield extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		if (!$this->app->isClient('site'))
		{
			return false;
		}

		if ($this->skipFiltering)
		{
			return false;
		}

		return ($this->wafParams->getValue('sessionshield', 1) == 1);
	}

	/**
	 * Protect against session hijacking data
	 */
	public function onAfterInitialise(): void
	{
		$patterns = [
			// pipe or :, O, :	integer : " identifier " : integer : {
			'@[\|:]O:\d{1,}:"[\w_][\w\d_]{0,}":\d{1,}:{@i',
			// pipe or :, a, :	integer :{
			'@[\|:]a:\d{1,}:{@i',
		];

		$hashes = ['get', 'post'];

		foreach ($hashes as $hash)
		{
			$input = $this->input->$hash;
			$ref   = new ReflectionProperty($input, 'data');
			$ref->setAccessible(true);
			$allVars = $ref->getValue($input);

			if (empty($allVars))
			{
				continue;
			}

			foreach ($patterns as $regex)
			{
				if ($this->recursiveRegExMatch($regex, $allVars, true))
				{
					$extraInfo = "Hash      : $hash\n";
					$extraInfo .= "Variables :\n";
					$extraInfo .= print_r($allVars, true);
					$extraInfo .= "\n";
					$this->exceptionsHandler->blockRequest('sessionshield', null, $extraInfo);
				}
			}
		}
	}
}
