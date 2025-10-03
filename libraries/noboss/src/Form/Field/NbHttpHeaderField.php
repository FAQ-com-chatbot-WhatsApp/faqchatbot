<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
// use Joomla\CMS\Language\Text;
// use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/* Permite definer um header http no carregamento da paina
    - O field surgiu inicialmente da necessidade de modificar o valor de 'cross-origin-opener-policy' para 'unsafe-none' dentro da extensao de calendario porque o Joomla 4 tem um plugin 'HTTP Headers' que define por padrao esse valor como 'same-origin', impedindo que uma janela de browser converse com outra (recurso usado na autenticacao da api do google)
    - O campo deve enviar dois parametros: 'header' e 'value'
*/
class NbHttpHeaderField extends FormField {

    protected function getInput(){
        $header = $this->getAttribute('header', '');
        $value = $this->getAttribute('value', '');

        if(!empty($header) && !empty($value)){
            Factory::getApplication()->setHeader($header, $value, true);
        }
    }
}
