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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbApiConnectionField extends FormField {

    protected function getLabel(){
        return '';
    }
    
  	protected function getInput(){
        // Inicia quebrando a div para nao ter o espaco do label na tela
        $html = "</div><div style='margin-top: 0px;'>";
        
        $app = Factory::getApplication();
		$post = $app->input->post;
        $wa = $app->getDocument()->getWebAssetManager();

        $wa->addInline("script", "var baseNameUrl = '".Uri::root()."';", array('name' => 'baseNameUrl'));

        // Obtem informacao se modal deve ser bloqueada por causa da licenca (se definido, bloqueamos o campo)
        $blockModal = $post->get('blockModal', 0, 'INT');

        $wa->registerAndUseScript('nobossapiconnection', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossapiconnection.min.js", ['version' => '2']);
		$wa->registerAndUseStyle('nobossapiconnection', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossapiconnection.min.css");

        $decodedValue = json_decode($this->value);

        if(empty($decodedValue->client_id)){
            $decodedValue = new \stdClass();
            $decodedValue->client_id = '';
            $decodedValue->client_secret = '';
        }

        // Obtem o alias da api que deseja requisicao
        $api = $this->element->attributes()->api;

        // Url prepara login
        $urlLogin = Uri::root()."administrator/index.php?option=com_nobossajax&library=noboss.src.Form.Field.Nbapiconnection.Nbapiconnectionhelper&method=generateToken&api={$api}&format=raw";

        // Gera a url de redirecionamento para o proxy NbCallbackProxy
        $baseUrl = Uri::root(); // Ex.: https://www.nobossextensions.com/
        $urlReturn = $baseUrl . 'libraries/noboss/src/Form/Field/Nbapiconnection/NbCallbackProxy.php';

        switch ($api) {
            case 'googlecalendar':
                $urlDoc = "https://wiki.nobossextensions.com/no-boss-calendar/google-calendar-credentials";
                break;
            
            default:
                $urlDoc = "https://docs.nobosstechnology.com/api/external-credentials/#{$api}";
                break;
        }

        $html .='<div class="control-group '.$this->class.'" style="display: inherit;">
                    '.Text::sprintf("LIB_NOBOSS_API_CONNECTION_INTRO_DOC", $urlDoc).'<br />
                    '.Text::sprintf("LIB_NOBOSS_API_CONNECTION_INTRO_URL_REDIRECT", $urlDoc).'<br />
                    <span class="apiconnection__doc-url">'.$urlReturn.'</span>
                </div>';
                
        // Declara div mais externa colocando como attr o alias da api requisitada e a url de redirecionamento
        $html .= '<div class="nobossapiconnection form-grid" data-api="'.$api.'" data-api-redirect="'.$urlLogin.'">';

        $classJ4 = 'stack span-3';

        // Cliente ID
        $html .= "<div class='control-group {$classJ4}'>
                    <div class='control-label'>
                        <label>".Text::_('LIB_NOBOSS_API_CONNECTION_CLIENT_ID_LABEL')."</label>
                    </div>
                    <div class='controls'>
                        <input type='text' class='form-control' value='{$decodedValue->client_id}' data-id='client_id' ".(($blockModal) ? 'disabled' : '')." />
                    </div>
                </div>";

        // Secret
        $html .= "<div class='control-group {$classJ4}'>
                    <div class='control-label'>
                        <label>".Text::_('LIB_NOBOSS_API_CONNECTION_CLIENT_SECRECT_LABEL')."</label>
                    </div>
                    <div class='controls'>
                        <input type='text' class='form-control' value='{$decodedValue->client_secret}' data-id='client_secret' ".(($blockModal) ? 'disabled' : '')." />
                    </div>
                </div>";

        if(!$blockModal){
            // Armazena os dados da conexao (chave, secret, tokens)
            $html .= "<input type='hidden' name='{$this->name}' data-id='apiconnection_hidden' value='{$this->value}' />";
        }

        // Exibe informacao sobre o status (conteudo atualizado via JS)
        $html .= ' <div data-api-status="" class="apiconnection__status">
                   '.Text::_('NOBOSS_EXTENSIONS_PUBLICATION_STATUS').': <span data-api-status-text></span>
                </div>';

        // Botao para conectar com api
        $html .= "<a class='btn btn-nb btn-primary apiconnection__btn-connect' ".((!$blockModal) ? "data-id='api-btn-connect'" : "")." ".(($blockModal) ? 'disabled' : '').">
                    <span>".Text::_('LIB_NOBOSS_API_CONNECTION_BTN_CONNECT')."</span>
                </a>";
                
        $html .= '</div>';

        // Obtem a tag do idioma que esta sendo navegado
        $currentLanguage = Factory::getLanguage()->getTag();

        $langSef = substr($currentLanguage, 0, 2);

        // Define sefLanguage caso ja nao definido
        $wa->addInline("script", "var sefLanguage = '{$langSef}';", array('name' => 'sefLanguage'));

        return $html;
    }
}
