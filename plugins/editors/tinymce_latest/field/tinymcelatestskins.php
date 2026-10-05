<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Editors.tinymce_latest
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * The TinyMCE 8 interface skins, by name (the folders of skins/ui).
 *
 * @since  3.17.0
 */
class JFormFieldTinymcelatestskins extends JFormFieldList
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $type = 'tinymcelatestskins';

	/**
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function getOptions()
	{
		$options = array();

		foreach (glob(JPATH_ROOT . '/media/editors/tinymce_latest/skins/ui/*', GLOB_ONLYDIR) ?: array() as $folder)
		{
			$options[] = JHtml::_('select.option', basename($folder), basename($folder));
		}

		return array_merge(parent::getOptions(), $options);
	}
}
