<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nblicense;

use Noboss\Library\Form\Field\Nblicense\NblicenseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Installer\Installer;
use Noboss\Library\Util\NbCurlUtil;
use Noboss\Library\Util\NbUrlUtil;

defined('_JEXEC') or die;

/**
 * Classe de campo personalizado para gerenciamento de licenças de extensoes (arquivo executado a partir de JS)
 */
class NblicenseHelper {

    /**
	 * Faz a requisição para o servidor principal para upgrade da extensao
	 */
	public static function upgradeLicensePlan() {
        $app = Factory::getApplication();
        $post = $app->input->post;
        // Instancia objete de linguagem
        $lang = Factory::getLanguage();
        // Carrega arquivo tradução da library no boss
        $lang->load('lib_noboss', JPATH_SITE.'/libraries/noboss');
        // Token da extensão
        $token = $post->post->get('token');
        // Id da coluda de update, usada para atualizar o plano depois de um update com sucesso
        $updateSiteId = $post->get('update_site_id');
        // Monta a url para onde será feita a requisição de validação do token
        $urlTokenValidate = NbUrlUtil::getUrlNbExtensions()."/index.php?option=com_nbextensoes&task=externallicenses.validateLicenseUpgrade&format=raw&token={$token}";
        // Dados que serão usados na querystring da requisição get
        $dataPost = array('token' => $token);
        // Simulacao do navegador
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';
        // Monta a url para onde será feita a requisição de download
        $urlDownload = NbUrlUtil::getUrlNbExtensions()."/upgrade/extension";
        // Faz uma requisição para validar o token
        $isValidToken = NbCurlUtil::request('GET', $urlTokenValidate, $dataPost, null, 20, $userAgent);
        $isValidToken->data = json_decode($isValidToken->data);
        $isValidToken->data = $isValidToken->data->tokenInfo;
        // Verifica se a requisição teve sucesso
        if(!$isValidToken->success){
            exit(json_encode($isValidToken));
        }
        // Verifica se o token é valido
        if (empty($isValidToken->data)) {
            $isValidToken->success = 0;
            $isValidToken->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_INVALID_TOKEN');
            exit(json_encode($isValidToken));
        }
        
        // Faz download do pacote para upgrade
        try{
            $fileName = InstallerHelper::downloadPackage($urlDownload."?token={$token}", uniqid('upgrade_').'.zip');
            if(!$fileName){
                throw new Exception();
            }
        } catch (\Exception $e) {
            $isValidToken->success = 0;
            $isValidToken->message = Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_MAIN_ERROR', NbUrlUtil::getUrlNbExtensions(), Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_DOWNLOAD_ERROR'));
            exit(json_encode($isValidToken));
        }

        // Unzipa o zip para uma pasta temporária
        try{
            $folderPath = InstallerHelper::unpack(Factory::getConfig()->get('tmp_path').'/'.$fileName, true);
            if(!$folderPath){
                throw new Exception();
            }
        } catch (\Exception $e) {
            $isValidToken->success = 0;
            $isValidToken->message = Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_MAIN_ERROR', NbUrlUtil::getUrlNbExtensions(), Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_UNZIP_ERROR'));
            exit(json_encode($isValidToken));
        }
        // Realiza o update da extensão
        try{
            $tmpInstaller = new Installer();
            $updateResult = $tmpInstaller->update($folderPath['extractdir']);
            if(!$updateResult){
                throw new Exception();
            }
        } catch(\Exception $e) {
            $isValidToken->success = 0;
            $isValidToken->message = Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_MAIN_ERROR', NbUrlUtil::getUrlNbExtensions(), Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_UPDATE_ERROR'));
            exit(json_encode($isValidToken));
        }

        if(!empty($isValidToken->data->token))){
            // Monta um array com as parametros para atualizar a coluna extra_query da tabela #__update_sites
            $extra_query = array('token' => $isValidToken->data->token);
    
            // Atualiza o plano no banco de dados e valida para saber se não deu erro
            if(!NblicenseModel::updateUserLocalPlan($updateSiteId, $extra_query)){
                $isValidToken->success = 0;
                $isValidToken->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_UPDATE_LOCAL_PLAN_ERROR');
                exit(json_encode($isValidToken));
            }
        }

        // Executa requisicao para informar no banco de dados da No Boss que extensao foi atualizada neste site
        $siteUrl = str_replace(array('https://www.', 'http://www.', 'https://', 'http://'), '', Uri::root());
        $url = NbUrlUtil::getUrlNbExtensions().'/index.php?option=com_nbextensoes&task=externallicenses.defineWebsiteUpdated&format=raw';
        $dataPost = array('token' => $isValidToken->data->token, 'url' => base64_encode($siteUrl));
        NbCurlUtil::request('GET', $url, $dataPost, null, 20, $userAgent);

        // Deu tudo certo
        $isValidToken->success = 1;
        $isValidToken->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_SUCCESS');
        exit(json_encode($isValidToken));
    }
    
    /**
	 * Faz a requisição para o servidor principal para instalação de uma nova extensão
	 */
	public static function installNewExtension() {
        $app = Factory::getApplication();
        $post = $app->input->post;
        // Instancia objete de linguagem
        $lang = Factory::getLanguage();
        // Carrega arquivo tradução da library no boss
        $lang->load('lib_noboss', JPATH_SITE.'/libraries/noboss');
        // Token da extensão
        $installUrl = $post->post->get('newExtUrl', array());

        $response = new \stdClass();

        // Percorre cada url
        foreach ($installUrl as $url) {
           $url = base64_decode($url);
            // Faz download do pacote para upgrade
            try{
                $fileName = InstallerHelper::downloadPackage($url, uniqid('upgrade_').'.zip');
                if(!$fileName){
                    throw new Exception();
                }
            } catch (\Exception $e) {
                $response->success = 0;
                $response->message = Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_MAIN_ERROR', NbUrlUtil::getUrlNbExtensions(), Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_DOWNLOAD_ERROR'));
                exit(json_encode($response));
            }

            // Unzipa o zip para uma pasta temporária
            try{
                $folderPath = InstallerHelper::unpack(Factory::getConfig()->get('tmp_path').'/'.$fileName, true);
                if(!$folderPath){
                    throw new Exception();
                }
            } catch (\Exception $e) {
                $response->success = 0;
                $response->message = Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_MAIN_ERROR', NbUrlUtil::getUrlNbExtensions(), Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_UNZIP_ERROR'));
                exit(json_encode($response));
            }
            // Realiza o update da extensão
            try{
                $tmpInstaller = new Installer();
                $updateResult = $tmpInstaller->install($folderPath['extractdir']);
                if(!$updateResult){
                    throw new Exception();
                }
            } catch(\Exception $e) {
                $response->success = 0;
                $response->message = Text::sprintf('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_MAIN_ERROR', NbUrlUtil::getUrlNbExtensions(), Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_UPDATE_ERROR'));
                exit(json_encode($response));
            }
        }
        // Deu tudo certo
        $response->success = 1;
        $response->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_UPGRADE_PLAN_SUCCESS');
        exit(json_encode($response));
    }

    /**
	 * Reinstala extensao
	 */
	public static function reinstallExtension() {
        $app = Factory::getApplication();
        $post = $app->input->post;
        // Instancia objete de linguagem
        $lang = Factory::getLanguage();
        // Carrega arquivo tradução da library no boss
        $lang->load('lib_noboss', JPATH_SITE.'/libraries/noboss');
        // Token da extensão
        $installUrl = $post->post->get('url_install', '', 'STRING');
        
        $response = new \stdClass();
        
        // JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models');
        // $modelInstaller = JModelLegacy::getInstance('install', 'InstallerModel');
        $modelInstaller = $app->bootComponent('com_installer')->getMVCFactory($app)->createModel('Install', 'Administrator');

        $input = Factory::getApplication()->input;
        // Seta para utilizar metodo de instalacao via url
        $input->set('installtype', 'url');
        // Seta a url de instalacao
        $input->set('install_url', $installUrl);
        // Executa metodo de instalacao
        $modelInstaller->install();

        exit(1);
    }
}
