<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

#[\AllowDynamicProperties]
class ExportimportModel extends BaseDatabaseModel
{
	public function exportData()
	{
		$return     = [];
		$exportData = $this->getState('exportdata') ?: [];
		$db         = $this->getDatabase();

		if (!is_array($exportData) || empty($exportData))
		{
			$exportData = Factory::getApplication()->input->get('exportdata', [], 'array');
		}

		if ($exportData['wafconfig'] ?? false)
		{
			/** @var ConfigurewafModel $configModel */
			$configModel = $this->getMVCFactory()->createModel('Configurewaf', 'Administrator');
			$config      = $configModel->getConfig();

			// Let's unset two factor auth stuff
			unset($config['twofactorauth']);
			unset($config['twofactorauth_secret']);
			unset($config['twofactorauth_panic']);

			$return['wafconfig'] = $config;
		}

		// WAF requests deny list
		if ($exportData['wafblacklist'] ?? false)
		{
			$query                  = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from('#__admintools_wafblacklists');
			$return['wafblacklist'] = $db->setQuery($query)->loadObjectList();
		}

		// Exceptions from WAF
		if ($exportData['wafexceptions'] ?? false)
		{
			$query                   = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from('#__admintools_wafexceptions');
			$return['wafexceptions'] = $db->setQuery($query)->loadObjectList();
		}

		// IP Allow list
		if ($exportData['ipallow'] ?? false)
		{
			$query                 = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from('#__admintools_ipallow');
			$return['ipallow'] = $db->setQuery($query)->loadObjectList();
		}

		// IP disallow list
		if ($exportData['ipblacklist'] ?? false)
		{
			$query                 = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from('#__admintools_ipblock');
			$return['ipblacklist'] = $db->setQuery($query)->loadObjectList();
		}

		// Admin IP allow list
		if ($exportData['ipwhitelist'] ?? false)
		{
			$query                 = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from('#__admintools_adminiplist');
			$return['ipwhitelist'] = $db->setQuery($query)->loadObjectList();
		}

		// Anti-spam bad words
		if ($exportData['badwords'] ?? false)
		{
			$query              = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from('#__admintools_badwords');
			$return['badwords'] = $db->setQuery($query)->loadObjectList();
		}

		// User Agents to Block
		if ($exportData['useragents'] ?? false)
		{
			$userAgents = [];

			foreach (['Htaccessmaker', 'Nginxconfmaker', 'Webconfigmaker'] as $confMaker)
			{
				/** @var ServerconfigmakerModel $makerModel */
				$makerModel = $this->getMVCFactory()->createModel($confMaker, 'Administrator', ['ignore_request' => true]);

				if ($makerModel->isSupported() === 0)
				{
					continue;
				}

				$config     = (object) $makerModel->loadConfiguration(false);
				$userAgents = array_merge($userAgents, $config->hoggeragents ?: []);
			}

			if (!empty($userAgents))
			{
				$return['useragents'] = $userAgents;
			}
		}

        // Server Configuration
        if ($exportData['serverconfig'] ?? false)
        {
            foreach (['Htaccessmaker', 'Nginxconfmaker', 'Webconfigmaker'] as $confMaker)
            {
                /** @var ServerconfigmakerModel $makerModel */
                $makerModel = $this->getMVCFactory()->createModel($confMaker, 'Administrator', ['ignore_request' => true]);

                $return['serverconfig'][$confMaker] = (object) $makerModel->loadConfiguration(false);
            }
        }

		return $return;
	}

