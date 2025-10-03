<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Script de instalação do pacote de mais de uma extensao de um mesmo produto (ex: FAQ que possui modulo e componente)
 * - Esse script é executado antes do script de instalacao de cada extensao do produto
 * 
 * - Formatos para retornar mensagens de erro / warning:
 *     // Mensagem de erro (fundo vermelho), sem interromper a instalacao
 *     JFactory::getApplication()->enqueueMessage(JText::_("Aqui mensagem"), 'Error');
 *           
 *     // Mensagem de warning (fundo amarelo), sem interromper a instalacao
 *     JFactory::getApplication()->enqueueMessage(JText::_("Aqui mensagem"), 'Warning');
 *           
 *     // Mensagem de warning (fundo amarelo), interrompendo a instalacao (Joomla tb exibe mensagem de erro da instalacao)
 *     throw new RuntimeException(JText::_("Aqui mensagem"));
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
}
// Library ainda esta no formato antigo sem pasta /src
else if(is_dir(JPATH_LIBRARIES.'/noboss/util')){
    require_once JPATH_LIBRARIES."/noboss/util/installscript.php";
}
class pkg_NobossfaqInstallerScript{
    /**
     * Evento executado antes de qualquer outro no processo de instalacao / update
     *  - Esse é o momento que a instalacao / update pode ser cancelado
     *  - Essa funcao eh executada antes da preflight do script de instalacao de cada extensao do pacote
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
     * Metodo executado apos a instalacao
     *  - Aqui podemos exibir textos fora de notices / warnings utilizanco 'echo' ou html direto
     * 
     * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
	function install($parent) {
	}
    
    /**
     * Metodo executado apos a atualizacao
     * - Aqui podemos exibir textos fora de notices / warnings utilizanco 'echo' ou html direto
     * - Utilizar tambem para colocar atualizacoes especificas de versoes (Ex: migrar dado no banco de estrutura antiga para nova)
     * - Para qual versao foi instalada (usar em alguma condicao), utilize $parent->get('manifest')->version
     * 
     * @param   JInstaller $parent      Classe que chama esse metodo (pode acessar funcoes dela)
     */
	function update($parent) {
    }

    /**
     * Método  de desistalação  da  extensão
     *
     * @param   \JInstallerAdapterPackage   $parent é a classe chamando esse método
     *
     * @return void
     */
    function uninstall($parent){
    }
}
