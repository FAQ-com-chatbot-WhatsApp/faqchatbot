<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Util;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\Http\Exception\InvalidResponseCodeException;
use Joomla\Http\Response;
use Laminas\Diactoros\Stream as StreamResponse;
use Joomla\CMS\Factory;
// use Joomla\CMS\Http\Response;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Classe para requisicoes via curl
class NbCurlUtil{
    
    private static function validateCurlRequest($response, $curlErroNo, $values){
        $httpCode = $response->code;

        // Verifica se código de erro da curl é de "timeout".
        if($curlErroNo == 28){
            $values->success = 0;
            $values->message = Text::_('LIB_NOBOSS_UTIL_CURL_ERROR_TIMEOUT');
        }
        else if($curlErroNo == 35){
            $values->success = 0;
            $values->message = Text::_('LIB_NOBOSS_UTIL_CURL_ERROR_SSL_ERROR_35');
        }
        // Verifica se código de erro da curl é relacionado ao certificado SSL (HTTPS)
        else if($curlErroNo == 60 || $curlErroNo == 77){
            $values->success = 0;
            $values->message = Text::_('LIB_NOBOSS_UTIL_CURL_ERROR_SSL_CERTIFICATE');
        }
        // Valida se não teve erro 404 (não encontrado)
        else if($httpCode == 404){
            $values->success = 0;
            $values->message = Text::_('LIB_NOBOSS_UTIL_CURL_ERROR_NOT_FOUND');
        } else if ($httpCode >= 400 && $httpCode < 500) {
            $values->success = 0;
            $values->message = Text::sprintf('LIB_NOBOSS_UTIL_CURL_ERROR_400_RANGE', $httpCode);
        }
        // Valida se não teve erro entre 500 e 600 (não encontrado)
        else if($httpCode >= 500 && $httpCode < 600){
            $values->success = 0;
            $values->message = Text::sprintf('LIB_NOBOSS_UTIL_CURL_ERROR_500_RANGE', $httpCode);
        }
        // Verifica se a resposta não é falsa
        else if (($response == "false" || empty($response)) && empty($curlErroNo)){
            $values->success = 0;
            $values->message = Text::_('LIB_NOBOSS_UTIL_CURL_ERROR_FALSE_RESPONSE');
        }
        
        return $values;
    }

