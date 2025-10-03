<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\CliRouting;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureEnv;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\ConfigureIO;
use Akeeba\Component\AdminTools\Administrator\CliCommand\MixIt\PrintFormattedArray;
use Akeeba\Component\AdminTools\Administrator\Model\ConfigurewafModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * admintools:waf:set
 *
 * Set the value of a WAF configuration option
 *
 */
class WafSet extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;
	use CliRouting;

	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:waf:set';

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
		$this->initCliRouting();
		$this->configureEnv();
		$this->configureSymfonyIO($input, $output);
		$this->getApplication()->getLanguage()->load();
		$this->getApplication()->getLanguage()->load('com_admintools', JPATH_ADMINISTRATOR);
		$this->getApplication()->getLanguage()->load('com_admintools', JPATH_SITE);

		$key      = (string) $this->cliInput->getOption('key');
		$setValue = $this->cliInput->getOption('value');
		$noValue  = empty($setValue) && $setValue !== 0 && $setValue !== "0";

		// Special handling for array-ish values
		$addValue    = $this->cliInput->getOption('add') ?? null;
		$description = $this->cliInput->getOption('description') ?? null;
		$removeValue = $this->cliInput->getOption('remove') ?? null;
		$doEmpty     = $this->cliInput->getOption('empty') ?? false;

		// Sanity check: one of --value, --add, --remove and --empty must be defined
		if ($noValue && empty($addValue) && !$removeValue && !$doEmpty)
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_WAFSET_ERR_NO_ACTION'));

			return 1;
		}

		// Sanity check: --value, --add, --remove and --empty are mutually exclusive
		if (!(!$noValue xor !empty($addValue) xor $removeValue xor $doEmpty))
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_WAFSET_ERR_MUTUALLY_EXCLUSIVE'));

			return 2;
		}

		// Sanity check: --description is only allowed with --add
		if (empty($addValue) && !empty($description))
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_WAFSET_ERR_DESCRIPTION_NOTALLOWED'));

			return 40;
		}

		/** @var ConfigurewafModel $model */
		$model        = $this->getMVCFactory()->createModel('Configurewaf', 'Administrator');
		$isSubFormKey = in_array($key, $model->getSubformConfigKeys());

		// Got a special key, double check if we're allowed to do so
		if ((!empty($addValue) || !empty($removeValue) || $doEmpty) && !$isSubFormKey)
		{
			$this->ioStyle->error(Text::_('COM_ADMINTOOLS_CLI_WAFSET_ERR_LIST_NOTALLOWED'));

			return 10;
		}

		// Get the corresponding Configure WAF form field
		$field = $model->getForm()->getField($key);

		// Fail if there is no such field (meaning there is no such configuration key)
		if (empty($key) || empty($field))
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_WAFSET_ERR_CANNOT_FIND', $key));

			return 20;
		}

		$valueForValidation = $setValue;
		$valueForSave       = $setValue;

		// If we have to edit the list of values, we have to do some extra steps
		if ($isSubFormKey)
		{
			$configuration = $model->getConfig();
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
					$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_WAFSET_LBL_UNCHANGED', $key));

					return 0;
				}
			}
			// Do we have to add? That's easy, then
			elseif ($addValue)
			{
				$valueForSave[] = [
					$addValue,
					$description ?? '',
				];
			}
			// Do we have to nuke it? Even simpler
			else
			{
				$valueForSave = [];
			}

			// Finally, massage the value so it will be compatible with Joomla Form
			$valueForValidation = $model->convertDatabaseDataToFormData($valueForSave);
		}

		// Validate the user-provided value
		$validationResult = $field->validate($valueForValidation);

		if ($validationResult instanceof \Throwable)
		{
			$this->ioStyle->error($validationResult->getMessage());

			return 30;
		}

		// Save the new value
		$configuration       = $model->getConfig();
		$configuration[$key] = $valueForSave;
		$model->saveConfig($configuration);

		$this->ioStyle->success(Text::sprintf('COM_ADMINTOOLS_CLI_WAFSET_LBL_SETTING', $key));

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
		$this->addOption('key', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_WAFSET_OPT_KEY'));
		$this->addOption('value', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_WAFSET_OPT_VALUE'));
		$this->addOption('add', null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_WAFSET_OPT_ADD'));
		$this->addOption('description', null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_WAFSET_OPT_ADD'));
		$this->addOption('remove', null, InputOption::VALUE_OPTIONAL, Text::_('COM_ADMINTOOLS_CLI_WAFSET_OPT_REMOVE'));
		$this->addOption('empty', null, InputOption::VALUE_NONE, Text::_('COM_ADMINTOOLS_CLI_WAFSET_OPT_EMPTY'));

		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_WAFSET_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_WAFSET_HELP'));
	}
}
