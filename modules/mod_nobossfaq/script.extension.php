<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\Filesystem\File;
use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Script de instalação da library utilizada por todas extensoes da No Boss
 * - Esse script normalmente é executado após o script de instalacao do pacote
 * 
 * - Formatos para retornar mensagens de erro / warning:
 *     // Mensagem de erro (fundo vermelho), sem interromper a instalacao'
 *     Factory::getApplication()->enqueueMessage(Text::_("Aqui mensagem"), 'Error');
 *           
 *     // Mensagem de warning (fundo amarelo), sem interromper a instalacao
 *     Factory::getApplication()->enqueueMessage(Text::_("Aqui mensagem"), 'Warning');
 *           
 *     // Mensagem de warning (fundo amarelo), interrompendo a instalacao (Joomla tb exibe mensagem de erro da instalacao)
 *     throw new RuntimeException(Text::_("Aqui mensagem"));
 *     return false;
 * 
 * 
 * - Exemplos de usos do $parent que é recebido como parametro nas funções:
 *     // Obter nova versao recem instalada
 *     $parent->get('manifest')->version;
 * 
 *     // Obter alias da extensao (Ex: 'mod_nobosscalendar)
 *     $parent->manifest->name;
 *       
 *     // Redirecionar usuario apos a instalacao (interessante usar no metodo install na instalacao de componentes nossos para redirecionar o usuario para a pagina principal do componente apos a instalacao)
 *     $parent->getParent()->setRedirectURL('index.php?option=com_helloworld');*
 *
 * 
 * - Sobre uso de constantes de tradução
 *    - Se você deseja que as KEYs desses idiomas sejam usadas na primeira instalação do componente, o arquivo de idioma .sys.ini deve ser armazenado na pasta do componente (admin/language/en-GB/en-GB.com_helloworld.sys.ini)
 *    - Quando ja temos a library instalada, podemos forcar o carregamento do idioma da library e usar as constantes dela
 */

// TODO: tratamento necessario pq usuario pode estar com versao antiga (sem /src) da library e nestes casos geraria erro
// Library encontrada no novo formato j4 / j5
if(is_dir(JPATH_LIBRARIES.'/noboss/src')){
    require_once JPATH_LIBRARIES."/noboss/src/Util/NbInstallScriptUtil.php";
    require_once JPATH_LIBRARIES."/noboss/src/Util/NbCurlUtil.php"; // Esse eh necessario pq eh usado dentro da classe NbInstallScriptUtil
    require_once JPATH_LIBRARIES."/noboss/src/Form/Field/Nblicense/NblicenseModel.php";
}
// Library ainda esta no formato antigo sem pasta /src
else if(is_dir(JPATH_LIBRARIES.'/noboss/util')){
    require_once JPATH_LIBRARIES."/noboss/util/installscript.php";
    require_once JPATH_LIBRARIES."/noboss/forms/fields/nobosslicense/nobosslicensemodel.php";
}

class mod_NobossfaqInstallerScript{

