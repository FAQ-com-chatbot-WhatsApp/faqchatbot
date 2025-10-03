<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Plugin\System\AdminTools\Feature
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') or die;

/**
 * Block Suspicious Core Parameters
 *
 * Checks for suspicious core Joomla! parameters.
 *
 * Joomla! has a number of “reserved” query string parameters – such as `option`, `view`, `task`, etc – which can only
 * be used for a specific purpose, therefore have a well-defined expected format. This feature checks whether the values
 * of these parameters don't follow the expected format and block the request. This discrepancy is usually caused by
 * potential attackers scoping your site for vulnerabilities such as Reflected Cross-Site Scripting (Reflected XSS), but
 * it may also happen due to coding mistakes in situations the developer could not have anticipated.
 *
 * @package     Akeeba\Plugin\System\AdminTools\Feature
 *
 * @since       7.4.5
 */
class SuspiciousCoreParams extends Base
{
	private ?string $badValue = null;

	/**
	 * Is this feature enabled?
	 *
	 * @return  bool
	 * @since   7.4.5
	 */
	public function isEnabled()
	{
		if ($this->isAdminAccessAttempt())
		{
			return false;
		}

		if (!$this->app->isClient('site'))
		{
			return false;
		}

		return ($this->wafParams->getValue('suspicious_params', 1) == 1);
	}

	/**
	 * The onAfterInitialise method is called after the CMS application has been initialised, before SEF routing.
	 *
	 * @return  void
	 * @since   7.4.5
	 */
	public function onAfterInitialise(): void
	{
		$this->checkForSuspiciousParams();
	}

	/**
	 * The onAfterInitialise method is called after the CMS application has completed SEF routing.
	 *
	 * @return  void
	 * @since   7.4.5
	 */
	public function onAfterRoute(): void
	{
		$this->checkForSuspiciousParams();
	}

	/**
	 * Checks for suspicious parameters and blocks requests if necessary.
	 *
	 * This method checks a list of predefined parameter names and their corresponding filter types.
	 * If a parameter does not pass through the filter, the method blocks the request and logs an exception.
	 *
	 * @return  void
	 * @since   7.4.5
	 */
	public function checkForSuspiciousParams(): void
	{
		$paramNames = [
			'option'        => 'cmd',
			'view'          => 'cmd',
			'task'          => 'cmd',
			'controller'    => 'cmd',
			'layout'        => 'string',
			'tmpl'          => 'cmd',
			'template'      => 'cmd',
			'format'        => 'cmd',
			'lang'          => 'string',
			'templateStyle' => 'uint',
		];

		$paramNames = array_filter(
			$paramNames,
			fn($x) => !in_array($x, $this->exceptions),
			ARRAY_FILTER_USE_KEY
		);

		// The feed format needs a type
		if ($this->input->getCmd('format') === 'feed')
		{
			$paramNames['type'] = 'cmd';
		}

		// The tp parameter only makes sense when you enable module position debug in the templates component
		if (ComponentHelper::getParams('com_templates')->get('template_positions_display'))
		{
			$paramNames['tp'] = 'int';
		}

		foreach (
			$paramNames as $paramName => $filter
		)
		{
			if ($this->isSusParam($paramName, $filter))
			{
				$this->exceptionsHandler->blockRequest(
					'susparam',
					'',
					sprintf('Contents of %s do not pass through the cmd filter', $paramName),
					sprintf('%s=%s', $paramName, htmlentities($this->badValue))
				);
			}
		}
	}

	/**
	 * Check if the given parameter is considered suspicious.
	 *
	 * @param   string  $paramName  The name of the parameter to check.
	 * @param   string  $filter     The filter to apply to the parameter value. Default is 'cmd'.
	 *
	 * @return  bool Returns true if the parameter is suspicious, false otherwise.
	 * @since   7.4.5
	 */
	private function isSusParam(string $paramName, string $filter = 'cmd'): bool
	{
		foreach (['request', 'get', 'post'] as $method)
		{
			$rawValue = $this->input->$method->get($paramName, null, 'raw');
			$value    = $this->input->$method->get($paramName, null, $filter);

			// Special exception: Itemid can be empty
			if ($paramName === 'Itemid' && empty($rawValue))
			{
				continue;
			}

			// NULL values means the variable is unset; no need to check it
			if ($rawValue === null || $value === null)
			{
				continue;
			}

			// This is an array, but core params never expect an array. Oh, naughty boy!
			if (is_array($rawValue))
			{
				$this->badValue = '(array)';

				return true;
			}

			// I do not know of any way this can _possibly_ be true, but here we are.
			if (!is_string($rawValue))
			{
				$this->badValue = '(not a string)';

				return true;
			}

			// If the filtered and raw values don't match even after type and case coercion, they're suspicious.
			if ($rawValue != $value && strtolower($rawValue) != strtolower($value))
			{
				$this->badValue = $rawValue;

				return true;
			}

			// But wait, there's more! Let's check the feed type, shall we?
			if ($paramName === 'type' && !in_array($value, ['', 'atom', 'rss']))
			{
				$this->badValue = $rawValue;

				return true;
			}
		}

		return false;
	}
}