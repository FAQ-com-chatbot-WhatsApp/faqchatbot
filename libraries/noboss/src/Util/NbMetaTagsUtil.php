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

class NbMetaTagsUtil {

    /**
     * Funcao que seta no head do Joomla palavras chave e descricao do site
     *
	 * @param	string	$metaKeywords		Palavras chave do site (opcional)
     * @param	string	$metaDescription	Descricao do site (opcional)
     * 
     * @return 	void
     */
    public static function setKeywordsAndDescription($metaKeywords = null, $metaDescription = null){
        $doc = Factory::getDocument();
        
        // Palavras chave ou descricao do site nao definidos
        if (empty($metaKeywords) || empty($metaDescription)){
            // Obtem dados do menu ativo
            $activeMenu = Factory::getApplication()->getMenu()->getActive();

            if(!empty($activeMenu)){
                $activeMenuParams = $activeMenu->getParams($activeMenu->id);
                
                if (empty($metaKeywords)){
                    $metaKeywords = $activeMenuParams['menu-meta_keywords'];
                }
                // Descricao do site nao definida: pega do item de menu
                if (empty($metaDescription)){
                    $metaDescription = $activeMenuParams['menu-meta_description'];
                }
            }
        }        
        
        // Seta palavras chave
        if (!empty($metaKeywords)){
            $doc->setMetaData( 'keywords', $metaKeywords);
        }
        // Seta descricao do site
        if (!empty($metaDescription)){
            $doc->setDescription($metaDescription);
        }
    }

    /**
     * Funcao que seta no head do Joomla uma imagem de compartilhamento para redes sociais
     *
	 * @param	string	$urlImageSharing	Url da imagem de compartilhamento
     * @param	string	$widthFace          Largura da imagem para o face (opcional - se não informado, o face define por conta propria)
     * @param	string	$heightFace	        Altura da imagem para o face (opcional - se não informado, o face define por conta propria)
     * 
     * @return 	void
     */
    public static function setImageSharing($urlImageSharing, $widthFace = false, $heightFace = false){
        $doc = Factory::getDocument();
        
        // Imagem para compartilhamento informada
		if (!empty($urlImageSharing)){
            $doc->setMetaData("og:image", $urlImageSharing);
            $doc->setMetaData("og:image:secure_url", $urlImageSharing);
            $doc->setMetaData("twitter:image", $urlImageSharing);
            
            // Definida largura para imagem no face
            if($widthFace){
                $doc->setMetaData("og:image:width", $widthFace);
            }
            // Definida altura para imagem no face
            if($heightFace){
                $doc->setMetaData("og:image:height", $heightFace);
            }
		}
    }

    /**
     * Funcao que seta no head do Joomla informacoes especificas para compartilhamento de artigos
     *
	 * @param	object	$options	Objeto com os dados do artigo a serem inseridos no head
     * @note    As opcoes disponiveis e que podem vir no array sao:
     *               'published_time': data de publicacao no formato 2018-05-22T17:58:06+00:00
     *               'modified_time': data de modificacao no formato 2018-05-22T17:58:06+00:00
     *               'section': normalmente o nome da categoria do artigo. Ex: 'Dicas para Web'
     *               'tag': array onde em cada posicao consta o nome da tag em que o artigo foi vinculado
     *               'publisher': url da pagina no facebook. Ex: https://www.facebook.com/nobosstechnology/
     * 
     * @return 	void
     */
    public static function setArticleSharing($options){
        $doc = Factory::getDocument();
        
        $doc->setMetaData("og:type", "article");

		if (!empty($options['published_time'])){
            $doc->setMetaData("article:published_time",  $options['published_time']);
        }
        if (!empty($options['modified_time'])){
            $doc->setMetaData("article:modified_time", $options['modified_time']);
            $doc->setMetaData("og:updated_time", $options['modified_time']);
        }
        if (!empty($options['section'])){
            $doc->setMetaData("article:section", $options['section']);
        }
        if (!empty($options['publisher'])){
            $doc->setMetaData("article:publisher", $options['publisher']);
        }
        if (!empty($options['tag'])){
            $tags = implode(", ", $options['tag']);
            $doc->setMetaData("article:tag", $tags);
		}
    }

    /**
     * Funcao que seta no head do Joomla um title novo para a pagina para uso em SEO e titulo do navegador
     *
	 * @param	string	 $title             Titulo da pagina
     * @param   string   $viewSiteName      Informa se deve ser exibido o titulo do site concatenado
     *                                      (false para nao exibir, after para exibir no final e before para exibir no inicio)
     * 
     * @return 	void
     */
    public static function setTitle($title, $viewSiteName = 'before'){
        $doc = Factory::getDocument();
                
        // Titulo definido
		if (!empty($title)){
            // Definido para exibir site name junto ao titulo informado
            if (!empty($viewSiteName)){
                $app = Factory::getApplication();
                $siteName = $app->getCfg( 'sitename' );

                if ($viewSiteName == 'after'){
                    $title = $title.' - '.$siteName;
                }
                else{
                    $title = $siteName.' - '.$title;
                }
            }

            $doc->setTitle($title);
		}
    }
}
