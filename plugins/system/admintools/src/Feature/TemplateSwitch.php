<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\Filesystem\Folder;
use Joomla\Database\ParameterType;

class TemplateSwitch extends Base
{
	private static array $siteTemplates = [];

	/**
	 * Is this feature enabled?
	 *
	 * @return  bool
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

		if ($this->wafParams->getValue('template', 0) != 1)
		{
			return false;
		}

		try
		{
			self::$siteTemplates = Folder::folders(JPATH_SITE . '/templates');
		}
		catch (\Exception $e)
		{
			self::$siteTemplates = [];
		}

		return true;
	}

	/**
	 * Disable template switching in the URL
	 */
	public function onAfterInitialise(): void
	{
		$this->checkTemplate();

		$this->checkTemplateStyle();
	}

	/**
	 * Check if the template query string parameter value should be blocked or allowed.
	 *
	 * This is the functionality which was added back in version 2.0.0 in the olden, Joomla! 1.5 days. The premise still
	 * stands.
	 *
	 * @return  void
	 * @since   7.4.5
	 */
	private function checkTemplate(): void
	{
		$template          = $this->input->getCmd('template', null);
		$option            = $this->input->getCmd('option', '');
		$block             = true;
		$allowSiteTemplate = $this->wafParams->getValue('allowsitetemplate', 0);

		// No template in the URL? Nothing to do.
		if (empty($template))
		{
			return;
		}

		/**
		 * Existing site templates are always allowed for com_mailto and com_ajax.
		 *
		 * com_mailto includes the default template in the URL since Joomla 1.7.
		 *
		 * com_ajax supports arbitrary AJAX tasks in templates using the template=something switch.
		 */
		if (in_array($option, ['com_mailto', 'com_ajax']))
		{
			$allowSiteTemplate = true;
		}

		if ($allowSiteTemplate)
		{
			$block = !in_array($template, self::$siteTemplates);
		}

		if (!$block)
		{
			return;
		}

		$this->exceptionsHandler->blockRequest('template');
	}

	/**
	 * Check if the templateStyle query string parameter value should be blocked or allowed.
	 *
	 * This checks the templateStyle query string parameter which was added since Joomla! 4.
	 *
	 * @return  void
	 * @since   7.4.5
	 */
	private function checkTemplateStyle()
	{
		$templateStyle     = $this->input->getInt('templateStyle', null);
		$option            = $this->input->getCmd('option', '');
		$allowSiteTemplate = $this->wafParams->getValue('allowsitetemplate', 0);

		if (empty($templateStyle))
		{
			return;
		}

		/**
		 * Existing site templates are always allowed for com_mailto and com_ajax.
		 *
		 * The assumption is that since these components support the `template` parameter, eventually this may be
		 * deprecated and switched over to the new templateStyle parameter instead – especially likely for com_mailto,
		 * as it needs to know which template to use when rendering its output.
		 */
		if (in_array($option, ['com_mailto', 'com_ajax']))
		{
			$allowSiteTemplate = true;
		}

		if ($allowSiteTemplate && $templateStyle > 0 && $this->isValidTemplateStyle($templateStyle))
		{
			return;
		}

		$this->exceptionsHandler->blockRequest('template');
	}

	/**
	 * Check if the specified template style is valid.
	 *
	 * A valid template style is one which exists in the database AND points to an installed site template.
	 *
	 * @param   int  $templateStyle  The ID of the template style to check.
	 *
	 * @return  bool  True if the template style is valid, false otherwise.
	 * @since   7.4.5
	 */
	private function isValidTemplateStyle(int $templateStyle)
	{
		$db = $this->db;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('template'))
			->from($db->quoteName('#__template_styles'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $templateStyle, ParameterType::INTEGER);

		try
		{
			$template = $db->setQuery($query)->loadResult();
		}
		catch (\Exception $e)
		{
			return false;
		}

		// If the template style ID does not exist we cannot accept it.
		if (empty($template))
		{
			return false;
		}

		// If the template referenced in the template style does not exist, we cannot accept it.
		return in_array($template, self::$siteTemplates);
	}


}
