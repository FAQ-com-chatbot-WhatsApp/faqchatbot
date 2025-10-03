<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2025 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// namespace Noboss\Library\Form\Field\Nbmodulesposition;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Noboss\Library\Util\NbRegularLabsUtil;

defined('_JEXEC') or die;

/*  
    - Esse arquivo serve para exibir a selecao de menus junto ao campo de escolha de posicao de modulo
    
    - Requisitos para usar esse campo:
        - Ter dois fields hidden no xml (um para assignment e outro para assigned). Ex da FAQ:
            <field name="assignment_module_faqs_display" type="hidden" module="assigment" />
		    <field name="assigned_module_faqs_display" type="hidden" module="assigned" />
        
        - No arquivo de tmpl, colocar o codigo abaixo dentro do foreach que percorre os campos a exibir:
            // Eh field de 'Atribuicao de menus' de modulo
            if(!empty($field->getAttribute("module"))){
                // Field de selecao de menus
                if($field->getAttribute("module") == 'assigment'){
                    // Obtem o nome do campo p/ usar em seguida no proximo field
                    $assignmentName = $field->getAttribute("name");
                    continue;
                }
                // Field que guarda os menus selecionados
                elseif($field->getAttribute("module") == 'assigned'){
                    // Obtem o nome do campo
                    $assignedName = $field->getAttribute("name");
                    $item = $displayData->get('Item');
                    // Carrega arquivo que ira exibir o campo
                    require_once JPATH_ROOT."/libraries/noboss/src/Form/Field/nbmodulesposition/assignment.php";
                    continue;                    
                }
            }

    - NOTA: quando o campo eh chamado mais de uma vez na mesma pagina, na segunda chamada os botoes se selecionar / expandir todos itens de menu nao funcionam. Mas eh apenas com os botoes de todos. os demais funcionam normalmente
*/

// Plugin e componente 'advancedmodules' estao habilitados
if(NbRegularLabsUtil::checksAdvancedModulePluginEnabled()){
    // Exibe mensagem na aba licenca com detalhes do erro
    echo  "<div class='alert alert-error'>
                <span class='icon-info-circle'></span>
                ".Text::sprintf('LIB_NOBOSS_UTIL_REGULARLABS_MODULE_ASSIGNMENT_NOTICE', 'index.php?option=com_plugins&view=plugins&filter[element]=advancedmodules')."
            </div>";

    // Sai do arquivo para nao exibir campos de atribuicao de pagina (com Regular Labs ativo isso nao funciona)
    return;
}

if(empty($item)){
    $item = new \stdClass();
}

// Pega valores salvos no banco (caso seja edicao de registro)
$item->assignment = (isset($item->{$assignmentName})) ? $item->{$assignmentName} : null;
$item->assigned = (isset($item->{$assignedName})) ? $item->{$assignedName} : null;
$item->client_id = 0;

// Showon (caso definido no input hidden)
$showon = (!empty($field->getAttribute("showon"))) ? ' data-showon=\'' . json_encode(FormHelper::parseShowOnConditions($field->getAttribute("showon"), $form->getFormControl())) . '\'' : '';

// Carrega tmpl do componente de modulos armazenando o retorno html em variavel php
ob_start();

$document = Factory::getDocument();
// Declara o JS usado dentro da tmpl sem especificar um arquivo (apenas para nao dar erro)
$document->getWebAssetManager()->registerScript('com_modules.admin-module-edit-assignment', '');  

// Declara manualmente o script que seria inserido dentro da tmpl pelo arquivo 'media\com_modules\js\admin-module-edit_assignment.js'. Isso eh necessario para trocar os nomes dos campos na chamada de JS.
$document->getWebAssetManager()->addInlineScript("(() => {
                    const onChange = value => {
                        if (value === '-' || parseInt(value, 10) === 0) {
                            document.getElementById('menuselect_".$assignmentName."-group').classList.add('hidden');
                            jQuery('#menuselect_".$assignmentName."-group').hide();
                        } else {
                            document.getElementById('menuselect_".$assignmentName."-group').classList.remove('hidden');
                            jQuery('#menuselect_".$assignmentName."-group').show();
                        }
                    };
                    const onBoot = () => {
                    const element = document.getElementById('jform_".$assignmentName."');
                    if (element) {
                        // Initialise the state
                        onChange(element.value); // Check for changes in the state
                        element.addEventListener('change', ({
                            target
                        }) => {
                            onChange(target.value);
                        });
                    }
                    document.removeEventListener('DOMContentLoaded', onBoot);
                    };
                    document.addEventListener('DOMContentLoaded', onBoot);
                })();");

// Inclui tmpl do componente modules
//require JPATH_ADMINISTRATOR . '/components/com_modules/tmpl/module/edit_assignment.php';
require JPATH_ROOT."/libraries/noboss/src/Form/Field/Nbmodulesposition/edit_assignment.php";

$assignmentHtml = ob_get_clean();

// Realiza replace de 'ids' e 'names' do assigment
$replaceSource = array('_assignment', '[assignment', '->assignment', 'menuselect', 'jform_menus-lbl', 'jform_menus"');
$replaceTarget = array("_{$assignmentName}", "[{$assignmentName}", "->{$assignmentName}", "menuselect_{$assignmentName}", "jform_menus-lbl_{$assignmentName}", "jform_menus_{$assignmentName}\"");
$assignmentHtml = str_replace($replaceSource, $replaceTarget, $assignmentHtml);

// Realiza replace de 'ids' e 'names' do assigned
$assignmentHtml = str_replace('assigned', $assignedName, $assignmentHtml);

// Definido showon: adiciona nas divs com classe css control-group
if(!empty($showon)){
    $assignmentHtml = str_replace('control-group"', 'control-group" '.$showon.' ', $assignmentHtml);
}

// Joomla 4
if(version_compare(JVERSION, '4', '>=')){
    // Adiciona classe 'stack' junto com 'control-group' para alinhamento
    $assignmentHtml = str_replace('control-group', 'control-group stack', $assignmentHtml);
    
    // Adiciona classe 'span-3' junto ao primeiro 'control-group' que eh do select 'Atribuicao de modulo'
    $assignmentHtml = substr_replace($assignmentHtml, 'control-group span-3-inline', strpos($assignmentHtml, 'control-group'), strlen('control-group'));
}

// Exibe
echo $assignmentHtml;
