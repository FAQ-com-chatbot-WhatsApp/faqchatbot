<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2018 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// Carrega arquivo que esta no padrao Joomla 5
use Noboss\Library\Form\Field\NbListImagesField;

 \defined('_JEXEC') or die;

class JFormFieldNobosslistimages extends NbListImagesField {

	protected $type = "nobosslistimages";
}
