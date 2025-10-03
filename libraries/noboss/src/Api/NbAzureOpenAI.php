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

class NbAzureOpenAI
{
    private string $apiKey; // Chave da API do Azure OpenAI
    private string $endpoint; // Endpoint da API no Azure
    private string $model; // Modelo utilizado (ex: gpt-4)
    private string $fileOriginal; // Caminho do arquivo original
    private string $fileTranslated; // Caminho do arquivo traduzido
    private bool $useOriginal; // Define se o arquivo original será utilizado na revisão

    /**
     * Construtor da classe.
     *
     * @param string $apiKey Chave da API do Azure OpenAI
     * @param string $endpoint URL do endpoint da API
     * @param string $model Modelo de IA a ser usado
     */
    public function __construct(string $apiKey, string $endpoint, string $model)
    {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->model = $model;
    }

    /**
     * Faz a revisão da traducao de arquivo INI do Joomla.
     *
     * @param string $fileOriginal Caminho do arquivo original
     * @param string $fileTranslated Caminho do arquivo traduzido
     * @param bool $useOriginal Se verdadeiro, usa o arquivo original na revisão
     * @return boolean true ou false
     * @throws \Exception Se houver erro ao ler ou gravar os arquivos
     */
    public function reviewTranslationFileJoomla(string $fileOriginal, string $fileTranslated, bool $useOriginal = true)
    {
        $translatedText = file_get_contents($fileTranslated);
        if ($translatedText === false) {
            throw new \Exception("Erro ao ler o arquivo traduzido.");
        }

        $originalText = $useOriginal ? file_get_contents($fileOriginal) : "";
        if ($useOriginal && $originalText === false) {
            throw new \Exception("Erro ao ler o arquivo original.");
        }

        // Comando para para IA executar (quanto maior for o prompt, mais tokens sao consumidos)
        $prompt = "Revise e melhore a tradução mantendo o idioma. Siga o padrão dos arquivos de tradução do Joomla mantendo ocorrências de '%s' e traduzindo apenas o valor das constantes que ficam após o = e dentro de aspas duplas. Mantenha tags html e corrija as tags que estiverem com sintaxe errada. Ignore as linhas que iniciarem com ';'.\n";

        // $prompt = "Tenho um arquivo .ini de tradução Joomla. Toda a tradução foi realizada automaticamente utilizando o Google Translate. " .
        //               "Preciso que a tradução seja revisada melhorando concordância, gramática e erros de tradução. " .
        //               "Mantenha o idioma original do texto do arquivo. Não traduza para português. " .
        //               "Mantenha tags HTML junto aos textos e ajuste tags que o Google Translate possa ter trocado por engano (exemplo: '&bull;'). " .
        //               "Mantenha as ocorrências de '%s' que são usadas posteriormente para fazer replace. " .
        //               "Não altere as constantes de tradução que são os identificadores em cada linha antes do símbolo de '='. " .
        //               "Linhas que iniciam com ';' são apenas comentários do arquivo e devem ser ignoradas.\n\n";
        
        // Revisar comparando com arquivo original
        if ($useOriginal) {
            $prompt .= "Aqui está o texto original:\n" . $originalText . "\n\nE aqui está a tradução que precisa ser revisada:\n" . $translatedText;
        }
        // Revisar sem comparar com arquivo original
        else{
            $prompt .= "Aqui está o texto traduzido que precisa ser revisado:\n" . $translatedText;
        }   
        
        $data = [
            "messages" => [
                ["role" => "system", "content" => "Você é um especialista em revisão de traduções para arquivos Joomla .ini."],
                ["role" => "user", "content" => $prompt]
            ],
            "max_tokens" => 100, // limite de tokens por chamada para ajudar no controle de custos
            "temperature" => 0.3, // quando menor o valor, mais direto e economico sera a resposta
            "top_p" => 1,
            "frequency_penalty" => 0,
            "presence_penalty" => 0
        ];

        try {
            $result = $this->sendRequest($data);

            if($result === false){
                throw new \Exception("Erro ao enviar a requisição para a API do Azure OpenAI.");
            }            

            // Atualiza o conteudo revisado no arquivo traduzido
            $this->safe_file_put_contents($fileTranslated, $result);

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Envia a requisição para a API do Azure OpenAI.
     *
     * @param array $data Dados da requisição
     * @return mixed Resposta da IA com a revisão da tradução ou false
     * @throws \Exception Se houver erro no retorno da API
     * 
     * Notas:
     *      * Lembrar que alem de criar o servico eh necessario depois criar um 'modelo de implementacao' para poder usar a api no Studio do OpenAI
     */
    private function sendRequest(array $data)
    {
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "api-key: {$this->apiKey}"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        // echo '<pre>';
        // var_dump($data);
        // exit;

        // echo '<pre>';
        // var_dump($result);
        // exit;

        if(isset($result['error'])){
            throw new \Exception($result['error']['message']);
        }

        return $result["choices"][0]["message"]["content"];
    }

    /**
     * Função estática para gravar dados em um arquivo com verificação de permissões.
     * 
     * Esta função tenta escrever os dados em um arquivo especificado. Se não houver permissões de
     * escrita adequadas no arquivo ou no diretório, ou se ocorrer qualquer outro erro durante
     * a operação, uma exceção será lançada.
     * 
     * @param string $filename Caminho do arquivo a ser gravado.
     * @param string $data Dados a serem gravados no arquivo.
     * 
     * @throws Exception Lança uma exceção em caso de falha na escrita do arquivo ou falta de permissões.
     * 
     * @return int|false Retorna o número de bytes gravados ou false em caso de falha.
     */
    public function safe_file_put_contents($filename, $data) {
        // Verifica se o arquivo é gravável
        if (!is_writable($filename) && !is_writable(dirname($filename))) {
            throw new \Exception("Permissão insuficiente para escrever no arquivo: " . $filename);
        }

        // Tenta escrever os dados no arquivo
        $result = file_put_contents($filename, $data);

        // Verifica se a operação de escrita falhou
        if ($result === false) {
            //FIXME: cai aqui, mas onde chama a funcao nao pega como excecao

            //FIXME: depois de resolver isso, verificar se o arquivo foi atualizado pq deixei texto com erros

            // FIXME: ver como usar direto a api openai e avaliar como ajusto essa classe para ter uma flag que permita utilizar openai direto ou via azure
            throw new \Exception("Falha ao escrever no arquivo: " . $filename);
        }

        return $result;
    }
}