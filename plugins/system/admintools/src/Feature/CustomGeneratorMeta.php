<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

class CustomGeneratorMeta extends Base
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

		return ($this->wafParams->getValue('custgenerator', 0) != 0);
	}

	/**
	 * Cloak the generator meta tag in feeds. This method deals with the hardcoded Joomla! reference. Yeah, I know,
	 * hardcoded?
	 */
	public function onAfterRender(): void
	{
		if ($this->input->getCmd('format', 'html') != 'feed')
		{
			return;
		}

		$generator = $this->wafParams->getValue('generator', '');

		if (empty($generator))
		{
			$generator = 'MYOB';
		}

		$buffer = $this->app->getBody();

		$buffer = preg_replace('#<generator uri(.*)/generator>#iU', '<generator>' . $generator . '</generator>', $buffer);

		$this->app->setBody($buffer);
	}

	/**
	 * Override the generator
	 */
	public function onAfterDispatch(): void
	{
		$generator = $this->wafParams->getValue('generator', 'MYOB');

		// Mind Your Own Business
		if (empty($generator))
		{
			$generator = 'MYOB';
		}

		$document = $this->app->getDocument();

		if (!method_exists($document, 'setGenerator'))
		{
			return;
		}

		$document->setGenerator($generator);
	}
}
