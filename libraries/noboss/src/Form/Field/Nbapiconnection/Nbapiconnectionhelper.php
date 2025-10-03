<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nbapiconnection;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Api\NbFacebookApi;
use Noboss\Library\Api\NbGoogleCalendarApi;
use Noboss\Library\Api\NbVimeoApi;
use Noboss\Library\Api\NbGoogleApi;
use Noboss\Library\Api\NbYoutubeApi;
use Noboss\Library\Util\NbModulesUtil;

defined('_JEXEC') or die;

/**
 * Classe de campo personalizado de conexao com api (utilizada diretamente pelos modulos Calendar e Video Gallery)
 */
class Nbapiconnectionhelper {

    /**
	 * Metodo que fica escutando a sessao para verificar se o token foi gerado
     * 
	 */
	public static function verifyTokenSession() {
        $app = Factory::getApplication();
        $input = $app->input;
       
        $api = $input->get('api', '', "STRING");

        // Prepara a resposta padrao
        $response = ['status' => 'waiting'];

        try {
            // Token esta definido no cookie: pega os dados e limpa o cookie
            if (isset($_COOKIE["nb_token_values_{$api}"])) {
                $response['status'] = 'ready';
                
                $tokenValues = $_COOKIE["nb_token_values_{$api}"];
                
                $response['data'] = [
                    'tokenValues' => $tokenValues,
                    'api' => $api
                ];
                
                // Limpa os cookies após ler
                setcookie("nb_token_values_{$api}", '', time() - 3600, '/');
            }
        
        } catch (\Exception $e) {
            // Retorna um erro genérico no JSON em caso de falha ao acessar a sessao
            $response = ['status' => 'error', 'message' => 'Internal server error while checking session in verifyTokenSession function of Nbapiconnectionhelper.php file of library.'];
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        echo json_encode($response);       
        exit();
    }

    /**
	 * 
     * Funcao principal responsavel por autenticar o usuario na API e obter o token
     * 
     * Processo executado a partir desta funcao:
     *     1º recebe o client_id e client_secret e redireciona o usuario para a url de autenticacao da API
     *     2º com a autenticacao do usuario em tela, API externa retorna o usuario para executar o NbCallbackProxy.php passando o code recebido da API
     *     2º o arquivo NbCallbackProxy.php executa novamente esta funcao via cUrl para autenticar a API, obter o token e salvar no cookie
	 */
	public static function generateToken() {
		$app = Factory::getApplication();
        $input = $app->input;
        
        $clientId = $input->get('client_id', '', "RAW");
        $clientSecret = $input->get('client_secret', '', "RAW");
        $code = $input->get('code', '', "RAW");
        $api = $input->get('api', '', "RAW");
        $isProxy = $input->get('proxy', 0, "INT");

        // Quando possui '+' o Joomla acaba trocando por ' ' e por isso temos que ajustar
        $clientSecret = str_replace(" ", "+", $clientSecret);

        if(empty($api)){
            exit('Api not defined.');
        }

        // Salva nome da api em cookie para o proxy usar
        setcookie('nb_api', $api, time() + 3600, '/'); // válido por 1 hora
        
        // Client id definido: salva em cookie
        if (!empty($clientId)) {
            setcookie('nb_client_id_' . $api, $clientId, time() + 3600, '/'); // válido por 1 hora
        }
        // Obtem o client id salvo em cookie
        else {
            $clientId = $_COOKIE['nb_client_id_' . $api] ?? '';
        }  

        // Client secret definido: salva em cookie
        if (!empty($clientSecret)) {
            // $session->set('client_secret_'.$api, $clientSecret);
            setcookie('nb_client_secret_' . $api, $clientSecret, time() + 3600, '/'); // válido por 1 hora
        }
        // Obtem o client secret salvo em cookie
        else {
           // $clientSecret = $session->get('client_secret_'.$api);
           $clientSecret = $_COOKIE['nb_client_secret_' . $api] ?? '';
        }

        // Gera a URI a ser enviada para o redirect da API
        $redirectURI = Uri::root() . 'libraries/noboss/src/Form/Field/Nbapiconnection/NbCallbackProxy.php';
        
        $messageError = '';
        $saveSuccess = false;

        try{
            // Executa funcao authorize conforme API. Na primeira vez redireciona para autorizacao do usuario e na segunda vez obtem os tokens
            switch ($api) {
                // Youtube
                case 'youtube':
                    $youtube = new NbYoutubeApi($redirectURI, array('client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code));
                    $tokenValues = $youtube->authorize(); 
                break;

                // Google Calendar
                case 'googlecalendar':
                    $googlecalendar = new NbGoogleCalendarApi($redirectURI, array('client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code));
                    $tokenValues = $googlecalendar->authorize(); 
                break;

                // Vimeo
                case 'vimeo':
                    $vimeo = new NbVimeoApi($redirectURI, array('client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code));
                    $tokenValues = $vimeo->authorize(); 
                    break;
                    
                // Facebook
                case 'facebook':
                    $facebook = new NbFacebookApi($redirectURI, array('client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code));
                    $tokenValues = $facebook->authorize(); 
                break;

                default:
                    exit('API type not defined in NobossNobossapiconnectionhelper:generateToken()');
                break;
            }

            if(empty($tokenValues)){
                $messageError = 'Token not obtained in NobossNobossapiconnectionhelper:generateToken()';
            }
        }catch (\Exception $e){
            $messageError .= $e->getMessage();
            if(!empty($e->getCode())){
                $messageError .= "<br> Error code: ".$e->getCode();
            }
        }

        // Daqui em diante acessa os dados somente na segunda requisicao, quando ja possui os tokens, preparando para salvar na sessao para leitura pela funcao verifyTokenSession

        if(empty($messageError)){
            $saveSuccess = true;
        }

        $dataToSave = [
            'tokenValues' => $tokenValues,
            'api' => $api
        ];

        // Salva os dados em cookies para acesso posterior
        setcookie("nb_token_values_{$api}", $tokenValues, time() + 3600, '/'); // válido por 1 hora

        // Como o aquivo NbCallbackProxy.php executa esta funcao via cUrl, precisamos preparar script JS para definir cookies no navegador
        $extraScript = '';
        if ($isProxy && $saveSuccess) {
            $cookieValues = addslashes($dataToSave['tokenValues']);

            // Define script JS para criar o cookie no navegador
            $extraScript = "<script>document.cookie = 'nb_token_values_{$api}={$cookieValues}; path=/; max-age=3600';</script>";
        }

        // Exibe a mensagem de sucesso ou erro
        self::displayPopupOutput($saveSuccess, $messageError, 7, $extraScript);

        exit;
    }
    
    /**
     * Função para gerar e exibir o HTML/JavaScript de uma página popup.
     * Mostra uma mensagem de sucesso com contador regressivo e fechamento automático,
     * ou uma mensagem de erro com um botão para fechar manualmente.
     *
     * @param bool $successStatus True se a operação anterior foi bem-sucedida, False caso contrário.
     * @param string $messageError Mensagem com texto a ser exido
     * @param int $initialSeconds Tempo inicial do contador regressivo.
     * @param string $extraScript Script JavaScript extra a ser incluído.
     * @return void Esta função imprime o HTML diretamente na saída.
     */
    public static function displayPopupOutput(bool $successStatus, string $messageError, int $initialSeconds, string $extraScript = ''): void{
        // 1. Define a mensagem e o script a serem usados com base no status
        $messageHtml = '';
        $scriptOrButtonHtml = ''; // Armazenará o script JS ou o botão HTML

        if ($successStatus) {
            // Mensagem de sucesso com o span para o contador
            $messageHtml = 'Connection successful. This window will close automatically in <span id="countdown">' . $initialSeconds . '</span> seconds.';

            // Script de countdown (usando sprintf para injetar $initialSeconds de forma segura)
            $scriptOrButtonHtml = sprintf(
                <<<'JS'
                <script type="text/javascript">
                    (function() {
                        const countdownElement = document.getElementById("countdown");
                        let secondsRemaining = %d; // Valor inicial vindo do PHP
                        let countdownInterval = null;

                        const updateCountdown = () => {
                            // Atualiza o elemento visual se ele existir
                            if (countdownElement) {
                                countdownElement.textContent = secondsRemaining;
                            }

                            // Verifica se o tempo acabou
                            if (secondsRemaining <= 0) {
                                // Para o intervalo (clearInterval(null) não causa erro)
                                clearInterval(countdownInterval);
                                window.close();
                            } else {
                                // Decrementa para a próxima iteração
                                secondsRemaining--;
                            }
                        };

                        // Garante que o elemento existe ANTES de iniciar o timer
                        if (countdownElement) {
                            updateCountdown(); // Chama uma vez para mostrar o valor inicial
                            // Atribui o ID do intervalo à variável (agora ela existe no escopo)
                            countdownInterval = setInterval(updateCountdown, 1000);
                        } else {
                            // Não precisa do clearInterval aqui, pois o intervalo nunca foi iniciado
                            setTimeout(window.close, (%d + 1) * 1000);
                        }
                    })();
                </script>
                JS
                , // Fim do bloco Nowdoc/Heredoc para sprintf
                $initialSeconds, // Primeiro %d
                $initialSeconds  // Segundo %d (para o setTimeout de fallback)
            );

        } else {
            // Mensagem de erro
            $messageHtml = $messageError;;
            // Botão para fechar manualmente
            $scriptOrButtonHtml = '<button onclick="window.close();" style="padding: 8px 15px; cursor: pointer; border-radius: 4px; border: 1px solid #6c757d; background-color: #6c757d; color: white; font-size: 0.9em; margin-top: 15px;">Close Window</button>';
        }

        // 2. Garante que os headers HTTP corretos sejam enviados (se ainda não foram)
        if (!headers_sent()) {
            if (ob_get_level()) {
                ob_end_clean(); // Limpa buffer para evitar saídas indesejadas
            }
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Data no passado para não cachear
        }

        // 3. Define a classe CSS para a mensagem
        $messageContainerClass = $successStatus ? 'success-message' : 'error-message';

        // 4. Gera e imprime o HTML final usando Heredoc
        // A variável $messageHtml já contém o span se for sucesso.
        // A variável $scriptOrButtonHtml contém o <script> ou o <button>.
        echo <<<HTML
            <!DOCTYPE html>
            <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <title>Connection - Status</title>
                <style>
                    body { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; display: flex; justify-content: center; align-items: center; min-height: 90vh; background-color: #f0f4f8; text-align: center; }
                    .container { max-width: 500px; width: 90%; padding: 30px 40px; background-color: #ffffff; border: 1px solid #d1d9e6; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
                    .message { font-size: 1.1em; color: #333; line-height: 1.6; margin-bottom: 15px;}
                    #countdown { font-weight: bold; margin: 0 3px; color: #0062cc; } /* Estilo para o número */
                    .error-message { color: #dc3545; font-weight: bold; } /* Vermelho para erro */
                    .success-message { color: #198754; } /* Verde para sucesso */
                </style>
            </head>
            <body>
                <div class="container">
                    <p class="message {$messageContainerClass}">
                        {$messageHtml} </p>
                    {$scriptOrButtonHtml} </div>
                {$extraScript}
            </body>
            </html>
            HTML;
    } 


    /**
	 * Obtem access_token valido (recebe um verificando se eh valido ou gera um novo a partir de um refresh_token)
	 */
    public static function getValidAccessToken() {
        error_reporting(0);
		$app = Factory::getApplication();
        $input = $app->input;
       
        $clientId = $input->get('client_id', '', "RAW");
        $clientSecret = $input->get('client_secret', '', "RAW");
        $refreshToken = $input->get('refresh_token', '', "RAW");
        $accessToken = $input->get('access_token', '', "RAW");
        $api = $input->get('api', '', "RAW");

        $response = array();

        if(empty($api)){
            $response['success'] = 0;
            $response['status'] = '-1';
            $response['message'] = 'Api not defined.';
            exit(json_encode($response));
        }

        try{
            // Executa metodos conforme API requisitada
            switch ($api) {
                // Apis do google
                case 'youtube':
                case 'googlecalendar':                   
                    // Obtem access token valido (caso o atual nao seja ainda valido)
                    $validAccessToken = NbGoogleApi::getValidAccessToken($clientId, $clientSecret, $refreshToken, $accessToken);

                    // Access token nao era valido e nao foi possivel gerar um novo (refresh_token inválido)
                    if($validAccessToken == false) {
                        $response['success'] = 1;
                        $response['status'] = '0';
                        exit(json_encode($response));
                    }

                    // Nao foi alterado o access token
                    if($accessToken == $validAccessToken){
                        $response['success'] = 1;
                        $response['status'] = '1';
                        $response['new_access_token'] = '';
                        exit(json_encode($response));
                    }

                    // Alterado o access token
                    $response['success'] = 1;
                    $response['status'] = '1';
                    $response['new_access_token'] = $validAccessToken;
                    exit(json_encode($response));

                break;

                // Vimeo
                case 'vimeo':                   
                    // Obtem access token valido (caso o atual nao seja ainda valido)
                    $validAccessToken = NbVimeoApi::getValidAccessToken($clientId, $clientSecret, $refreshToken, $accessToken);

                    // Access token nao era valido e nao foi possivel gerar um novo (refresh_token inválido)
                    if($validAccessToken == false) {
                        $response['success'] = 1;
                        $response['status'] = '0';
                        exit(json_encode($response));
                    }

                    // Nao foi alterado o access token
                    if($accessToken == $validAccessToken){
                        $response['success'] = 1;
                        $response['status'] = '1';
                        $response['new_access_token'] = '';
                        exit(json_encode($response));
                    }

                    // Alterado o access token
                    $response['success'] = 1;
                    $response['status'] = '1';
                    $response['new_access_token'] = $validAccessToken;
                    exit(json_encode($response));

                    break;

            }
        } catch (\Exception $e){
            if(!empty($e->getCode())){
                $error = "Code: ".$e->getCode();
            }
            $error .= $e->getMessage();

            $response['success'] = 0;
            $response['status'] = '-1';
            $response['message'] = $error;
            exit(json_encode($response));
        }
    }

    /**
     * Atualiza no banco de dados o valor de access_token (considerando que campo esteja dentro de uma modal de um modulo)
	 *
     * @param   String      $accessToken    Token de acesso para requisicao na API
     * @param   Int         $idModule       Id do modulo
     * @param   String      $aliasField     Alias da modal (qnd tiver modal) ou alias do field de conexao (qnd nao tiver modal)
     * @param   Boolean     $isModal        Informa se os dados da conexao estao em uma modal
     * 
     * @return  Boolean     true ou false
	 */
    public static function updateAccessTokenDb($accessToken, $idModule, $aliasField, $isModal = true){
        // Obtem parametros do modulo
        $dataParams = NbModulesUtil::getDataModule($idModule, true); 

        // Campo de conexao esta dentro de modal
        if($isModal){
            $modalName = $aliasField; // Alias da modal
            // Extrai somente os parametros do campo de api carregado na modal
            $paramsModalApi = json_decode($dataParams->$modalName);
            if(empty($paramsModalApi)){
                return false;
            }

            $connection = $paramsModalApi->connection;
        }
        else{
            $connection = $dataParams->{$aliasField};
        }

        // Extrai dados da conexao da API
        $paramsApiConnection = json_decode($connection);
        if(empty($paramsApiConnection)){
            return false;
        }

        // Atualiza parametro do token conforme novo valor recebido
        $paramsApiConnection->access_token = $accessToken;

        // Encoda novamente os parametros
        $connection = json_encode($paramsApiConnection);

        // Campo de conexao esta dentro de modal
        if($isModal){
           $paramsModalApi->connection = $connection;
           $paramsModalApi = json_encode($paramsModalApi);
           $dataParams->$modalName = $paramsModalApi;
        }
        else{
            $dataParams->{$aliasField} = $connection;
        }

        // Atualiza o access_token no banco
        if(NbModulesUtil::setDataModule($idModule, $dataParams, true)){
            return true;
        }
        return false;
    }
}
