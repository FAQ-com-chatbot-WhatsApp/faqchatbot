<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/** @var $this \Akeeba\Component\AdminTools\Administrator\View\Schedulinginformation\HtmlView */

$url = str_replace(['/', '?', '&', '='], ['<wbr>/', '<wbr>?', '<wbr>&', '<wbr>='], $this->escape($this->croninfo->info->root_url . '/' . $this->croninfo->frontend->path));

?>
<div class="alert alert-info">
	<h3 class="alert-heading">
		<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_RUN_FILESCANNER'); ?>
	</h3>

	<p>
		<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_HEADERINFO'); ?>
	</p>
</div>

<div class="card mb-3">
	<h3 class="card-header bg-primary text-white">
		<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_CLICRON'); ?>
	</h3>

	<div class="card-body">
		<div class="alert alert-info">
			<p>
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_CLICRON_INFO'); ?>
			</p>
			<p>
				<a class="btn btn-info"
				   href="https://www.akeeba.com/documentation/admin-tools-joomla/php-file-scanner-cron.html" target="_blank">
					<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_GENERICREADDOC'); ?>
				</a>
			</p>
		</div>
		<p>
			<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_GENERICUSECLI'); ?>
			<br/>
			<code>
				<?=$this->escape($this->croninfo->info->php_path); ?>
				<?=$this->escape($this->croninfo->cli->path); ?>
				admintools:scan
			</code>
		</p>
		<?php if (!$this->croninfo->info->php_accurate): ?>
		<p>
			<span class="badge bg-danger"><?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_CLIGENERICIMPROTANTINFO'); ?></span>
			<?=Text::sprintf('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_CLIGENERICINFO', $this->croninfo->info->php_path); ?>
		</p>
		<?php endif ?>
	</div>
</div>

<div class="card mb-3">
	<h3 class="card-header">
		<?= Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_JOOMLASCHEDULER') ?>
	</h3>

	<div class="card-body">
		<?php if ($this->croninfo->joomla->supported): ?>
			<div class="alert alert-info">
				<p>
					<?= Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_JOOMLASCHEDULER_INFO') ?>
				</p>
				<p>
					<a class="btn btn-primary me-3"
							href="https://www.akeeba.com/documentation/admin-tools-joomla/php-file-scanner-joomlascheduled.html"
							target="_blank">
						<?= Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_GENERICREADDOC') ?>
					</a>
					<a class="btn btn-success"
							href="index.php?option=com_scheduler&view=tasks">
						<span class="icon-clock" aria-hidden="true"></span>
						<?= Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_JOOMLASCHEDULER_BUTTON') ?>
					</a>
				</p>
			</div>
			<div class="alert alert-warning">
				<h3 class="alert-heading">
					<?= Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_JOOMLASCHEDULER_PLUGIN_DISABLED_HEAD') ?>
				</h3>
				<p>
					<?= Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_JOOMLASCHEDULER_PLUGIN_DISABLED_BODY') ?>
				</p>
				<a class="btn btn-dark"
				   href="index.php?option=com_plugins&filter[folder]=task&filter[enabled]=&filter[element]=admintools&filter[access]=&filter[search]=">
					<span class="icon-plug" aria-hidden="true"></span>
					<?= Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_JOOMLASCHEDULER_PLUGIN_DISABLED_BUTTON') ?>
				</a>
			</div>
        <?php endif; ?>
	</div>
</div>

<div class="card mb-3">
	<h3 class="card-header bg-secondary text-white">
		<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTENDBACKUP'); ?>
	</h3>

	<div class="card-body">
		<div class="alert alert-info">
			<p>
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTENDBACKUP_INFO'); ?>
			</p>
			<p>
				<a class="btn btn-info"
				   href="https://www.akeeba.com/documentation/admin-tools-joomla/php-file-scanner-frontend.html" target="_blank">
					<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_GENERICREADDOC'); ?>
				</a>
			</p>
		</div>
		<?php if(!$this->croninfo->info->feenabled): ?>
			<div class="alert alert-danger">
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_DISABLED'); ?>
			</div>
		<?php elseif(!trim($this->croninfo->info->secret)): ?>
			<div class="alert alert-danger">
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_SECRET'); ?>
			</div>
		<?php else: ?>
			<p>
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTENDBACKUP_MANYMETHODS'); ?>
			</p>

			<h4><?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTENDBACKUP_TAB_WEBCRON'); ?></h4>
			<p>
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON'); ?>
			</p>

			<table class="table table-striped">
				<tr>
					<td></td>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_INFO'); ?>
					</td>
				</tr>
				<tr>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_NAME'); ?>
					</td>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_NAME_INFO'); ?>
					</td>
				</tr>
				<tr>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_TIMEOUT'); ?>
					</td>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_TIMEOUT_INFO'); ?>
					</td>
				</tr>
				<tr>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_URL'); ?>
					</td>
					<td>
						<?= $url ?>
					</td>
				</tr>
				<tr>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_LOGIN'); ?>
					</td>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_LOGINPASSWORD_INFO'); ?>
					</td>
				</tr>
				<tr>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_PASSWORD'); ?>
					</td>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_LOGINPASSWORD_INFO'); ?>
					</td>
				</tr>
				<tr>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_EXECUTIONTIME'); ?>
					</td>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_EXECUTIONTIME_INFO'); ?>
					</td>
				</tr>
				<tr>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_ALERTS'); ?>
					</td>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_ALERTS_INFO'); ?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WEBCRON_THENCLICKSUBMIT'); ?>
					</td>
				</tr>
			</table>

			<h4><?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTENDBACKUP_TAB_WGET'); ?></h4>
			<p>
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_WGET'); ?>
				<code>
					wget --max-redirect=10000
					"<?= $url ?>"
					-O - 1>/dev/null 2>/dev/null
				</code>
			</p>

			<h4><?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTENDBACKUP_TAB_CURL'); ?></h4>
			<p>
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_CURL'); ?>
				<code>
					curl -L --max-redirs 1000 -v
					"<?= $url ?>"
					1>/dev/null 2>/dev/null
				</code>
			</p>

			<h4><?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTENDBACKUP_TAB_SCRIPT'); ?></h4>
			<p>
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_CUSTOMSCRIPT'); ?>
			</p>
			<pre style="text-wrap: wrap">
<?='&lt;?php'; ?>

	$curl_handle=curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, '<?= $url ?>');
	curl_setopt($curl_handle,CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($curl_handle,CURLOPT_MAXREDIRS, 10000);
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER, 1);
	$buffer = curl_exec($curl_handle);
	curl_close($curl_handle);
	if (empty($buffer))
		echo "Sorry, the scan didn't work.";
	else
		echo $buffer;
<?='?&gt;'; ?>

		</pre>
			<h4><?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTENDBACKUP_TAB_URL'); ?></h4>
			<p>
				<?=Text::_('COM_ADMINTOOLS_SCHEDULINGINFORMATION_LBL_FRONTEND_RAWURL'); ?>
				<code>
					<?= $url ?>

				</code>
			</p>

		<?php endif; ?>
	</div>
</div>
