<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

/**
 * Proxy para callback de OAuth de APIs (Google, Vimeo, etc)
 * 
 * O motivo de utlizar um proxy eh reduzir a chance de problemas com Mod_Security em hospedagens que bloqueiam redirecionamentos externos.
 * Caso haja bloqueio, o proxy exibe uma mensagem amigavel ao usuario com instrucoes para resolver o problema.
 */

// Recebe os parâmetros da callback (considera GET e POST)
$code = $_REQUEST['code'] ?? '';
$scope = $_REQUEST['scope'] ?? '';
$state = $_REQUEST['state'] ?? ''; // Se houver
$api = $_COOKIE['nb_api'] ?? 'googlecalendar'; // Pega o api via cookie
$clientId = $_COOKIE['nb_client_id_' . $api] ?? '';
$clientSecret = $_COOKIE['nb_client_secret_' . $api] ?? '';

// Valida se os parâmetros essenciais estão presentes
if (empty($code) || empty($scope)) {
    http_response_code(400);
    echo 'Parâmetros inválidos na callback.';
    exit;
}

// Constrói a URL do endpoint real dinamicamente baseada na URI atual
$proxyPath = '/libraries/noboss/src/Form/Field/Nbapiconnection/NbCallbackProxy.php';
$parsedUri = parse_url($_SERVER['REQUEST_URI']);
$requestPath = $parsedUri['path']; // Remove query string e fragment
$baseUri = str_replace($proxyPath, '', $requestPath);
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . $baseUri;
$realEndpoint = $baseUrl . '/index.php?option=com_nobossajax&library=noboss.src.Form.Field.Nbapiconnection.Nbapiconnectionhelper&method=generateToken&format=raw';

// Dados para enviar via POST
$postData = array(
    'api' => $api,
    'code' => $code,
    'scope' => $scope,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'proxy' => 1
);

if (!empty($state)) {
    $postData['state'] = $state;
}

// Faz a requisição interna via POST usando cURL com opções seguras
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $realEndpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Segue redirecionamentos
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36'); // UserAgent padrão da NbCurlUtil
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pems'); // Certificado CA, como na NbCurlUtil

// Inclui os cookies da requisição original para manter a sessão
if (!empty($_COOKIE)) {
    $cookieString = '';
    foreach ($_COOKIE as $name => $value) {
        $cookieString .= $name . '=' . urlencode($value) . '; ';
    }
    curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookieString, '; '));
}

// Opções de segurança baseadas na NbCurlUtil
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Expect:',
    'Content-Type: application/x-www-form-urlencoded; charset=utf-8'
]);

// Executa a requisição
$content = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curlError = curl_error($ch);
curl_close($ch);

// Processa a resposta para obter apenas o body, como na NbCurlUtil
if (is_string($content)) {
    $response = substr($content, $headerSize);
} else {
    $response = '';
}

// Verifica se houve erro de Mod_Security (códigos comuns: 403 Forbidden ou 406 Not Acceptable)
if ($httpCode == 403 || $httpCode == 406) {
    $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    $urlBase = $baseUrl . $_SERVER['PHP_SELF'];
    $urlCompleta = $baseUrl . $_SERVER['REQUEST_URI'];
    // Mensagem personalizada para o usuário
    echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Connection Error - Mod_Security</title>
            <style>
                body { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; display: flex; justify-content: center; align-items: center; min-height: 90vh; background-color: #f0f4f8; text-align: center; }
                .container { max-width: 1000px; width: 90%; padding: 30px 40px; background-color: #ffffff; border: 1px solid #d1d9e6; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
                .message { font-size: 1.1em; color: #333; line-height: 1.6; margin-bottom: 15px;}
                .error-message { color: #dc3545; font-weight: bold; }
                .solution { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px; text-align: left; }
                .solution h3 { margin-top: 0; color: #856404; }
                .solution ul { margin: 10px 0; padding-left: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <p class="message error-message">
                    Oops! There was a problem connecting to Google. <br /> This happened because your hosting firewall (called Mod_Security) blocked the connection.
                </p>
                <div class="solution">
                    <h3>What to do to resolve:</h3>
                    <p><strong>This problem is caused by the hosting configuration, not the Joomla extension.</strong></p>
                    <p>Follow these steps:</p>
                    <ol>
                        <li style="margin-bottom: 15px;"><strong>Contact your hosting support</strong> (e.g.: HostGator, Locaweb, etc.) via chat or email.</li>
                        <li style="margin-bottom: 15px;"><strong>Send this ready message to hosting support:</strong><br>
                            <pre style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap; font-family: inherit; font-size: 0.9em; line-height: 1.5;">   Hello, support!

            I am facing a problem with Mod_Security blocking a Google Calendar OAuth integration on my Joomla site.

            What is happening:
            - My site tries to connect to Google Calendar via OAuth (secure authentication).
            - When Google redirects back to my site, Mod_Security is blocking the request.
            - This results in a 403 Forbidden error or similar.

            - <strong>Base affected URL:</strong> $urlBase
            - <strong>Full URL with parameters (current blocking example):</strong> $urlCompleta

            Parameters being sent in the request (and possibly blocked):
            - code: A long authorization code provided by Google (example: 4/0AfJohX...)
            - scope: Requested permissions (example: https://www.googleapis.com/auth/calendar.readonly)

            I need you to:
            1. Check if Mod_Security is active and configured to block these requests.
            2. Temporarily disable Mod_Security for this specific URL, or adjust the rules to allow parameters like 'code' and 'scope' which are essential for OAuth.
            3. If possible, exclude specific rules that are causing false positives (like SQL injection or XSS rules that confuse OAuth codes with attacks).

            This is a standard process for integrating with external APIs and does not represent a security threat. Many Joomla sites do this without problems.

            Please confirm when you resolve it so I can test again.

            Thank you!</pre>
                        </li>
                        <li style="margin-bottom: 15px;"><strong>If support doesn't help or takes too long:</strong> Consider switching to hosting that supports OAuth connections without excessive blocks, such as SiteGround, Kinsta, or AWS. They have more flexible configurations for sites integrating with external APIs.</li>
                    </ol>
                    <p><em>After resolving with hosting, try connecting again.</em></p>
                </div>
                <button onclick="window.close();" style="padding: 8px 15px; cursor: pointer; border-radius: 4px; border: 1px solid #6c757d; background-color: #6c757d; color: white; font-size: 0.9em; margin-top: 15px;">Close Window</button>
            </div>
        </body>
        </html>
    HTML;
    exit;
}

// Se houve erro de curl
if ($curlError) {
    http_response_code(500);
    echo 'Erro interno no servidor: ' . htmlspecialchars($curlError);
    exit;
}

// Exibe a resposta (HTML do popup)
echo $response;