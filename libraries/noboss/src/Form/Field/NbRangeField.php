<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\RangeField;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbJsConstantsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbRangeField extends RangeField {

	protected function getInput() {
        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();

        // Verifica se o min e max não está null e define valores default
        $this->min = empty($this->min) ? 0 : $this->min;
        $this->max = empty($this->max) ? 100 : $this->max;
        
        // Adiciona a classe nobossrange no campo range
        $this->class .= " nobossrange";

        $attr = '';

        if ((string) $this->readonly == '1' || (string) $this->readonly == 'true'){
            $attr .= ' readonly';
            $this->disabled = 'true';
        }
        
        $html = parent::getInput();
        // Cria um campo number que fica ao lado do range
        $html .= "<input class='nobossrange--input form-control' type='number' {$attr} name='{$this->fieldname}' value='{$this->value}' min='{$this->min}' max='{$this->max}' step='{$this->step}'/>";

        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Adiciona o js e css do campo personalizado na pagina
		$wa->registerAndUseStyle('nobossrange', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossrange.min.css");
        $wa->registerAndUseScript('nobossrange', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossrange.min.js");
        
        return $html;
	}
}
