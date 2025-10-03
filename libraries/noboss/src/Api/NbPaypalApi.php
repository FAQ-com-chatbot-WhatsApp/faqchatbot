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

// Classe para requisicoes para API do Paypal
class NbPaypalApi {
    private $config;
    private $host;

    /**
     * __construct
     *
     * @param   array       $config         matriz associativa, deve conter client_id, client_secret e pode conter tb 'sandbox'.
     * @param   boolean     $sandbox        Informe se deve executar requisicao no sandbox
     * 
     * @return void
     */
    public function __construct($config, $sandbox = false) {
        $this->config = $config;

        if($sandbox){
            $this->host = 'https://api.sandbox.paypal.com/';
        }
        else{
            $this->host = 'https://api.paypal.com/';
        }
    }

     /**
     * Autentica a aplicacao obtendo o token para proximas requisicoes
     * 
     * @return  array access and refresh token
     */
    public function authenticate() {
        $dataPost = array(
            'grant_type' => 'client_credentials');
            
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode( $this->config['client_id'] . ':' . $this->config['client_secret'] )
            ];

        // Realiza a requisição
        $response = NbCurlUtil::request("POST", $this->host.'v1/oauth2/token', $dataPost, $headers);

        if (!$response->success) {
            $data = json_decode($response->data);
          
            if(!empty($data->details)){
                $data->message .= "<br /><b>Details:</b> ".$data->details['0']->issue;
            }
            throw new \Exception($data->message, 1);
        }

        $response->data = json_decode($response->data);

        return $response->data->access_token;
    }

    /**
     * Obtem transacoes realizadas por periodo
     *
     * @param   string    $token    access_token
     * @param   array     $options  parametros para enviar na requisicao conforme documentacao do paypal (obrigatorio 'start_date' e 'end_date')
     * 
     * Documentacao: https://developer.paypal.com/docs/api/transaction-search/v1/#transactions_get
     * 
     * @return
     */
    public function geTransactions($token, $options) {
        $dest = $this->host.'v1/reporting/transactions';

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];
        
        $response = NbCurlUtil::request("GET", "{$dest}?" . http_build_query($options), null, $headers);

        if (!$response->success) {
            $data = json_decode($response->data);

            // echo '<pre>';
            // var_dump($data);
            // exit;

            if(!empty($data->details)){
                $data->message .= "<br /><b>Details:</b> ".$data->details['0']->issue;
            }
            throw new \Exception($data->message, 1);
        }

        $data = \json_decode($response->data, true);

        return $data;
    }

    /**
     * Obtem detalhes de um pagamento, incluindo infos de conversao automatica de moeda
     *
     * @param   string    $token      access_token
     * @param   int       $idPayment  id do pagamento (valor payment.cart obtido no retorno do Paypal para o pagamento)
     * 
     * NOTA: no sandbox nao esta retornando dados de conversao porque o paypal nao esta convertendo a moeda automaticamente como faz em producao.
     * 
     * Documentacao: https://developer.paypal.com/docs/api/orders/v2/
     * 
     * @return
     */
    public function getOrderPayment($token, $idPayment){
        $dest = $this->host."v2/checkout/orders/{$idPayment}";

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ];

        $response = NbCurlUtil::request("GET", "{$dest}", null, $headers);

        // echo '<pre>';
        // var_dump($response);
        // exit;

        if (!$response->success) {
            $data = json_decode($response->data);

            if(!empty($data->details)){
                $data->message .= "<br /><b>Details:</b> ".$data->details['0']->issue;
            }
            throw new \Exception($data->message, 1);
        }

        $data = \json_decode($response->data, true);

        // echo '<pre>';
        // var_dump($data);
        // exit;

        // Valor de um produto especifico
        // $data['purchase_units'][0]['items'][0]['unit_amount']['currency_code']
        // $data['purchase_units'][0]['items'][0]['unit_amount']['value']

        // $paymentData = $data['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown'];
       
        // // Valor total bruto (sem conversao de moeda)
        // $paymentData['gross_amount']['currency_code']; // USD
        // $paymentData['gross_amount']['value'];

        // // Valor da taxa paypal (sem conversao de moeda)
        // $paymentData['paypal_fee']['currency_code']; // USD
        // $paymentData['paypal_fee']['value'];

        // // Valor liquido (total bruto - taxa paypal) (sem conversao de moeda)
        // $paymentData['net_amount']['currency_code']; // USD
        // $paymentData['net_amount']['value'];

        // // Valor liquido convertido para real
        // $paymentData['receivable_amount']['currency_code']; // BRL
        // $paymentData['receivable_amount']['value'];

        // // Valor do cambio
        // $paymentData['exchange_rate']['source_currency']; // Moeda de venda. Ex: USD
        // $paymentData['exchange_rate']['target_currency']; // Moeda convertido. Ex: BRL
        // $paymentData['exchange_rate']['value']; // Valor cambio. Ex? 4.6127

        return $data;
    }
}
