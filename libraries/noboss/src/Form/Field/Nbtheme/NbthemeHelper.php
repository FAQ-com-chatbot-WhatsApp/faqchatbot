<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nbtheme;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Form\Form;
use Noboss\Library\Util\NbCurlUtil;
use Noboss\Library\Util\NbUrlUtil;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * Classe de campo personalizado para exibição de temas (executado a partir de JS)
 */
class NbthemeHelper {
    /**
     * Carrega uma modal para a escolha de temas e exemplos de um modulo
     */
    public static function loadModuleSample() {
        $app = Factory::getApplication();
        $post = $app->input->post;
        
        // pega o idioma vindo da requisicao ajax
        $langCode = $post->get('lang');

        $language = Factory::getLanguage();
        $lang = Language::getInstance($langCode, (bool) $app->get('debug_lang'));
        Factory::$language = $lang;
        $app->loadLanguage($lang);

        // Carrega arquivo tradução da library no boss
        $lang->load('lib_noboss', JPATH_SITE.'/libraries/noboss');

        $extensionToken = $post->get('token', '');
        $extension = $post->get('extensionName', 'banners');
        $model = $post->get('model', 'model1');
        $sampleId = $post->get('sampleId', "demo_{$extension}_{$model}_default");
        $itemsFormName = $post->get('itemsFormName');
        $addModals = $post->get('addModals');
        $fieldsNames = $post->get("fieldsNames");
        $language = $lang->get('tag', 'en-GB');
        $mainSubform = $post->getString('loadModeSubform', '');

        // TODO: codigo abaixo pode ser descomentando se quiser testar o script direto no navegador forcando uma extensao  e acessando a url https://localhost/nb/extensions/pt/?option=com_nobossajax&library=noboss.src.Form.Field.Nbtheme.NbthemeHelper&method=loadModuleSample&format=raw
        // $extensionToken = '536f40a0542b1ad75dfad213a4bd37ca';
        // $extension = 'banners';
        // $mainSubform = 'model1';

        // Extensao esta sendo carregada no site demo
        if (strpos(Uri::root(), 'nobossextensions.com/demos') !== false) {
            $siteDemo = true;
        }
        else{
            $siteDemo = false;
        }
        
        $url = NbUrlUtil::getUrlNbExtensions().'/index.php?option=com_nbextensoes&task=externalthemes.getSample&format=raw';

        // Cria o objeto que será retornado
        $values = new \stdClass();
        $values->success = 0;

        // Configura dados do POST.
        $dataPost = array(
            'token'     => $extensionToken,
            'sampleId'  => $sampleId,
            'model'  => $model,
            'language'  => $language,
            'itemsFormName' => $itemsFormName,
            'addModals' => $addModals,
            'fieldsNames' => $fieldsNames,
            'mainSubform' => $mainSubform,
            'websiteRequest' => Uri::root()
        );

        $dataPost = http_build_query($dataPost);

        $fullResponse = NbCurlUtil::request('POST', $url, $dataPost, null, 20, null, null, true);

        // Codigo de erro da curl eh de timeout
        if($fullResponse->data->errorno == 28){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_CONNECTION_TIMEOUT');
            exit(json_encode($values));
        }

        $response = $fullResponse->data->body;

        // echo '<pre>';
        // var_dump($response);
        // exit;

        //exit(json_encode($fullResponse));

        // Verifica se a resposta não é falsa, ou seja, não tem internet ou não foi possível se comunicar com o servidor
        if (empty($fullResponse->data) || $response == false || empty($response)){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_NOT_CONNECTION');
            exit(json_encode($values));
        }
        
        // Verifica se não deu erro de exemplo não encontrado
        if (trim($response) == "SAMPLE_NOT_FOUND"){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_NOT_FOUND');
            exit(json_encode($values));
        }
        
        try{
            // Decodifica a resposta do servidor
            $response = json_decode($response, true);
        } catch (\Exception $e){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_JSON_PARSE_SERVER');
            exit(json_encode($values));
        }

        // Nenhum conteudo retornado em response
        if (empty($response)){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_NO_RESPONSE_FROM_SERVER');
            exit(json_encode($values));
        }

        // Token invalido: personaliza mensagem conforme situacao
        if(!$siteDemo && array_key_exists('valid_token', $response)){
            // Token nao existe na base local
            if($response['valid_token'] == 0){
                $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_NOT_VALID');
            }
            // A licenca do usuario nao inclui o tema escolhido
            else if ($response['in_plan'] == 0){
                $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_PLAN_NOT_INLCUDED');
            }
            // Nao esta no periodo de atualizacoes
            else if ($response['inside_support_updates'] == 0){
                $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_SUPPORT_UPDATES_EXPIRATED');
            }
            
            $values->data = "INVALID_TOKEN";
            exit(json_encode($values));
        }

        if ($values) {
            // Nome da extensao foi definido no xml
            if ($extension){
                // Carrega arquivo tradução da extensao em que a modal está sendo chamada
                $lang->load("mod_noboss{$extension}", JPATH_ROOT."/modules/mod_noboss{$extension}");
            }      

            // guarda a referencia do array de itens de subform
            $items = $response['items'];
            $addFields = $response['fields'];

            // echo '<pre>';
            // var_dump($items);
            // exit;

            // Carrega o xml da extensão
            $xml = simplexml_load_file(JPATH_ROOT."/modules/mod_noboss{$extension}/mod_noboss{$extension}.xml");

            $fields = $xml->config->fields;

            // Foi setado fields especificos a terem conteudo de exemplo copiado
            if(!empty($addFields)){
                foreach ($response['fields'] as $fieldName => $value) {
                    $xmlField = $fields->xpath('//field[@name="'.$fieldName.'"]');
                    $field = $xmlField[0];

                    try{
                        // Extensao esta no novo formato p/ J5
                        if(is_dir(JPATH_SITE."/modules/mod_noboss{$extension}/src")){
                            $extensionUcFirst = ucfirst($extension);

                            // Monta o xml que sera usado pelo getinstance
                            $newXml = '<form addfieldprefix="Noboss\Library\Form\Field"><fieldset addfieldprefix="Noboss\Module\\'.$extensionUcFirst.'\Site\Field" name="basic" >'.$field->asXML().'</fieldset></form>';

                            //echo $newXml; exit;
                        }
                        // TODO: qnd todas extensoes estiverem migradas para novo formato do J5 podemos remover esse else e deixar soh o conteudo do if acima
                        // Extensao esta no formato antigo que exige plugin de compatibilidade
                        else{
                            // Monta o xml que sera usado pelo getinstance
                            $newXml = '<form addfieldpath="libraries/noboss/forms/fields"><fieldset addfieldpath="modules/mod_noboss'.$extension.'/fields" name="basic" >'.$field->asXML().'</fieldset></form>';
                        }

                        $form = Form::getInstance($fieldName, $newXml, array('control' => 'jform[params]'), true);
                        
                    } catch (\Exception $e){
                        $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_XML_INVALID');
                        exit(json_encode($values));
                    }

                    // // se tiver valor seta no campo
                    // if(!empty($value)){
                        // Seta os valores do field
                        $form->setValue($fieldName, null, ($addFields[$fieldName]));
                    // }
                    
                    // adiciona na resposta o html do field
                    $response['fields'][$fieldName] = $form->getField($fieldName)->renderField();
                }
            }
            
            // Foi setado ao menos um subform a ter conteudo de exemplo copiado
            if(!empty($items)){

                foreach($response['items'] as $subformName => $val){                  
                    try{
                        // Extensao esta no novo formato p/ J5
                        if(is_dir(JPATH_SITE."/modules/mod_noboss{$extension}/src")){
                            $xmlSubform = $fields->xpath('//field[@name="'.$subformName.'" and starts-with(@type,"NbSubform")]');

                            $subform = $xmlSubform[0]; 

                            $extensionUcFirst = ucfirst($extension);

                            // Monta o xml que sera usado pelo getinstance
                            $newXml = '<form addfieldprefix="Noboss\Library\Form\Field"><fieldset addfieldprefix="Noboss\Module\\'.$extensionUcFirst.'\Site\Field" name="basic" >'.$subform->asXML().'</fieldset></form>';

                            //echo $newXml; exit;
                        }
                        // TODO: qnd todas extensoes estiverem migradas para novo formato do J5 podemos remover esse else e deixar soh o conteudo do if acima
                        // Extensao esta no formato antigo que exige plugin de compatibilidade
                        else{
                            $xmlSubform = $fields->xpath('//field[@name="'.$subformName.'" and starts-with(@type,"nobosssubform")]');

                            $subform = $xmlSubform[0]; 

                            // Monta o xml que sera usado pelo getinstance
                            $newXml = '<form addfieldpath="libraries/noboss/forms/fields"><fieldset addfieldpath="modules/mod_noboss'.$extension.'/fields" name="basic" >'.$subform->asXML().'</fieldset></form>';
                        }
                        
                        // Instancia o formulário
                        $form = Form::getInstance($subformName, $newXml, array('control' => 'jform[params]'), true);

                    } catch (\Exception $e){
                        $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_XML_INVALID');
                        exit(json_encode($values));
                    }

                    // Seta os valores do subform
                    $form->setValue($subformName, null, ($items[$subformName]));

                    $response['items'][$subformName] = $form->getField($subformName)->renderField();
                }
            }

            $values->success = 1;
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_SUCCESS');
            $values->data = $response;
        }
        
        exit(json_encode($values));
    }	
    
