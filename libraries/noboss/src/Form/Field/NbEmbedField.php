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
// use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/* 
 * Exibe um embed para usuario utilizar para chamar a extensao diretamente
 *      - Soh funciona a partir da versao 3.9 do Joomla
 */
class NbEmbedField extends FormField {
  
    protected function getInput(){
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // JS s CSS do field
		$wa->registerAndUseScript('nobossembed', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossembed.min.js");
		$wa->registerAndUseStyle('nobossembed', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossembed.min.css");

        // Modal onde campo eh carregado esta bloqueado pela licenca: nao permite visualizar campo
        if($app->input->post->get('blockModal', 0, 'INT')){
            return "<div class='alert' style='padding: 5px; display: inline-block;'>".Text::_('LIB_NOBOSS_BLOCK_FIELD_SIDE_FIELD')."</div>";
        }

        // Id do registro que esta sendo editado (para quando field eh chamado dentro de modal)
        $id = $app->input->get('valuefieldid', 0);

        // Nao carrega o campo de embed se registro ainda nao estiver salvo
        if(empty($id)){
            return '<div class="alert" style="padding: 5px; display: inline-block;">'.Text::_('NOBOSS_EXTENSIONS_EMBED_MSG_NOT_SAVE').'.</div>';
        }
        
        // Obtem o tipo de embed a carregar
        $embedType = $this->getAttribute('embedtype', 'iframe');

        // Url do iframe
        $iframeSrc = Uri::root()."index.php?option=com_nobossajax&module-id={$id}";

        switch ($embedType) {
            // Carrega embed de url pura
            case 'url':
                $iframeSrc .= '&load-head=1';
                //$iframeSrc .= '&load-css=1&load-js=1';
                
                $html = "<div class='noboss-embed'>
                            <textarea class='noboss-embed__content-copy'>{$iframeSrc}</textarea>
                        </div>";
                break;

            // Carrega embed de iframe
            case 'iframe':
                $iframeSrc .= '&load-head=1';
                //$iframeSrc .= '&load-css=1&load-js=1';

                $html = "<div class='noboss-embed'>
                            <textarea class='noboss-embed__content-copy'>&lt;iframe src='{$iframeSrc}' frameborder='0' scrolling='no' onload='this.style.height=(this.contentWindow.document.body.scrollHeight)+\"px\";' style='width: 100%;' &gt;&lt;/iframe&gt;&nbsp;</textarea>
                        </div>";
                break;

            // Carrega embed de iframe c/ correcao de altura via CSS
            case 'iframe-height-auto':
                $iframeSrc .= '&load-head=1';
                //$iframeSrc .= '&load-css=1&load-js=1';
                
                // Conteudo do css que esta minificado mais abaixo dentro da tag '<style>':
                /*
                [style*='--aspect-ratio'] &gt; :first-child {
                    width: 100%;
                }

                [style*='--aspect-ratio'] &gt; img {  
                    height: auto;
                }

                @supports (--custom:property) {
                    [style*='--aspect-ratio'] {
                        position: relative;
                    }
                    
                    [style*='--aspect-ratio']::before {
                        content: '';
                        display: block;
                        padding-bottom: calc(100% / (var(--aspect-ratio)));
                    }
                    
                    [style*='--aspect-ratio'] &gt; :first-child {
                        position: absolute;
                        top: 0;
                        left: 0;
                        height: 100%;
                    }  
                }
                */
                $html = "<div class='noboss-embed'>
                            <textarea class='noboss-embed__content-copy'>&lt;style&gt; [style*='--aspect-ratio'] &gt;:first-child {width: 100%;}[style*='--aspect-ratio'] &gt;img {height: auto;}@supports (--custom:property) {[style*='--aspect-ratio'] {position: relative;}[style*='--aspect-ratio']::before {content: '';display: block;padding-bottom: calc(100% / (var(--aspect-ratio)));}[style*='--aspect-ratio'] &gt;:first-child {position: absolute;top: 0;left: 0;height: 100%;}} &lt;/style&gt; &lt;div style='--aspect-ratio: 16/9;'&gt; &lt;iframe src='{$iframeSrc}' width='100%' height='100%' frameborder='0' scrolling='no'&gt; &lt;/iframe&gt; &lt;/div&gt;</textarea>
                        </div>";
                break;

            // Carrega embed de jquery
            case 'jquery':
                $idRandom = uniqid();
                $iframeSrc .= '&nb=1'; // Variavel que determina que CSS e JS sejam inseridos no head via JS e nao PHP (qnd ocorrer erro no console do navegador, retirar a chamada desse parametro na url para que carregue via PHP)
                $html = "<div class='noboss-embed'>
                            <textarea class='noboss-embed__content-copy'>&lt;div id='nb-embed-{$idRandom}'&gt;&lt;/div&gt; &lt;script&gt; jQuery.get('{$iframeSrc}', function(data) { jQuery('#nb-embed-{$idRandom}').html(data); }); &lt;/script&gt;</textarea>
                        </div>";
                break;

            // Carrega embed via javascript
            /* TODO: a opcao via Javascript nao foi implantada pq tem um problema com os códigos e arquivos Javascripts que são imbutidos via innetHTML. Por questões de segurança, os códigos JS inseridos desta forma nao sao executados. Se um dia quisermos fazer funcionar, será necessario:
                    - Criar uma funcao JS que pega todos arquivos JS inseridos e reinsira eles na página de outra forma (ver melhor no google: https://www.google.com/search?q=Fun%C3%A7%C3%A3o+javascript+dentro+de+innerHTML+n%C3%A3o+funciona&oq=Fun%C3%A7%C3%A3o+javascript+dentro+de+innerHTML+n%C3%A3o+funciona)
                    - Essa função a ser criada devera ser inserida no site do cliente junto com o restante do codigo feito abaixo que faz o embed. Para que nao fique um codigo grande para o usuario inserir, podemos colocar a funcao em um arquivo JS que fique hospedado no nosso servidor e no codigo abaixo do embed adicionamos uma chamada para esse arquivo nosso.
                    - Importante se ligar que nao sao soh os arquivos JS inseridos no innerHTML que nao funcionam, mas tb as chamadas diretas de JS.
            */
            // case 'javascript':
            //     $idRandom = uniqid();
            //     $html = "<div class='noboss-embed'>
            //                 <textarea class='noboss-embed__content-copy'>&lt;div id='nbembed-{$idRandom}'&gt;&lt;/div&gt; &lt;script&gt;let nbembed = new XMLHttpRequest(); nbembed.open('GET', '{$iframeSrc}', true); nbembed.send(); nbembed.onload = function () { document.getElementById('nbembed-{$idRandom}').innerHTML = nbembed.responseText; }; &lt;/script&gt;</textarea>
            //             </div>";
                //break;
        }

        return $html;
    }
}

