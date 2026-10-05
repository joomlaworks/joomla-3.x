<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('radio');

/**
 * Install Sample Data field.
 *
 * @since  1.6
 */
class InstallationFormFieldSample extends JFormFieldRadio
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $type = 'Sample';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.6
	 */
	protected function getOptions()
	{
		$options = array();
		$type    = $this->form->getValue('db_type');

		// Some database drivers share DDLs; point these drivers to the correct parent
		if ($type === 'mysqli' || $type === 'pdomysql' || $type === 'mysqlonsqlite')
		{
			$type = 'mysql';
		}
		elseif ($type === 'sqlsrv')
		{
			$type = 'sqlazure';
		}
		elseif ($type === 'pgsql')
		{
			$type = 'postgresql';
		}

		// Get a list of files in the search path with the given filter.
		$files = JFolder::files(JPATH_INSTALLATION . '/sql/' . $type, '^sample.*\.sql$');

		// Each choice shows its name and description (the installer's styles turn them into cards)
		$options[] = JHtml::_('select.option', '', $this->choice('INSTL_SITE_INSTALL_SAMPLE_NONE', 'INSTL_SITE_INSTALL_SAMPLE_NONE_DESC'));

		// Build the options list from the list of files.
		if (is_array($files))
		{
			$lang = JFactory::getLanguage();

			foreach ($files as $file)
			{
				$name = strtoupper(JFile::stripExt($file));

				$options[] = JHtml::_('select.option', $file, $lang->hasKey('INSTL_' . $name . '_SET')
					? $this->choice('INSTL_' . $name . '_SET', 'INSTL_' . $name . '_SET_DESC')
					: htmlspecialchars($file, ENT_COMPAT, 'UTF-8'));
			}
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}

	/**
	 * The label of a choice: its name and description.
	 *
	 * @param   string  $title        The language key of the name
	 * @param   string  $description  The language key of the description
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function choice($title, $description)
	{
		return '<span class="sample-title">' . JText::_($title) . '</span><span class="sample-desc">' . JText::_($description) . '</span>';
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string   The field input markup.
	 *
	 * @since   1.6
	 */
	protected function getInput()
	{
		if (!$this->value)
		{
			$conf = JFactory::getConfig();

			if ($conf->get('sampledata'))
			{
				$this->value = $conf->get('sampledata');
			}
			else
			{
				$this->value = '';
			}
		}

		return parent::getInput();
	}
}
