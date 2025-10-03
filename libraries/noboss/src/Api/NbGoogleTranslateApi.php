<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Api;

use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Classe para traducoes via google translate
class NbGoogleTranslateApi {
   
    /**
	 *  Funcao principal para salvar os dados da planilha importada
	 *
     * @param   Mides       $text           Texto a ser traduzido (string se for apenas um texto ou array se forem varios textos separados)
     * @param   String      $source         Tag do idioma de origem (ex: 'pt') - para detectar automaticamente, passar valor 'auto'
     * @param   String      $target         Tag do idioma a ser traduzido (ex: 'en')
     * @param   String      $apiKey         Chave da API do Google
     * @param   String      $format         Formato de retorno dos dados (valores possíveis: 'text' ou 'html')
     * @param   Array       $replaces       Array com valores que deseja fazer replace apos traducao 
     *                                        * Util para casos em que o google faz alguma traducao errada ou quebra uma tag html
     *                                        * Exemplo de como passar valors: 
     *                                              array(array('search' => 'texto origem', 'replace' => 'texto modificado'));
     * 
	 * @return  mixed       Objeto com um array das traducoes e o idioma de origem detectado automaticamente
	 */
    public static function googleTranslate($text, $source, $target, $apiKey, $format = 'text', $replaces = array()){
        $queryTexts = '';

        // Recebido um array de itens a traduzir
        if(is_array($text)){
            foreach ($text as $q) {
                $queryTexts .= "&q=".rawurlencode($q);
            }
        }
        // Recebida uma string a traduzir
        else{
            $queryTexts .= "&q=".rawurlencode($text);
        }

        // Url para requisicao
        $url = "https://www.googleapis.com/language/translate/v2?key={$apiKey}{$queryTexts}&target={$target}&format={$format}";

        // Soh passa idioma de origem se nao for para detectar automaticamente
        if($source != 'auto'){
            $url .= "&source={$source}";
        }

        // Realiza requisicao simples via curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);                 
        curl_close($ch);

        $result = json_decode($response,true);

        if($result != null) {
            // Ocorreu um erro
            if(isset($result['error'])) {
                //throw new \RuntimeException("Erro: ".$result['error']['message']);
                Factory::getApplication()->enqueueMessage("Erro: ".$result['error']['message'], 'error');
            }
            // Traducao teve sucesso
            else{
                $return = new \stdClass();
                $return->translated = array();
                $return->sourceLanguage = "";

                // Percorre cada traducao solicitada
                foreach ($result['data']['translations'] as $translate) {
                    // Definido replaces a fazer no texto traduzido
                    if(count($replaces) > 0){
                        // Percorre cada replace a executar
                        foreach ($replaces as $item) {
                            $translate['translatedText'] = str_replace($item['search'], $item['replace'], $translate['translatedText']);
                        }
                    }
                    
                    // Guarda item traduzido
                    $return->translated[] = $translate['translatedText'];

                    // Salva idioma de origem
                    if(empty($return->sourceLanguage) && (!empty($translate['detectedSourceLanguage']))){
                        $return->sourceLanguage = $translate['detectedSourceLanguage'];
                    }
                }

                return $return;
            }
        }
       
        return false;
    }

    /**
	 *  Executa metodo de traducao utilizando conjunto aleatorio de chaves de API
     *      -> Util para contornar limite de 500k caracteres mensais que o google disponibiliza sem cobrar
	 *
     * @param   Mides       $text           Texto a ser traduzido (string se for apenas um texto ou array se forem varios textos separados)
     * @param   String      $source         Tag do idioma de origem (ex: 'pt') - para detectar automaticamente, passar valor 'auto'
     * @param   String      $target         Tag do idioma a ser traduzido (ex: 'en')
     * @param   Array       $apisKeys       Chaves de APIs do Google
     * @param   String      $format         Formato de retorno dos dados (valores possíveis: 'text' ou 'html')
     * @param   Array       $replaces       Array com valores que deseja fazer replace apos traducao 
     *                                        * Util para casos em que o google faz alguma traducao errada ou quebra uma tag html
     *                                        * Exemplo de como passar valors: 
     *                                              array(array('search' => 'texto origem', 'replace' => 'texto modificado'));
     * 
	 * @return  array       Array com campo 'success' (0/1) + 'text' (qnd sucesso) ou 'message' (qnd erro)
	 */
    public static function multipleKeyTranslation($text, $source, $target, $apisKeys, $format = 'text', $replaces = array()){
        if(empty($text) || empty($apisKeys)){
            return array('success' => 0, 'message' => 'API keys not reported or text to be translated not defined.');
        }

        // Ordena aleatoriamente o array de chaves
        shuffle($apisKeys);

        // Vai pegando cada chave ateh que consiga traduzir com sucesso com alguma delas
        while ($apiKey = array_shift($apisKeys)) {
            try{
                // Realiza traducao
                $translate = self::googleTranslate($text, $source, $target, $apiKey, $format, $replaces);

                // Traducao ocorreu com sucesso
                if(!empty($translate->translated)){
                    return array('success' => 1, 'text' => $translate->translated, 'sourceLanguage' => $translate->sourceLanguage);
                }
            }
            catch (\Exception $e) {
                // Todas chaves ja foram usadas na tentativa de traduzir
                if(empty($apisKeys)){
                    return array('success' => 0, 'message' => 'It was not possible to translate with any given api key. Error details:'.$e->getMessage());
                }
            }
        }
    }

    /**
     * Realiza traducao de items de subform
     *
     * @param json 		$subformContent 	Conteudo do subform encodado em json
     * @param string	$sefLanguage		Tag do idioma para qual deve ser traduzido (ex: en)
     * @param array	    $keysGoogle		    Array com chaves de traducao do google
     * @param array     $colsNotTranslate   Alias de colunas que nao devem ser traduzidas
     *
     * @return boolean|object Retorna true se o registro é único caso contrário
     * retorna um objeto com dados da extensão que já possui o alias e linguagem.
     */
    public static function translateSubform($subformContent, $sefLanguage, $keysGoogle, $colsNotTranslate = array()) {
        $contentCopy = json_decode($subformContent, true);

        $itemsToTranslate = array();

        // Percorre todos items do subform
        foreach ($contentCopy as $keyItem => $itemSubform) {
            // Percorre colunas do item
            foreach ($itemSubform as $keyCol => $colItem) {
                // Nomes de colunas que nao devem ser traduzidas
                if (in_array($keyCol, $colsNotTranslate)) {
                    continue;
                }
                // Adiciona texto da coluna no array de itens a traduzir
                $itemsToTranslate[] = $colItem;
            }
        }

        // Realiza traducao do array
        $returnTranslate = self::multipleKeyTranslation($itemsToTranslate, 'auto', $sefLanguage, $keysGoogle, 'html');

        // Traducao realizada com sucesso
        if($returnTranslate['success']){
            // Percorre todos items do subform novamente para colocar os items traduzidos
            foreach ($contentCopy as $keyItem => $itemSubform) {
                foreach ($itemSubform as $keyCol => $colItem) {
                    // Nomes de colunas que nao devem ser traduzidas
                    if (in_array($keyCol, $colsNotTranslate)) {
                        continue;
                    }
                    $contentCopy[$keyItem][$keyCol] = ucfirst(strtolower(array_shift($returnTranslate['text'])));
                }
            }
            
            return $contentCopy;
        }
        // Erro na traducao: seta mensagem de erro e retorna conteudo sem traducao
        else{
            Factory::getApplication()->enqueueMessage('Erro na tradução: '.$returnTranslate['message'], 'error');

            return $contentCopy;
        }
    }
}
