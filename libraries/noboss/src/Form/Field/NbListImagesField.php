<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2018 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\FilelistField;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbListImagesField extends FilelistField {

  	protected function getInput(){
			$html = array();
			// Pega o caminho original, setado pelo usuário
			$pathRaw = $this->directory;

			//monta o caminho da imagem no sistema
			if (!is_dir($pathRaw)){
				$path = JPATH_ROOT . '/' . $pathRaw;
			}else{
				$path = $pathRaw;
			}
			// Pega os arquivos em um determinado caminho
			$files = Folder::files($path, $this->filter);


			$html[] = '<div class="'.$this->class.'">';
			// Build the options list from the list of files.
			if (is_array($files)){
				foreach ($files as $file){
					$html[] = '<img style="max-height:20px" src="'.Uri::root().$pathRaw.'/'.$file.'" />';
					$file = File::stripExt($file);
					$html[] = '<span>'.$file.'</span>';
				}
			}
			$html[] = '</div>';

		  return implode($html);
  	}
}
