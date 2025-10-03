<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Util;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbUrlUtil;

 // phpcs:disable PSR1.Files.SideEffects
 \defined('_JEXEC') or die;
 // phpcs:enable PSR1.Files.SideEffects

class NbJsConstantsUtil {

    // Funcao que adiciona variaveis uteis e padroes no site via JS
    public static function addConstantsDefault(){
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();
        $doc = Factory::getDocument();

        // Define variavel JS 'basenameUrl' caso ja nao esteja definido
        $wa->addInline("script", "var baseNameUrl = '".Uri::root()."';", array('name' => 'baseNameUrl'));

        // Define variavel JS 'nbExtensionsUrl' caso ja nao esteja definido
        $wa->addInline("script", "var nbExtensionsUrl = '".NbUrlUtil::getUrlNbExtensions()."';", array('name' => 'nbExtensionsUrl'));

        // Define variavel JS 'majorVersionJoomla' (versao macro do Joomla. ex: '4') caso ja nao esteja definido
        $wa->addInline("script", "var majorVersionJoomla = '".substr(JVERSION, 0, 1)."';", array('name' => 'majorVersionJoomla'));

        // Define variavel JS 'completeVersionJoomla' (versao completa do Joomla. ex: '4.0.1') caso ja nao esteja definido
        $wa->addInline("script", "var completeVersionJoomla = '".JVERSION."';", array('name' => 'completeVersionJoomla'));

        // Obtem a tag do idioma que esta sendo navegado
        $currentLanguage = Factory::getLanguage()->getTag();

        /* TODO: modificado para pegar sef do idioma direto no idioma corrente cortando estring (ex: extraimos 'pt' de 'pt-BR')
                - Anteriormente buscavamos o sef dos idiomas de conteúdo instalados, mas isso poderia dar problema pq eles podem estar desabilitados sem que o acesso pelo idioma esteja desabilitado
        */
        // $languages = LanguageHelper::getLanguages('lang_code');
        // $langSef = $languages[$currentLanguage];
        // $langSef = $langSef->sef;
        $langSef = substr($currentLanguage, 0, 2);

        // Define sefLanguage caso ja nao definido
        $wa->addInline("script", "var sefLanguage = '{$langSef}';", array('name' => 'sefLanguage'));
    }

    /**
     * Funcao que executa solucao de sprintf do Joomla, mas colocando a variavel no JS
     * Para constante normal sem replace temos o Text::script, mas essa funcao resolve os casos em que temos replace
     * 
     * @param   string  $string  The format string.
     */
    public static function sprintfJS($string){
        $doc = Factory::getDocument();

        // Obtem todos os argumentos de parametros enviados na funcao
        $args  = \func_get_args();

        $args[0] = Text::_($args[0]);

        // Obtem array com todas variaveis ja adicionadas no Joomla.Text._
        $strings = $doc->getScriptOptions('joomla.jtext');

        // Executa funcao Text::sprintf do joomla passando os parametros recebidos na funcao atual
        $strings[$string] = \call_user_func_array("sprintf", $args);

        // Update Joomla.Text script options
        Factory::getDocument()->addScriptOptions('joomla.jtext', $strings, false);
    }
}
