<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

/* Layout para exibir os campos de titulo e alias (caso definido) no topo de um componente
    - Esse codigo eh baseado no layouts\joomla\edit\title_alias.php do Joomla que adaptamos para permitir definir o name dos dois campos (o joomla fixava)
    
    - Formato correto para chamar esse layout:
        // Exibe campos de titulo e alias
        $this->fieldName = 'name_faqs_group';
        $this->fieldAlias = ''; // Soh prencher qnd tiver campo de alias
        echo JLayoutHelper::render('noboss.edit.title_alias', $this);
*/

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$form = $displayData->getForm();

if((empty($displayData->fieldName)) && (empty($displayData->fieldAlias))){
    return;
}

?>
<div class="row title-alias form-vertical mb-3">
    <?php
    if(!empty($displayData->fieldName)){
    ?>
        <div class="col-12 col-md-6">
            <?php echo $form->renderField($displayData->fieldName); ?>
        </div>
    <?php
    }
    if(!empty($displayData->fieldAlias)){
    ?>
        <div class="col-12 col-md-6">
            <?php echo $form->renderField($displayData->fieldAlias); ?>
        </div>
    <?php
    }
    ?>
</div>