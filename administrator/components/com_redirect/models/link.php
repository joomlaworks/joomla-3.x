<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');

/**
 * Redirect link model.
 *
 * @since  1.6
 */
class RedirectModelLink extends JModelAdmin
{
    /**
     * @var        string    The prefix to use with controller messages.
     * @since   1.6
     */
    protected $text_prefix = 'COM_REDIRECT';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   1.6
     */
    protected function canDelete($record)
    {
        if ($record->published != -2) {
            return false;
        }

        return parent::canDelete($record);
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $type    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  JTable  A JTable object
     *
     * @since   1.6
     */
    public function getTable($type = 'Link', $prefix = 'RedirectTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  JForm  A JForm object on success, false on failure
     *
     * @since   1.6
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_redirect.link', 'link', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        // Modify the form based on access controls.
        if ($this->canEditState((object) $data) != true) {
            // Disable fields for display.
            $form->setFieldAttribute('published', 'disabled', 'true');

            // Disable fields while saving.
            // The controller has already verified this is a record you can edit.
            $form->setFieldAttribute('published', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get a single record: the target split into the form's two fields.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed  Object on success, false on failure.
     *
     * @since   3.18.0
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item) {
            $item->target_type      = RedirectHelper::isExternal($item->new_url) ? 'external' : 'internal';
            $item->new_url_external = $item->target_type === 'external' ? $item->new_url : '';
            $item->new_url          = $item->target_type === 'external' ? '' : $item->new_url;
        }

        return $item;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.6
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState('com_redirect.edit.link.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_redirect.link', $data);

        return $data;
    }

    /**
     * Saves an entry: the target is the field of the chosen type. A target which is the source of another entry is saved
     * with a warning (each visitor is redirected twice).
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     *
     * @since   3.18.0
     */
    public function save($data)
    {
        if (isset($data['target_type']) && $data['target_type'] === 'external') {
            $data['new_url'] = isset($data['new_url_external']) ? $data['new_url_external'] : '';

            if (trim($data['new_url']) !== '' && !RedirectHelper::isExternal(trim($data['new_url']))) {
                $this->setError(JText::_('COM_REDIRECT_ERROR_DESTINATION_URL_INVALID'));

                return false;
            }
        }

        unset($data['target_type'], $data['new_url_external']);

        if (!parent::save($data)) {
            return false;
        }

        $source = RedirectHelper::normaliseSource($data['old_url']);
        $target = RedirectHelper::normaliseTarget($data['new_url']);

        if ($source !== false && $target !== false
            && RedirectHelper::checkLoop($source, $target, (int) $this->getState($this->getName() . '.id')) === 'chain') {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_REDIRECT_WARNING_CHAIN'), 'warning');
        }

        return true;
    }
}
