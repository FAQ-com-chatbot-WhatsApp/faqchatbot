<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use ReflectionProperty;

class SQLiShield extends Base
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

		return ($this->wafParams->getValue('sqlishield', 0) == 1);
	}

	/**
	 * Fend off most common types of SQLi attacks. See the comments in the code
	 * for more security-minded information.
	 */
	public function onAfterRoute(): void
	{
		/**
		 * We filter all hashes separately to guard against variable shading.
		 *
		 * Remember that Joomla's input object returns by default the $_REQUEST parameters which are typically
		 * registered in EGPCS order, meaning a GET variable can "shade" a POST variable. However, the input object also
		 * allows you to explicitly request data from $_GET or $_POST. An attacker could use that to perform an SQLi
		 * attack against a component which explicitly uses POST data, hiding their attack from the WAF by setting an
		 * innocuous value to the same request parameter in the URL (therefore registering a GET parameter which
		 * "shades" the POST parameter). By checking GET and POST separately this kind of misdirection doesn't work
		 * since we'd see BOTH the innocuous GET value AND the malicious POST value, blocking the request as a result.
		 */
		$hashes = ['get', 'post'];
		$regex  = '#(union([\s]{1,}|/\*(.*)\*/){1,}(all([\s]{1,}|/\*(.*)\*/){1,})?select|select(([\s]{1,}|/\*(.*)\*/|`){1,}([\w]|_|-|\.|\*){1,}([\s]{1,}|/\*(.*)\*/|`){1,}(,){0,})*from([\s]{1,}|/\*(.*)\//){1,}[a-z0-9]{1,}_|select([\s]{1,}|/\*(.*)\*/|\(){1,}(COUNT|MID|FLOOR|LIMIT|RAND|SLEEP|ELT)|select([\s]{1,}|/\*(.*)\*/|`){1,}.*from([\s]{1,}|/\*(.*)\//){1,}INFORMATION_SCHEMA\.|EXTRACTVALUE([\s]{1,}|\(){1,}|(insert|replace)(([\s]{1,}|/\*(.*)\*/){1,})((low_priority|delayed|high_priority|ignore)([\s]{1,}|/\*(.*)\*/){1,}){0,}into|drop([\s]{1,}|/\*(.*)\*/){1,}(database|schema|event|procedure|function|trigger|view|index|server|(temporary([\s]{1,}|/\*(.*)\*/){1,}){0,1}table){1,1}([\s]{1,}|/\*(.*)\*/){1,}|update([\s]{1,}|/\*[^\w]*\/){1,}(low_priority([\s]{1,}|/\*[^\w]*\/){1,}|ignore([\s]{1,}|/\*[^\w]*\/){1,})?`?[\w]*_.*set|delete([\s]{1,}|/\*(.*)\*/){1,}((low_priority|quick|ignore)([\s]{1,}|/\*(.*)\*/){1,}){0,}from|benchmark([\s]{1,}|/\*(.*)\*/){0,}\(([\s]{1,}|/\*(.*)\*/){0,}[0-9]{1,}){1,}#i';

		foreach ($hashes as $hash)
		{
			$input = $this->input->$hash;

			$ref = new ReflectionProperty($input, 'data');
			$ref->setAccessible(true);
			$allVars = $ref->getValue($input);

			if (empty($allVars))
			{
				continue;
			}

			if ($this->recursiveRegExMatch($regex, $allVars, false, function ($v) {
				// Empty values are processed as-is
				if (empty($v))
				{
					return $v;
				}

				// Non-SQL values are processed as-is
				if (preg_match('#^[\p{L}\d,\s]+$#iu', $v) >= 1)
				{
					return $v;
				}

				// Strip SQL comments (inline OR rest of the line) and convert them to the semantically equivalent space character
				$regex  = '@(--|#).+\n@iu';
				$regex2 = '#\/\*(.*?)\*\/#iu';
				$v      = preg_replace($regex2, ' ', $v);
				$v      = preg_replace($regex, ' ', $v);
				// Convert stray newlines to the semantically equivalent space character
				$v = str_replace(["\n", "\r"], ' ', $v);

				return $v;
			}))
			{
				$extraInfo = "Hash      : $hash\n";
				$extraInfo .= "Variables :\n";
				$extraInfo .= print_r($allVars, true);
				$extraInfo .= "\n";
				$this->exceptionsHandler->blockRequest('sqlishield', null, $extraInfo);
			}
		}
	}
}
