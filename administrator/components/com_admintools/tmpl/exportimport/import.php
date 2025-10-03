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

?>
<form action="<?= Route::_('index.php?option=com_admintools&view=Exportimport') ?>" class="card" enctype="multipart/form-data" id="adminForm" method="post"
	  name="adminForm">

	<h3 class="card-header bg-primary text-white">
		<?= Text::_('COM_ADMINTOOLS_TITLE_IMPORT_SETTINGS') ?>
	</h3>

	<div class="card-body">
		<div class="row mb-3">
			<label class="col-sm-3 col-form-label">
				<?= Text::_('COM_ADMINTOOLS_EXPORTIMPORT_LBL_FILE') ?>
			</label>

			<div class="col-sm-9">
				<input type="file" name="importfile" value="" />
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<?= HTMLHelper::_('form.token') ?>
</form>
