<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\CliCommand;

defined('_JEXEC') || die;

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
 * admintools:waf:get
 *
 * Get the value of a WAF configuration option
 *
 */
class WafGet extends AbstractCommand
{
	use ConfigureEnv;
	use ConfigureIO;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'admintools:waf:get';

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

		$rule = (string) $this->cliInput->getOption('rule');

		/** @var ConfigurewafModel $model */
		$model = $this->getMVCFactory()->createModel('Configurewaf', 'Administrator');

		$configuration = $model->getConfig();

		if (!$rule || !isset($configuration[$rule]))
		{
			$this->ioStyle->error(Text::sprintf('COM_ADMINTOOLS_CLI_WAFGET_ERR_CANNOT_FIND', $rule));

			return 1;
		}

		$output = [
			$rule => $configuration[$rule]
		];

		return $this->printFormattedAndReturn($output, 'json');
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
		$this->addOption('rule', null, InputOption::VALUE_REQUIRED, Text::_('COM_ADMINTOOLS_CLI_WAFGET_OPT_RULE'));
		$this->setDescription(Text::_('COM_ADMINTOOLS_CLI_WAFGET_DESC'));
		$this->setHelp(Text::_('COM_ADMINTOOLS_CLI_WAFGET_HELP'));
	}
}
