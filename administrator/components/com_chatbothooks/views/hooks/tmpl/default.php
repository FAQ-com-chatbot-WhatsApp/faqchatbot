<?php
/** @package Noboss.ChatbotHooks */
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_chatbothooks&view=hooks'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_CHATBOTHOOKS_NO_HOOKS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table table-striped" id="hookList">
                        <thead>
                            <tr>
                                <th class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col" class="w-50">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CHATBOTHOOKS_HEADING_DESCRIPTION', 'h.description', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CHATBOTHOOKS_HEADING_CREATED', 'h.created_at', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CHATBOTHOOKS_HEADING_UPDATED', 'h.updated_at', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5"><?php echo Text::_('COM_CHATBOTHOOKS_HEADING_ID'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_('index.php?option=com_chatbothooks&task=hook.edit&id=' . (int) $item->id); ?>">
                                            <?php echo htmlspecialchars(mb_strimwidth($item->description, 0, 120, '...')); ?>
                                        </a>
                                    </td>
                                    <td><?php echo HTMLHelper::_('date', $item->created_at, Text::_('DATE_FORMAT_LC5')); ?></td>
                                    <td><?php echo HTMLHelper::_('date', $item->updated_at, Text::_('DATE_FORMAT_LC5')); ?></td>
                                    <td><?php echo $item->id; ?></td>
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
