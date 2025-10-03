<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand\ServerConfiguration;

use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureEnv;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureIO;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\PrintFormattedArray;
use Akeeba\Component\AdminTools\Administrator\Model\ServerconfigmakerModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ServerConfigurationSet extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;

	/** @var string Which engine are we working on? */
	protected $server_engine = '';

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   7.5.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureEnv();
		$this->configureSymfonyIO($input, $output);

		$key      = (string) $this->cliInput->getOption('key');
		$setValue = $this->cliInput->getOption('value', null);

		// Special handling for array-ish values
		$addValue    = $this->cliInput->getOption('add') ?? null;
		$removeValue = $this->cliInput->getOption('remove') ?? null;
		$doEmpty     = $this->cliInput->getOption('empty') ?? false;

		// Sanity check: one of --value, --add, --remove and --empty must be defined
		if (is_null($setValue) && empty($addValue) && !$removeValue && !$doEmpty)
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_ERR_NO_ACTION'));

			return 1;
		}

		// Sanity check: --value, --add, --remove and --empty are mutually exclusive
		if (!(!is_null($setValue) xor !empty($addValue) xor $removeValue xor $doEmpty))
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_ERR_MUTUALLY_EXCLUSIVE'));

			return 2;
		}

		/** @var ServerconfigmakerModel $model */
		$model = $this->getMVCFactory()->createModel(ucfirst($this->server_engine), 'Administrator');
		$isSubFormKey = in_array($key, $model->getSubformConfigKeys());
		$isArrayKey   = in_array($key, $model->getArrayConfigKeys());

		// Get the corresponding Configure WAF form field
		$field = $model->getForm()->getField($key);

		// Fail if there is no such field (meaning there is no such configuration key)
		if (empty($key) || empty($field))
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_SERVERCONFIG_ERR_CANNOT_FIND', $key));

			return 20;
		}

		$valueForValidation = $setValue;
		$valueForSave       = $setValue;

		// If we have to edit the list of values, we have to do some extra steps
		if ($isSubFormKey || $isArrayKey)
		{
			$configuration = $model->loadConfiguration();
			$valueForSave  = $configuration[$key];

			// Do we have to remove? Find and remove the matching item
			if ($removeValue)
			{
				if (in_array($removeValue, $valueForSave))
				{
					unset($valueForSave[array_search($removeValue, $valueForSave)]);
				}
				else
				{
					$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_LBL_UNCHANGED', $key));

					return 0;
				}
			}
			// Do we have to add? That's easy, then
			elseif ($addValue)
			{
				$valueForSave[] = $addValue;
			}
			// Do we have to nuke it? Even simpler
			else
			{
				$valueForSave = [];
			}

			$valueForValidation = $valueForSave;

			// If the value is inside a subform, massage it so it will be compatible with Joomla Form
			if ($isSubFormKey)
			{
				$valueForValidation = $model->convertDatabaseDataToFormData($valueForSave);
			}
		}

		// Validate the user-provided value
		$validationResult = $field->validate($valueForValidation);

		if ($validationResult instanceof \Throwable)
		{
			$this->ioStyle->error($validationResult->getMessage());

			return 30;
		}

		// Save the new value
		$configuration       = $model->loadConfiguration();
		$configuration[$key] = $valueForSave;
		$model->saveConfiguration($configuration);

		$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_LBL_SETTING', $key));

		return 0;
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   7.5.0
	 */
	protected function configure(): void
	{
		$this->addOption('key', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_OPT_KEY'));
		$this->addOption('value', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_OPT_VALUE'));
		$this->addOption('add', null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_OPT_ADD'));
		$this->addOption('remove', null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_OPT_REMOVE'));
		$this->addOption('empty', null, InputOption::VALUE_NONE, Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_OPT_EMPTY'));

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_SERVERCONFIGSET_HELP'));
	}
}