<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	com_nobossfaq
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

/**
 *  Classe View para edicao de registros que segue 'Metodo No Boss de desenvolvimento'
 *  @author  Johnny Salazar Reidel
 * 
 *  Orientacao: preencha as informacoes corretamente nas funcoes abaixo:
 *      - display(): defina os parametros sinalizados dentro da funcao
 *      - loadExtensionAssets: defina o carregamento de variaveis e arquivos JS e CSS
 *      - specificTreatments(): local para adicionar codigos especificos desta view
 */

jimport('noboss.components.view.edit');

class NobossfaqViewGroup extends NobossComponentsViewEdit {

    /**
     * Metodo principal
     */
	public function display($tpl = null) {
		// Nome do fieldset principal
        $this->defaultFieldSetName = 'theme';

        // Titulo da pagina
        $this->pageTitle = 'No Boss FAQ: '.JText::_('COM_NOBOSSFAQ_TITLE_GROUPS');

        // Icone exibico junto ao titulo da pagina (sem informar prefixo 'icon-')
        $this->pageIcon = 'tree-2';

        // Nomes dos fieldsets que devem ser ignorados no foreach que percorre todos fieldsets para exibicao dos fields
        $this->fieldsetsIgnore = array('hidden', 'details', 'intro');
        
		parent::display($tpl);
	}

    /**
     * Funcao para carregamento de variaveis e arquivos JS e CSS
     */
    public function loadExtensionAssets(){
        /**
         * Ao carregar a funcao parent, sera adicionado na pagina o jquery, variaveis JS (baseNameUrl, majorVersionJoomla, completeVersionJoomla, ira carregar o arquivo JS da extensao (caso esteja definido um no caminho default. Ex: '/administrator/components/com_nobossfaq/assets/admin/js/com_nobossfaq.min.js') e ira carregar o arquivo CSS da extensao (caso esteja definido um no caminho default. Ex: '/administrator/components/com_nobossfaq/assets/admin/css/com_nobossfaq.min.css')
         */
        parent::loadExtensionAssets();

        // Voce pode adicionar aqui tb outras chamadas de JS e CSS
    }

    /**
     * Funcao para tratamentos especificos desta view
     */
    public function specificTreatments(){
    }
}