    /**
     * Carrega uma modal para a escolha de temas e exemplos de um componente
     */
    public static function loadComponentSample() {
        $app = Factory::getApplication();
        $post = $app->input->post;
        
        // pega o idioma vindo da requisicao ajax
        $langCode = $post->get('lang');

        $language = Factory::getLanguage();
        $lang = Language::getInstance($langCode, (bool) $app->get('debug_lang'));
        Factory::$language = $lang;
        $app->loadLanguage($lang);

        // Carrega arquivo tradução da library no boss
        $lang->load('lib_noboss', JPATH_SITE.'/libraries/noboss');

        // Token da extensão
        $extensionToken = $post->get('token'); 
        // Pega o nome da extensão
        $extension = $post->get('extensionName', '');
        // Nome da extensao foi definido no xml
        if ($extension){
            // Carrega arquivo tradução da extensao em que a modal está sendo chamada
           $lang->load("com_noboss{$extension}", JPATH_ROOT."/administrator/components/com_noboss{$extension}");
        }
        // Pega o nome do tema escolhido
        $model = $post->get('model', 'model1');
        // Pega o modelo da extensão
        $sampleId = $post->get('sampleId', "demo_{$extension}_{$model}_default");
        // Pega a a tag de linguagem atual
        $language = Factory::getLanguage()->get('tag');
        $url = NbUrlUtil::getUrlNbExtensions().'/index.php?option=com_nbextensoes&task=externalthemes.getSample&format=raw';
        // Cria o objeto que será retornado
        $values = new \stdClass();
        $values->success = 0;

        // Configura dados do POST.
        $dataPost = array(
            'token'     => $extensionToken,
            'sampleId'  => $sampleId,
            'model'     => $model,
            'language'  => $language,
            'component' => true
        );

        // Faz uma requisição curl usando a library
        $fullResponse = NbCurlUtil::request('POST', $url, $dataPost, null, 20, null, null, true);

        // Verifica se código de erro da curl é de "timeout".
        if($fullResponse->data->errorno == 28){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_CONNECTION_TIMEOUT');
            exit(json_encode($values));
        }

        $response = $fullResponse->data->body;

        // Verifica se a resposta não é falsa, ou seja, não tem internet ou não foi possível se comunicar com o servidor
        if (empty($fullResponse->data) || $response == false || empty($response)){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_NOT_CONNECTION');
            exit(json_encode($values));
        }
        
        try{
            // Decodifica a resposta do servidor
            $response = json_decode($response, true);
        } catch (\Exception $e){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_JSON_PARSE_SERVER');
            exit(json_encode($values));
        }

        // Verifica se o token é invalido ou o periodo de suporte expirou
        if(array_key_exists('valid_token', $response)){
            // Varifica se o token existe
            if($response['valid_token'] == 0){
                $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_NOT_VALID');
            // Verifica se o plano inclui o modelo
            } else if ($response['in_plan'] == 0){
                $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_PLAN_NOT_INLCUDED');
            // Caso seja custom, verifica se está no periodo do suporte
            } else if ($response['inside_support_updates'] == 0){
                $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_TOKEN_SUPPORT_UPDATES_EXPIRATED');
            }
            $values->data = "INVALID_TOKEN";
            exit(json_encode($values));
        }
        

        // Carrega o xml da extensão
        $xml = simplexml_load_file(JPATH_ROOT."/administrator/components/com_noboss{$extension}/models/forms/group.xml");
        
        try{
            // Extensao esta no novo formato p/ J5
            if(is_dir(JPATH_ROOT."/administrator/components/com_noboss{$extension}/src")){
                $extensionUcFirst = ucfirst($extension);

                // Monta inicio do xml que sera usado pelo getinstance
                $xmlFields = '<form addfieldprefix="Noboss\Library\Form\Field"><fieldset addfieldprefix=Noboss\Component\\'.$extensionUcFirst.'\Administrator\Field" name="basic" >';

                //echo $xmlFields; exit;
            }
            // TODO: qnd todas extensoes estiverem migradas para novo formato do J5 podemos remover esse else e deixar soh o conteudo do if acima
            // Extensao esta no formato antigo que exige plugin de compatibilidade
            else{
                // Monta inicio do xml que sera usado pelo getinstance
                $xmlFields = '<form addfieldpath="libraries/noboss/forms/fields"><fieldset addfieldpath="administrator/components/com_noboss'.$extension.'/models/fields" name="basic" >';
            }

            // Percorre todos os fields a serem adicionados no form
            foreach ($response['fields'] as $key => $value) {
                $field = $xml->xpath('//field[@name="'.$key.'"]');
                $field = $field[0];
                if(!empty($field)){
                    $xmlFields .= $field->asXML();
                }
            }
            $xmlFields .= "</fieldset></form>";   

            // Instancia o formulario
            $form = Form::getInstance('sample_data', $xmlFields, array('control' => 'jform'), true);

        } catch (\Exception $e){
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_ERROR_XML_INVALID');
            exit(json_encode($values));
        }
        // percorre cada campo de dado de exemplo
        foreach ($response['fields'] as $key => $value) {
            // pega o objeto form do campo atual
            $field = $form->getField($key);

            if(!empty($field)){
                // seta o valor
                $field->setValue($value);
                // renderiza o html desse field na resposta da requisicao
                $response['fields'][$key] = $field->renderField();
            }
            else{
                unset($response['fields'][$key]);
            }
        }

        if ($values) {
            
            // seta como sucesso a requisicao
            $values->success = 1;
            // mensagem de sucesso
            $values->message = Text::_('LIB_NOBOSS_FIELD_NOBOSSTHEME_GENERATE_EXAMPLES_SUCCESS');
            // adiciona os json das modais no valor que sera retornado
            $values->data = $response;
        }
        // Retorna em formato json
        exit(json_encode($values));
    }	
}
