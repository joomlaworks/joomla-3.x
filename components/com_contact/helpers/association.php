<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('ContactHelper', JPATH_ADMINISTRATOR . '/components/com_contact/helpers/contact.php');
JLoader::register('ContactHelperRoute', JPATH_SITE . '/components/com_contact/helpers/route.php');
JLoader::register('CategoryHelperAssociation', JPATH_ADMINISTRATOR . '/components/com_categories/helpers/association.php');

/**
 * Contact Component Association Helper
 *
 * @since  3.0
 */
abstract class ContactHelperAssociation extends CategoryHelperAssociation
{
    /**
     * Method to get the associations for a given item
     *
     * @param   integer  $id    Id of the item
     * @param   string   $view  Name of the view
     *
     * @return  array   Array of associations for the item
     *
     * @since  3.0
     */
    public static function getAssociations($id = 0, $view = null)
    {
        $jinput = JFactory::getApplication()->input;
        $view   = $view === null ? $jinput->get('view') : $view;
        $id     = empty($id) ? $jinput->getInt('id') : $id;

        if ($view === 'contact') {
            if ($id) {
                // Only associated items the visitor may see (as Articles' associations)
                $user      = JFactory::getUser();
                $db        = JFactory::getDbo();
                $advClause = array(
                    'c2.access IN (' . implode(',', array_map('intval', $user->getAuthorisedViewLevels())) . ')',
                    'c2.language != ' . $db->quote(JFactory::getLanguage()->getTag()),
                );

                if (!$user->authorise('core.edit.state', 'com_contact') && !$user->authorise('core.edit', 'com_contact')) {
                    $nullDate = $db->quote($db->getNullDate());
                    $nowDate  = $db->quote(JFactory::getDate()->toSql());

                    $advClause[] = '(c2.publish_up IS NULL OR c2.publish_up = ' . $nullDate . ' OR c2.publish_up <= ' . $nowDate . ')';
                    $advClause[] = '(c2.publish_down IS NULL OR c2.publish_down = ' . $nullDate . ' OR c2.publish_down >= ' . $nowDate . ')';
                    $advClause[] = 'c2.published = 1';
                }

                $associations = JLanguageAssociations::getAssociations('com_contact', '#__contact_details', 'com_contact.item', $id, 'id', 'alias', 'catid', $advClause);

                $return = array();

                foreach ($associations as $tag => $item) {
                    $return[$tag] = ContactHelperRoute::getContactRoute($item->id, (int) $item->catid, $item->language);
                }

                return $return;
            }
        }

        if ($view === 'category' || $view === 'categories') {
            return self::getCategoryAssociations($id, 'com_contact');
        }

        return array();

    }
}
