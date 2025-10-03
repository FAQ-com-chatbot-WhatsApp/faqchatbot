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
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbJsConstantsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbRequestJsCssField extends FormField {

    protected function getInput(){
      $app = Factory::getApplication();
      $wa = $app->getDocument()->getWebAssetManager();

      // Caminho do arquivo CSS ou JS desde o diretório raiz do projeto
      $file     = $this->getAttribute('file');
      // Tipo do arquivo que pode ser 'css', 'js' ou 'jquery'
      $fileType   = $this->getAttribute('filetype');
      // Informa se deve ser concatenada a url do site na requisicao ('internal' ou 'external')
      $prefixUrlSite  = $this->getAttribute('prefixurlsite', 'internal');
      // Obtem a versao principal do Joomla que deve ser carregada (se aplica somente qnd nao deve usar em todas versoes)
      $majorVersionJoomlaAccept   = $this->getAttribute('majorVersionJoomlaAccept');

      // Versao principal do Joomla (ex: '3', '4')
      $majorVersionJoomla = substr(JVERSION, 0, 1);

      // Definido que arquivo soh deve ser carregado em uma das versoes (3 ou 4) e essa versao eh diferente da atual
      if(!empty($majorVersionJoomlaAccept) && ($majorVersionJoomlaAccept != $majorVersionJoomla)){
        return;
      }

      if($fileType == 'jquery'){
        $wa->useScript('jquery');

        // Garante que o jquery antigo carregado indevidamente pelo plugin 'System - Web357 Framework' nao seja carregado na pagina
        unset(Factory::getDocument()->_scripts['https://code.jquery.com/jquery-3.6.0.min.js']);

        return;
      }

      // Parametros file não informado
      if (!isset($file) || $file==''){
        return false;
      }

      // Concatenar url do site na requisicao
      if ($prefixUrlSite == 'internal'){
        // Requisicao eh de JS
        if($fileType=='js'){
            // Adiciona constantes padroes do JS
            NbJsConstantsUtil::addConstantsDefault();
        }

        $file = Uri::root() . $file;

        $input = Factory::getApplication()->input;

        // Obtem a versao da extensao
        $extensionsVersion = $input->get('nbExtensionVersion');

        // Adiciona a versao da extensao no final da url para controle de cache
        if(!empty($extensionsVersion)){
            $file .= "?v={$extensionsVersion}";
        }
      }

      // Requiseção CSS
      if ($fileType=='css'){
        $wa->registerAndUseStyle('nobossrequestjscss', $file);
      }
      // Requisição JS
      else if ($fileType=='js'){
        $wa->registerAndUseScript('nobossrequestjscss', $file);
      }
      // Parametro fileType não definido ou definido incorretamente
      else{
        return false;
      }
    }
}
