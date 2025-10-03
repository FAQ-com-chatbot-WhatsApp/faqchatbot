<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	com_nobossfaq
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2018 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;
?>

<div class='alert alert-notice'>
	<button type="button" class="close" data-dismiss="alert">×</button>
	<h4><?php echo str_replace("#link#", JRoute::_('index.php?option=com_nobossfaq&view=groups'), JText::_('COM_NOBOSSFAQ_ALERT_NO_GROUPS')); ?></h4>
</div>
