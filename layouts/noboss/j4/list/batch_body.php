<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/*
 * Esse arquivo pode ser sobreescrito dentro da pasta tmpl da view do componente utilizando o mesmo nome (batch_body.php ou default_batch_body.php)
 * 
 * Nota: a sobreescrita eh util e necessaria somente quando voce precisar criar recursos proprios para lote
 */
?>

<div class="p-3">
    <div class="row">
        <?php 
        // Multiidioma habilitado no site e componente possui coluna de idioma definida na listagem
        if (Multilanguage::isEnabled() && !empty($displayData->languageColumn)){
        ?>
            <div class="form-group col-md-6">
                <div class="controls">
                    <?php echo LayoutHelper::render('joomla.html.batch.language', []); ?>
                </div>
            </div>
        <?php
        }

        // Componente possui coluna de nivel de acesso definido na listagem
        if(!empty($displayData->accessColumn)){
        ?>
            <div class="form-group col-md-6">
                <div class="controls">
                    <?php echo LayoutHelper::render('joomla.html.batch.access', []); ?>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
</div>
<div class="btn-toolbar p-3">
    <joomla-toolbar-button task="<?php echo $displayData->createViewAlias; ?>.batch" class="ms-auto">
        <button type="button" class="btn btn-success"><?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?></button>
    </joomla-toolbar-button>
</div>
 

