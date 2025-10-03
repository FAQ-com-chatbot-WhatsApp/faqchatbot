<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.comgi
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\HiddenField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Form\Field\Nblicense\NblicenseModel;
use Noboss\Library\Util\NbJsConstantsUtil;
use Noboss\Library\Util\NbCurlUtil;
use Noboss\Library\Util\NbUrlUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbLicenseField extends HiddenField {

    protected function getLabel(){
        $view_license_info = (string) $this->element['view_license_info'];
        $view_license_info = $view_license_info == '' ? true : (bool)$view_license_info;
        // Caso view_license_info seja verdadeiro, esconde a label
        if($view_license_info){
            parent::getLabel();
        }
    }
    
    protected function getInput(){
        // Extensao esta sendo carregada no site demo
        if (strpos(Uri::root(), 'nobossextensions.com/demos') !== false) {
            $siteDemo = true;
        }
        else{
            $siteDemo = false;
        }

        // Extensao esta sendo carregada no site demo
        if ($siteDemo) {
            ?>
            <div data-id="license-alert-message" class="feedback-notice alert alert-info feedback-notice--info ">
                <div class="feedback-notice__content">
                    <h4 class="feedback-notice__title">Demo website</h4>
                    <p class="feedback-notice__message" data-id="license-alert-message-text">
                        License data is not displayed because you are in demo mode.
                    </p>
                </div>
            </div>
            <?php
            return;
        }

        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();

        $app = Factory::getApplication();
        $doc = Factory::getDocument();
        $wa = $app->getDocument()->getWebAssetManager();

        // Usuario esta em um modulo
        if (!empty($this->form->getData()->get('module'))){
            $extensionName = $this->form->getData()->get('module');
        }
        // Usuario esta nas configuracoes gloabais de um componente
        else if (!empty(Factory::getApplication()->input->get->get('component'))) {
            $extensionName = Factory::getApplication()->input->get->get('component');
        }
        // Usuario esta em um template
        else if (!empty($this->form->getData()->get('template'))) {
            $extensionName = $this->form->getData()->get('template');
        }
        // Usuario esta em um plugin
        else if(!empty($this->form->getData()->get('element'))){
            $extensionName = $this->form->getData()->get('element');
        }
        // Considera que usuario esta em um componente
        else {
            $formNameArray = explode('.', $this->form->getName());
            $extensionName = $formNameArray[0];
        }

        // Define variavel JS 'nbExtensionsUrl' caso ja nao esteja definido
        $wa->addInline("script", 'var nbExtensionsUrl =  "'.NbUrlUtil::getUrlNbExtensions().'";', array('name' => 'nbExtensionsUrl'));

        $html = '';
        $dataValue = '';

        // Obtem dados da licenca / extensao na base local
        $localLicenseData = NblicenseModel::getlocalLicenseData($extensionName);

        // echo '<pre>';
        // var_dump($localLicenseData);
        // exit;

        // Cria propriedades no contexto para uso posterior
        $token = array_key_exists("token", $localLicenseData) ? $localLicenseData['token'] : '';
        $inside_support_updates_expiration = '';
        $inside_support_technical_expiration = '';
        $state = '-1'; // status default para qnd nao sabemos o status
        $update_site_id = array_key_exists("update_site_id", $localLicenseData) ? $localLicenseData['update_site_id'] : '';

        $view_license_info = (string) $this->element['view_license_info'];
        $modal_display_messages = (string) $this->element['modal_display_messages'];
        $modal_display_notice_license = (string) $this->element['modal_display_notice_license'];
        
        // Cria valores default
        $view_license_info = $view_license_info == '' ? true : (bool)$view_license_info;
        $modal_display_messages = $modal_display_messages == '' ? true : (bool)$modal_display_messages;
        $modal_display_notice_license = $modal_display_notice_license == '' ? true : (bool)$modal_display_notice_license;

        $flags = new \stdClass();
        $flags->modal_display_messages = $modal_display_messages;
        $flags->modal_display_notice_license = $modal_display_notice_license;

        // Token definido
        if(!empty($token)){
            // Busca as informações da licença, mandando o token da licenca
            $licenseInfo = $this->getLicenseInfo($token, $modal_display_messages, $localLicenseData['installed_version'], $localLicenseData['last_version']);

            // Tenta decodificar dados retornados (caso nao esteja em branco)
            $licenseInfoData = json_decode($licenseInfo->data);

            // echo '<pre>';
            // var_dump($licenseInfoData);
            // exit;

            // Ocorreu um erro na requisicao
            if (empty($licenseInfo->data) || (!$licenseInfo->success)){
                // Nao ha mensagem definida para o erro da requisao: define msg generica
                if(empty($licenseInfo->message)){
                    //FIXME: cliente esta caindo aqui. Essa mensagem nao esta fazendo sentido. mudar ou excluir.
                    $licenseInfo->message = "The server's IP address could not be found or the data could not be retrieved from the database.";
                }

                // url que permite ver o erro em simulacao de teste
                $urlTest = Uri::root()."?option=com_nobossajax&library=noboss.src.Util.NbSupportUtil&method=curlTest";

                // Exibe mensagem na aba licenca com detalhes do erro
                echo  "<div class='alert alert-error' style='max-width: 800px;'><span class='icon-joomla icon-info'></span>".Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_INITIAL_CONNECTION_ERROR_DESC').Text::sprintf('LIB_NOBOSS_ERROR_DETAILS_TITLE', $licenseInfo->message)."<br /><br />".Text::_('LIB_NOBOSS_UTIL_CURL_ERROR_MSG_LINK_TEST')." <br /> <a href='{$urlTest}' target='_blank'>{$urlTest}</a></div>";

                // Define retorno a enviar para JS
                $dataValue = 'CONNECTION_ERROR';
            }
            else{
                // Token invalido
                if($licenseInfo->data == 'INVALID_TOKEN'){
                    $dataValue = 'INVALID_TOKEN';
                }
                // Informacoes foram obtidas do servidor
                else if(!empty($licenseInfoData)){
                    $state = $licenseInfoData->state;

                    // Adiciona constantes para o js com replaces
                    NbJsConstantsUtil::sprintfJS('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UNAUTHORIZED_URL_DESC', $licenseInfoData->authorized_url, NbUrlUtil::getUrlNbExtensions(), $licenseInfoData->id_license);
                   
                    // CONTROLE DE PIRATARIA: email utilizado na conta vinculada com a licenca eh alguma conta demo (usuario esta possivelmente usando uma conta demo em um site dele)
                    if (!empty(array_filter(array('contato@noboss.com.br', 'margane@nobossextensions.com', 'contato1@noboss.com.br'), fn($pattern) => strpos($licenseInfoData->responsible_email, $pattern) !== false))) {
                        $dataValue = 'PIRATE_LICENSE';
                    }

                    // Licenca esta despublicada
                    else if($state === '0'){
                        $dataValue = 'INACTIVE_LICENSE';
                    }
                    // Licenca publicada
                    else{
                        $inside_support_updates_expiration = $licenseInfoData->inside_support_updates_expiration;
                        $inside_support_technical_expiration = $licenseInfoData->inside_support_technical_expiration;
                        $updates_near_to_expire = $licenseInfoData->days_to_expire_support_updates < 7 && $licenseInfoData->days_to_expire_support_updates > 0;
                        $has_parent_license = !empty($licenseInfoData->id_parent_license);
                        $licenseInfoData->siteUrl = base64_encode(str_replace(array('https://www.', 'http://www.', 'https://', 'http://'), '', Uri::root()));
                        $licenseInfoData->view_license_info = $view_license_info;
                        $licenseInfoData->authorized_url = $licenseInfoData->authorized_url;
                        $license_has_errors = !$inside_support_updates_expiration || !$licenseInfoData->state || !$licenseInfoData->isAuthorizedUrl;
                        $flags->license_has_errors = $license_has_errors;
                        $flags->has_parent_license = $has_parent_license;
                        $licenseInfoData->jversion = JVERSION;

                        // Url de instalacao / update da extensao
                        $licenseInfoData->url_installation =  NbUrlUtil::getUrlNbExtensions().'/installation/'.$licenseInfoData->repository_folder.'/'.$licenseInfoData->token;
                        
                        // Dados da licenca que poderao ser salvos no banco para recuperar qnd comunicacao nao funcionar com servidor
                        $objLicenseSave = new \stdClass();
                        $objLicenseSave->themes_alias = $licenseInfoData->themes_alias;
                        $objLicenseSave->fields_block = $licenseInfoData->fields_block;
                        $objLicenseSave->loadmode_alias = $licenseInfoData->loadmode_alias;
                        $objLicenseSave->isAuthorizedUrl = $licenseInfoData->isAuthorizedUrl;
                        // Converte o obejto para json e adiciona no value do campo para passar depois no input hidden
                        $this->value = json_encode($objLicenseSave);

                        // Salva numa em uma variavel para passar para o js depois
                        $dataValue = $licenseInfoData;
                        // Verifica se deve exibir as informações da licença
                        if($view_license_info){
                            // Inclui o html da modal de tema, escondida
                            ob_start();
                            require("Nblicense/NblicenseLayout.php");
                            $html .= ob_get_clean();
                        }
                    }          
                }
            }
        } else {
            $dataValue = 'TOKEN_OR_PLAN_NOT_FOUND';
        }

        // Adiciona as variáveis ao js
        Factory::getDocument()->addScriptOptions('nobosslicense', array(
            'data' => $dataValue,
            'flags' => $flags
        ));

        $html .= "<input type='hidden' id='license_token' name='license_token' value='{$token}'>";
        $html .= "<input type='hidden' id='license_update_support_period' name='license_update_support_period' value='{$inside_support_updates_expiration}'>";
        // Campo para atualizar o plano no banco depois de um update
        $html .= "<input type='hidden' id='update_site_id' name='update_site_id' value='{>update_site_id}'>";
        // Campo para manter armazenado no banco dados mais importantes da licenca para qnd falhar comunicacao
        $html .= "<input type='hidden' name='{$this->name}' value='{$this->value}'>";

        // Campo que informa se estamos no gerenciador de modulos com_advancedmodules
        $advancedmodules = (Factory::getApplication()->input->get->get('option') == 'com_advancedmodules') ? '1' : '0';
        $html .= "<input type='hidden' name='advancedmodules' value='{$advancedmodules}'>";

        
        // Adiciona constantes para o js com replaces
        NbJsConstantsUtil::sprintfJS('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UNPUBLISHED_LICENSE_DESC', Text::_('NOBOSS_EXTENSIONS_URL_SITE_CONTACT'));
        NbJsConstantsUtil::sprintfJS('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_CHANGE_LICENSE_URL_UPDATE_ERROR_DESC', NbUrlUtil::getUrlNbExtensions());

        // Adiciona constantes para o js sem replaces
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_CHANGE_LICENSE_URL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_AUTHORIZED_URL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_PIRATE_LICENSE_DESC');

        /* TODO: qnd token e/ou id do plano nao eh localizado na base local ou eh invalido, a constante abaixo eh exibida. 
                    * Podemos melhorar para ter um mini formulario que o usuario digite o ID do plano e o token para atualizar na base local.
                    * Para atualizar na base, podemos aproveitar a funcao ja existente:
                        NblicenseModel::updateUserLocalPlan($updateSiteId, $extra_query)
                    * Para conseguir o dados a serem inseridos, o usuario continuara tendo que entrar em contato para obte-los com a gente
         */
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_INVALID_TOKEN_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_INVALID_TOKEN_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UNPUBLISHED_LICENSE_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UNAUTHORIZED_URL_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_ALERT_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_CHANGE_LICENSE_URL_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_CHANGE_LICENSE_URL_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_CHANGE_LICENSE_URL_BUTTON_CONFIRM');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_CHANGE_LICENSE_URL_BUTTON_CANCEL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_CHANGE_LICENSE_URL_UPDATE_ERROR_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_CHANGE_LICENSE_URL_UPDATE_SUCESS_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_CHANGE_LICENSE_URL_UPDATE_SUCESS_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UNAUTHORIZED_URL_BUTTON_KEEP_URL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UNAUTHORIZED_URL_BUTTON_UPDATE_URL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UPGRADE_CONFIRM_ACTION_BUTTON_CANCEL');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UPGRADE_CONFIRM_ACTION_BUTTON_CONFIRM');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UPGRADE_AVAILABLE_DOWNLOAD_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UPGRADE_CONFIRM_ACTION_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UPGRADE_CONFIRM_ACTION_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UPGRADE_AVAILABLE_DOWNLOAD_NOW_BUTTON');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UPGRADE_AVAILABLE_DOWNLOAD_LATER_BUTTON');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_INITIAL_CONNECTION_ERROR_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_INITIAL_CONNECTION_ERROR_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_INSTALL_NEW_CONFIRM_ACTION_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_INSTALL_NEW_CONFIRM_ACTION_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_INSTALL_NEW_CONFIRM_ACTION_BUTTON_CONFIRM');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_EXPIRED_LICENSE_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_EXPIRED_LICENSE_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_EXPIRED_LICENSE_CLOSE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_EXPIRED_LICENSE_RENEW');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_EXPIRING_LICENSE_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_EXPIRING_LICENSE_DESC');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_EXPIRING_LICENSE_CLOSE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_EXPIRING_LICENSE_RENEW');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_ALERT_HAS_ERRORS_MODULE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_ALERT_HAS_ERRORS_MODULE_LINK');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_ALERT_HAS_ERRORS_COMPONENT');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_ALERT_HAS_ERRORS_COMPONENT_LINK');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_ALERT_HAS_ERRORS_GLOBAL_COMPONENT');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_ALERT_HAS_ERRORS_GLOBAL_COMPONENT_LINK');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_PAGE_REFRESH_ALERT_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_PAGE_REFRESH_ALERT_DESC');
        Text::script('LIB_NOBOSS_BLOCK_FIELD_SIDE_FIELD');
        Text::script('LIB_NOBOSS_BLOCK_FIELD_MODAL_COMPLETE_SIDE_FIELD');
        Text::script('LIB_NOBOSS_BLOCK_FIELD_SUBFORM_HEADER');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_REINSTALL_LINK');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_REINSTALL_CONFIRM_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_REINSTALL_CONFIRM_CONTENT');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_REINSTALL_MODAL_SUCCESS_TITLE');
        Text::script('LIB_NOBOSS_FIELD_NOBOSSLICENSE_REINSTALL_MODAL_SUCCESS_CONTENT');
        Text::script('NOBOSS_EXTENSIONS_GLOBAL_BT_CANCEL');
        Text::script('NOBOSS_EXTENSIONS_GLOBAL_BT_UPDATE');

        if(!empty($licenseInfoData->url_installation)){
            // Adiciona constantes para o js com replaces
            //NbJsConstantsUtil::sprintfJS('LIB_NOBOSS_FIELD_NOBOSSLICENSE_MODAL_UPGRADE_AVAILABLE_DOWNLOAD_DESC', $licenseInfoData->url_installation, Uri::base().'index.php?option=com_installer&view=install');
        }

        // Carrega os js e css
        $wa->registerAndUseStyle('nobosslicense', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobosslicense.min.css");
        $wa->registerAndUseScript('nobosslicense', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobosslicense.min.js");      

        // retorna o html do campo
        return $html;
    }

    /**
     * Busca através de requisição as informações relacionadas a uma determinada licença
     *
     * @param String $extensionToken Token da licença que será buscado
     * @param Boolean $modalDisplayMessages Flag que informa se deve trazar as mensagens personalizadas da licença
     * 
     * @return Object Retorna um objeto com as informações da licença e o array de mensagens
     */
    private function getLicenseInfo($extensionToken,  $modalDisplayMessages, $installedVersion, $lastVersion){
        // Url requisicao
        $url = NbUrlUtil::getUrlNbExtensions().'/index.php?option=com_nbextensoes&task=externallicenses.getLicenseInfo&format=raw';

        // Obtem dominio da url atual
        $siteUrl = str_replace(array('https://www.', 'http://www.', 'https://', 'http://'), '', Uri::root());
       
        // Identifica o idioma que esta sendo navegado para enviar junto na requisicao
        $currentLanguage = Factory::getLanguage()->getTag();
       
        // Prepara dados a enviar via post
        $dataPost = array('token' => $extensionToken, 'modal_display_message' => $modalDisplayMessages, 'site_url' => $siteUrl, 'language' => $currentLanguage, 'jversion' => JVERSION, 'installed_version' => $installedVersion, 'last_version' => $lastVersion);

        // Realiza a requisição
        $tokenInfo = NbCurlUtil::request("GET", $url, $dataPost, null, 20);

        return $tokenInfo;
    }
}
