<?php
/**
 * @package     Noboss.ChatbotMessages
 * @subpackage  com_chatbotmessages
 *
 * @copyright   Copyright (C) 2025 Noboss. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_chatbotmessages&view=messages'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('COM_CHATBOTMESSAGES_NO_MESSAGES'); ?>
					</div>
				<?php else : ?>
					<table class="table table-striped" id="messageList">
						<thead>
							<tr>
								<th class="w-1 text-center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</th>
								<th scope="col" class="w-10">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_CHATBOTMESSAGES_HEADING_DIRECTION', 'm.direction', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="w-10">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_CHATBOTMESSAGES_HEADING_TYPE', 'm.type', $listDirn, $listOrder); ?>
								</th>
								<th scope="col">
									<?php echo Text::_('COM_CHATBOTMESSAGES_HEADING_CONTENT'); ?>
								</th>
								<th scope="col" class="w-10">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_CHATBOTMESSAGES_HEADING_STATUS', 'm.status', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="w-15">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_CHATBOTMESSAGES_HEADING_CREATED', 'm.created_at', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" class="w-5">
									<?php echo Text::_('COM_CHATBOTMESSAGES_HEADING_ID'); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($this->items as $i => $item) : ?>
								<tr class="row<?php echo $i % 2; ?>">
									<td class="text-center">
										<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
									</td>
									<td>
										<span class="badge bg-<?php echo $item->direction === 'in' ? 'success' : 'primary'; ?>">
											<?php echo Text::_('COM_CHATBOTMESSAGES_DIRECTION_' . strtoupper($item->direction)); ?>
										</span>
									</td>
									<td>
										<?php echo Text::_('COM_CHATBOTMESSAGES_TYPE_' . strtoupper($item->type)); ?>
									</td>
									<td>
										<?php
										$content = json_decode($item->content, true);
										if (isset($content['text'])) {
											echo htmlspecialchars(substr($content['text'], 0, 100)) . (strlen($content['text']) > 100 ? '...' : '');
										} else {
											echo '<em>' . Text::_('COM_CHATBOTMESSAGES_CONTENT_BINARY') . '</em>';
										}
										?>
									</td>
									<td>
										<span class="badge bg-<?php
											switch ($item->status) {
												case 'sent': echo 'success'; break;
												case 'delivered': echo 'info'; break;
												case 'read': echo 'primary'; break;
												case 'failed': echo 'danger'; break;
												default: echo 'secondary';
											}
										?>">
											<?php echo Text::_('COM_CHATBOTMESSAGES_STATUS_' . strtoupper($item->status)); ?>
										</span>
									</td>
									<td>
										<?php echo HTMLHelper::_('date', $item->created_at, Text::_('DATE_FORMAT_LC5')); ?>
									</td>
									<td>
										<?php echo $item->id; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<?php echo $this->pagination->getListFooter(); ?>
				<?php endif; ?>

				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>