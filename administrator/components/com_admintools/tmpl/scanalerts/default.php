<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Scanalerts\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$this->tableColumnsAutohide();
$this->tableColumnsMultiselect('#articleList');

$app               = Factory::getApplication();
$user              = $app->getIdentity();
$userId            = $user->id;
$listOrder         = $this->escape($this->state->get('list.ordering'));
$listDirn          = $this->escape($this->state->get('list.direction'));
$nullDate          = Factory::getContainer()->get(DatabaseInterface::class)->getNullDate();
$hasCategoryFilter = !empty($this->getModel()->getState('filter.category_id'));
$saveOrder         = $listOrder == 'ordering';
$baseUri           = Uri::root();

$i = 0;

?>

<form action="<?= Route::_('index.php?option=com_admintools&view=Scanalerts'); ?>"
	  method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]) ?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span
								class="visually-hidden"><?= Text::_('INFO'); ?></span>
						<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="table" id="articleList">
						<caption class="visually-hidden">
							<?= Text::_('COM_ADMINTOOLS_SCANALERTS_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_SCANALERTS_LBL_PATH', 'path', $listDirn, $listOrder); ?>
							</th>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_SCANALERTS_LBL_STATUS', 'filestatus', $listDirn, $listOrder); ?>
							</th>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_SCANALERTS_LBL_THREAT_SCORE', 'threat_score', $listDirn, $listOrder); ?>
							</th>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_SCANALERTS_LBL_ACKNOWLEDGED', 'acknowledged', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $item) :
							$pathParts = explode('/', $item->path);
							?>
							<tr class="row<?= $i++ % 2; ?>">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id, !(empty($item->checked_out_time) || ($item->checked_out_time === $nullDate)), 'cid', 'cb', $item->path); ?>
								</td>

								<td>
									<a href="<?= Route::_('index.php?option=com_admintools&task=scanalert.edit&id=' . (int) $item->id); ?>"
									   title="<?= Text::_('JACTION_EDIT'); ?>">
										<?= implode('/<wbr>', $pathParts) ?>
									</a>
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

								<td class="text-center">
									<?= HTMLHelper::_('jgrid.published', $item->acknowledged, $i, 'scanalerts.', $user->authorise('core.edit.state', 'com_admintools'), 'cb'); ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php // Load the pagination. ?>
					<?= $this->pagination->getListFooter(); ?>
				<?php endif; ?>

				<input type="hidden" name="task" value="">
				<input type="hidden" name="boxchecked" value="0">
				<input type="hidden" name="scan_id" value="<?= $this->scan->id ?>">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
