<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_newsfeeds
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('NewsfeedsHelper', JPATH_ADMINISTRATOR . '/components/com_newsfeeds/helpers/newsfeeds.php');
JLoader::register('NewsfeedsHelperRoute', JPATH_SITE . '/components/com_newsfeeds/helpers/route.php');
JLoader::register('CategoryHelperAssociation', JPATH_ADMINISTRATOR . '/components/com_categories/helpers/association.php');

/**
 * Newsfeeds Component Association Helper
 *
 * @since  3.0
 */
abstract class NewsfeedsHelperAssociation extends CategoryHelperAssociation
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

        if ($view === 'newsfeed') {
            if ($id) {
                // Only associated items the visitor may see (as Articles' associations)
                $user      = JFactory::getUser();
                $db        = JFactory::getDbo();
                $advClause = array(
                    'c2.access IN (' . implode(',', array_map('intval', $user->getAuthorisedViewLevels())) . ')',
                    'c2.language != ' . $db->quote(JFactory::getLanguage()->getTag()),
                );

                if (!$user->authorise('core.edit.state', 'com_newsfeeds') && !$user->authorise('core.edit', 'com_newsfeeds')) {
                    $nullDate = $db->quote($db->getNullDate());
                    $nowDate  = $db->quote(JFactory::getDate()->toSql());

                    $advClause[] = '(c2.publish_up IS NULL OR c2.publish_up = ' . $nullDate . ' OR c2.publish_up <= ' . $nowDate . ')';
                    $advClause[] = '(c2.publish_down IS NULL OR c2.publish_down = ' . $nullDate . ' OR c2.publish_down >= ' . $nowDate . ')';
                    $advClause[] = 'c2.published = 1';
                }

                $associations = JLanguageAssociations::getAssociations('com_newsfeeds', '#__newsfeeds', 'com_newsfeeds.item', $id, 'id', 'alias', 'catid', $advClause);

                $return = array();

                foreach ($associations as $tag => $item) {
                    $return[$tag] = NewsfeedsHelperRoute::getNewsfeedRoute($item->id, (int) $item->catid, $item->language);
                }

                return $return;
            }
        }

        if ($view === 'category' || $view === 'categories') {
            return self::getCategoryAssociations($id, 'com_newsfeeds');
        }

        return array();
    }
}
