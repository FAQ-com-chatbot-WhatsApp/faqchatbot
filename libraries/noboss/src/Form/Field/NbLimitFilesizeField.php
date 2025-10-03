<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\NumberField;
use Noboss\Library\Form\Field\Nbbinaryfile\NbsizescaleHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbLimitFilesizeField extends NumberField {

	protected function getInput() {
		// Valida e parâmetro "min" especificado para o campo.
		$this->min = 1;

 		// Pega valor da configuração de "limite de upload de arquivos" do PHP.ini.
 		$uploadMaxSize =  ini_get('upload_max_filesize');

 		// Remove espaços do valor.
		$uploadMaxSize = trim($uploadMaxSize);

		// Pega o valor removendo o último caracter da configuração.
		$valueUploadMaxSize = substr($uploadMaxSize, 0, -1);

		// Pega último caractere da configuração que corresponde a grandeza de entrada.
		$scaleInput = strtolower($uploadMaxSize[strlen($uploadMaxSize)-1]);

   		// Limite de upload em bytes.
		$this->max = NbsizescaleHelper::convertScale($valueUploadMaxSize, $scaleInput, 'b');

		// Chama função getInput da classe pai.
		return parent::getInput();
	}
}
