<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

$displayData = [
	'textPrefix' => 'COM_ADMINTOOLS_BLOCKEDREQUESTSLOG',
	'formURL'    => 'index.php?option=com_admintools&view=blockedrequestslog',
	//'helpURL'    => 'https://docs.joomla.org/Special:MyLanguage/Adding_a_new_article',
	'icon'       => 'fa fa-clipboard-list',
	'createURL'  => 'index.php?option=com_admintools&view=Webapplicationfirewall',
];

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
