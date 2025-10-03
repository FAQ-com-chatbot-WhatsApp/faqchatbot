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

/** @var \Akeeba\Component\AdminTools\Administrator\View\Scans\HtmlView $this */

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
HTMLHelper::_('bootstrap.modal', '.admintoolsModal', [
	'backdrop' => 'static',
	'keyboard' => true,
	'focus'    => true,
]);

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

<form action="<?= Route::_('index.php?option=com_admintools&view=Scans'); ?>"
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
							<?= Text::_('COM_ADMINTOOLS_SCANS_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_SCAN_LBL_START', 'scanstart', $listDirn, $listOrder); ?>
							</th>

							<th scope="col">
								<?= Text::_('COM_ADMINTOOLS_SCAN_LBL_TOTAL') ?>
							</th>

							<th scope="col">
								<?= Text::_('COM_ADMINTOOLS_SCAN_LBL_MODIFIED') ?>
							</th>

							<th scope="col">
								<?= Text::_('COM_ADMINTOOLS_SCAN_LBL_THREATNONZERO') ?>
							</th>

							<th scope="col">
								<?= Text::_('COM_ADMINTOOLS_SCAN_LBL_ADDED') ?>
							</th>

							<th scope="col">
								<?= Text::_('COM_ADMINTOOLS_SCAN_LBL_ACTIONS') ?>
							</th>

							<th scope="col" class="w-1 d-none d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $item) : ?>
							<?php
							$canEdit    = $user->authorise('core.edit', 'com_admintools');
							?>
							<tr class="row<?= $i++ % 2; ?>">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id, !(empty($item->checked_out_time) || ($item->checked_out_time === $nullDate)), 'cid', 'cb', $item->id); ?>
								</td>

								<td>
									<div class="d-flex gap-2">
										<?php if (trim($item->comment ?? '') != ''): ?>
											<button type="button"
													class="btn btn-info btn-sm hasTooltip"
													title="<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_COMMENT') ?>"
													data-bs-toggle="modal"
													data-bs-target="#commentModal"
													data-bs-comment="<?= $this->escape($item->comment) ?>"
											>
												<span class="fa fa-comment" aria-hidden="true"></span>
												<span class="visually-hidden"><?= Text::_('COM_ADMINTOOLS_SCANS_LBL_COMMENT') ?></span>
											</button>
										<?php endif; ?>

										<?php if ($item->status == 'fail'): ?>
											<div class="text-danger hasTooltip" title="<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_STATUS_FAIL') ?>">
												<span class="fa fa-times-circle" aria-hidden="true"></span>
												<span class="visually-hidden">
													<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_STATUS_FAIL') ?>
												</span>
											</div>
										<?php elseif ($item->status == 'run'): ?>
											<div class="text-warning hasTooltip" title="<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_STATUS_RUN') ?>">
												<span class="fa fa-play-circle" aria-hidden="true"></span>
												<span class="visually-hidden">
													<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_STATUS_RUN') ?>
												</span>
											</div>
										<?php else: ?>
											<div class="text-success hasTooltip" title="<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_STATUS_COMPLETE') ?>">
												<span class="fa fa-check-circle" aria-hidden="true"></span>
												<span class="visually-hidden">
													<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_STATUS_COMPLETE') ?>
												</span>
											</div>
										<?php endif ?>

										<?php if ($item->origin === 'backend'): ?>
											<div class="hasTooltip" title="<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_ORIGIN_BACKEND') ?>">
												<span class="fa fa-desktop" aria-hidden="true"></span>
												<span class="visually-hidden">
													<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_ORIGIN_BACKEND') ?>
												</span>
											</div>
										<?php elseif ($item->origin === 'cli'): ?>
											<div class="hasTooltip" title="<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_ORIGIN_CLI') ?>">
												<span class="fa fa-terminal" aria-hidden="true"></span>
												<span class="visually-hidden">
													<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_ORIGIN_CLI') ?>
												</span>
											</div>
										<?php elseif ($item->origin === 'joomla'): ?>
											<div class="hasTooltip" title="<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_ORIGIN_JOOMLA') ?>">
												<span class="fa fab fa-joomla" aria-hidden="true"></span>
												<span class="visually-hidden">
													<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_ORIGIN_JOOMLA') ?>
												</span>
											</div>
										<?php endif ?>

										<?php if ($canEdit && $item->status === 'complete'): ?>
											<a href="<?= Route::_('index.php?option=com_admintools&task=scan.edit&id=' . (int) $item->id); ?>"
													title="<?= Text::_('JACTION_EDIT'); ?>">
												<?= HTMLHelper::_('admintools.formatDate', $item->scanstart) ?></a>
										<?php else: ?>
											<?= HTMLHelper::_('admintools.formatDate', $item->scanstart) ?>
										<?php endif ?>
									</div>

									<?php
										$duration = null;
										if (($item->status === 'complete') && !empty($item->scanend) && ($item->scanend != $nullDate) && $duration !== null) {
											try {
												$duration = (clone Factory::getDate($item->scanend))->diff(clone Factory::getDate($item->scanstart));
											} catch (Exception $e) {
												$duration = null;
											}
										}
										if (($item->status === 'complete') && !empty($item->scanend) && ($item->scanend != $nullDate) && $duration !== null):
										?>
									<div class="text-muted">
										<span class="icon-clock" aria-hidden="true"></span>
										<?= $duration->format('%H:%I:%S') ?>
									</div>
									<?php endif ?>
								</td>

								<td>
									<?= $item->totalfiles ?>
								</td>

								<td class="<?= $item->files_modified ? 'fw-bold text-danger' : '' ?>">
									<?= $item->files_modified ?>
								</td>

								<td class="<?= $item->files_suspicious ? 'fw-bold text-danger' : '' ?>">
									<?= $item->files_suspicious ?>
								</td>

								<td class="<?= $item->files_new ? 'fw-bold text-danger' : '' ?>">
									<?= $item->files_new ?>
								</td>

								<td>
									<?php if( $item->files_modified + $item->files_new + $item->files_suspicious): ?>
										<a class="btn btn-primary btn-sm text-decoration-none <?= $item->status !== 'complete' ? 'disabled' : '' ?>"
										   href="<?= Route::_(sprintf('index.php?option=com_admintools&view=Scanalerts&scan_id=%d', $item->id)) ?>">
											<?= Text::_('COM_ADMINTOOLS_SCAN_LBL_ACTIONS_VIEW') ?>
										</a>
									<?php endif; ?>
								</td>

								<td class="w-1 d-none d-md-table-cell">
									<?= $item->id ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php // Load the pagination. ?>
					<?= $this->pagination->getListFooter(); ?>
				<?php endif; ?>

				<input type="hidden" name="task" value=""> <input type="hidden" name="boxchecked" value="0">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>

<?= $this->loadAnyTemplate('scans/scanmodal') ?>

<div id="commentModal"
	 class="modal"
	 role="dialog"
	 tabindex="-1"
	 aria-labelledby="akeeba-admintools-comment-title"
	 aria-hidden="true">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="akeeba-admintools-comment-title">
					<?= Text::_('COM_ADMINTOOLS_SCANS_LBL_COMMENT') ?>
				</h3>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
			</div>
			<div class="modal-body p-3" id="akeeba-admintools-comment-content">
			</div>
		</div>
	</div>
</div>