	/**
	 * Imports passed data inside Admin Tools
	 *
	 * @param   string  $new_settings  String in JSON format containing the new settings
	 * @param   bool    $withDefaults  Should I try to load defaults?
	 *
	 * @throws Exception
	 */
	public function importData($new_settings, bool $withDefaults = true)
	{
		/** @var ConfigurewafModel $configModel */
		$configModel = $this->getMVCFactory()->createModel('Configurewaf', 'Administrator');
		$db          = $this->getDatabase();
		$errors      = [];

		$data = json_decode($new_settings, true);

		if (!$data)
		{
			throw new Exception(Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_READING_FILE'));
		}

		try
		{
			$db->transactionStart();
		}
		catch (Exception $e)
		{
		}

		// Everything seems ok, let's start importing data
		if (isset($data['wafconfig']))
		{
			$configModel->saveConfig($data['wafconfig']);
		}

		if (isset($data['wafblacklist']))
		{
			try
			{
				$this->batchImport('#__admintools_wafblacklists', [
					'option',
					'view',
					'task',
					'query',
					'query_type',
					'query_content',
					'verb',
				], $data['wafblacklist']);
			}
			catch (Exception $e)
			{
				$errors[] = Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_WAFBLACKLIST');
				$errors[] = $e->getMessage();
			}
		}

		if (isset($data['wafexceptions']))
		{
			try
			{
				$this->batchImport('#__admintools_wafexceptions', [
					'option',
					'view',
					'query',
				], $data['wafexceptions']);
			}
			catch (Exception $e)
			{
				$errors[] = Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_WAFEXCEPTIONS');
			}
		}

		if (isset($data['ipallow']))
		{
			try
			{
				$this->batchImport('#__admintools_ipallow', [
					'ip',
					'description',
				], $data['ipallow']);
			}
			catch (Exception $e)
			{
				$errors[] = Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_IPALLOW');
			}
		}

		if (isset($data['ipblacklist']))
		{
			try
			{
				$this->batchImport('#__admintools_ipblock', [
					'ip',
					'description',
				], $data['ipblacklist']);
			}
			catch (Exception $e)
			{
				$errors[] = Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_BLACKLIST');
			}
		}

		if (isset($data['ipwhitelist']))
		{
			try
			{
				$this->batchImport('#__admintools_adminiplist', [
					'ip',
					'description',
				], $data['ipwhitelist']);
			}
			catch (Exception $e)
			{
				$errors[] = Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_WHITELIST');
			}
		}

		if (isset($data['badwords']))
		{
			try
			{
				$this->batchImport('#__admintools_badwords', [
					'word',
				], $data['badwords']);
			}
			catch (Exception $e)
			{
				$errors[] = Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_BADWORDS');
			}
		}

		try
		{
			$db->transactionCommit();
		}
		catch (Exception $e)
		{
		}

		if (isset($data['useragents']))
		{
			foreach (['Htaccessmaker', 'Nginxconfmaker', 'Webconfigmaker'] as $confMaker)
			{
				/** @var ServerconfigmakerModel $makerModel */
				$makerModel = $this->getMVCFactory()->createModel($confMaker, 'Administrator', ['ignore_request' => true]);

				if ($makerModel->isSupported() === 0)
				{
					continue;
				}

				$config     = (object) $makerModel->loadConfiguration($withDefaults);
				$config->hoggeragents = $data['useragents'];
				$makerModel->saveConfiguration($config, $withDefaults);
			}
		}

        if (isset($data['serverconfig']))
        {
            foreach ($data['serverconfig'] as $confMaker => $values)
            {
                /** @var ServerconfigmakerModel $makerModel */
                $makerModel = $this->getMVCFactory()->createModel($confMaker, 'Administrator', ['ignore_request' => true]);

                // Do not die if we can't create a model
                if (!$makerModel)
                {
                    continue;
                }

                $makerModel->saveConfiguration($data['serverconfig'][$confMaker], $withDefaults);
            }
        }

		if ($errors)
		{
			throw new Exception(implode('<br/>', $errors));
		}
	}

	/**
	 * Handles settings data coming from a file upload
	 *
	 * @throws Exception
	 */
	public function importDataFromRequest()
	{
		$input = Factory::getApplication()->input->files;
		$file  = $input->get('importfile', null, 'file');

		// Sanity checks
		if (!$file)
		{
			throw new Exception(Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_NOFILE'));
		}

		$data = file_get_contents($file['tmp_name']);

		if ($data === false)
		{
			throw new Exception(Text::_('COM_ADMINTOOLS_EXPORTIMPORT_ERR_READING_FILE'));
		}

		$this->importData($data);
	}

	private function batchImport(string $table, array $columns, array $rows)
	{
		$db = $this->getDatabase();

		$db->truncateTable($table);

		$insert   = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->insert($db->qn($table))
			->columns(array_map([$db, 'quoteName'], $columns));
		$doInsert = false;

		foreach ($rows as $row)
		{
			$values = [];

			foreach ($columns as $column)
			{
				$values[] = $db->q($row[$column]);
			}

			$insert->values(implode(',', $values));
			$doInsert = true;

			if (strlen((string) $insert) >= 262144)
			{
				$db->setQuery($insert)->execute();
				$insert->clear('values');
				$doInsert = false;
			}
		}

		if ($doInsert)
		{
			$db->setQuery($insert)->execute();
		}
	}
}