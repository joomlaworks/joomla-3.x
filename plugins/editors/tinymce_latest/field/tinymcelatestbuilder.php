<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Editors.tinymce_latest
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * The toolbar builder: the sets of menus and buttons, each for some user groups, with the set's options.
 *
 * @since  3.17.0
 */
class JFormFieldTinymcelatestbuilder extends JFormField
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $type = 'tinymcelatestbuilder';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $layout = 'plugins.editors.tinymce_latest.field.tinymcebuilder';

	/**
	 * @var    array
	 * @since  3.17.0
	 */
	protected $layoutData = array();

	/**
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function getLayoutData()
	{
		if ($this->layoutData)
		{
			return $this->layoutData;
		}

		require_once JPATH_PLUGINS . '/editors/tinymce_latest/tinymce_latest.php';

		$data       = parent::getLayoutData();
		$params     = (object) $this->form->getValue('params');
		$setsAmount = empty($params->sets_amount) ? 3 : max(3, (int) $params->sets_amount);
		$value      = is_array($this->value) ? $this->value : array();

		$data['value']         = $value;
		$data['menus']         = array(
			'edit'   => array('label' => 'Edit'),
			'insert' => array('label' => 'Insert'),
			'view'   => array('label' => 'View'),
			'format' => array('label' => 'Format'),
			'table'  => array('label' => 'Table'),
			'tools'  => array('label' => 'Tools'),
			'help'   => array('label' => 'Help'),
		);
		$data['buttons']       = PlgEditorTinymce_latest::getKnownButtons();
		$data['toolbarPreset'] = PlgEditorTinymce_latest::getToolbarPreset();
		$data['setsAmount']    = $setsAmount;
		$data['setsNames']     = array();
		$data['setsForms']     = array();

		for ($i = 0; $i < $setsAmount; $i++)
		{
			$data['setsNames'][$i] = JText::sprintf('PLG_TINYMCE_LATEST_SET_TITLE', $i);
		}

		$groupsInUse = array();

		foreach (array_keys($data['setsNames']) as $num)
		{
			$form = JForm::getInstance('tinymce_latest.set.' . $num, JPATH_PLUGINS . '/editors/tinymce_latest/form/setoptions.xml',
				array('control' => $this->name . '[setoptions][' . $num . ']'));

			if (empty($value['setoptions'][$num]))
			{
				// Set 0: Administrator, Editor and Super Users; set 1: Registered and Manager; Public for the first other set
				$values         = new stdClass;
				$values->access = !$num ? array(4, 7, 8) : ($num === 1 ? array(2, 6) : array());

				if (!$values->access && !in_array(1, $groupsInUse))
				{
					$values->access = array(1);
				}
			}
			else
			{
				$values = (object) $value['setoptions'][$num];
			}

			if (!empty($values->access))
			{
				$groupsInUse = array_merge($groupsInUse, (array) $values->access);
			}

			$form->bind($values);
			$data['setsForms'][$num] = $form;
		}

		// The editor's translation of the labels, when there is one
		$language            = JFactory::getLanguage();
		$data['languageFile'] = '';

		foreach (array($language->getTag(), str_replace('-', '_', $language->getTag()), strtok($language->getTag(), '-')) as $candidate)
		{
			if (is_file(JPATH_ROOT . '/media/editors/tinymce_latest/langs/' . $candidate . '.js'))
			{
				$data['languageFile'] = 'media/editors/tinymce_latest/langs/' . $candidate . '.js';
				break;
			}
		}

		$this->layoutData = $data;

		return $data;
	}
}
