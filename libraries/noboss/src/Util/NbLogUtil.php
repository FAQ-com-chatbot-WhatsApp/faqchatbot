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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbLogUtil {
    /**
     * Funcao que obtem o IP local do usuario
     *
     * @return 	String 	Ip do usuario
     */
	public static function getIp() {
		$ip = "";
		if (getenv("HTTP_CLIENT_IP")){
			$ip = getenv("HTTP_CLIENT_IP");
		}else if(getenv("HTTP_X_FORWARDED_FOR")) {
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		}else if(getenv("REMOTE_ADDR")) {
			$ip = getenv("REMOTE_ADDR");
		}else {
			$ip = "UNKNOWN";
		}

        // Site esta rodando em localhost e por isso nao retorna ip da conexao (172.18.0.1 eh retornado no servidor docker)
        if(($ip == '::1') || ($ip == '172.18.0.1')){
            $ip = self::getIpLocalhost();
        }

		return $ip;
    }

    /**
     * Funcao que obtem o IP local do usuario usando site externo para quando acesso esta em localhost
     *
     * @return 	String 	Ip do usuario
     */
	public static function getIpLocalhost() {
		// Carrega library do cache para usar nestes casos que vamos buscar o ip atraves de um site externo
        $cache = Factory::getCache('nbhandlercssip', '');

        // Habilita cache mesmo estando desabilitado nas configuracoes globais do Joomla
        $cache->setCaching(1); 
    
        // Define o tempo do cache
        $cache->setLifeTime(400);
    
        // Valor nao esta disponivel no cache
        if (empty($cache->get("ipcurrent"))){
            // Obtem ip atraves de site externo
            $ipCurrent = file_get_contents("http://ipecho.net/plain");
            // Salva valor no cache
            $cache->store($ipCurrent, "ipcurrent");
        }
        else{
            $ipCurrent = $cache->get("ipcurrent");
        }

        return $ipCurrent;
    }

    /**
     * Funcao que obtem o IP do servidor do site
     *
     * @return 	String 	Ip
     */
    public static function getIpServer(){
        // Obtem no formato IPV4
        $dnsARecord = dns_get_record($_SERVER['HTTP_HOST'],DNS_A);

        // Nao localizado: obtem no formato IPV6
        if(empty($dnsARecord)){
            $dnsARecord = dns_get_record($_SERVER['HTTP_HOST'],DNS_AAAA);	
        }

        return $dnsARecord[0]['ip'];
    }
    
    /**
     * Funcao que obtem informacoes do browser do usuario
     *
     * @return 	String 	   Informacoes do browser concatenadas
     */
    public static function getBrowserInfo(){
        return $_SERVER['HTTP_USER_AGENT'];
    }

}
