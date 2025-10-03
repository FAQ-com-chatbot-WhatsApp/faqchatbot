<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/* Layout para exibir um description em cada fieldset do componente (similar ao que eh feito nos modulos do Joomla)

    - Formato correto para chamar esse layout:
        echo JLayoutHelper::render('noboss.edit.component_description', $fieldSet->description);
*/

// Fieldset possui description
if(!empty($displayData)){
    ?>
    <legend class='alert alert-info'>
        <span class="icon-info-circle" aria-hidden="true"></span>
        <?php echo Text::_($displayData); ?>
    </legend>
    <?php
}