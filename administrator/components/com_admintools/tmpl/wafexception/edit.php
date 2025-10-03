<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Wafexception\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');

?>
<div id="admintools-whatsthis" class="alert alert-info">
	<p><?=Text::_('COM_ADMINTOOLS_WAFEXCEPTIONS_LBL_WHATSTHIS_INTRO'); ?></p>
	<ul>
		<li><?=Text::_('COM_ADMINTOOLS_WAFEXCEPTIONS_LBL_WHATSTHIS_GROUP_A'); ?></li>
		<li><?=Text::_('COM_ADMINTOOLS_WAFEXCEPTIONS_LBL_WHATSTHIS_GROUP_B'); ?></li>
	</ul>
</div>

<form action="<?php echo Route::_('index.php?option=com_admintools&view=wafexception&layout=edit&id=' . $this->item->id); ?>"
      aria-label="<?= Text::_('COM_ADMINTOOLS_TITLE_WAFEXCEPTION_EDIT', true) ?>"
      class="form-validate" id="adminForm" method="post" name="adminForm">

	<div class="card card-body mb-2">
		<?php foreach ($this->form->getFieldset('details') as $field): ?>
			<?= $field->renderField(); ?>
		<?php endforeach; ?>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
