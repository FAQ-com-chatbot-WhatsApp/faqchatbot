<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Rule;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\Rule\NumberRule;
use Joomla\Registry\Registry;
use Noboss\Library\Form\Field\Nbbinaryfile\NbsizescaleHelper;

\defined('_JEXEC') or die;

// Classe utilizada para validar se limite escolhido pelo usuario esta dentro da margem maxima determinada pelo php.ini (utilizado no componente testimonials)
class NbLimitFilesizeRule extends NumberRule {

    public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null) {

		// Verifica se o valor não é 0.
        if ((int)$value <= 0) {
        	// Adiciona mensagem.
        	$element->addAttribute("message", Text::_($element['error_less_then_zero']));
            return false;
        }

   		// Pega valor da configuração de "limite de upload de arquivos" do PHP.ini.
   		$uploadMaxSize =  ini_get('upload_max_filesize');

   		// Remove espaços do valor.
	    $uploadMaxSize = trim($uploadMaxSize);

	    // Pega o valor removendo o último caracter da configuração.
	    $valueUploadMaxSize = substr($uploadMaxSize, 0, -1);

	    // Pega último caractere da configuração que corresponde a grandeza de entrada.
	    $scaleInput = strtolower($uploadMaxSize[strlen($uploadMaxSize)-1]);

   		// Limite de upload em kilobytes.
		$postMaxSizeInbytes = NbsizescaleHelper::convertScale($valueUploadMaxSize, $scaleInput, 'b');

        // Verifica se o valor informado não ultrapassa o limite da configuração de upload do PHP.ini
        if ((int)$value > $postMaxSizeInbytes) {
        	// Adiciona mensagem.
        	$message = Text::_($element['error_bigger_then_config']);
        	$message = str_replace("#upload_max_filesize_bytes#", $postMaxSizeInbytes, $message);
        	$element->addAttribute("message", $message);

            return false;
        }

        return true;

	}
}
