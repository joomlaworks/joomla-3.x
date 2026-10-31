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
use Joomla\CMS\User\User;

/**
 * Changes a user's password.
 *
 * @since  3.17.0
 */
class UserResetPasswordCommand extends AbstractUserCommand
{
    /**
     * @var    string
     * @since  3.17.0
     */
    protected $name = 'user:reset-password';

    /**
     * @var    string
     * @since  3.17.0
     */
    protected $description = 'Change a user\'s password';

    /**
     * @var    string
     * @since  3.17.0
     */
    protected $help = 'Sets a new password for a user. Missing values are asked for, unless the command runs without interaction; '
        . 'then --username and --password are required. Give the password at the prompt to keep it out of the shell history.';

    /**
     * @var    boolean
     * @since  3.17.0
     */
    protected $dryRun = true;

    /**
     * A new password for an account which can put code on the server (isCodeGroup()) takes it over: code for the MCP server
     *
     * @param   array|null  $options    The options of a run
     * @param   array|null  $arguments  The arguments of a run
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function writesCode(?array $options = null, ?array $arguments = null)
    {
        if ($options === null) {
            return true;
        }

        $userId = $this->getUserId(isset($options['username']) ? (string) $options['username'] : '');

        return $userId > 0 && (bool) array_filter(Access::getGroupsByUser($userId), array($this, 'isCodeGroup'));
    }

    /**
     * @return  void
     *
     * @since   3.17.0
     */
    protected function configure()
    {
        $this->addOption('username', null, self::OPTION_REQUIRED, 'username');
        $this->addOption('password', null, self::OPTION_REQUIRED, 'password');
    }

    /**
     * @param   CommandIO  $io  The input values and the output
     *
     * @return  integer
     *
     * @since   3.17.0
     */
    protected function doExecute(CommandIO $io)
    {
        $io->title('Change Password');

        $username = $this->getRequiredOption($io, 'username', 'Please enter a username');

        if ($username === '') {
            return self::INVALID;
        }

        $userId = $this->getUserId($username);

        if (!$userId) {
            $io->error(sprintf('The user "%s" does not exist.', $username));

            return self::NOT_FOUND;
        }

        $password = $this->getRequiredOption($io, 'password', 'Please enter a new password', true);

        if ($password === '') {
            return self::INVALID;
        }

        $user = User::getInstance($userId);
        $data = array('password' => $password, 'password2' => $password);

        if ($io->isDryRun()) {
            $io->plan(sprintf('Change the password of "%s"', $username), array('action' => 'reset-password', 'username' => $username, 'id' => (int) $userId));

            return self::SUCCESS;
        }

        if (!$user->bind($data) || !$user->save(true)) {
            $io->error($user->getError() ?: 'The password was not changed.');

            return self::FAILURE;
        }

        $io->success('Password changed.');

        return self::SUCCESS;
    }
}
