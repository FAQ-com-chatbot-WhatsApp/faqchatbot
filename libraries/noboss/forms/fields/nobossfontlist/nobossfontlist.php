<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

// Carrega arquivo que esta no padrao Joomla 5
use Noboss\Library\Form\Field\Nbfontlist\NbfontlistHelper;

/**
 * Classe de campo personalizado para listagem da fonte e estilos de fonte. (usado a partir de JS)
 */
//FIXME: após ajustado o caminho no JS que chama essa classe podemos remover esse diretorio e deixar só no /src
class NobossNobossfontlist extends NbfontlistHelper {
	
}
