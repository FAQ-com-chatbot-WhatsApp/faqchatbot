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

// Classe para envio de emails
class NbEmailsUtil {

    /**
     * Função que envia um e-mail para um lista de destinatários.
     * 
     * @param 	string 		$subject 				Assunto do e-mail.
     * @param 	string 		$message 				Menssagem do e-mail.
     * @param 	mixed 		$senderList 			Dados do remetente onde a primeira posicao do array eh o email e a segunda o nome OU eh somente uma string com email
     * @param 	mixed 		$recipients 			Lista com endereços dos destinatários do e-mail podendo ser enviada em array ou separado por ponto ou ponto e virgula
     * @param 	array 		$attachments 			Lista de arquivos para anexar ao e-mail.
     * @param 	mixed 		$recipientsHidden 		Lista com os endereços dos destinatários ocultos do e-mail podendo ser enviada em array ou separado por ponto ou ponto e virgula
     * @param 	mixed 		$reply 					Dados para email de retorno onde a primeira posicao do array eh o email e a segunda o nome OU eh somente uma string com email
     * 
     * @return bolean Retorna true se o e-mail foi enviado, caso contrário retorna false.
     */
    public static function sendEmail($subject, $message, $senderList = array(), $recipients = array(), $attachments = array(), $recipientsHidden = array(), $reply = array()){
        // Verifica se esta definido os destinatarios
        if (empty($recipients)){
            return false;
        }
        
        // Configura objeto mailer.
        $mailer = Factory::getMailer();
        // Objeto de configs default do joomla.
        $config = Factory::getConfig();
        // E-mail do remetente
        $senderList = !empty($senderList) ? $senderList : array($config->get('mailfrom'), $config->get('fromname'));

        // Configura remetente.
        $mailer->setSender($senderList);
        
        // Email de resposta
        if (!empty($reply)){
            // Configura replyto
            $mailer->addReplyTo($reply);
        }

        // Configura assunto do e-mail.
        $mailer->setSubject($subject);
        // Configura mensagem do corpo do e-mail.
        $message = empty($message) ? '.' : $message;
        $mailer->setBody($message);
        // Informa que o e-mail possui código HTML.
        $mailer->isHTML(true);

        // Percorre a lista de anexos.
        foreach ($attachments as $file) {
            $mailer->addAttachment($file['tmp_name'], $file['name'], "base64" ,$file['type']);
        }

        // Executa funcao que ira garantir que os emails estejam em formato de array
        $recipients = self::convertsMailListArray($recipients);

        // Adiciona destinatários não ocultos
        foreach($recipients as $recipient) {
            $mailer->addRecipient($recipient);
        }

        // Executa funcao que ira garantir que os emails estejam em formato de array
        $recipientsHidden = self::convertsMailListArray($recipientsHidden);

        // Adiciona destinatários ocultos.
        foreach($recipientsHidden as $recipient) {
            $mailer->addBcc($recipient);
        }

        //Realiza o envio do e-mail.
        return $mailer->Send();
    }

    /**
     * Função que envia um e-mail para um lista de destinatários.
     * 
     * @param 	mixed 		$emails 		Lista de emails que pode ser recebida como string ou array
     * 
     * @return 	array 		Lista de emails convertida para array
     */
    public static function convertsMailListArray($emails){
        // Ja esta em formato de array: retorna
        if (is_array($emails)) { 
            return $emails;
        }
        // Ha mais de um email separado por virgula
        if (strstr($emails, ',')){
            $emails = explode(',', $emails);
        }
        // Ha mais de um email separado por ponto e virgula
        else if (strstr($emails, ';')){
            $emails = explode(';', $emails);
        }
        // Ha somente um email definido
        else{
            $emails = array($emails);
        }
        return $emails;
    }

}
    