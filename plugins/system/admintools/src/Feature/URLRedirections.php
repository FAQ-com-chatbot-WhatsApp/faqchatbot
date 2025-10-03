<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Uri\Uri;
use Akeeba\Plugin\System\AdminTools\Utility\Cache;

class URLRedirections extends Base
{
	private static $siteTemplates = null;

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

		return ($this->wafParams->getValue('urlredirection', 1) == 1);
	}

	/**
	 * Performs custom redirections defined in the back-end of the component.
	 */
	public function onAfterInitialise(): void
	{
		$basePath     = Uri::base(true);
		$relativePath = Uri::getInstance()->toString(['path', 'query']);
		$myUrlParams = [];

		if (strpos($relativePath, '?') !== false)
		{
			[$relativePath, $myQuery] = explode('?', $relativePath, 2);
			parse_str($myQuery, $myUrlParams);
		}

		if (!empty($basePath) && ($basePath != '/') && (strpos($relativePath, $basePath) === 0))
		{
			$relativePath = substr($relativePath, strlen($basePath));
		}

		$relativePath = trim($relativePath, '/');

		$redirections = array_filter(Cache::getCache('redirects'),
			function ($redirect) use ($relativePath, $myUrlParams) {
				$check = $redirect['dest'];

				// We can't have an empty path to redirect.
				if (empty($check))
				{
					return false;
				}

				// Remove any fragments from the URL; PHP does NOT see the URL fragments, they are client-side only.
				if (strpos($check, '#') !== false)
				{
					[$check, $junk] = explode('#', $check, 2);
				}

				// If the URL looks like an absolute URL we will only keep the path, thank you.
				if (strpos($check, '://') !== false)
				{
					$check = (new Uri($check))->toString(['path', 'query']);
				}

				$check = trim($check, '/');

				if (strpos($check, '?') === false)
				{
					$checkPath = $check;
					$checkQueryString = '';
				}
				else
				{
					[$checkPath, $checkQueryString] = explode('?', $check, 2);
				}

				if ($checkPath !== $relativePath)
				{
					return false;
				}

				// Redirection target without query string parameters. Check the two paths.
				if (empty($checkQueryString))
				{
					return true;
				}

				// Redirection target WITH query string parameters but current URL doesn't have any. Immediate false.
				if (empty($myUrlParams))
				{
					return false;
				}

				// Get the query string parameters from the two paths.
				parse_str($checkQueryString, $checkQuery);

				// Make sure all $checkQuery parameters exist in $currentQuery and they match.
				foreach ($checkQuery as $k => $v)
				{
					if (!isset($myUrlParams[$k]))
					{
						return false;
					}

					if ($myUrlParams[$k] != $v)
					{
						return false;
					}
				}

				return true;
			});

		// No redirections match?
		if (empty($redirections))
		{
			return;
		}

		/**
		 * If only one redirection matches then the array contains the only redirection we need to consider. If more
		 * than one redirections match the array is sorted by ordering ascending. Therefore the first item is the one I
		 * need to use to perform the redirection.
		 */
		[$newURL, $check, $keepQueryParams] = array_values(array_shift($redirections));

		$new  = Uri::getInstance($newURL);
		$host = $new->getHost();

		if (empty($host))
		{
			$base = Uri::getInstance(Uri::base());
			$new->setHost($base->getHost());
			$new->setPort($base->getPort());
			$new->setScheme($base->getScheme());
			$new->setPath($base->getPath() . $new->getPath());
			$new->setFragment($new->getFragment());
		}

		// Keep URL Params == 1 (override all)
		if ($keepQueryParams == 1)
		{
			foreach ($myUrlParams as $k => $v)
			{
				$new->setVar($k, $v);
			}
		}
		// Keep URL Params == 2 (add only)
		elseif ($keepQueryParams == 2)
		{
			$newUrlParams = $new->getQuery(true);

			foreach ($myUrlParams as $k => $v)
			{
				if (!isset($newUrlParams[$k]))
				{
					$new->setVar($k, $v);
				}
			}
		}

		$this->app->redirect($new->toString(), 301);
	}
}
