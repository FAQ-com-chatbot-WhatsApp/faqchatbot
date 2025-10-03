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

$exportAreas   = ['wafconfig', 'wafblacklist', 'wafexceptions', 'ipallow', 'ipblacklist', 'ipwhitelist', 'badwords', 'useragents', 'serverconfig'];

?>
<form action="<?= Route::_('index.php?option=com_admintools&view=Exportimport') ?>"
	  id="adminForm" method="post" name="adminForm" class="card">

	<h3 class="card-header bg-primary text-white">
		<?= Text::_('COM_ADMINTOOLS_EXPORTIMPORT_LBL_FINE_TUNING') ?>
	</h3>

	<div class="card-body">
		<?php foreach ($exportAreas as $area): ?>
			<div class="row mb-3">
				<label class="col-sm-3 col-form-label" for="exportdata_<?= $area ?>">
					<?= Text::_('COM_ADMINTOOLS_EXPORTIMPORT_LBL_' . $area) ?>
				</label>

				<div class="col-sm-9">
					<?= HTMLHelper::_('admintools.booleanList', 'exportdata[' . $area . ']', true, Text::_('COM_ADMINTOOLS_EXPORTIMPORT_LBL_' . $area)); ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<input type="hidden" name="task" value="" />
	<?= HTMLHelper::_('form.token') ?>
</form>
