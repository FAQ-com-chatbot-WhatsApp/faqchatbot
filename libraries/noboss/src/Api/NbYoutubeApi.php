<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Api;

use Noboss\Library\Util\NbCurlUtil;
use Noboss\Library\Api\NbGoogleApi;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Classe para requisicoes para API do Youtube
class NbYoutubeApi extends NbGoogleApi{

    public $client;
    public $redirectURI;
    public $config;
    public $scope = 'https://www.googleapis.com/auth/youtube.readonly';
    public $oauthURL = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * Obtem playlists disponiveis ao token informado
     *
     * @param   string    $token  access_token
     * 
     * @return array with each playlist information
     */
    public static function getPlaylists($token) {
        $dest = 'https://www.googleapis.com/youtube/v3/playlists';
        $queryParams = [
            'part' => 'snippet',
            'maxResults' => 50,
            'mine' => true
        ];
        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];

        $items = [];

        // lista pode retornar varias páginas, fazemos um loop até $pageToken não ser válido
        while (true) {
            $response = NbCurlUtil::request("GET", "{$dest}?" . \http_build_query($queryParams), null, $headers);

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

            $json = \json_decode($response->data, true);

            // echo '<pre>';
            // var_dump($json);
            // exit;

            // Percorre resultados para ajustar em novo array
            foreach ($json['items'] as $item) {
                $playlist = new \stdClass();

                $playlist->id = $item['id'];
                //$playlist->id = $item['snippet']['channelId'];
                $playlist->title = $item['snippet']['title'];                
                
                $items[] = $playlist;
            }

            if (\array_key_exists('nextPageToken', $json)) {
                $queryParams['pageToken'] = $json['nextPageToken'];
            } else {
                break;
            }
        }
        return $items;
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
        $dest = 'https://www.googleapis.com/youtube/v3/playlistItems';
        $queryParams = [
            'part' => 'snippet',
            'maxResults' => 50,
            'playlistId' => $playlistID
        ];

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

        // lista pode retornar varias páginas, fazemos um loop até $pageToken não ser válido
        while (true) {
            $response = NbCurlUtil::request("GET", filter_var("{$dest}?" . \http_build_query($queryParams), FILTER_SANITIZE_URL), null, $headers);
            
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

            $json = \json_decode($response->data, true);

            // echo '<pre>';
            // var_dump($json['items']);
            // exit;

            // Percorre todos videos da playlist
            foreach ($json['items'] as $video) {
                $videoObj = new \stdClass();
                $videoObj->title = $video['snippet']['title'];
                $videoObj->subtitle = $video['snippet']['description'];
                $videoObj->video_id = $video['snippet']['resourceId']['videoId'];
                $videoObj->publication_date = $video['snippet']['publishedAt'];

                // Esta definida a url da imagem do video utilizada para thumb
                if(!empty($video['snippet']['thumbnails']['high']['url'])){
                    $videoObj->thumbnail_url = $video['snippet']['thumbnails']['high']['url'];
                }
                // Monta url thumb manualmente
                else{
                    $videoObj->thumbnail_url = "//img.youtube.com/vi/{$videoObj->video_id}/0.jpg";
                }

                // Esta definida a url da imagem do video utilizada para video principal (tamanho grande)
                if(!empty($video['snippet']['thumbnails']['maxres']['url'])){
                    $videoObj->max_thumbnail_url = $video['snippet']['thumbnails']['maxres']['url'];
                }
                // Monta url da imagem do video utilizando a mesma do thumb
                else{
                    $videoObj->max_thumbnail_url = $videoObj->thumbnail_url;
                }

                $items[] = $videoObj;
            }

            if (\array_key_exists('nextPageToken', $json)) {
                $queryParams['pageToken'] = $json['nextPageToken'];
            } else {
                break;
            }
        }

        // Ordenacao eh randomica
        if($direction == 'random'){
            shuffle($items);
        }
        // Ordenacao eh 'desc' ou 'asc'
        else if($direction == 'desc' || $direction == 'asc'){
            usort($items, function ($a, $b) use ($direction) {
                if ($direction == 'asc') {
                    return strtotime($a->publication_date) < strtotime($b->publication_date) ? -1 : 1;
                }
                return strtotime($a->publication_date) > strtotime($b->publication_date) ? -1 : 1;
            });
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
    public static  function getPlaylistData($token, $idPlaylist) {
        
        $dest = 'https://www.googleapis.com/youtube/v3/playlists';
        $queryParams = [
            'part' => 'snippet',
            'maxResults' => 1,
            'id' => $idPlaylist
        ];
        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];

        $items = [];

        $response = NbCurlUtil::request("GET", "{$dest}?" . \http_build_query($queryParams), null, $headers);
       
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

        $json = \json_decode($response->data, true);

        return $json['items'][0]['snippet'];
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
        $dest = 'https://www.googleapis.com/youtube/v3/videos';

        $queryParams = [
            'part' => 'snippet,contentDetails',
            'id' => $videoID
        ];
        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];

        $response = NbCurlUtil::request("GET", "{$dest}?" . http_build_query($queryParams), null, $headers);

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

        $json = \json_decode($response->data, true);

        // Video nao encontrado
        if (!$json['items']) {
            return false;
        }

        return $json['items'][0];
    }
}