    /**
     * Evento executado antes de qualquer outro no processo de instalacao / update
     *  - Esse é o momento que a instalacao / update pode ser cancelado
     *  - Antes dessa funcao, apenas o preflight do script de instalacao do pacote é executado
     *
     * @param   string     $type        Tipo de intalações (install, update, discover_install)
     * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     *
     * @return  boolean  true caso deva ser instalada, false para cancelar a instalação
     */
    function preflight($type, $parent){
        if($type == 'uninstall'){
            return;
        }

        // Library nao foi instalado
        if(!is_dir(JPATH_LIBRARIES.'/noboss')) {
            throw new RuntimeException('In order to install the extension, first install No Boss Library using the url https://www.nobossextensions.com/en/installation/nobosslibrary', 500);
        }
        // No Boss Ajax nao foi instalado
        else if(!is_dir(JPATH_ROOT.'/components/com_nobossajax/')){
            throw new RuntimeException('In order to install the extension, first install No Boss Ajax using the url https://www.nobossextensions.com/en/installation/nobossajax', 500);
        }

        // Arquivo do zip de instalacao / update com o token da licenca
        $extraInfoPath = __DIR__ . '/extra-info.json';

        // Confirma se arquivo existe no zip de instalacao
        if(is_file($extraInfoPath)){
            // Lê o conteudo do json que contem o token
            $this->extraInfo = json_decode(file_get_contents($extraInfoPath), true);
            // Monta um array apenas com as informações que serão salvas na coluna extra_query do banco
            $extraQueryArray = array('token' => $this->extraInfo['token']);

            // Library encontrada no novo formato j4 / j5
            if(is_dir(JPATH_LIBRARIES.'/noboss/src')){
                // Salva o token na coluna extra_query do banco
                Noboss\Library\Util\NbInstallScriptUtil::updateExtraQuery($parent, http_build_query($extraQueryArray));
            }
            // Library ainda esta no formato antigo sem pasta /src
            else if(is_dir(JPATH_LIBRARIES.'/noboss/util')){
                // Salva o token na coluna extra_query do banco
                NoBossUtilInstallscript::updateExtraQuery($parent, http_build_query($extraQueryArray));
            }
        }
    }

