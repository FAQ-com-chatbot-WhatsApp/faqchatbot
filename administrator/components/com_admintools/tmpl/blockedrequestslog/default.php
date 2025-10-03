<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Blockedrequestslog\HtmlView $this */

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
$baseUri           = Uri::root();

$i = 0;

$iplink  = 'https://' . Storage::getInstance()->getValue('iplookup', 'whatismyipaddress.com/ip/{ip}');

?>

<form action="<?= Route::_('index.php?option=com_admintools&view=blockedrequestslog'); ?>"
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
							<?= Text::_('COM_ADMINTOOLS_BLOCKEDREQUESTSLOG_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_LOG_LBL_LOGDATE', 'logdate', $listDirn, $listOrder); ?>
							</th>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_LOG_LBL_IP', 'ip', $listDirn, $listOrder); ?>
							</th>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_LOG_LBL_REASON', 'reason', $listDirn, $listOrder); ?>
							</th>

							<th scope="col" class="d-none d-md-table-cell w-25">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ADMINTOOLS_LOG_LBL_URL', 'url', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $item) : ?>
							<tr class="row<?= $i++ % 2; ?>">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->id); ?>
								</td>

								<td>
									<?= HTMLHelper::_('admintools.formatDate', $item->logdate) ?>
								</td>

								<td>
									<a href="<?= str_replace('{ip}', urlencode($item->ip), $iplink) ?>" target="_blank" class="btn btn-secondary btn-sm external-link-no-before">
										<span class="fa fa-search" aria-hidden="true"></span>
									</a>

									<?php if($item->block): ?>
										<a class="btn btn-success btn-sm"
										   href="<?= Route::_(sprintf('index.php?option=com_admintools&view=SecurityExceptions&task=unban&id=%d&%s=1', $item->id, Factory::getApplication()->getFormToken())) ?>"
										   title="<?= Text::_('COM_ADMINTOOLS_LOG_LBL_UNBAN') ?>">
											<span class="fa fa-minus-square" aria-hidden="true"></span>
										</a>&nbsp;
									<?php else: ?>
										<a class="btn btn-danger btn-sm"
										   href="<?= Route::_(sprintf('index.php?option=com_admintools&view=SecurityExceptions&task=ban&id=%d&%s=1', $item->id, Factory::getApplication()->getFormToken())) ?>"
										   title="<?=Text::_('COM_ADMINTOOLS_LOG_LBL_BAN'); ?>">
											<span class="fa fa-ban" aria-hidden="true"></span>
										</a>&nbsp;
									<?php endif; ?>

									<code><?= $this->escape($item->ip); ?></code>
								</td>

								<td>
									<?= Text::_('COM_ADMINTOOLS_LOG_LBL_REASON_' . $item->reason) ?>
								</td>

								<td>
									<?= $this->escape($item->url); ?>
									<?php if ($item->extradata): ?>
									<div class="small text-muted">
										<?= $this->escape($item->extradata) ?>
									</div>
									<?php endif ?>
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
