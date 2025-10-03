<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Feature;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\ParameterType;
use Joomla\Plugin\System\Webauthn\CredentialRepository;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use RuntimeException;
use Throwable;

class PWAuthOnWebAuthn extends Base
{
	/**
	 * Is this feature enabled?
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		if (!($this->app instanceof CMSApplication))
		{
			return false;
		}

		// This feature only applies to HTTPS sites (WebAuthn is not available on plain old HTTP).
		if (!Uri::getInstance()->isSsl())
		{
			return false;
		}

		return $this->wafParams->getValue('nopwonwebauthn', 0) != 0;
	}

	public function onUserLogin($response, $options = []): bool
	{
		$user = $this->getUserObject($response, $options);

		// If this is not a password login do nothing
		if (strtolower(($response['type'] ?? 'joomla')) != 'joomla')
		{
			return true;
		}

		// If this is not a blocked login do nothing
		if (!$this->isBlockedLogin($user))
		{
			return true;
		}

		// If the user does not have WebAuthn enabled do nothing
		if (!$this->hasWebAuthn($user))
		{
			return true;
		}

		// Logout the user and close the session before throwing the error (otherwise the user won't be logged out).
		$this->app->logout($user->id, []);
		$this->app->getSession()->close();

		// Throw error
		throw new RuntimeException(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
	}

	public function onContentPrepareForm(Form $form, $data): void
	{
		// This feature only applies to HTTPS sites.
		if (!Uri::getInstance()->isSsl())
		{
			return;
		}

		// This feature only applies if the WebAuthn plugin is enabled
		if (!PluginHelper::isEnabled('system', 'webauthn'))
		{
			return;
		}

		$name         = $form->getName();
		$allowedForms = [
			'com_users.user', 'com_users.profile', 'com_users.registration',
		];

		if (!\in_array($name, $allowedForms))
		{
			return;
		}

		// If we have no $data, it means that Joomla is simply loading the user profile without loading the data inside it
		// This means that we can't perform some checks on the user, simply attach our form
		if (!$data)
		{
			$method = strtoupper($this->input->getMethod());
			$option = $this->input->get->getCmd('option');
			$id     = $this->input->get->getInt('id');

			if ($method !== 'POST' || $option !== 'com_users' || empty ($id))
			{
				return;
			}

			$data['id'] = $id;
		}

		// Get the user object
		$user   = $this->getUserFromFormData($data);
		$mySelf = $this->app->getIdentity();

		// Make sure the loaded user is the correct one
		if (\is_null($user))
		{
			return;
		}

		// Make sure I am either editing myself OR I am a Super User
		if (($mySelf->id != $user->id) && !$mySelf->authorise('core.edit', 'com_users'))
		{
			return;
		}

		if (!$this->needsUserProfileFields($user->id))
		{
			return;
		}

		// Add the fields to the form.
		Form::addFormPath(JPATH_PLUGINS . '/system/admintools/forms');
		$form->loadFile('pwloginonwebauthn', false);
	}

	public function onContentPrepareData($context, $data): void
	{
		if (
			!in_array($context, ['com_users.profile', 'com_users.user', 'com_users.registration'])
			|| !is_object($data) || !isset($data->id) || (($data->id ?: 0) <= 0)
		)
		{
			return;
		}

		try
		{
			$db     = $this->db;
			$key    = 'com_admintools.nopwonwebauthn';
			$userId = $data->id ?: 0;
			$query  = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select($db->quoteName('profile_value'))
				->from($db->quoteName('#__user_profiles'))
				->where($db->quoteName('user_id') . ' = ' . $db->quote($userId))
				->where($db->quoteName('profile_key') . ' = ' . $db->quote($key));
			$result = $db->setQuery($query)->loadResult() ?? 1;

			// Merge the profile data.
			$data->pwloginonwebauthn = [
				'nopwonwebauthn' => $result,
			];
		}
		catch (Throwable $e)
		{
			// Ignore exception
		}
	}

	public function onUserAfterSave($data, $isNew, $result, $error): void
	{
		$userId = ArrayHelper::getValue($data, 'id', 0, 'int');

		if ($userId <= 0 || count($data['pwloginonwebauthn'] ?: []) == 0)
		{
			return;
		}

		try
		{
			$db    = $this->db;
			$key   = 'com_admintools.nopwonwebauthn';
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->delete($db->quoteName('#__user_profiles'))
				->where($db->quoteName('user_id') . ' = :user_id')
				->where($db->quoteName('profile_key') . ' = :key')
				->bind(':user_id', $userId)
				->bind(':key', $key);
			$db->setQuery($query)->execute();

			$o = (object) [
				'user_id'       => $userId,
				'profile_key'   => 'com_admintools.nopwonwebauthn',
				'profile_value' => $data['pwloginonwebauthn']['nopwonwebauthn'],
				'order'         => 1,
			];
			$db->insertObject('#__user_profiles', $o);
		}
		catch (Exception $e)
		{
			// Ignore
		}
	}

	public function onUserAfterDelete($user, $success, $msg): void
	{
		if (!$success)
		{
			return;
		}

		$userId = ArrayHelper::getValue($user, 'id', 0, 'int');

		if ($userId)
		{
			return;
		}

		try
		{
			$db    = $this->db;
			$key   = 'com_admintools.nopwonwebauthn';
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->delete($db->quoteName('#__user_profiles'))
				->where($db->quoteName('user_id') . ' = :user_id')
				->where($db->quoteName('profile_key') . ' = :key')
				->bind(':user_id', $userId)
				->bind(':key', $key);
			$db->setQuery($query)->execute();
		}
		catch (Throwable $e)
		{
			// Ignore
		}
	}


	private function getUserObject($user, $options = [])
	{
		$instance = new User();

		if ($id = intval(UserHelper::getUserId($user['username'])))
		{
			$instance->load($id);

			return $instance;
		}

		$config           = ComponentHelper::getParams('com_users');
		$defaultUserGroup = $config->get('new_usertype', 2);

		$instance->id       = 0;
		$instance->name     = $user['fullname'];
		$instance->username = $user['username'];
		$instance->email    = $user['email']; // Result should contain an email (check)
		$instance->usertype = 'deprecated';
		$instance->groups   = [$defaultUserGroup];

		return $instance;
	}

	private function isBlockedLogin(User $user): bool
	{
		$isBackend = $user->authorise('core.login.admin', 0);
		$setting   = $this->wafParams->getValue('nopwonwebauthn', 0);

		// Allowed for all?
		if ($setting == 0)
		{
			return false;
		}

		// Disabled for all backend or frontend users?
		if (($isBackend && in_array($setting, [8, 9, 12])) || (!$isBackend && ($setting == 12)))
		{
			return true;
		}

		// In any other case we need to check the user's preference.
		return $this->getUserParam($user->id, 'com_admintools.nopwonwebauthn') == 0;
	}

	private function needsUserProfileFields(int $id): bool
	{
		$user      = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id);
		$isBackend = $user->authorise('core.login.admin', 0);
		$setting   = $this->wafParams->getValue('nopwonwebauthn', 0);

		// Allowed for all?
		if ($setting == 0)
		{
			return false;
		}

		// Disabled for all backend or frontend users?
		if (($isBackend && in_array($setting, [8, 9, 12])) || (!$isBackend && ($setting == 12)))
		{
			return false;
		}

		// In any other case we need to check the user's preference.
		return true;
	}

	private function getUserParam(?int $id, string $key): int
	{
		$db    = $this->db;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('profile_value'))
			->from($db->qn('#__user_profiles'))
			->where($db->qn('profile_key') . ' = :profileKey')
			->where($db->qn('user_id') . ' = :userId');

		$query->bind(':profileKey', $key, ParameterType::STRING);
		$query->bind(':userId', $id, ParameterType::INTEGER);

		return (int) ($db->setQuery($query)->loadResult() ?? 0);
	}

	private function hasWebAuthn(User $user): bool
	{
		// If the WebAuthn plugin is not enabled the user can't log into the site with WebAuthn (DUH!)
		if (!PluginHelper::isEnabled('system', 'webauthn'))
		{
			return false;
		}

		if (!class_exists(CredentialRepository::class))
		{
			return false;
		}

		try
		{
			$webAuthnCredentials = (new CredentialRepository($this->db))->getAll($user->id) ?: [];
		}
		catch (Throwable $e)
		{
			return false;
		}

		return count($webAuthnCredentials) > 0;
	}

	private function getUserFromFormData($data): ?User
	{
		$id = null;

		if (\is_array($data))
		{
			$id = isset($data['id']) ? $data['id'] : null;
		}
		elseif (\is_object($data) && ($data instanceof Registry))
		{
			$id = $data->get('id');
		}
		elseif (\is_object($data))
		{
			$id = isset($data->id) ? $data->id : null;
		}

		$user = empty($id) ? $this->app->getIdentity() : Factory::getContainer()
			->get(UserFactoryInterface::class)
			->loadUserById($id);

		// Make sure the loaded user is the correct one
		if ($user->id != $id)
		{
			return null;
		}

		return $user;
	}

}