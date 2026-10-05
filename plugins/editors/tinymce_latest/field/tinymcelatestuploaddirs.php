<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Editors.tinymce_latest
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('folderlist');

/**
 * The folders of the images folder (Media Manager's "Path to Images Folder") which uploaded images can go to.
 *
 * @since  3.17.0
 */
class JFormFieldTinymcelatestuploaddirs extends JFormFieldFolderList
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $type = 'tinymcelatestuploaddirs';

	/**
	 * @param   SimpleXMLElement  $element  The field's element
	 * @param   mixed             $value    Its value
	 * @param   string            $group    Its group
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		$this->directory   = JComponentHelper::getParams('com_media')->get('image_path', 'images');
		$this->recursive   = true;
		$this->hideDefault = true;

		return $return;
	}

	/**
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function getOptions()
	{
		$options = parent::getOptions();

		// "None" is the images folder itself
		if (isset($options[0]) && $options[0]->value === '-1')
		{
			$options[0]->value = '';
			$options[0]->text  = JComponentHelper::getParams('com_media')->get('image_path', 'images');
		}

		return $options;
	}
}
