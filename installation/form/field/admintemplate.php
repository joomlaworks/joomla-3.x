<?php

/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('radio');

/**
 * The administrator's template: Prothero or Isis, each shown with its thumbnail.
 *
 * @since  3.18.0
 */
class InstallationFormFieldAdmintemplate extends JFormFieldRadio
{
    /**
     * The templates offered, value => name
     *
     * @var    array
     * @since  3.18.0
     */
    public const TEMPLATES = array('prothero' => 'Prothero', 'isis' => 'Isis');

    /**
     * The form field type.
     *
     * @var    string
     * @since  3.18.0
     */
    protected $type = 'Admintemplate';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   3.18.0
     */
    protected function getOptions()
    {
        $options = array();

        // The installer's own copies of the thumbnails: the administrator's folder may be behind HTTP authentication
        foreach (static::TEMPLATES as $value => $name) {
            $options[] = JHtml::_(
                'select.option',
                $value,
                '<img class="template-thumb" src="template/images/admin-' . $value . '.webp" width="400" height="400" alt="" />'
                . '<span class="sample-title">' . $name . '</span>'
            );
        }

        return array_merge(parent::getOptions(), $options);
    }

    /**
     * Method to get the field input markup.
     *
     * @return  string   The field input markup.
     *
     * @since   3.18.0
     */
    protected function getInput()
    {
        if (!isset(static::TEMPLATES[(string) $this->value])) {
            $this->value = 'prothero';
        }

        return parent::getInput();
    }
}
