<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Api;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Noboss\Library\Util\NbCurlUtil;
use Joomla\CMS\Factory;

/* TODO: anotações sobre testes feitos com API do Facebook em Jan 2022
    * A API do face é uma desgraça para trabalhar, cheia de burogracias e restrições
    * Existem varios tipos de token que podem ser necessários obter para as requisicoes.
        * Existe token de acesso do usuario, da página e do APP
        * Diferente do Google, nao existe token refresh (que permite renovar um token), mas existe token de longa duracao (60 ou 90 dias) que pode ser obtido a partir do token de acesso principal
    * Inicialmente iríamos utilizar a API apenas para buscar a thumb de um video público. Isso porque o Face não permite obter a thumb sem uso de uma API.
        * Se o video pertence a conta do usuario que logamos para obter o token de acesso, voce consegue obter o thumb ja com os dados que temos aqui construidos nesta API. O problema é se o video for de uma página, dai tu precisa ter token com permissao especifica naquela pagina.
        * Ficou muito complexo para apenas obter a porcaria de um thumb de um video e resolvemos abortar o uso.
*/

// Classe para requisicoes para API do Facebook
class NbFacebookApi {

    public $redirectURI;
    public $client;
    public $config;
    public $scope = "user_videos";
    public $oauthURL = 'https://www.facebook.com/v12.0/dialog/oauth';
    public $oauthTokenURL = 'https://graph.facebook.com/v12.0/oauth/access_token';

    /**
     * __construct
     *
     * @param   string      $redirectURI    url para redirecionar apos autorizar aplicacao (deve estar tb cadastrada no console da API)
     * @param   array       $config         matriz associativa, deve conter client_id e client_secret.
     * 
     * @return void
     */
    public function __construct($redirectURI, $config) {
        $this->redirectURI = $redirectURI;
        $this->config = $config;
    }

