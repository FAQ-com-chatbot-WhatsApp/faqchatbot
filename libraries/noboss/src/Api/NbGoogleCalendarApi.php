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

// Classe para requisicoes para API do Google Calendar
class NbGoogleCalendarApi extends NbGoogleApi{

    public $client;
    public $redirectURI;
    public $config;
    public $scope = 'https://www.googleapis.com/auth/calendar.readonly';
    public $oauthURL = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * Obtem lista de calendarios vinculados a um token
     *
     * @param   string      $token      access_token
     * 
     * @return  array       Dados dos calendarios vinculados ao token
     */
    public static function getCalendars($token) {
        $dest = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';
        $queryParams = [
            'maxResults' => 50,
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


            foreach ($json['items'] as $item) {
                $items[$item['id']] = $item;
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
     * Obtem a lista de eventos vinculados a um calendario dentro de um periodo de tempo
     *
     * @param   string      $token          access_token
     * @param   string      $calendarID     Id do calendario
     * @param   string      $start          lower bound (exclusive) RFC3339 timestamp + timezone. Eg: '2020-05-03T00:00:00+00:00'
     * @param   string      $end            upper bound (exclusive) RFC3339 timestamp + timezone. Eg: '2020-05-09T00:00:00+00:00'
     * @param   Boolean     $ignorePrivate  informa se eventos marcados como 'privados' devem ou nao ser ignorados          
     * 
     * @return  array of events inside $calendarID and between $start and $end
     */
    public static function getCalendarEvents($token, $calendarID, $start, $end, $ignorePrivate = true) {
        $dest = "https://www.googleapis.com/calendar/v3/calendars/" . \rawurlencode($calendarID) . "/events";
        $queryParams = [
            'maxResults' => 50,
            'timeMin' => $start,
            'timeMax' => $end,
            'singleEvents' => 'true'
        ];
        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];

        $items = [];

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

            foreach ($json['items'] as $item) {
                // Adiciona o ID do calendario nos dados do evento
                $item['calendarID'] = $calendarID;

                // Nao esta setado para igorar eventos 'privados' e o evento nao eh privado
                if(!$ignorePrivate || empty($item['visibility']) || $item['visibility'] != 'private'){
                    $items[] = $item;
                }
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
     * Obtem cores disponiveis para os eventos do Google
     *
     * @param   string      $token      access_token
     * 
     * @return  array       Cores disponiveis para os eventos
     */
    public static function getEventsColors($token) {
        $dest = 'https://www.googleapis.com/calendar/v3/colors';
        $queryParams = [
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

        foreach ($json['event'] as $key => $item) {
            $items[$key] = $item['background'];
        }
        
        return $items;
    }
}
