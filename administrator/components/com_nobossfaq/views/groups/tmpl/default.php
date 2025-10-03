<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	com_nobossfaq
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

// Joomla 3
if(version_compare(JVERSION, '4', '<')){
    // Renderiza layout tradicional de formulario
    echo JLayoutHelper::render('noboss.j3.list.traditional', $this);
}
// Joomla a partir do 4
else{
    // Renderiza layout tradicional de formulario
    echo LayoutHelper::render('noboss.j4.list.traditional', $this);
}
