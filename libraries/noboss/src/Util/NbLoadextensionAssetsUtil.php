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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbLoadextensionAssetsUtil {
    
    // Variavel que armazena o nome da extensao com prefixo
    var $extensionName = '';

    // Variavel que armazena o diretorio da extensao
    var $directoryExtension = '';

    // Variavel que armazena o prefixo da extensao para códigos inline
    var $prefixCode = '';

    var $extensionsVersion = '';

    /**
     * Metodo construtor
     *
     * @param	string		$extensionName	    nome da extensao (ex: mod_nobossbanners)
     * @param	string		$prefixCode	        prefixo da extensao para codigos inline (opcional)
     */
    public function __construct($extensionName, $prefixCode = ''){
        // Seta o nome da extensao
        $this->extensionName = $extensionName;
        // Seta o prefixo da extensao
        $this->prefixCode = $prefixCode;
        // Seta a versao da extensao
        $this->extensionsVersion = self::getExtensionVersion($extensionName);
    }
    
    /**
     * Obtem a versao da extensao (registrada em banco)
     * 
     * @param	string		$extensionName	    nome da extensao (ex: mod_nobossbanners)
     * 
     * @return	mixed   String com versao ou false, caso nao encontrado
     */
    public static function getExtensionVersion($extensionName){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('manifest_cache')->from($db->quoteName('#__extensions'))->where('element = "' . $extensionName . '"');
        $db->setQuery($query);
        $result = $db->loadResult();

        if(!$result){
            return false;
        }

        $manifest = json_decode($result, true);

        if(empty($manifest) || empty($manifest['version'])){
            return false;
        }

        return $manifest['version'];
    }

    /**
     * Carrega arquivo e codigos inline JS
     *
     * @param	boolean		$loadFile				flag para informar se arquivo da extensao deve ser carregado
     * @param	array		$optionsInlineCode		dados de codigos a serem inseridos inline (opcional) - principais valores: 'prefix' e 'code'
     * @param	boolean		$loadJquery				flag para informar se a biblioteca jquery deve ser adicionada
     * @param   boolean     $loadBaseNameUrl        flag para declarar ou nao a variavel baseNameUrl
     * @param   boolean     $admin                  flag para declarar se esta carregando na area admin
     * @param   boolean     $fileName               nome do arquivo (informar somente qnd nao for o nome da extensao mesmo)
     * 
     * @return	void
     */
    public function loadJs($loadFile = true, $optionsInlineCode = array(), $loadJquery = true, $loadBaseNameUrl = true, $admin = false, $fileName = ''){
        $doc = Factory::getDocument();
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();
        
        // Defindo para chamar a variavel 'baseNameUrl'
        if ($loadBaseNameUrl){
            // Define variavel JS 'basenameUrl' caso ja nao esteja definido
            $wa->addInline("script", "var baseNameUrl = '".Uri::root()."';", array('name' => 'baseNameUrl'));

            // Define variavel JS 'majorVersionJoomla' (versao macro do Joomla. ex: '4') caso ja nao esteja definido
            $wa->addInline("script", "var majorVersionJoomla = '".substr(JVERSION, 0, 1)."';", array('name' => 'majorVersionJoomla'));

            // Define variavel JS 'completeVersionJoomla' (versao completa do Joomla. ex: '4.0.1') caso ja nao esteja definido
            $wa->addInline("script", "var completeVersionJoomla = '".JVERSION."';", array('name' => 'completeVersionJoomla'));
        }       

        // Setado para carregar jquery
        if ($loadJquery){
            // Carrega framework jquery adicionado pelo Joomla
            $wa->useScript('jquery');
        }

        // Setado para carregar o arquivo de JS da extensao
        if ($loadFile){
            // Se nome do arquivo nao foi definido, coloca nome da extensao
            if(empty($fileName)){
                $fileName = $this->extensionName;
            }

            if ($fileName == ''){
                return false;
            }

            // Obtem o diretorio da extensao desde o diretorio raiz do site
            if ($this->getDirectoryExtension()){
                // Monta a url do arquivo a ser adicionado
                $urlFile = $this->directoryExtension . "assets/".(($admin) ? 'admin' : 'site')."/js/{$fileName}.min.js";

                // Verifica se arquivo existe
                if(is_file(JPATH_ROOT. "/". (($admin) ? 'administrator/' : '') . $urlFile)){
                    // Adiciona a versao da extensao no final da url para controle de cache
                    if(!empty($this->extensionsVersion)){
                        $urlFile .= "?v={$this->extensionsVersion}";
                    }

                    // Adiciona os scripts e/ou css pertencentes a extensao
                    $wa->registerAndUseScript($this->directoryExtension.'_load', Uri::base() . $urlFile, array(), array('defer' => 'defer'));
                }
            }
        }

        if(empty($optionsInlineCode)){
            $optionsInlineCode = array();
        }

        // Prefixo nao informado como parametro: obtem o definido na classe
        $optionsInlineCode['prefix'] = !empty($optionsInlineCode['prefix'])?: $this->prefixCode;
        
        // Ha codigo inline a ser inserido
        if (!empty($optionsInlineCode['code'])){
            // Definido prefixo da extensao
            if (!empty($optionsInlineCode['prefix'])){
                // Atualiza codigo adicionando prefixo
                $optionsInlineCode['code'] = $this->addContextInJs($optionsInlineCode['code'], $optionsInlineCode['prefix']);
            }

            // Adiciona codigo inline na pagina
            $wa->addInlineScript($optionsInlineCode['code']);
        }

        // Extensao admin: aproveita e carrega JS para corrigir valores default de alguns campos
        if($admin){
            $wa->registerAndUseScript('defaultvalues', Uri::root().'libraries/noboss/assets/util/js/min/defaultvalues.min.js');
            $wa->addInlineScript('util.fixDefaultValues();');
        }
    }

    /**
     * Carrega arquivo e codigos inline CSS
     *
     * @param	boolean		$loadFile				flag para informar se arquivo da extensao deve ser carregado
     * @param	array		$optionsInlineCode		dados de codigos a serem inseridos inline (opcional) - principais valores: 'prefix' e 'code'
     * @param   boolean     $admin                  flag para declarar se esta carregando na area admin
     * @param   boolean     $fileName               nome do arquivo (informar somente qnd nao for o nome da extensao mesmo)
     *
     * @return	void
     */
    public function loadCss($loadFile = true, $optionsInlineCode = array(), $admin = false, $fileName = ''){
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Setado para carregar o arquivo de CSS da extensao
        if ($loadFile){
            // Se nome do arquivo nao foi definido, coloca nome da extensao
            if(empty($fileName)){
                $fileName = $this->extensionName;
            }

            if ($fileName == ''){
                return false;
            }
            
            // Obtem o diretorio da extensao desde o diretorio raiz do site
            if ($this->getDirectoryExtension()){
                // Monta a url do arquivo a ser adicionado
                $urlFile = $this->directoryExtension . "assets/".(($admin) ? 'admin' : 'site')."/css/{$fileName}.min.css";

                // Verifica se arquivo existe
                if(is_file(JPATH_ROOT. "/". (($admin) ? 'administrator/' : '') . $urlFile)){
                    // Adiciona a versao da extensao no final da url para controle de cache
                    if(!empty($this->extensionsVersion)){
                        $urlFile .= "?v={$this->extensionsVersion}";
                    }
    
                    // Adiciona o arquivo CSS
                    $wa->registerAndUseStyle($this->directoryExtension.'_load', Uri::base() . $urlFile);
                }
            }
        }
        
        // Ha codigo inline a ser inserido
        if (!empty($optionsInlineCode) && !empty($this->prefixCode) && !empty($optionsInlineCode['code'])){
            // Insere codigo inline, caso esteja definido
            $this->addStyleWithPrefix($optionsInlineCode['code'], $this->prefixCode);
        }

        if(empty($optionsInlineCode)){
            $optionsInlineCode = array();
        }

        // Prefixo nao informado como parametro: obtem o definido na classe
        $optionsInlineCode['prefix'] = !empty($optionsInlineCode['prefix'])?: $this->prefixCode;
        
        // Ha codigo inline a ser inserido
        if (!empty($optionsInlineCode['code'])){
            // Definido prefixo da extensao
            if (!empty($optionsInlineCode['prefix'])){
                // Adiciona codigo na pagina incluindo prefixo
                $this->addStyleWithPrefix($optionsInlineCode['code'], $optionsInlineCode['prefix']);
            }else{
                // Adiciona codigo inline na pagina sem prefixos
                $wa->addInlineStyle($optionsInlineCode['code']);
            }
        }
    }

    /**
     * Adiciona uma declaracao de estilo colocando prefixo da extensao
     *
     * @param   String 	$code 		Código CSS
     * @param   String 	$prefix 	Prefixo a ser adicionado (opcional)
     *
     * @return  void
     */
    public function addStyleWithPrefix($code, $prefix = ''){
        // Prefixo nao informado como parametro: obtem o definido na classe
        if (empty($prefix)){
           $prefix = $this->prefixCode;
        }

        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Ha codigo inline a ser inserido
        if ($code && $prefix != ''){
            // Atualiza codigo com prefixo informado
            if ($code = $this->addPrefixInCss($code, $prefix)){
                // Adiciona codigo inline na pagina
                $wa->addInlineStyle($code);
            }
        }
        else{
            return false;
        }
    }

    /**
     * Carrega arquivo css para uma familia de icones especificada
     *
     * @param	string		$aliasFamily		alias da familiar a ser adicionada
     *
     * @return	mixed       Adiciona arquivo em caso de sucesso ou retorna false em caso de erro
     */
    public static function loadFamilyIcons($aliasFamily){
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Verifica familia solicitada para definir a url
        //TODO: em julho de 2025 começamos a retirar uso externo de bibliotecas por conta da lei de proteecao de dados GDPR
        switch($aliasFamily){
            case 'font-awesome':
                $url = "https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css";
                break;
            case 'font-awesome-v6':
                //$url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css";
                $url = Uri::root()."libraries/noboss/assets/plugins/stylesheets/css/font-awesome.css?v=6.7.2";
                break;
            case 'material-icons':
                $url = "https://fonts.googleapis.com/icon?family=Material+Icons";
                break;
            case 'material-design':
                $url = "https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined";
                break;
            case 'simple-line':
                $url = "https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.5.5/css/simple-line-icons.min.css";
                break;
            case 'linear-icons':
                $url = "https://cdn.linearicons.com/free/1.0.0/icon-font.min.css";
                break;
            default:
                $url = "";
                break;
        }
        
        // Adiciona url do arquivo css
        if ($url != ''){
            $wa->registerAndUseStyle($aliasFamily.'_icons', $url, ['version' => '']);
            return;
        }

        return false;
    }

    /**
     * Adiciona o contexto da extensao nos blocos de JS
     *
     * @param   String 	$code 		Código JS
     * @param   String 	$prefix 	Prefixo (contexto) a ser adicionado
     *
     * @return  mixed 	Codigo com conexto adicionado ou false em caso de erro ou falta de informacoes
     */
    private function addContextInJs($code, $prefix){
        
        // Dados nao informados ou em branco
        if (empty($code) || empty($prefix)){
            return false;
        }

        // adiciona declaracao de $ como jQuery no codigo
        $code = "var $ = jQuery;\n" . $code;
        
        // Classe utilizada no elemento HTML mais externo da extensao
        $outerClass = '.' . substr($this->extensionName, 4);

        // regex para seletores usando 'jQuery' ou '$' com aspas duplas ou simples
        $pattern = '/(\$|jQuery)(\(\s*)(\'|\")(.*?)(\'|\")(\s*\))/';

        // para cada padrao encontrado no codigo, executa um callback
        $code = preg_replace_callback(
            $pattern, 
            function($found) use ($outerClass, $prefix){
                // separa os seletores informados em itens de array
                $selectors = explode(" ", $found[4]);
                // verifica se o ultimo seletor é igual a classe mais externa da extensao 
                if(end($selectors) == $outerClass){
                    // subtitui os seletores informados para o module-id da extensao
                    return str_replace($found[4], $prefix, $found[0]);
                }
                // verifica se algum dos seletores eh a classe da ext
                elseif(in_array($outerClass, $selectors)){                
                    // regex para pegar todos os seletores ate a classe da ext
                    $r = '/(?<=\\\'|\\")(.*?'.$outerClass.')+\s/';
        
                    // remove ocorrencias do padrao $r e adiciona o contexto da section da ext como parametro do seletor
                    $temp = str_replace($found[6], ", '{$prefix}')", $found[0]);
                    return (!empty($temp)) ? preg_replace($r, '', $temp) : '';
                }
                // se o padrao encontrado nao atender as condicoes acima, retorna sem nenhum tratamento
                else{
                    return $found[0];          
                }
            },
            $code
        );

        // retorna o codigo tratado com os contextos adicionados
        return $code;
    }
 
    /**
     * Adiciona um prefixo em todos os blocos de CSS
     *
     * @param   String 	$code 		Código CSS
     * @param   String 	$prefix 	Prefixo a ser adicionado (opcional)
     *
     * @return  mixed 	Codigo com prefixo adicionado ou false em caso de erro ou falta de informacoes
     */
    private function addPrefixInCss($code, $prefix = ''){
        // Prefixo nao informado como parametro: obtem o definido na classe
        if (empty($prefix)){
           $prefix = $this->prefixCode;
        }

        // Classe externa utilizada no elemento HTML mais externo da extensao
        $outerClass = substr($this->extensionName, 4);
       
        // Dados nao informados ou em branco
        if ($code == '' || $prefix == ''){
            return false;
        }

        // divide os trechos de css pelo }
        $parts = explode('}', $code);
        // Percorre cada trecho
        foreach ($parts as &$part) {
            $part = trim($part);
                        
            // Verifica se não é um trecho vazio
            if (empty($part)) continue;

            // Valida a existiencia de um @media e evita que seja colocado prefixo nele
            $mediaPart = '';
            if ($part[0] == "@") {
                // Separa o media
                $tmp = explode('{', $part);
                // Guarda em uma variavel temporaia
                $mediaPart = $tmp[0];
                // remove a posição do array
                unset($tmp[0]);
                $part = implode('{', $tmp);
            }
      
            // Substitui as vírgulas dentro de parentesis por ## para que o prefixer não de errado em casos como rgba(0,0,0,0)        
            $part = preg_replace_callback("|\((.*)\)|",
                function ($s) {
                    return str_replace(",", "##", $s[0]);
                },
                $part);

            // Separa as subpartes por virgula
            $subParts = explode(',', $part);

            // Adiciona o prefixo para cada subtrecho
            foreach ($subParts as &$subPart) {
                // caso possua classes
                if(!empty($subPart)){
                    // Pega os seletores
                    $subPart = trim($subPart);

                    $outerClassWithDot = '.'.$outerClass;
                    // Posicao da classe do modulo nos seletores
                    $outerClassPosition = strpos($outerClassWithDot, $subPart);
                    $outerClassLength =  strlen($outerClassWithDot);
                    // Pega o primeiro char depois da classe do modulo
                    $firstCharAfterOuterClass = substr($subPart,  $outerClassPosition + $outerClassLength, 1);

                    /*
                     * O proximo caracter apos a classe principal eh '.' (ex: .nobossfaq.container{...}) OU;
                     * O proximo caracter apos a classe principal eh ':' (ex: .nobossfaq:not(...){...}) OU;
                     * O proximo caracter apos a classe principal eh ' ' (ex: .nobossfaq .container{...}) OU;
                     * O proximo caracter apos a classe principal eh '{' (ex: .nobossfaq{...}) OU;
                     */
                    if ($firstCharAfterOuterClass === '.' 
                            || $firstCharAfterOuterClass === ':'
                            || $firstCharAfterOuterClass === ' '
                            || $firstCharAfterOuterClass === '{'
                        ){
                        /* Significa que a primeira classe seletora eh a classe do modulo, ou seja, o mesmo elemento que 
                        possui o module id, entao eles precisam ficar juntos */
                        $subPart = $prefix . $subPart;    
                    }
                    else{
                        /* A primeira classe entao nao eh a mesma que a classe do modulo e o seletor
                         externo passa a ser o id do modulo */
                        $subPart = $prefix . ' ' . $subPart;
                    }
                }
            }     
            
            // Remonta o trecho
            $part = implode(', ', $subParts);
            
            // Dá replace de volta nos ## dentro de parenteses para vírgula
            $part = str_replace('##', ',', $part);

            // Readiciona a tag @media
            if(!empty($mediaPart)){
                $part = $mediaPart.'{'.$part;
            }
        }
        // Remonta a string de css
        return trim(implode("}\n", $parts));
    }

    /**
     * Obtem o diretorio de uma extensao a partir do nome
     *
     * @param   boolean   $admin        Flag para informar se extensao eh admin ou site
     *
     * @return	mixed 		String com o diretorio ou false caso o tipo de extensao nao seja identificada
     */
    public function getDirectoryExtension($admin = ''){
        // Se variavel nao for setada pelo usuario pega informacao do acesso do usuario
       /* if ($admin == ''){
           @NBTODO: desenvolver forma de saber se deve carregar o caminho do diretorio no administrator ou do site (o metodo abaixo nao funciona)
            $app = Factory::getApplication();
            $admin = $app->isClient('admin'); 
        }*/
    
        // Caminho base
        $basePath = ($admin) ? JPATH_ADMINISTRATOR : JPATH_SITE;
    
        // Obtem o prefixo da extensao para localizar diretorio
        $extensionPrefix = substr($this->extensionName, 0, 3);
    
        // Monta diretorio da extensao conforme o tipo
        switch ($extensionPrefix) {
            // Extensao eh um modulo
            case 'mod':
            $directoryExtension = "modules/{$this->extensionName}/";
            break;
            // Extensao eh um componente
            case 'com':
            $directoryExtension = "components/{$this->extensionName}/";
            break;
            // Extensao eh uma library
            case 'lib':
            $directoryExtension = "libraries/".substr($this->extensionName, 4)."/";
                break;
            // Tipo de extensao nao identificada
            default:
                return false;
                break;
        }
        $this->directoryExtension = $directoryExtension;
        // Monta caminho do diretorio completo
        $directoryExtension = "{$basePath}/{$directoryExtension}";
    
        return $directoryExtension;
    }

    /**
     * Carrega na pagina arquivos codigos inline de CSS (nao funciona nas requicoes feitas para viewraw pq nestes casos nao existe a funcao getHeadData que usamos)
     *
     * @param   Boolean 	$addViaJs 		Informa se o codigo deve ser adicionado via JS (se for informado false, retorna html com as chamadas a serem exibidas na pagina
     *
     * @return  mixed       void ou string com html a ser exibido
     */
    public static function addCss($addViaJs = false){
        $doc = Factory::getDocument();

        // Obtem dados do head
        // TODO: acho que no J5 essa funcao nao eh reconhecida em requisicoes ajax
        $dataHead = $doc->getHeadData();

        $stylesUrls = array();
        $stylesInline = '';
        $html = '';

        // Existem url de scripts (formato J3, mas que ainda funciona no J4)
        if(!empty($dataHead['styleSheets'])){
            $stylesUrls = array_keys($dataHead['styleSheets']); 
        }

        // Existem scripts inline (formato J3, mas que ainda funciona no J4)
        if(!empty($dataHead['style']['text/css'])){
            $stylesInline = $dataHead['style']['text/css'];

            // No J4 vem como array e convertemos para string para ficar igual ao J3
            if(is_array($stylesInline)){
                $stylesInline = implode(' ', $stylesInline);
            }
        }
        
        // Joomla 4
        if(version_compare(JVERSION, '4', '>=')){
            // Existem scripts definidos no formato novo do J4 (inline e url)
            if (!empty($dataHead['assetManager']['assets']['style'])){
                $stylesUrlsJ4 = array();
                $stylesInlineJ4 = '';

                // Percorre todos styles
                foreach ($dataHead['assetManager']['assets']['style'] as $asset){
                    $options = $asset->getOptions();
                    // Eh inline
                    if(!empty($options['inline']) && $options['inline']){
                        // Concatena conteudo inline
                        $stylesInlineJ4 .= $options['content'];
                    }
                    // Eh url
                    else{
                        // Armazena url em posicao do array
                        $stylesUrlsJ4[] = $asset->getUri();
                    }
                }

                // Concatena conteudo inline do formato novo com antigo
                $stylesInline = trim("{$stylesInlineJ4} {$stylesInline}");

                // Concatena urls do formato novo com antigo
                $stylesUrls = array_merge($stylesUrlsJ4, $stylesUrls);
            }
        }      

        // Forca margin 0 no body
        $stylesInline .= ' body{ margin: 0; }';

        // Adiciona na pagina via PHP para todos os casos (ajax ou nao)
        $html .= "<style>{$stylesInline}</style>";
        
        // Adiciona arquivos e inline via JS na pagina
        if($addViaJs){
            ?>
            <script>
                var stylesFiles = [
                    <?php foreach($stylesUrls as $url){
                        echo "'{$url}',";
                    } ?>
                ];
                var queueStyle = stylesFiles.map(function (styleFile) {
                    if (jQuery("link[href*='" + styleFile.slice(-30) + "']").length === 0) {
                        var fileCssTemp = document.createElement('link');
                        fileCssTemp.type = "text/css";
                        fileCssTemp.rel = "stylesheet";
                        fileCssTemp.href = styleFile;
                        document.head.appendChild(fileCssTemp); 
                    }
                });
            </script>
            <?php
            return true;
        }

        // Requisicao normal inserindo arquivos via PHP
        else{
            // faz a chamada dos arquivos css na pagina
            foreach($stylesUrls as $url){
                $html .= "<link href={$url} rel='stylesheet' type='text/css'>";
            }
        }

        return $html;
    }

    /**
     * Carrega na pagina arquivos codigos inline de JS (nao funciona nas requicoes feitas para viewraw pq nestes casos nao existe a funcao getHeadData que usamos)
     *
     * @param   Boolean 	$addViaJs 		Informa se o codigo deve ser adicionado via JS (se for informado false, retorna html com as chamadas a serem exibidas na pagina
     *
     * @return  mixed       void ou string com html a ser exibido
     */
    public static function addJs($addViaJs = false){
        $doc = Factory::getDocument();

        // Obtem dados do head
        // TODO: acho que no J5 essa funcao nao eh reconhecida em requisicoes ajax
        $dataHead = $doc->getHeadData();

        $scriptsUrls = array();
        $scriptsInline = '';
        $html = '';

        // Joomla 4
        if(version_compare(JVERSION, '4', '>=')){
            // Existem scripts definidos no formato novo do J4 (inline e url)
            if (!empty($dataHead['assetManager']['assets']['script'])){

                // Carrega na pagina os options que poderao ser acessados posteriormente pelo Joomla usando a funcao Joomla.getOptions
                $scriptOptions = $doc->getScriptOptions();
                if ($scriptOptions){
                    $prettyPrint = (JDEBUG && \defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : false);
                    $jsonOptions = json_encode($scriptOptions, $prettyPrint);
                    $jsonOptions = $jsonOptions ? $jsonOptions : '{}';
                    echo '<script type="application/json" class="joomla-script-options new">'.$jsonOptions.'</script>';
                }

                // Percorre todos scripts
                foreach ($dataHead['assetManager']['assets']['script'] as $asset){
                    $options = $asset->getOptions();
                    // Eh inline
                    if(!empty($options['inline']) && $options['inline']){
                        // Concatena conteudo inline
                        $scriptsInline .= $options['content'];
                    }
                    // Eh url
                    else{
                        $attributes = $asset->getattributes();
                        $script = new \stdClass();
                        $script->url = $asset->getUri();
                        if(!empty($version = $asset->getVersion())){
                            $script->url .= "?{$version}";
                        }
                        
                        // Eh do type 'module'
                        if ((!empty($attributes['type'])) && ($attributes['type'] == 'module')){
                            $script->module = 'type="module"';
                        }
                        else{
                            $script->module = '';
                        }

                        // Eh defer
                        if (!empty($attributes['defer'])){
                            $script->defer = 'defer';
                        }
                        else{
                            $script->defer = '';
                        }

                        $scriptsUrls[] = $script;
                    }
                }

                // Faz com que o Joomla renderize os options inseridos na pagina
                $scriptsInline .= " if(typeof Joomla !== 'undefined'){ Joomla.loadOptions(); } ";

            }
        }      
        
        // Existem url de scripts (formato J3, mas que ainda funciona no J4)
        if(!empty($dataHead['scripts'])){
            foreach ($dataHead['scripts'] as $key => $value) {
                $script = new \stdClass();
                $script->url = $key;
                $script->module = '';
                $script->defer = '';
                $scriptsUrls[] = $script;
            }
        }
        
        // Existem scripts inline
        if(!empty($dataHead['script'])){          
            if(!empty($dataHead['script']['text/javascript'])){
                // No J4 vem como array e convertemos para string para ficar igual ao J3
                if(is_array($dataHead['script']['text/javascript'])){
                    $scriptsInline .= implode(' ', $dataHead['script']['text/javascript']);
                }
                else{
                    $scriptsInline .= $dataHead['script']['text/javascript'];
                }
            }
            // TODO: versoes mto antigas eh possivel que venha em string assim
            else{
                $scriptsInline .= $dataHead['script'];
            }            
        }     

        $scriptsInline = trim($scriptsInline);
                
        // Adiciona arquivos e inline via JS na pagina
        if($addViaJs){
            ?>
            <script>
                var scripts = [
                    <?php foreach($scriptsUrls as $script){
                        // TODO: neste formato nao estamos colocando o $script->module e $script->defer
                        echo "'{$script->url}',";
                    } ?>
                ];
                var queue = scripts.map(function (script) {
                    if (jQuery("script[src*='" + script.slice(-30) + "']").length === 0) {
                        var fileJsTemp = document.createElement('script');
                        fileJsTemp.src = script;
                        document.head.appendChild(fileJsTemp); 
                    }
                });

                // Todos arquivos terminaram de serem carregados
                jQuery.when.apply(null, queue).done(function () {
                    <?php echo $scriptsInline; ?>
                });
            </script>
            <?php

            return true;
        }

        // Requisicao normal: insere todos JS via PHP
        else{
            // Adiciona na pagina os codiso JS inline
            $html .= "<script>{$scriptsInline}</script>";       

            // faz a chamada dos arquivos na pagina
            foreach($scriptsUrls as $script){
                $html .= "<script src='{$script->url}' {$script->module} {$script->defer}></script>";
            }
        }

        return $html;
    }
}
