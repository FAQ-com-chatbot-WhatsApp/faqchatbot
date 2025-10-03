<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Util;

use Joomla\CMS\Factory;
use Noboss\Library\Util\NbLoadextensionAssetsUtil;
use Noboss\Library\Form\Field\Nblicense\NblicenseModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

// Classe de apoio para suportes tecnico da equipe No Boss Extensions com as extensoes
class NbSupportUtil {
    
    /**
     * Retorna dump de insert para tabelas de uma extensao especificada
     * 
     * @param     POST      token               token da licença do usuario para permitir requisicao
     * @param     POST      extension_name      alias da extensao (ex: 'mod_nobosscalendar')
     * @param     POST      id_module           id do modulo, quando desejado restringir (opcional)
     * 
     * @return void
     */
    public static function generatesDumpExtension() {
        // Permite requisicoes da plataforma no boss extensions
        header('Access-Control-Allow-Origin: https://www.nobossextensions.com');

        //pega as configuracoes globais
        $config = Factory::getConfig();

        $return = new \stdClass();
        $return->error = 0;

        // Token recebido na requisicao para bater com o do banco
        $tokenRequisicao = Factory::getApplication()->input->post->get('token', '', 'string');
        // Obtem o nome da extensao solicitada para gerar dump
        $extensionName = Factory::getApplication()->input->post->get('extension_name', '', 'string');
        // Id do modulo solicitado (parametro opcional)
        $idModule = Factory::getApplication()->input->post->get('id_module', '', 'INT');

        // Dados obrigatorios nao enviados na requisicao
        if (empty($tokenRequisicao) || empty($extensionName)){
            $return->error = 1;
            $return->message = '<b>Erro:</b> <br /><br /> É obrigatório o envio do TOKEN e ALIAS DA EXTENSÃO na requisição via POST.';
            exit(json_encode($return));
        }

        // Obtem o token e plano da base local 
        $tokenPlanArray = NblicenseModel::getlocalLicenseData($extensionName);

        // Token da extensao na base local, caso encontrado
        $tokenLocal = array_key_exists("token", $tokenPlanArray) ? $tokenPlanArray['token'] : '';

        // Token nao localizado na base local
        if (empty($tokenLocal)){
            $return->error = 1;
            $return->message = 'Não foi localizado token para a extensão solicitada na base de dados do usuário.';
            exit(json_encode($return));
        }

        // Token da requisicao diferente da base local
        if($tokenRequisicao != $tokenLocal){
            $return->error = 1;
            $return->message = '<b>Acesso negado:</b> <br /><br />O token enviado na requisição é diferente do token da base de dados do usuário.';
            exit(json_encode($return));
        }

        // Nome da tabela de modulos
        $tableModules = $config->get('dbprefix').'modules';

        $outString = '';
        $whereIdModule = ''; 

        // Restringir dump apenas do id do modulo
        if(!empty($idModule)){
            $whereIdModule = " and id = '{$idModule}'";
        }

        // Comando dump para tabela de modulos
        $command = "mysqldump --user=".$config->get('user')." --password='".$config->get('password')."' --host=".$config->get('host')." ".$config->get('db')." --no-create-info  --no-set-names --compact ".$tableModules." --where=\"module='{$extensionName}' {$whereIdModule}\"";
        
        // Executa comando SQL
        exec($command, $outString);

        // Retorno em branco no dump
        if (empty($outString)){
            $return->error = 1;
            $return->message = '<b>Erro no dump</b> <br /><br />Não foi possível gerar o dump ou nennhum dado foi encontrado.';
            exit(json_encode($return));
        }

        $outString = implode("\n",$outString);
        $outString = str_replace($config->get('dbprefix'), "#__", $outString);
        
        // Exibe na tela o dump para tabela de modulos
        $return->content = "<b>Dump referente a tabela ".$config->get('dbprefix')."modules:</b> <br /><br />";
        $return->content .= $outString;

        // Tratamentos adicionais conforme nome da extensao
        switch ($extensionName) {
            // Calendario de eventos
            case 'mod_nobosscalendar':
                // INICIO DUMP TABELA #__noboss_calendar
                $tableCalendar = $config->get('dbprefix').'noboss_calendar';
                $outString = '';
                // Restringir dump apenas do id do modulo
                if(!empty($idModule)){
                    $whereIdModule = "--where=\"id_module = '{$idModule}'\"";
                }
                // Comando dump para tabela '#__noboss_calendar'
                $command = "mysqldump --user=".$config->get('user')." --password='".$config->get('password')."' --host=".$config->get('host')." ".$config->get('db')." --no-create-info  --no-set-names --compact ".$tableCalendar." {$whereIdModule}";

                // Executa comando SQL
                exec($command, $outString);
                $outString = implode("\n",$outString);
                $outString = str_replace($config->get('dbprefix'), "#__", $outString);

                // Exibe na tela o dump para tabela de eventos
                $return->content .= "<br /><br /><br /><br /><b>Dump referente a tabela ".$config->get('dbprefix')."noboss_calendar:</b> <br /><br />";
                $return->content .= $outString;

                // INICIO DUMP TABELA #__noboss_calendar_categories
                $tableCalendar = $config->get('dbprefix').'noboss_calendar_categories';
                $outString = '';
                // Restringir dump apenas do id do modulo
                if(!empty($idModule)){
                    $whereIdModule = "--where=\"id_module = '{$idModule}'\"";
                }
                // Comando dump
                $command = "mysqldump --user=".$config->get('user')." --password='".$config->get('password')."' --host=".$config->get('host')." ".$config->get('db')." --no-create-info  --no-set-names --compact ".$tableCalendar." {$whereIdModule}";

                // Executa comando SQL
                exec($command, $outString);
                $outString = implode("\n",$outString);
                $outString = str_replace($config->get('dbprefix'), "#__", $outString);

                // Exibe na tela o dump para tabela de eventos
                $return->content .= "<br /><br /><br /><br /><b>Dump referente a tabela ".$config->get('dbprefix')."noboss_calendar_categories:</b> <br /><br />";
                $return->content .= $outString;

                break;
        }

        exit(json_encode($return));
    }

    /**
     * Teste de requisicao curl para servidor da No Boss (util para cliente executar testes junto com sua equipe de infra / hospedagem quando tem um problema de curl, sem precisar ficar acessando a area admin da extensao)
     *
     * Formato a chamar na url: SITE/index.php?option=com_nobossajax&library=noboss.src.Util.NbSupportUtil&method=curlTest
     * 
     * 
     * @return void
     */
    public static function curlTest() {
        // Inclui arquivo de traducao da library
        $assetsObject = new NbLoadextensionAssetsUtil('lib_noboss');
        $extensionPath = $assetsObject->getDirectoryExtension(0);
        Factory::getLanguage()->load('lib_noboss', $extensionPath);

        // Url para teste
        $urlTest = 'https://www.nobossextensions.com/';
        
        // realiza requisicao curl
        $result = NbCurlUtil::request('GET', $urlTest);
        
        // Requisicao realizada com sucesso
        if(!empty($result->success) && $result->success){
            echo 'Successful curl request to host '.$urlTest;
        }
        // Erro
        else{
            echo "Error when performing curl request via PHP using the curl_exec function for the host {$urlTest}.<br /><br /><b>Error details:</b> <br />";
            echo $result->message;
        }
        exit;
    }
}
