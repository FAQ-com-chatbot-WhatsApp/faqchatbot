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
use Noboss\Library\Util\NbLoadextensionAssetsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Obter a versao da extensao informada e seta no input para poder ser obtido em outros locais via php
class NbExtensionVersionField extends FormField {

    protected function getInput(){
        $doc = Factory::getDocument();
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();
        
        // Nome da extensao (ex: mod_nobosscalendar)
        $extension     = $this->getAttribute('extension');

        if(empty($extension)){
            return;
        }
        // Obtem versao da extensao
        $extensionsVersion = NbLoadextensionAssetsUtil::getExtensionVersion($extension);

        if(empty($extensionsVersion)){
            return;
        }

        $input = Factory::getApplication()->input;

        // Seta versao no input para poder ser obtido em codigos PHP que forem executados na sequencia
        $input->set('nbExtensionVersion', $extensionsVersion);

        // Armazena versao em constante JS, caso ainda nao definido
        $wa->addInline("script", "var nbExtensionVersion = '{$extensionsVersion}';", array('name' => 'nbExtensionVersion'));
    }
}