    /**
     * Metodo executado após o término da instalação / update
     * 
     * @param   string     $type        Tipo de intalações (install, update, discover_install)
     * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
    public function postflight($type, $parent) {
        if($type == 'uninstall'){
            return;
        }

        $extensionName = $parent->manifest->name;

        // Library encontrada no novo formato j4 / j5
        if(is_dir(JPATH_LIBRARIES.'/noboss/src')){
            $tokenArray = Noboss\Library\Form\Field\Nblicense\NblicenseModel::getLicenseTokenAndPlan($extensionName);
            $token = array_key_exists("token", $tokenArray) ? $tokenArray['token'] : '';

            // Salva a url autorizada
            // TODO: comentado para evitar erros com curl (campo licenca depois ira salvar a url)
            // Noboss\Library\Util\NbInstallScriptUtil::saveAuthorizedUrl($token);
        }
        // Library ainda esta no formato antigo sem pasta /src
        else if(is_dir(JPATH_LIBRARIES.'/noboss/util')){
            $tokenArray = NobossModelNobosslicense::getLicenseTokenAndPlan($extensionName);
            $token = array_key_exists("token", $tokenArray) ? $tokenArray['token'] : '';

            // Salva a url autorizada
            // TODO: comentado para evitar erros com curl (campo licenca depois ira salvar a url)
            // NoBossUtilInstallscript::saveAuthorizedUrl($token);
        }

        // Eh Update
        if($type == 'update'){
            // Library encontrada no novo formato j4 / j5
            if(is_dir(JPATH_LIBRARIES.'/noboss/src')){
                // Verifica se a library esta na lista de atualizacoes pendentes
                $libUpdateId = Noboss\Library\Util\NbInstallScriptUtil::getLibraryUpdateId($parent);
            }
            // Library ainda esta no formato antigo sem pasta /src
            else if(is_dir(JPATH_LIBRARIES.'/noboss/util')){
                // Verifica se a library esta na lista de atualizacoes pendentes
                $libUpdateId = NoBossUtilInstallscript::getLibraryUpdateId($parent);
            }

            // Library precisa ser atualizada
            if(!empty($libUpdateId)){
                // Diretorios da library estao sem permissao de escrita
                if(!Joomla\Filesystem\Path::canChmod(JPATH_SITE.'/libraries/noboss/') || !Joomla\Filesystem\Path::canChmod(JPATH_SITE.'/layouts/noboss/')){
                    // Seta mensagem a ser exibida orientando sobre o problema
                    $urlLibrary = 'https://www.nobossextensions.com/installation/nobosslibrary';
                    $urlDocPermission = 'https://docs.nobosstechnology.com/extensoes-joomla/ajustando-permissoes-de-diretorios-e-arquivos-no-joomla';
                    $message = "It was not possible to update No Boss Library (library used by the extension) due to lack of permissions in the '/libraries/noboss/' and '/layouts/noboss/' directories. <br> <br> Correct the permissions of the directories and after updating the library on the page of <a href='index.php?option=com_installer&view=update' target='_blank'>extension updates</a>. It is also possible to update by installing the library again from the url <a href='{$urlLibrary}' target='_blank'>{$urlLibrary}</a>. <br> <br> For information on how to fix permissions, access the <a href='{$urlDocPermission}' target='_blank'>documentation on setting Joomla file and directory permissions</a>.";

                    // Exibe mensagem como warning (fundo amarelo)
                    Factory::getApplication()->enqueueMessage($message, 'Warning');
                }
                // Realiza atualizacao da library
                else{
                    
                    // $updateModel = JModelLegacy::getInstance('update', 'InstallerModel');
                    $updateModel = Factory::getApplication()->bootComponent('com_installer')->getMVCFactory(Factory::getApplication())->createModel('Update', 'Administrator');

                    // Realiza o update da library usando o id dela
                    $updateModel->update(array($libUpdateId));
                }
            }
        }
    }
    
    /**
     * Metodo executado apos a instalacao
     *  - Aqui podemos exibir textos fora de notices / warnings utilizanco 'echo' ou html direto
     * 
     * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
	function install($parent) {
	}
    
    /**
     * Metodo executado apos a atualizacao
     * - OBS: aqui nao pega warning ou 'echo'
     * - Utilizar para colocar atualizacoes especificas de versoes (Ex: migrar dado no banco de estrutura antiga para nova)
     * - Para qual versao foi instalada (usar em alguma condicao), utilize $parent->get('manifest')->version
     * 
     * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
	function update($parent) {

        // TODO: Funcao que migra Joomla para v5
        //self::updateJoomla5();
    }

    /**
     * Método  de desistalação  da  extensão
     *
     * @param   JInstallerAdapterPackage   $parent é a classe chamando esse método
     *
     * @return void
     */
    function uninstall($parent){
        // Diretorio da 'No boss library' nao foi encontrado: nao sera possivel fazer verificacao de extensoes filhas
        if(!is_dir(JPATH_LIBRARIES.'/noboss/')) {
            return;
        }

        // Nome da extensao (ex: mod_nobosscalendar)
        $extensionName = strtolower($parent->manifest->name);
        
        // Library encontrada no novo formato j4 / j5
        if(is_dir(JPATH_LIBRARIES.'/noboss/src')){
            // Obtem token da extensao na base de dados
            $tokenArray = Noboss\Library\Form\Field\Nblicense\NblicenseModel::getLicenseTokenAndPlan($extensionName);

            $token = array_key_exists("token", $tokenArray) ? $tokenArray['token'] : '';

            if(!empty($token)){
                // Remove a url do site do usuario na plataforma da No Boss Extensions
                Noboss\Library\Util\NbInstallScriptUtil::removeUrlSite($token);
            }
        }
        // Library ainda esta no formato antigo sem pasta /src
        else if(is_dir(JPATH_LIBRARIES.'/noboss/util')){
            // Obtem token da extensao na base de dados
            $tokenArray = NobossModelNobosslicense::getLicenseTokenAndPlan($extensionName);

            $token = array_key_exists("token", $tokenArray) ? $tokenArray['token'] : '';
            
            if(!empty($token)){
                // Remove a url do site do usuario na plataforma da No Boss Extensions
                NoBossUtilInstallscript::removeUrlSite($token);
            }
        }
    }


    /*
        TODO: funcao temporaria que remove arquivos do formato antigo da extensao antes do Joomla5
                * Criado em Julho de 2024
    */
    // public static function updateJoomla5(){
    //     if(is_file(JPATH_ROOT . '/modules/mod_nobossbanners/mod_nobossbanners.php')){
    //         File::delete(JPATH_ROOT . '/modules/mod_nobossbanners/mod_nobossbanners.php');
    //     }
    //     if(is_file(JPATH_ROOT . '/modules/mod_nobossbanners/helper.php')){
    //         File::delete(JPATH_ROOT . '/modules/mod_nobossbanners/helper.php');
    //     }
    // }
}
