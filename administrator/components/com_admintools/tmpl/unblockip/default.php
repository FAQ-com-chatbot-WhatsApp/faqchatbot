<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var    $this   \Akeeba\Component\AdminTools\Administrator\View\Unblockip\HtmlView */
?>
<form action="<?= Route::_('index.php?option=com_admintools&view=Unblockip&task=unblock') ?>"
	  id="adminForm" method="post" name="adminForm">

	<div class="card card-body">
		<div class="alert alert-info">
			<?= Text::_('COM_ADMINTOOLS_UNBLOCKIP_LBL_INFO'); ?>
		</div>

		<div class="row mb-3">
			<label for="ip" class="col-sm-3 col-form-label">
				<?= Text::_('COM_ADMINTOOLS_UNBLOCKIP_LBL_CHOOSE_IP'); ?>
			</label>
			<div class="col-sm-9">
				<input class="form-control" id="ip" name="ip" type="text" value="" />
			</div>
		</div>

		<div class="row mb-3">
			<div class="col-sm-9 offset-sm-3">
				<button type="submit"
						class="btn btn-primary btn-lg">
					<span class="fa fa-unlock" aria-hidden="true"></span>
					<?= Text::_('COM_ADMINTOOLS_UNBLOCKIP_LBL_IP'); ?>
				</button>
			</div>
		</div>
	</div>

	<?= HTMLHelper::_('form.token') ?>
</form>
