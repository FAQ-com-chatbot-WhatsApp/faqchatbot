<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2018 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Noboss\Library\Util\NbLoadextensionAssetsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbLoadTranslationField extends FormField {

    /**
     * Metodo que carrega o arquivo de traducao para extensao especificada. 
     * Caso o nome da extensao nao seja especificada, carrega traducao da library da no boss
     */
    protected function getInput(){
      // Nome da extensao
      $extensionName = $this->getAttribute('extension', 'lib_noboss');
      // Instancia objeto da classe para obter o diretorio da extensao
      $assetsObject = new NbLoadextensionAssetsUtil($extensionName);
      // Obtem diretorio da extensao
      $extensionPath = $assetsObject->getDirectoryExtension($this->getAttribute('admin')=='true');
      // Carrega arquivo traducao para extensao especificada
      Factory::getLanguage()->load($extensionName, $extensionPath);
      
      $app = Factory::getApplication();
      $wa = $app->getDocument()->getWebAssetManager();
      
      // seta estilo do campo para que nenhum espaçamento seja exibido
      $wa->addInlineStyle(
        ".control-group.{$this->type} {
            display: none;
        }"
      );


    }
}
