<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Scanalerts\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

?>
<h1>
	<?= Text::sprintf('COM_ADMINTOOLS_TITLE_SCANALERTS', $this->scan->id) ?>
</h1>
<h2>
	<?= HTMLHelper::_('admintools.formatDate', $this->scan->scanstart) ?>

</h2>

<table class="table">
	<thead>
	<tr>
		<th width="10%"></th>
		<th>
			<?= Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_PATH'); ?>
		</th>
		<th width="50%">
			<?= Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_STATUS'); ?>
		</th>
		<th width="20%">
			<?= Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_THREAT_SCORE'); ?>
		</th>
		<th width="40%">
			<?= Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_ACKNOWLEDGED'); ?>
		</th>
	</tr>
	</thead>
	<tbody>
	<?php if (count($this->items) > 0): ?>
		<?php $i = 0; ?>
		<?php foreach ($this->items as $item): ?>
			<tr>
				<td>
					<?= ++$i; ?>

				</td>
				<td>
					<?php if (strlen($item->path) > 100): ?>
						&hellip;
						<?= $this->escape(substr($item->path, -100)); ?>

					<?php else: ?>
						<?= $this->escape($item->path); ?>

					<?php endif; ?>
				</td>
				<td>
					<?php if($item->newfile): ?>
						<span class="<?=$item->threat_score ? 'fw-bold text-primary' : '' ?>">
											<?=Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_STATUS_NEW'); ?>
										</span>
					<?php elseif($item->suspicious): ?>
						<span class="<?=$item->threat_score ? 'fw-bold text-danger' : ''; ?>">
											<?=Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_STATUS_SUSPICIOUS'); ?>
										</span>
					<?php else: ?>
						<span class="<?=$item->threat_score ? 'fw-bold text-warning' : ''; ?>">
											<?=Text::_('COM_ADMINTOOLS_SCANALERTS_LBL_STATUS_MODIFIED'); ?>
										</span>
					<?php endif; ?>
				</td>
				<td>
					<?php
					if ($item->threat_score == 0)
					{
						$icon = 'fa fa-check-circle';
						$class = 'text-success';
					}
					elseif ($item->threat_score < 10)
					{
						$icon = 'fa fa-exclamation';
						$class = 'text-primary fst-italic';
					}
					elseif ($item->threat_score < 100)
					{
						$icon = 'fa fa-exclamation-circle';
						$class = 'text-warning fw-bold';
					}
					else
					{
						$icon = 'fa fa-radiation';
						$class = 'text-danger fw-bold';
					}
					?>
					<span class="<?= $class ?>">
						<span class="<?= $icon ?> w-25" aria-hidden="true"></span>&nbsp;
						<?= $item->threat_score ?>
					</span>
				</td>
				<td>
					<?php if ($item->acknowledged): ?>
						<span class="text-success">
							<span class="fa fa-shield-alt" aria-hidden="true"></span>
							<?= Text::_('JYES'); ?>
						</span>
					<?php else: ?>
						<span class="text-muted">
							<?= Text::_('JNO'); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	<?php else: ?>
		<tr>
			<td colspan="20" align="center"><?= Text::_('COM_ADMINTOOLS_MSG_COMMON_NOITEMS'); ?></td>
		</tr>
	<?php endif; ?>
	</tbody>
</table>
