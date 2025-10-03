<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// namespace Noboss\Library\Form\Field\Nblicense;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbUrlUtil;

defined('_JEXEC') or die('Restricted access');

// Obtem configuracoes globais
$config     = Factory::getConfig();
// Obtem offset das configuracoes globais
$dateOffSet = $config->get('offset', 'America/Sao_Paulo');

// Link da area de cliente
$linkClientArea = NbUrlUtil::getUrlNbExtensions()."/customer-area/";

// Link de upgrade do plano
$linkUpgradeLicense = NbUrlUtil::getUrlNbExtensions()."/buy-process/planstable?token=".$localLicenseData['token']."&processtype=upgrade";

// Link da pagina de changelog do produto
$changelogUrlProduct = NbUrlUtil::getUrlNbExtensions()."/".$licenseInfoData->page_product_alias."/changelog/";

// Link da pagina de changelog da library
$changelogUrlLibrary = NbUrlUtil::getUrlNbExtensions()."/nobosslibrary/changelog/";
?>

<section class="license-section nb-lg-8 nb-md-10 nb-sm-12 nb-xs-12">
    <?php
    // Defindas mensagens para exibir no topo da aba de notificacoes
    if ((isset($licenseInfoData->notices_tab_license)) && (count($licenseInfoData->notices_tab_license) > 0)){
        // Percorre todas mensagens
        foreach ($licenseInfoData->notices_tab_license as $message) {
            if (empty($message->icon)){
                $message->icon = 'fa-info-circle';
            }
            ?>
            <div data-id='license-alert-message' class='feedback-notice alert alert-info feedback-notice--<?php echo !empty($message->type) ? $message->type : ''; ?> <?php if(!empty($message->class)){ echo $message->class;} ?>'>
                <div class="feedback-notice__content">
                    <?php if (!empty($message->title)){?>
                        <h4 class="feedback-notice__title"><?php echo $message->title; ?></h4>
                    <?php } ?>
                    <p class="feedback-notice__message" data-id='license-alert-message-text'>
                        <?php echo $message->message; ?>
                    </p>
                </div>
            </div>
            <?php
        }
    }

    // Exibe informacoes da licenca
    ?>
    <div class="license-table">
        <h3 class="license-table__title">
            <?php echo Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_INTRO_TITLE'); ?> 
        </h3>

        <div class="license-infos">
            <div class="license-infos__item">
                <div class="license-infos__label">
                    <?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_RESPONSIBLE_LABEL"); ?>
                </div>
                <div class="license-infos__text">
                    <?php echo $licenseInfoData->responsible_name; ?>
                </div>
            </div>
            <div class="license-infos__item">
                <div class="license-infos__label">
                    <?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_SUPPORT_UPDATES_EXPIRATION_DATE_LABEL"); ?>
                </div>
                <div class="license-infos__text">
                    <?php 
                    // Obtem objeto de data e hora atual
                    $dateLicenseObj = Factory::getDate($licenseInfoData->support_updates_expiration, $dateOffSet);
                    // Converte a data para o formato definido para o idioma do usuario
                    $dateExpireUpdates = $dateLicenseObj->format(Text::_("NOBOSS_EXTENSIONS_GLOBAL_DATE_FORMAT"));

                    // Licenca esta com suporte de atualizacoes ativo
                    if($licenseInfoData->inside_support_updates_expiration){
                        echo $dateExpireUpdates;
                    }
                    // Licenca esta com suporte de atualizacoes expirado
                    else{
                        // Exibe mensagem que esta sem suporte com link para entrar em contato para regularizar
                        ?>
                        <span style='color: #e64242'>
                            <?php
                            echo Text::sprintf("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_SUPPORT_UPDATE_INVALID_PERIOD",$dateExpireUpdates);
                            ?>
                        </span>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="license-infos__item">
                <div class="license-infos__label">
                    <?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_SUPPORT_TECHNICAL_EXPIRATION_DATE_LABEL"); ?>
                </div>
                <div class="license-infos__text">
                    <?php 
                        // Obtem objeto de data e hora atual
                        $dateLicenseObj = Factory::getDate($licenseInfoData->support_technical_expiration, $dateOffSet);
                        // Converte a data para o formato definido para o idioma do usuario
                        $dateExpireTechnical = $dateLicenseObj->format(Text::_("NOBOSS_EXTENSIONS_GLOBAL_DATE_FORMAT"));

                        // Licenca esta com suporte tecnico ativo
                        if($licenseInfoData->inside_support_technical_expiration){
                            echo $dateExpireTechnical;

                            // Link para pagina de pedido de ajuda
                            $linkSupport = $linkClientArea.'need-help/index.php?cod-license='.$licenseInfoData->id_license;

                            // Exibe link para solicitar contato
                            ?>
                            <a href="<?php echo $linkSupport; ?>" target="_blank">
                                <?php echo Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_SUPPORT_TECHNICAL_CONTACT_BUTTON'); ?>
                            </a>
                            <?php
                        }
                        // Licenca esta com suporte ativo expirado
                        else{
                            // Exibe mensagem que esta sem suporte com link para entrar em contato para regularizar
                            ?>
                            <span style='color: #e64242'>
                                <?php echo Text::sprintf("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_SUPPORT_TECHNICAL_INVALID_PERIOD", $dateExpireTechnical); ?>
                            </span>
                            <a href='<?php echo $linkUpgradeLicense; ?>' target="_blank">
                                <?php echo Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_LINK_RENEW_PLAN'); ?>
                            </a>
                            <?php
                        }
                    ?>
                </div>
            </div>
            <div class="license-infos__item">
                <div class="license-infos__label">
                    <?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_LICENSE_NUMBER_LABEL"); ?>
                </div>
                <div class="license-infos__text">
                    <?php echo $licenseInfoData->id_license; ?>
                </div>
            </div>
            <div class="license-infos__item">
                <div class="license-infos__label">
                    <?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_EXTENSION_VERSION_LABEL"); ?>
                </div>
                <div class="license-infos__text">
                    <?php
                    // Exibe vesao instalada
                    echo $localLicenseData['installed_version'];
                    
                    // Usuario nao possui a ultima versao: exibe mensagem da versao disponivel
                    if (!empty($localLicenseData['last_version']) && (version_compare($localLicenseData['installed_version'], $localLicenseData['last_version']) != 0)) {
                        ?>
                        <span style='color: #e64242'>
                            <?php echo "(".Text::sprintf("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_EXTENSION_VERSION_MSG_VERSION_AVAILABLE", $localLicenseData['last_version']).")."; ?>
                        </span>

                        <?php
                        // Cliente nao esta com suporte para atualizacoes em dia: exibe link para renovar plano
                        if(!$licenseInfoData->inside_support_updates_expiration){
                            ?>
                            <a href='<?php echo $linkUpgradeLicense; ?>' target="_blank">
                                <?php echo Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_LINK_RENEW_PLAN_EXTEND'); ?>
                            </a>
                            <?php
                        }
                        // Cliente esta com suporte em dia: exibe link para pagina de atualizacoes
                        else{
                            ?>
                            <a href='index.php?option=com_installer&view=update' target='_blank'>
                                <?php echo Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_EXTENSION_VERSION_LINK_PG_UPDATES'); ?>
                            </a>
                            <?php
                        }
                    }
                    // Usuario esta com a versao mais recente instalada: exibe mensagem
                    else{
                        ?>
                        <span style='color: #006f0f;'>
                            <?php echo "(".Text::_('LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_EXTENSION_VERSION_MSG_UPDATE_VERSION').")."; ?>
                        </span>
                        <?php
                    }

                    // Licenca esta com suporte de atualizacoes ativo e usuario tem permissao de atualizacao de extensoes no joomla
                    /*if (($licenseInfoData->inside_support_updates_expiration) && (Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_installer'))){
                        // Exibe link que permite usuario reinstalar extensao
                        ?>
                        <a href="#" data-id="btn-reinstall" style="font-weight: 400;"><?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSLICENSE_REINSTALL_LINK"); ?></a>
                        <?php
                    }*/
                    ?>
                </div>
            </div>

            <?php
            // Produto possui changelog cadastrado para o produto: exibe link changelog do produto e tb da library
            if($licenseInfoData->has_changelog == "1"){
                ?>
                <div class="license-infos__item">
                    <div class="license-infos__label">
                        <?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_CHANGELOG"); ?>
                    </div>
                    <div class="license-infos__text">
                        <a href='<?php echo $changelogUrlProduct; ?>' target='_blank'>
                            <?php echo $licenseInfoData->product_name; ?>
                        </a>
                        |
                        <a href='<?php echo $changelogUrlLibrary; ?>' target='_blank'>
                            No Boss Library
                        </a>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="nb-license-copyright">
            <?php // copyright ?> 
            <?php echo Text::_("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_COPYRIGHT_VALUE"); ?>
            &nbsp;|&nbsp;
            <?php // Link para area do cliente ?>
            <?php echo Text::sprintf("LIB_NOBOSS_FIELD_NOBOSSLICENSE_CONTENT_TAB_INFO_CLIENT_AREA", $linkClientArea.'my-requests'); ?>
        </div>
    </div>
</section>
