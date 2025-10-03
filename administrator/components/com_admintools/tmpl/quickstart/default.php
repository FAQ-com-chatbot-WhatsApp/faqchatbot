<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Quickstart\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$formStyle    = $this->isFirstRun ? '' : 'display: none';
$warningStyle = $this->isFirstRun ? 'display: none' : '';

?>
<div class="card mb-3" style="<?=$this->escape($warningStyle); ?>" id="youhavebeenwarnednottodothat">
	<h3 class="card-header bg-danger text-white">
		<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_HEAD'); ?>
	</h3>
	<div class="card-body">
		<p>
			<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_BODY'); ?>
		</p>
		<p>
			<a href="<?= Route::_('index.php?option=com_admintools') ?>" class="btn btn-success btn-lg">
				<span class="fa fa-home" aria-hidden="true"></span>
				<?= Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_BTN_NO') ?>
			</a>

			<a id="admintoolsQuickstartConfirmExecute"
			   class="btn btn-danger">
				<span class="fa fa-exclamation-circle" aria-hidden="true"></span>
				<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_BTN_YES') ?>
			</a>
		</p>
	</div>
</div>

<form action="<?= Route::_('index.php?option=com_admintools&view=Quickstart&task=commit') ?>"
	  id="adminForm" method="post" name="adminForm"
	  style="<?=$this->escape($formStyle); ?>">

	<div class="alert alert-info mb-3" style="<?=$this->escape($formStyle); ?>">
		<p>
			<?=Text::sprintf('COM_ADMINTOOLS_QUICKSTART_INTRO', 'https://www.akeeba.com/documentation/admin-tools.html'); ?>
		</p>
	</div>

	<div class="alert alert-danger mb-3" style="<?=$this->escape($warningStyle); ?>">
		<h3 class="alert-heading">
			<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_NOSUPPORT_HEAD'); ?>
		</h3>
		<p>
			<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_NOSUPPORT_BODY'); ?>
		</p>
	</div>

	<div class="card mb-3">
		<h3 class="card-header bg-primary text-white">
			<?=Text::_('COM_ADMINTOOLS_QUICKSTART_HEAD_ADMINSECURITY'); ?>
		</h3>

		<div class="card-body">
			<div class="row mb-3">
				<label for="adminpw"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_ADMINPW'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_ADMINPW_TIP'); ?>">
					<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_ADMINPW'); ?>
				</label>
				<div class="col-sm-9">
					<input type="text" size="20" name="adminpw" id="adminpw" class="form-control"
						   value="<?=$this->escape($this->wafconfig['adminpw']); ?>" />
				</div>
			</div>

			<?php if($this->hasHtaccess): ?>
				<div class="row mb-3">
					<label for="admin_username"
						   class="col-sm-3 col-form-label"
						   rel="popover"
						   title="<?=Text::_('COM_ADMINTOOLS_TITLE_ADMINPASSWORD'); ?>"
						   data-bs-content="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ADMINISTRATORPASSORD_INFO'); ?>">
						<?=Text::_('COM_ADMINTOOLS_TITLE_ADMINPASSWORD'); ?>
					</label>

					<div class="col-sm-9 d-flex">
						<input type="text" name="admin_username" id="admin_username" class="form-control"
							   value="<?=$this->escape($this->admin_username); ?>" autocomplete="off"
							   placeholder="<?=Text::_('COM_ADMINTOOLS_ADMINPASSWORD_LBL_USERNAME'); ?>"
						/>
						<input type="text" name="admin_password" id="admin_password" class="form-control"
							   value="<?=$this->escape($this->admin_password); ?>" autocomplete="off"
							   placeholder="<?=Text::_('COM_ADMINTOOLS_ADMINPASSWORD_LBL_PASSWORD'); ?>"
						/>
					</div>
				</div>
			<?php endif; ?>

			<div class="row mb-3">
				<label for="emailonadminlogin"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ADMINLOGINEMAIL_LBL'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ADMINLOGINEMAIL_DESC'); ?>">
					<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ADMINLOGINEMAIL_LBL'); ?>
				</label>

				<div class="col-sm-9">
					<input type="text" size="20" name="emailonadminlogin" id="emailonadminlogin" class="form-control"
						   value="<?=$this->escape($this->wafconfig['emailonadminlogin']); ?>">
				</div>
			</div>

			<div class="row mb-3">
				<label for="ipwl"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_IPWL'); ?>"
					   data-bs-content="<?=Text::sprintf('COM_ADMINTOOLS_QUICKSTART_WHITELIST_DESC', $this->escape($this->myIp)); ?>"
				>
					<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_IPWL'); ?>
				</label>

				<div class="col-sm-9">
					<?=HTMLHelper::_('admintools.booleanList', 'ipwl', 0, Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_IPWL')); ?>
				</div>
			</div>

			<div class="row mb-3">
				<label for="nonewadmins"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_NONEWADMINS'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_NONEWADMINS_DESC'); ?>">
					<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_NONEWADMINS'); ?>
				</label>

				<div class="col-sm-9">
					<?=HTMLHelper::_('admintools.booleanList', 'nonewadmins', $this->wafconfig['nonewadmins'], Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_NONEWADMINS')); ?>
				</div>
			</div>

			<div class="row mb-3">
				<label for="nofesalogin"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_NOFESALOGIN'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_NOFESALOGIN_TIP'); ?>">
					<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_NOFESALOGIN'); ?>
				</label>

				<div class="col-sm-9">
					<?=HTMLHelper::_('admintools.booleanList', 'nofesalogin', $this->wafconfig['nofesalogin'], Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_NOFESALOGIN')); ?>
				</div>
			</div>
		</div>
	</div>

	<div class="card mb-3">
		<h3 class="card-header bg-primary text-white">
			<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPTGROUP_BASIC'); ?>
		</h3>

		<div class="card-body">
			<div class="row mb-3">
				<label for="enablewaf"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ENABLEWAF_LBL'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ENABLEWAF_DESC'); ?>"
				>
					<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ENABLEWAF_LBL'); ?>
				</label>

				<div class="col-sm-9">
					<?=HTMLHelper::_('admintools.booleanList', 'enablewaf', 1, Text::_('COM_ADMINTOOLS_QUICKSTART_ENABLEWAF_LBL')); ?>
				</div>
			</div>

			<div class="row mb-3">
				<label for="autoban"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_AUTOBAN_LBL'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_AUTOBAN_DESC'); ?>"
				>
					<?=Text::_('COM_ADMINTOOLS_QUICKSTART_AUTOBAN_LBL'); ?>
				</label>

				<div class="col-sm-9">
					<?=HTMLHelper::_('admintools.booleanList', 'autoban', 1, Text::_('COM_ADMINTOOLS_QUICKSTART_AUTOBAN_LBL')); ?>
				</div>
			</div>

			<div class="row mb-3">
				<label for="autoblacklist"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_AUTOBLACKLIST_LBL'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_AUTOBLACKLIST_DESC'); ?>"
				>
					<?=Text::_('COM_ADMINTOOLS_QUICKSTART_AUTOBLACKLIST_LBL'); ?>
				</label>

				<div class="col-sm-9">
					<?=HTMLHelper::_('admintools.booleanList', 'autoblacklist', 1, Text::_('COM_ADMINTOOLS_QUICKSTART_AUTOBLACKLIST_LBL')); ?>
				</div>
			</div>

			<div class="row mb-3">
				<label for="emailbreaches"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_EMAILBREACHES'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_EMAILBREACHES_TIP'); ?>">
					<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_EMAILBREACHES'); ?>
				</label>

				<div class="col-sm-9">
					<input type="text" size="20" name="emailbreaches" class="form-control"
						   value="<?=$this->escape($this->wafconfig['emailbreaches'] ?? ''); ?>">
				</div>
			</div>

			<div class="row mb-3">
				<label for="allowed_domains"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_ALLOWED_DOMAINS'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_ALLOWED_DOMAINS_TIP'); ?>">
					<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_ALLOWED_DOMAINS'); ?>
				</label>

				<div class="col-sm-9">
					<input type="text" size="20" name="allowed_domains" class="form-control"
						   value="<?=$this->escape(implode(', ', $this->wafconfig['allowed_domains'])); ?>">
				</div>
			</div>

		</div>
	</div>

	<div class="card mb-3">
		<h3 class="card-header bg-primary text-white">
			<?=Text::_('COM_ADMINTOOLS_QUICKSTART_HEAD_ADVANCEDSECURITY'); ?>
		</h3>

		<div class="card-body">
			<div class="row mb-3">
				<label for="bbhttpblkey"
					   class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_BBHTTPBLKEY'); ?>"
					   data-bs-content="<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_BBHTTPBLKEY_TIP'); ?>">
					<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_LBL_OPT_BBHTTPBLKEY'); ?>
				</label>

				<div class="col-sm-9">
					<input type="text" size="45" name="bbhttpblkey" class="form-control"
					   value="<?=$this->escape($this->wafconfig['bbhttpblkey']); ?>" />
				</div>
			</div>

			<?php if($this->hasHtaccess): ?>
				<div class="row mb-3">
					<label for="htmaker"
						   class="col-sm-3 col-form-label"
						   rel="popover"
						   title="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_HTMAKER_LBL'); ?>"
						   data-bs-content="<?=Text::_('COM_ADMINTOOLS_QUICKSTART_HTMAKER_DESC'); ?>"
					>
						<?=Text::_('COM_ADMINTOOLS_QUICKSTART_HTMAKER_LBL'); ?>
					</label>

					<div class="col-sm-9">
						<?=HTMLHelper::_('admintools.booleanList', 'htmaker', 1, Text::_('COM_ADMINTOOLS_QUICKSTART_HTMAKER_LBL')); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="card mb-3">
		<h3 class="card-header bg-info text-white">
			<?=Text::_('COM_ADMINTOOLS_QUICKSTART_HEAD_ALMOSTTHERE'); ?>
		</h3>

		<div class="card-body">
			<p>
				<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALMOSTTHERE_INTRO'); ?>
			</p>
			<ul>
				<li>
					<a href="https://akee.ba/lockedout4">https://akee.ba/lockedout4</a>
				</li>
				<li>
					<a href="https://akee.ba/500htaccess4">https://akee.ba/500htaccess4</a>
				</li>
				<li>
					<a href="https://akee.ba/adminpassword4">https://akee.ba/adminpassword4</a>
				</li>
				<li>
					<a href="https://akee.ba/403edituser4">https://akee.ba/403edituser4</a>
				</li>
			</ul>
			<p>
				<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALMOSTTHERE_OUTRO'); ?>
			</p>
		</div>
	</div>

	<div class="alert alert-danger mb-3" style="<?=$this->escape($warningStyle); ?>">
		<h3 class="alert-heading">
			<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_NOSUPPORT_HEAD'); ?>
		</h3>
		<p>
			<?=Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_NOSUPPORT_BODY'); ?>
		</p>
	</div>

	<div class="card card-body mb-3" style="<?=$this->escape($formStyle); ?>">
		<button type="submit" class="btn btn-primary">
			<span class="fa fa-save" aria-hidden="true"></span>
			<?=Text::_('JSAVE'); ?>
		</button>
	</div>

	<div class="card card-body mb-3" style="<?=$this->escape($warningStyle); ?>">
		<button type="submit" class="btn btn-danger m-2">
			<span class="fa fa-exclamation-circle" aria-hidden="true"></span>
			<?=Text::_('JSAVE'); ?>
		</button>

		<a href="<?= Route::_('index.php?option=com_admintools') ?>" class="btn btn-success btn-lg m-2">
			<span class="fa fa-home" aria-hidden="true"></span>
			<?= Text::_('COM_ADMINTOOLS_QUICKSTART_ALREADYCONFIGURED_BTN_NO') ?>
		</a>
	</div>

	<input type="hidden" name="detectedip" id="detectedip" value="" />
	<?= HTMLHelper::_('form.token') ?>
</form>