    /**
     * Realiza autorizacao de usuario via interface e depois obtem um token de aplicacao
     * 
     * Quando utilizar: 
     *      - Somente quando precisa autorizacao de um usuario antes de obter o token.
     *      - Quando nao precisar, execute diretamente a funcao 'authenticate'
     * 
     * Como funciona:
     *      1 - Funcao eh executada redirecionando para tela de autenticacao onde sera informado um usuario e senha
     *      2 - Apos autenticado usuario, ocorre um redirecionamento de volta para aplicacao conforme definida a url em         'redirectURI' executando o mesmo codigo que chamou essa funcao
     *      3 - A aplicacao ira chamar novamente essa funcao, agora passando 'code' na requisicao
     *      4 - A funcao ira cair agora no else para obter o token da requisicao e token de renovacao
     *
     * @return  json com token ou null
     */
    public function authorize() {
        // Redireciona usuario para tela de autenticacao
        $input = Factory::getApplication()->getInput();
        $code = $input->get('code', '', 'string');
        if (empty($code)) {
            $authURL = "{$this->oauthURL}?" . \http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->redirectURI,
            'state' => '1',
            'scope' => $this->scope
            ]);
            header('Location: ' . filter_var($authURL, FILTER_SANITIZE_URL));
        }
        // Segunda requisicao desta funcao (usuario ja autorizado): executa funcao que obtem o token
        else {
            $authCode = $code;
            return json_encode($this->authenticate($authCode));
        }
    }  

     /**
     * Autentica a aplicacao obtendo o token para proximas requisicoes
     *
     * @param   string  $code   returned by authorize()
     * 
     * @return  array access and refresh token
     */
    public function authenticate($code) {
        $queryParams = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->redirectURI,
            'client_secret' => $this->config['client_secret'],
            'code' => $code
        ];
        
        $response = NbCurlUtil::request("GET", "{$this->oauthTokenURL}?" . \http_build_query($queryParams));
        
        if (!$response->success) {
            $data = json_decode($response->data);
            if(!empty($data->error_description)){
                throw new \Exception($data->error_description.' | '.$data->error); 
            }
            else if(!empty($data->error->message)){
                throw new \Exception($data->error->message, $data->error->code);
            }
            else if(!empty($response->message)){
                throw new \Exception($response->message); 
            }
            else{
                throw new \Exception('Error.'); 
            }
        }
            
        return \json_decode($response->data, true);
    }

    /**
     * Obtem novo access token a partir do refresh token
     *
     * @param   String      $clientId       Id da api
     * @param   String      $clientSecret   Secret da api
     * @param   String      $refreshToken   Token de renovacao para geracao de novo token de acesso
     * 
     * @return  mixed       access token ou false em caso de erro
     */
    // FIXME: funcao copiada do google que temos que adequar ao face
    // public static function getNewAccessToken($clientId, $clientSecret, $refreshToken){
    //     $queryParams = [
    //         'client_id' => $clientId,
    //         'client_secret' => $clientSecret,
    //         'grant_type' => 'refresh_token',
    //         'refresh_token' => $refreshToken
    //     ];
        
    //     // FIXME: ver como obter para o face
    //     $response = NbCurlUtil::request("POST", 'https://oauth2.googleapis.com/token', $queryParams);

    //     $data = json_decode($response->data);
        
    //     if (!$response->success) {
    //         if(!empty($data->error_description)){
    //             throw new \Exception($data->error_description.' | '.$data->error); 
    //         }
    //         else if(!empty($data->error->message)){
    //             throw new \Exception($data->error->message, $data->error->code);
    //         }
    //         else if(!empty($response->message)){
    //             throw new \Exception($response->message); 
    //         }
    //         else{
    //             throw new \Exception('Error.'); 
    //         }
    //     }

    //     // FIXME: ver como obter para o face
    //     if (property_exists($data, 'expires_in') && $data->expires_in > 0) {
    //         return $data->access_token;
    //     }
    //     return false;
    // }

    /**
     * Verifica se access token informado eh valido
     *
     * @param   String      $accessToken    Token de acesso para requisicao na API
     * 
     * @return  boolean     true ou false
     */
    // FIXME: funcao copiada do google que temos que adequar ao face
    // public static function checkAccessToken($accessToken){
    //     // Realiza requisicao para API p/ verificar       
        
    //     // FIXME: ver como obter para o face 
    //     //$isAccessTokenValidJSON = NbCurlUtil::request('GET', "https://graph.facebook.com/debug_token?input_token={input-token}&access_token={valid-access-token");


    //     $isAccessTokenValidJSON = NbCurlUtil::request('GET', "https://oauth2.googleapis.com/tokeninfo?access_token={$accessToken}");

    //     $isAccessTokenValid = json_decode($isAccessTokenValidJSON->data);

    //     // Ainda eh valido
    //     if (property_exists($isAccessTokenValid, 'expires_in') && $isAccessTokenValid->expires_in > 0) {
    //         return true;
    //     }
        
    //     return false;
    // }

    /**
     * Obtem access token valido (verifica se o token enviado como parametro eh valido ou gera um novo a partir do refresh token)
     * 
     *
     * @param   String      $clientId       Id da api
     * @param   String      $clientSecret   Secret da api
     * @param   String      $refreshToken   Token de renovacao para geracao de novo token de acesso
     * @param   String      $accessToken    Token acesso atual
     * 
     * @return  mixed       acceess token ou false
     */
    // FIXME: funcao copiada do google que temos que adequar ao face
    // TODO: essa funcao eh executada a partir da funcao getValidAccessToken do field nobossapiconnection para verificar a validade do token e renovar se necessario. Precisamos lembrar que no face nao existe token refresh. Apenas token de longa duracao.
    // public static function getValidAccessToken($clientId, $clientSecret, $refreshToken, $accessToken){
    //     $accessTokenIsValid = self::checkAccessToken($accessToken);

    //     // Access token ainda eh valido
    //     if ($accessTokenIsValid) {
    //         return $accessToken;
    //     } else {
    //         return self::getNewAccessToken($clientId, $clientSecret, $refreshToken);
    //     }
    // }

    /**
     * Obtem todos videos de um usuario
     *
     * @param   string      $token      access_token
     * @param   string      $userId     id do usuaqio
     * 
     * @return  array       videos do usuario
     */
    // FIXME: TERMINAR DE DESENVOLVER A FUNCAO - TEMOS QUE VER COMO OBTER O ID DO USUARIO QUE LOGOU
    public function getVideosUser($token, $userId) {
        $dest = "https://graph.facebook.com/v12.0/{$userId}/videos";

        $queryParams = [
            //'fields' => 'description,title,created_time,thumbnails',
            'access_token' => $token
        ];

        $headers = [
        'Accept' => 'application/json'
        ];
        
        $response = NbCurlUtil::request("GET", "{$dest}?" . \http_build_query($queryParams), null, $headers);
        
        if (!$response->success) {
            $data = json_decode($response->data);
            if(empty($data->error->message)){
                throw new \Exception($data->error_description.' | '.$data->error);   
            }
            else{
                throw new \Exception($data->error->message, $data->error->code);
            }
        }

        $json = \json_decode($response->data, true);
        
        return $json;
    }

    /**
     * Obtem videos de uma pagina
     *
     * @param   string      $token      access_token
     * @param   string      $pageId     id da pagina
     * 
     * @return  array       videos do usuario
     */
    // FIXME: TERMINAR DE DESENVOLVER A FUNCAO - TEMOS QUE VER COMO OBTER O ID DA PAGINA. SERIA NECESSARIO ANTES TER OUTRA FUNCAO QUE BUSQUE TODAS FAGINAS VINCULADAS A UM USUARIO
    public function getVideosPage($token, $pageId) {
        $dest = "https://graph.facebook.com/v12.0/{$pageId}/videos";

        $queryParams = [
            //'fields' => 'description,title,created_time,thumbnails',
            'access_token' => $token
        ];

        $headers = [
        'Accept' => 'application/json'
        ];
        
        $response = NbCurlUtil::request("GET", "{$dest}?" . \http_build_query($queryParams), null, $headers);
        
        if (!$response->success) {
            $data = json_decode($response->data);
            if(empty($data->error->message)){
                throw new \Exception($data->error_description.' | '.$data->error);     
            }
            else{
                throw new \Exception($data->error->message, $data->error->code);
            }
        }

        $json = \json_decode($response->data, true);
        
        return $json;
    }

    /**
     * Obtem detalhes de um video (permite somente videos do proprio usuario)
     *
     * @param   string      $token access_token
     * @param   string      $videoID id of video
     * 
     * @return  array       dados do video
     */
    public static function getVideoData($token, $videoID) {
        $dest = "https://graph.facebook.com/v12.0/{$videoID}";

        //FIXME: retorna varios thumbnails e temos que ver qual deles tem o parametro is_preferred igual true

        $queryParams = [
            // FIXME: ver se quero manter limitacao dos campos retornados
            'fields' => 'description,title,created_time,thumbnails',
            'access_token' => $token
        ];

        $headers = [
        'Accept' => 'application/json'
        ];
        
        $response = NbCurlUtil::request("GET", "{$dest}?" . \http_build_query($queryParams), null, $headers);
        
        if (!$response->success) {
            $data = json_decode($response->data);
            if(empty($data->error->message)){
                throw new \Exception($data->error_description.' | '.$data->error);     
            }
            else{
                throw new \Exception($data->error->message, $data->error->code);
            }
        }

        $json = \json_decode($response->data, true);
        
        return $json;
    }
}
