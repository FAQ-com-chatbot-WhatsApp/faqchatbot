<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Akeeba\Plugin\System\AdminTools\Utility\Cache;

class WAFDenyList extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return true;
	}

	public function onAfterApiRoute(): void
	{
		$this->onAfterRoute();
	}

	/**
	 * Filters visitor access using WAF blacklist rules
	 */
	public function onAfterRoute(): void
	{
		// Get the option, view, controller and task for the request
		$method     = strtoupper($this->input->server->getCmd('REQUEST_METHOD', 'GET'));
		$controller = $this->input->getCmd('controller', '');
		$view       = $this->input->getCmd('view', '');
		$task       = $this->input->getCmd('task', '');
		$option     = $this->input->getCmd('option', '');
		$client     = strtolower($this->app->getName());

		if (strpos($task, '.') !== false)
		{
			// NB! The controller.task convention always overrides an explicit controller query string parameter.
			[$controller, $task] = explode('.', $task);
		}

		// Load all rules which match the current HTTP method, application, component, controller, view and task.
		$rules = array_filter(Cache::getCache('wafblacklists'),
			function ($rule) use ($method, $client, $controller, $view, $task, $option) {
				$controllerFilter = '';

				if (strpos($rule['task'], '.') !== false)
				{
					[$controllerFilter, $rule['task']] = explode('.', $rule['task']);
				}

				if (!in_array($rule['application'], ['both', '', $client]))
				{
					return false;
				}

				if (!in_array($rule['verb'], ['', $method]))
				{
					return false;
				}

				if (!in_array($rule['option'], ['', '*', $option]))
				{
					return false;
				}

				if (!in_array($controllerFilter, ['', $controller]))
				{
					return false;
				}

				if (!in_array($rule['view'], ['', '*', empty($controllerFilter) ? $controller : '', $view]))
				{
					return false;
				}

				if (!in_array($rule['task'], ['', '*', $task]))
				{
					return false;
				}

				return true;
			});

		// Don't block anything if there are no matching rules.
		if (empty($rules))
		{
			return;
		}

		// Ok, let's analyze all the matching rules
		$block = array_reduce($rules, function ($alreadyBlocked, $rule) {
			/**
			 * If a previous rule already blocks this request return true immediately.
			 *
			 * If the query of this rule is empty then we're supposed to block the access to this component, controller,
			 * view and task regardless of any request parameters. We return an immediate true value in this case.
			 */
			if ($alreadyBlocked || empty($rule['query']))
			{
				return true;
			}

			/**
			 * I need to address each source individually. The main input object pulls from $_REQUEST which might draw data
			 * from cookies. Moreover, $_REQUEST can "shade" content. If the input order is EGPCS the cookies shadow the
			 * POST parameters which shadow the GET parameters. Depending on the component, it might be using data from a
			 * specific source, e.g. GET. In this case a wiley attacker will send a POST request with an innocuous POST
			 * parameter and a malicious GET parameter. If we used $_REQUEST we'd see the innocuous content because of the
			 * "shading" but the component would use the malicious content and get compromised.
			 */
			foreach (['get', 'post'] as $inputSource)
			{
				$inputObject = $this->input->{$inputSource};

				foreach ($inputObject->getArray() as $key => $value)
				{
					if ($this->isBlockedByRule((object) $rule, $key, $value))
					{
						return true;
					}
				}
			}

			return false;
		}, false);

		if (!$block)
		{
			return;
		}

		$extraInfo = '';

		// If the rule matched any variable, let's print the variables that caused the block, so we can inspect later
		if (isset($inputSource) && isset($inputObject))
		{
			// PLEASE NOTE! If POST data is passed, but the GET array is empty, Input will use the whole $_REQUEST
			// array, so $inputSource will be GET even if we truly had a POST request. However this is an edge case
			$extraInfo = "Hash      : " . strtoupper($inputSource) . "\n";
			$extraInfo .= "Variables :\n";
			$extraInfo .= print_r($inputObject->getArray(), true);
			$extraInfo .= "\n";
		}

		$this->exceptionsHandler->blockRequest('wafblacklist', null, $extraInfo);
	}

	private function isBlockedByRule(object $rule, string $key, $value, string $prefix = ''): bool
	{
		// Handle array values
		if (is_array($value))
		{
			foreach ($value as $subKey => $subValue)
			{
				$newPrefix = empty($prefix) ? $key : sprintf("%s[%s]", $prefix, $key);

				if ($this->isBlockedByRule($rule, $subKey, $subValue, $newPrefix))
				{
					return true;
				}
			}

			return false;
		}

		$key       = empty($prefix) ? $key : sprintf("%s[%s]", $prefix, $key);
		$ruleQuery = $rule->query;

		switch (strtoupper($rule->query_type))
		{
			// Partial match
			case 'P':
				$found = stripos($key, $ruleQuery) !== false;
				break;

			// RegEx match
			case 'R':
				$regex  = $ruleQuery;
				$negate = false;

				if (substr($regex, 0, 1) == '!')
				{
					$negate = true;
					$regex  = substr($regex, 1);
				}

				$found = @preg_match($regex, $key) > 0;
				$found = $negate ? !$found : $found;
				break;

			// Exact match
			default:
				// Empty rule query: always matches. Empty key: never matches. Else: exact match of the key.
				$found = empty($ruleQuery) || (!empty($key) && ($key == $ruleQuery));
				break;
		}

		if (!$found)
		{
			return false;
		}

		// Empty content rule => always block, no matter what
		if (!$rule->query_content)
		{
			return true;
		}

		// The content rule is non-empty, therefore it's a regular expression.
		$negate = false;
		$regex  = $rule->query_content;

		if (substr($regex, 0, 1) == '!')
		{
			$negate = true;
			$regex  = substr($regex, 1);
		}

		$isFiltered = (@preg_match($regex, $value) ?: 0) >= 1;

		if ($negate)
		{
			$isFiltered = !$isFiltered;
		}

		return $isFiltered;
	}
}