    /**
     * Send a request to the server and return a HttpResponse object with the response.
     * 
     * Funcao baseada na do Joomla, com verificacoes adicionais desenvolvidas pela No Boss (funcao  original disponivel em \libraries\vendor\joomla\http\src\Transport\Curl.php)
     *
     * @param   string   $method            The HTTP method for sending the request.
     * @param   string      $uri               The URI to the resource to request.
     * @param   mixed    $data              Either an associative array or a string to be sent with the request.
     * @param   array    $headers           An array of request headers to send with the request.
     * @param   integer  $timeout           Read timeout in seconds.
     * @param   string   $userAgent         The optional user agent string to send with the request.
     * @param   string   $paramsOptions     Associative array of optional parameters
     * @param   boolean   $returnFullInfo     Sould return only the body response or bject with headers, body, httpcode and curl error code
     *
     * @return  Response
     *
     * @since   11.3
     * @throws  \RuntimeException
     */
    public static function request($method, $uri, $data = null, array $headers = null, $timeout = null, $userAgent = null, $paramsOptions = null, $returnFullInfo = false) {
        if($headers == null){
            $headers = array();
        }

        // Caso a curl não exista, solta um erro
        if (!function_exists('curl_init') || !is_callable('curl_init')) {
            throw new \RuntimeException('Cannot use a cURL transport when curl_init() is not available.');
        }
        // Cria um novo registro para não gerar erro
        if($paramsOptions === null) {
            $paramsOptions = new Registry;
        }
        // Caso um useragent não tenha sido definido, define como se fodde um navegador
        if($userAgent == null) {
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';
        }

        // Cria o objeto que será retornado
        $values = new \stdClass();
        $values->success = 1;

        // Setup the cURL handle.
        $ch = curl_init();

        $options = array();
        // Set the request method.
        switch (strtoupper($method)) {
            case 'GET':
                $options[CURLOPT_HTTPGET] = true;
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                break;
            case 'PUT':
            default:
                $options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
                break;
        }

        // Don't wait for body when $method is HEAD
        $options[CURLOPT_NOBODY] = ($method === 'HEAD');
        // Initialize the certificate store
        $options[CURLOPT_CAINFO] = $paramsOptions->get('curl.certpath', __DIR__ . '/cacert.pems');
        // Desativa a verificação ssl
        $options[CURLOPT_SSL_VERIFYPEER] = false;
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        // If data exists let's encode it and make sure our Content-type header is set.
        if (isset($data) && !empty($data)) {
            // If the data is a scalar value simply add it to the cURL post fields.
            if (is_scalar($data) || (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'multipart/form-data') === 0)) {
                $options[CURLOPT_POSTFIELDS] = $data;
            }
            // Otherwise we need to encode the value first.
            else {
                $options[CURLOPT_POSTFIELDS] = http_build_query($data);
            }
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
            }
            // Add the relevant headers.
            if (is_scalar($options[CURLOPT_POSTFIELDS])) {
                $headers['Content-Length'] = strlen($options[CURLOPT_POSTFIELDS]);
            }
        }

        // Build the headers string for the request.
        $headerArray = array();
        if (isset($headers)) {
            foreach ($headers as $key => $value) {
                $headerArray[] = $key . ': ' . $value;
            }
            // Add the headers string into the stream context options array.
            $options[CURLOPT_HTTPHEADER] = $headerArray;
        }
        // Curl needs the accepted encoding header as option
        if (isset($headers['Accept-Encoding'])) {
            $options[CURLOPT_ENCODING] = $headers['Accept-Encoding'];
        }
        // If an explicit timeout is given user it.
        if (isset($timeout)) {
            $options[CURLOPT_TIMEOUT] = (int) $timeout;
            $options[CURLOPT_CONNECTTIMEOUT] = (int) $timeout;
        }
        // If an explicit user agent is given use it.
        if (isset($userAgent)) {
            $options[CURLOPT_USERAGENT] = $userAgent;
        }
        // Set the request URL.
        $options[CURLOPT_URL] = (string) $uri;
        // We want our headers. :-)
        $options[CURLOPT_HEADER] = true;
        // Return it... echoing it would be tacky.
        $options[CURLOPT_RETURNTRANSFER] = true;

        // Forca o uso de IPv4
        $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;

        // Override the Expect header to prevent cURL from confusing itself in its own stupidity.
        // Link: http://the-stickman.com/web-development/php-and-curl-disabling-100-continue-header/
        $options[CURLOPT_HTTPHEADER][] = 'Expect:';

        //teste para "Error connecting to the server: HTTP/2 stream 0 was not closed cleanly: PROTOCOL_ERROR (err 1)"
        //$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;

        // Proxy configuration
        $config = Factory::getConfig();

        if ($config->get('proxy_enable')) {
            $options[CURLOPT_PROXY] = $config->get('proxy_host') . ':' . $config->get('proxy_port');
            if ($user = $config->get('proxy_user')) {
                $options[CURLOPT_PROXYUSERPWD] = $user . ':' . $config->get('proxy_pass');
            }
        }

        // Set any custom transport options
        foreach ($paramsOptions->get('transport.curl', array()) as $key => $value) {
            $options[$key] = $value;
        }

        // Authentification, if needed
        if ($paramsOptions->get('userauth') && $paramsOptions->get('passwordauth')) {
            $options[CURLOPT_USERPWD] = $paramsOptions->get('userauth') . ':' . $paramsOptions->get('passwordauth');
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }

        // Set the cURL options.
        curl_setopt_array($ch, $options);
        // Execute the request and close the connection.
        $content = curl_exec($ch);

        // echo '<pre>';
        // var_dump($content);
        // exit;

        if (!is_string($content)) {
            $message = curl_error($ch);
            if (empty($message)) {
                // Error but nothing from cURL? Create our own
                $message = 'No HTTP response received';
            }

            $values->success = 0;
            $values->message = $message;
            return $values;            
            // throw new \RuntimeException($message);
        }
        
        // Pega o numero do erro curl
        $curlErroNo = curl_errno($ch);
        
        // Get the request information.
        $info = curl_getinfo($ch);

        $response = self::getResponse($content, $info);
    
        // Chama o método para fazer as validações de curl
        $values = self::validateCurlRequest($response, $curlErroNo, $values);

        // Obtem detalhes do erro, caso exista
        $detailsError = curl_error($ch);

        // Foi identificado um erro e temos detalhe da funcao curl_error
        if(!empty($values->message) && !empty($detailsError)){
            // Concatena informacao com mensagem ja obtida
            $values->message .= "<br /><br /><b>".Text::_('LIB_NOBOSS_UTIL_CURL_ERROR_MSG_FUNCTION').":</b> <br />.{$detailsError}.";

            // Requisicao nao eh da url de teste: adiciona orientacao para teste direto na url
            // Sanitiza o parâmetro 'method' recebido via GET
            $method = isset($_GET['method']) ? htmlspecialchars($_GET['method'], ENT_QUOTES, 'UTF-8') : '';
            if(empty($method) || $method != 'curlTest'){
                // url que permite ver o erro em simulacao de teste
                $urlTest = Uri::root()."?option=com_nobossajax&library=noboss.src.Util.NbSupportUtil&method=curlTest";

                // Concatena mensagem
                $values->message .= "<br /><br />".Text::_('LIB_NOBOSS_UTIL_CURL_ERROR_MSG_LINK_TEST')." <br /> <a href='{$urlTest}' target='_blank'>{$urlTest}</a>";

                // Concatena url de teste 
                $values->message .= "";
            }

        }
        // fecha a curl
        curl_close($ch);

        // Seguir manualmente os redirecionamentos se o servidor não permitir seguir o local usando curl
        if ($response->code >= 301 && $response->code < 400 && isset($response->headers['Location'])) {
            $redirect_uri = new Uri($response->headers['Location'][0]);
            
            if (in_array($redirect_uri->getScheme(), array('file', 'scp'))) {
                throw new \RuntimeException('Curl redirect cannot be used in file or scp requests.');
            }
            return self::request($method, (string) $redirect_uri, $data, $headers, $timeout, $userAgent,$paramsOptions, $returnFullInfo);
        }

        // Armazena a resposta para retorno
        if($returnFullInfo){
            $values->data = $response;
        } else {
            $values->data = $response->body;
        }
        return $values;
    }

    
    /**
	 * Método para obter um objeto de resposta de uma resposta do servidor.
     * 
     * Funcao foi copiada do Joomla na versao 5, validado ate 5.1.2. (funcao  original disponivel em libraries\src\Http\Transport\CurlTransport.php)
     * As modificacoes comparada a versao original foram:
     *      - Modificada funcao para estatica
     *      - Modificada chamada da processHeaders como self 
     *      - Trocado a linha de tratamento de erro (throw new InvalidResponseCodeException('No HTTP response code found.');) por return false;
	 *
	 * @param   string  $content  The complete server response, including headers
	 *                            as a string if the response has no errors.
	 * @param   array   $info     The cURL request information.
	 *
	 * @return  Response
	 *
	 * @since   1.0
	 * @throws  InvalidResponseCodeException
	 */
	protected static function getResponse($content, $info)
	{
		// Try to get header size
		if (isset($info['header_size']))
		{
			$headerString = trim(substr($content, 0, $info['header_size']));
			$headerArray  = explode("\r\n\r\n", $headerString);

			// Get the last set of response headers as an array.
			$headers = explode("\r\n", array_pop($headerArray));

			// Set the body for the response.
			$body = substr($content, $info['header_size']);
		}
		// Fallback and try to guess header count by redirect count
		else
		{
			// Get the number of redirects that occurred.
			$redirects = $info['redirect_count'] ?? 0;

			/*
			 * Split the response into headers and body. If cURL encountered redirects, the headers for the redirected requests will
			 * also be included. So we split the response into header + body + the number of redirects and only use the last two
			 * sections which should be the last set of headers and the actual body.
			 */
			$response = explode("\r\n\r\n", $content, 2 + $redirects);

			// Set the body for the response.
			$body = array_pop($response);

			// Get the last set of response headers as an array.
			$headers = explode("\r\n", array_pop($response));
		}

		// Obtenha o código de resposta do primeiro deslocamento dos cabeçalhos de resposta.
		preg_match('/[0-9]{3}/', array_shift($headers), $matches);

		$code = \count($matches) ? $matches[0] : null;

		if (!is_numeric($code))
		{
			// No valid response code was detected.
			// throw new InvalidResponseCodeException('No HTTP response code found.');
            return false;
		}

		$statusCode      = (int) $code;
		$verifiedHeaders = self::processHeaders($headers);

		$streamInterface = new StreamResponse('php://memory', 'rw');
		$streamInterface->write($body);

		return new Response($streamInterface, $statusCode, $verifiedHeaders);
	}

    /**
	 * Processes the headers from a transport's response data.
     * 
     * Funcao copiada do Joomla na versao 4.2 em 30/06/23 e apenas modificada para estatica(funcao  original disponivel em \libraries\vendor\joomla\http\src\AbstractTransport.php)
	 *
	 * @param   array  $headers  The headers to process.
	 *
	 * @return  array
	 *
	 * @since   2.0.0
	 */
	protected static function processHeaders(array $headers): array
	{
		$verifiedHeaders = [];

		// Add the response headers to the response object.
		foreach ($headers as $header)
		{
			$pos     = strpos($header, ':');
			$keyName = trim(substr($header, 0, $pos));

			if (!isset($verifiedHeaders[$keyName]))
			{
				$verifiedHeaders[$keyName] = [];
			}

			$verifiedHeaders[$keyName][] = trim(substr($header, ($pos + 1)));
		}

		return $verifiedHeaders;
	}
}
