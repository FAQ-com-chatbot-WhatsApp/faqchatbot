<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\Component\Modules\Administrator\Field\ModulesPositioneditField;
use Joomla\Component\Modules\Administrator\Service\HTML\Modules;
use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Adiciona arquivo de traducao do com_modules
Factory::getLanguage()->load('com_modules', JPATH_ROOT.'/administrator/');

class NbModulesPositionField extends ModulesPositioneditField {

    /* A funcao eh reescrita para Joomla 4 apenas para mudar a forma que eh chamada a funcao 'positions'. No field original eh chamada atravpes de HTMLHelper::_('modules.positions', $clientId, 1, $this->value), o que gera erro no uso por outras extensoes. Mudamos para chamar diretamente por Modules::positions('modules.positions', $clientId, 1, $this->value);
        */
    protected function getInput(){
        
        $data = $this->getLayoutData();
        $clientId  = $this->client === 'administrator' ? 1 : 0;
        
        $modules = new Modules();
        $positions = $modules->positions('modules.positions', $clientId, 1, $this->value);

        $data['client']    = $clientId;
        $data['positions'] = $positions;

        $renderer = $this->getRenderer($this->layout);
        $renderer->setComponent('com_modules');
        $renderer->setClient(1);

        return $renderer->render($data);
    }

}    