<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') || die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

class DisablePwdReset extends Base
{
	public function isEnabled()
	{
		if ($this->wafParams->getValue('disablepwdreset', 0) == 0)
		{
			return false;
		}

		if (!$this->wafParams->getValue('disablepwdreset_groups', []))
		{
			return false;
		}

		return true;
	}

	/**
	 * Intercept the creation of the form for requesting a reset. Since Joomla doesn't have a proper event, we have to
	 * inspect the request and double check if we really have to block the request or not
	 *
	 * @param Form  $form
	 * @param array $data
	 *
	 * @return void
	 */
	public function onContentPrepareForm(Form $form, $data): void
	{
		if ($form->getName() != 'com_users.reset_request')
		{
			return;
		}

		$jform = $this->input->get('jform', [], 'string');

		if (!isset($jform['email']))
		{
			return;
		}

		$db    = $this->db;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
					->select($db->qn('id'))
					->from($db->qn('#__users'))
					->where($db->qn('email').' = :email')
					->bind(':email', $jform['email']);

		$userid = $db->setQuery($query)->loadResult();

		// Can't find any user with that email? Nothing to do
		if (!$userid)
		{
			return;
		}

		$juser = new User($userid);

		// This should never happen, but better be safe than sorry
		if ($juser->guest)
		{
			return;
		}

		$disabled_groups = $this->wafParams->getValue('disablepwdreset_groups', []);
		$user_groups     = $juser->groups;

		foreach ($user_groups as $user_group)
		{
			if (in_array($user_group, $disabled_groups))
			{
				throw new \RuntimeException(Text::_('PLG_ADMINTOOLS_MSG_DISABLEPWDRESET'), 403);
			}
		}
	}
}