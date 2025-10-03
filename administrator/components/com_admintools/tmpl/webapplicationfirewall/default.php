<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Webapplicationfirewall\HtmlView $this */

?>
<div class="alert alert-info mb-3">
	<?= Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_HTACCESSTIP') ?>
</div>

<div class="card mb-3">
	<div class="akeeba-cpanel-container card-body d-flex flex-row flex-wrap align-items-stretch gap-2">

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-dark border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Configurewaf') ?>">
			<div class="bg-dark text-white d-block text-center p-3 h2">
				<span class="fa fa-bolt" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_CONFIGUREWAF') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Wafexceptions') ?>">
			<div class="bg-primary text-white d-block text-center p-3 h2">
				<span class="fa fa-filter" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_WAFEXCEPTIONS') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-danger border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Wafdenylists') ?>">
			<div class="bg-danger text-white d-block text-center p-3 h2">
				<span class="fa fa-door-closed" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_WAFDENYLISTS') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-success border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Adminallowlists') ?>">
			<div class="bg-success text-white d-block text-center p-3 h2">
				<span class="fa fa-passport" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_ADMINALLOWLISTS') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-success border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=allowlists') ?>">
			<div class="bg-success text-white d-block text-center p-3 h2">
				<span class="fa fa-door-open" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_ALLOWLISTS') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-danger border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Disallowlists') ?>">
			<div class="bg-danger text-white d-block text-center p-3 h2">
				<span class="fa fa-ban" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_DISALLOWLISTS') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-danger border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Badwords') ?>">
			<div class="bg-danger text-white d-block text-center p-3 h2">
				<span class="fa fa-toilet-paper-slash" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_BADWORDS') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-dark border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Blockedrequestslog') ?>">
			<div class="bg-dark text-white d-block text-center p-3 h2">
				<span class="fa fa-clipboard-list" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_LOG') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-secondary border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Autobannedaddresses') ?>">
			<div class="bg-secondary text-white d-block text-center p-3 h2">
				<span class="fa fa-times-circle" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_AUTOBANNEDADDRESSES') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-secondary border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Ipautobanhistories') ?>">
			<div class="bg-secondary text-white d-block text-center p-3 h2">
				<span class="fa fa-history" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_IPAUTOBANHISTORIES') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-success border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Unblockip') ?>">
			<div class="bg-success text-white d-block text-center p-3 h2">
				<span class="fa fa-unlock-alt" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_UNBLOCKIP') ?>
			</span>
		</a>

		<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
		   href="<?= Route::_('index.php?option=com_admintools&view=Emailtemplates') ?>">
			<div class="bg-primary text-white d-block text-center p-3 h2">
				<span class="fa fa-envelope" aria-hidden="true"></span>
			</div>
			<span>
				<?= Text::_('COM_ADMINTOOLS_TITLE_EMAILTEMPLATES') ?>
			</span>
		</a>

	</div>
</div>
