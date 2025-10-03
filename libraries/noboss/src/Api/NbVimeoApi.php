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

// Classe para requisicoes para API do Vimeo
class NbVimeoApi {

    public $redirectURI;
    public $client;
    public $config;
    public $scope = "public private";
    public $oauthURL = 'https://api.vimeo.com/oauth/authorize';
    public $oauthTokenURL = 'https://api.vimeo.com/oauth/access_token';

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
        $headers = [
            'Authorization' => 'basic ' . \base64_encode("{$this->config['client_id']}:{$this->config['client_secret']}"),
            'Content-Type' => 'application/json',
            'Accept' => 'application/vnd.vimeo.*+json;version=3.4'
        ];

        $queryParams = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectURI
        ];
        
        $response = NbCurlUtil::request("POST", $this->oauthTokenURL, \json_encode($queryParams), $headers);

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
    /* TODO: ateh 28/01/22 o Vimeo ainda não trabalha com refresh_token, mas no link https://developer.vimeo.com/api/reference/response/auth sinaliza que vao trabalhar no futuro.
            * Por esse motivo, no momento nao temos regeracao de token.
            * A principio, parece que no momento o token nao expira e essa funcao nem seria necessaria    
    */
    // public static function getNewAccessToken($clientId, $clientSecret, $refreshToken){
    //     $queryParams = [
    //         'client_id' => $clientId,
    //         'client_secret' => $clientSecret,
    //         'grant_type' => 'refresh_token',
    //         'refresh_token' => $refreshToken
    //     ];
        
    //     //TODO: adequar para vimeo
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

    //     //TODO: adequar para vimeo
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
    // TODO: essa requisicao retorna dados de todo o perfil do usuario vinculado ao access token, mas nao temos certeza como eh o comportamento qnd expira o access_token. Inclusive ha informacoes na internet que talvez o access_token nem expire nunca.
    public static function checkAccessToken($accessToken){
        $dest = "https://api.vimeo.com/oauth/verify";

        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Accept' => 'application/json'
        ];
        
        $response = NbCurlUtil::request("GET", "{$dest}", null, $headers);

        if ($response->success) {
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * Obtem access token valido (verifica se o enviado como parametro eh valido ou gera um novo a partir do refresh token)
     *
     * @param   String      $clientId       Id da api
     * @param   String      $clientSecret   Secret da api
     * @param   String      $refreshToken   Token de renovacao para geracao de novo token de acesso
     * @param   String      $accessToken    Token acesso atual
     * 
     * @return  mixed       acceess token ou false
     */
    public static function getValidAccessToken($clientId, $clientSecret, $refreshToken, $accessToken){
        $accessTokenIsValid = self::checkAccessToken($accessToken);

        // Access token ainda eh valido
        if ($accessTokenIsValid) {
            return $accessToken;
        } 
        // Gera novo access token
        else {
            return false;
            // TODO: comentada funcao original que usa refresh_token, pq o Vimeo ainda nao da suporte para isso
            //return self::getNewAccessToken($clientId, $clientSecret, $refreshToken);
        }
    }

    /**
     * Obtem playlists disponiveis ao token informado
     *
     * @param   string    $token  access_token
     * 
     * @return array with each playlist information
     */
    public static function getPlaylists($token) {
        $dest = 'https://api.vimeo.com/me/albums';

        $queryParams = [
            'per_page' => 100,
            'page' => 1
        ];
        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];
        $playlists = [];
        
        while (true) {
            $response = NbCurlUtil::request("GET", "{$dest}?" . http_build_query($queryParams), null, $headers);

            if (!$response->success) {
                $data = json_decode($response->data);
                throw new \Exception($data->error, 1);
            }

            $json = \json_decode($response->data, true);

            // echo '<pre>';
            // var_dump($json['data'][0]);
            // exit;

            // Percorre resultados para ajustar em novo array
            foreach ($json['data'] as $item) {
                $playlist = new \stdClass();

                // Obtem o id do video a partir do link do album
                preg_match('/\/(\d+)$/', $item['share_link'], $match);

                $playlist->id = $match[1];
                $playlist->title = $item['name'];
                
                $playlists[] = $playlist;
            }

            if (! $json['paging']['next']) {
                break;
            } else {
                $query['page']++;
            }
        }

        return $playlists;
    }

    /**
     * Lista informacoes dos videos de uma playlist
     *
     * @param   string      $token                  access_token
     * @param   string      $playlistID             id of playlist
     * @param   string      $direction              ordenacao por data do video (asc|desc|random)
     * @param   Boolean     $displayPlaylistData    informa se deve ser retornado tb os dados da playlist
     * 
     * @return array with videos found at $plalistID
     */
    public static function getVideosPlaylist($token, $playlistID, $direction = 'asc', $displayPlaylistData = false) {
        $dest = "https://api.vimeo.com/me/albums/{$playlistID}/videos";

        $queryParams = [
            'per_page' => 100,
            'page' => 1,
            'sort' => 'date'
        ];

        if($direction != 'random'){
            $queryParams['direction'] = $direction;
        }

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];
        
        $return = new \stdClass();
        $return->videos = [];

        // Setado para obter dados da playlist tb
        if($displayPlaylistData){
            $return->playlist = self::getPlaylistData($token, $playlistID);
        }

        $items = array();
        
        while (true) {
            $response = NbCurlUtil::request("GET", "{$dest}?" . http_build_query($queryParams), null, $headers);
            
            if (!$response->success) {
                $data = json_decode($response->data);
                throw new \Exception($data->error, 1);
            }

            $json = \json_decode($response->data, true);
            
            // echo '<pre>';
            // var_dump($json['data']);
            // exit;

            // Percorre todos videos da playlist
            foreach ($json['data'] as $video) {
                $videoObj = new \stdClass();
                // Obtem o id do video a partir do link
                preg_match('/\/(\d+)$/', $video['link'], $match);
                $videoObj->video_id = $match[1];
                $videoObj->title = $video['name'];
                $videoObj->subtitle = $video['description'];
                $videoObj->publication_date = $video['created_time'];
                
                // Url da imagem do video utilizada para thumb
                $videoObj->thumbnail_url = $video['pictures']['base_link'];
                
                // Esta definida a url da imagem do video utilizada para video principal (tamanho grande)
                if(!empty($video['pictures']['sizes'][6])){
                    $videoObj->max_thumbnail_url = $video['pictures']['sizes'][6]['link'];
                }
                // Monta url da imagem do video utilizando a mesma do thumb
                else{
                    $videoObj->max_thumbnail_url = $videoObj->thumbnail_url;
                }

                $items[] = $videoObj;
            }

            if (! $json['paging']['next']) {
                break;
            } else {
                $query['page']++;
            }
        }

        // Ordenacao eh randomica
        if($direction == 'random'){
            shuffle($items);
        }

        return $items;
    }

    /**
     * Obtem dados de uma playlist
     *
     * @param   string      $token          access_token
     * @param   string      $idPlaylist     Id da playlist
     * 
     * @return  array       Dados da playlist
     */
    private function getPlaylistData($token, $idPlaylist) {
        $dest = "https://api.vimeo.com/me/albums/{$idPlaylist}";

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];
        
        $response = NbCurlUtil::request("GET", "{$dest}", null, $headers);
        
        if (!$response->success) {
            $data = json_decode($response->data);
            throw new \Exception($data->error, 1);
        }

        $json = \json_decode($response->data, true);

        return $json;
    }

    /**
     * Obtem detalhes de um video
     *
     * @param   string      $token access_token
     * @param   string      $videoID id of video
     * 
     * @return  array       with details of $videoID
     */
    public static function getVideoData($token, $videoID) {
        $dest = "https://api.vimeo.com/videos/{$videoID}";

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];
        
        $response = NbCurlUtil::request("GET", "{$dest}", null, $headers);
        
        if (!$response->success) {
            $data = json_decode($response->data);
            throw new \Exception($data->error, 1);
        }

        $json = \json_decode($response->data, true);

        return $json;
    }
}
