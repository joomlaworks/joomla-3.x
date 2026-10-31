<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserHelper;

/**
 * Shared code of the user:* commands which change users.
 *
 * @since  3.17.0
 */
abstract class AbstractUserCommand extends AbstractCommand
{
    /**
     * @var    boolean
     * @since  3.17.0
     */
    protected $superUser = true;

    /**
     * Get an option's value, asking for it when it's missing and the command is interactive.
     *
     * @param   CommandIO  $io        The input values and the output
     * @param   string     $option    The option name
     * @param   string     $question  The question to ask
     * @param   boolean    $hidden    Hide the typed value
     *
     * @return  string  The value, or '' after reporting the missing option
     *
     * @since   3.17.0
     */
    protected function getRequiredOption(CommandIO $io, $option, $question, $hidden = false)
    {
        $value = (string) $io->getOption($option);

        while ($value === '' && $io->isInteractive()) {
            $value = (string) $io->ask($question, null, $hidden);
        }

        if ($value === '') {
            $io->error(sprintf('The --%s option is required.', $option));
        }

        return $value;
    }

    /**
     * @param   string  $username  The username
     *
     * @return  integer  The user ID, 0 if there's no such user
     *
     * @since   3.17.0
     */
    protected function getUserId($username)
    {
        return (int) UserHelper::getUserId($username);
    }

    /**
     * Get the IDs of user groups given by title, separated by commas.
     *
     * @param   CommandIO  $io      The output
     * @param   string     $titles  The group titles
     *
     * @return  array|false  The group IDs, false after reporting an unknown group
     *
     * @since   3.17.0
     */
    protected function getGroupIds(?CommandIO $io, $titles)
    {
        $db  = Factory::getDbo();
        $ids = array();

        foreach (array_filter(array_map('trim', explode(',', (string) $titles)), 'strlen') as $title) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__usergroups'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($title));
            $id = (int) $db->setQuery($query)->loadResult();

            if (!$id) {
                if ($io) {
                    $io->error(sprintf('The user group "%s" doesn\'t exist.', $title));
                }

                return false;
            }

            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param   integer  $groupId  The group ID
     *
     * @return  string
     *
     * @since   3.17.0
     */
    protected function getGroupTitle($groupId)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('id') . ' = ' . (int) $groupId);

        return (string) $db->setQuery($query)->loadResult();
    }

    /**
     * Whether a user group grants Super User rights.
     *
     * @param   integer  $groupId  The group ID
     *
     * @return  boolean
     *
     * @since   3.17.0
     */
    protected function isSuperUserGroup($groupId)
    {
        return (bool) Access::checkGroup((int) $groupId, 'core.admin');
    }

    /**
     * Whether a user group can put code on the server: Super Users, and groups which may manage extensions (installing
     * runs a package's PHP) or templates (the template editor writes PHP files). Handing such a group to an account is
     * writing code for the MCP server (writesCode(): --allow-code), not only a change (--allow-write).
     *
     * @param   integer  $groupId  The group ID
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    protected function isCodeGroup($groupId)
    {
        $groupId = (int) $groupId;

        return Access::checkGroup($groupId, 'core.admin') || Access::checkGroup($groupId, 'core.manage', 'com_installer')
            || Access::checkGroup($groupId, 'core.manage', 'com_templates');
    }

    /**
     * Whether any of the groups (titles separated by commas, as the commands take them) can put code on the server
     *
     * @param   string  $titles  The group titles
     *
     * @return  boolean  True also when they can't be read: the run is judged when it happens
     *
     * @since   3.18.0
     */
    protected function namesCodeGroup($titles)
    {
        try {
            $ids = $this->getGroupIds(null, $titles);
        } catch (\Exception $e) {
            return true;
        }

        return $ids === false ? false : (bool) array_filter($ids, array($this, 'isCodeGroup'));
    }

    /**
     * Count the active (not blocked) Super Users other than the given user.
     *
     * @param   integer  $userId  The user to leave out
     *
     * @return  integer
     *
     * @since   3.17.0
     */
    protected function countOtherActiveSuperUsers($userId)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__usergroups'));
        $groups = array_filter(array_map('intval', $db->setQuery($query)->loadColumn()), array($this, 'isSuperUserGroup'));

        if (!$groups) {
            return 0;
        }

        $query = $db->getQuery(true)
            ->select('COUNT(DISTINCT ' . $db->quoteName('u.id') . ')')
            ->from($db->quoteName('#__users', 'u'))
            ->join('INNER', $db->quoteName('#__user_usergroup_map', 'm') . ' ON ' . $db->quoteName('m.user_id') . ' = ' . $db->quoteName('u.id'))
            ->where($db->quoteName('m.group_id') . ' IN (' . implode(',', $groups) . ')')
            ->where($db->quoteName('u.block') . ' = 0')
            ->where($db->quoteName('u.id') . ' <> ' . (int) $userId);

        return (int) $db->setQuery($query)->loadResult();
    }
}
