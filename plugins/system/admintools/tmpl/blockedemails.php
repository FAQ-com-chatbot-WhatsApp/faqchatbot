<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;

?>
<h4 class="fs-5">
	<?= Text::_('PLG_ADMINTOOLS_LBL_EBRNOTICE_HEAD') ?>
</h4>
<p>
	<?= Text::_('PLG_ADMINTOOLS_LBL_EBRNOTICE_MESSAGE') ?>
</p>
<div class="d-flex flex-row gap-3">
	<button type="button" class="btn btn-primary d-none" id="plgSystemAdmintoolsDBE">
		<span class="fa fa-toggle-off me-1" aria-hidden="true"></span>
		<?= Text::_('PLG_ADMINTOOLS_LBL_EBRNOTICE_BTN_NOEMAIL') ?>
	</button>
	<button type="button" class="btn btn-danger d-none" id="plgSystemAdmintoolsΗΒΕΜ">
		<span class="fa fa-eye-slash me-1" aria-hidden="true"></span>
		<?= Text::_('PLG_ADMINTOOLS_LBL_EBRNOTICE_BTN_NONOTICE') ?>
	</button>
</div>

