<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

/**
 * Cleans the site and administrator cache, or only its expired entries.
 *
 * @since  3.17.0
 */
class CacheCleanCommand extends AbstractCommand
{
    /**
     * @var    string
     * @since  3.17.0
     */
    protected $name = 'cache:clean';

    /**
     * @var    string
     * @since  3.17.0
     */
    protected $description = 'Clean cache entries';

    /**
     * @var    string
     * @since  3.17.0
     */
    protected $help = 'Deletes every cache group of the site and the administrator, like System > Clear Cache > Delete All. '
        . 'With the "expired" argument it only removes expired entries, like System > Clear Expired Cache. '
        . 'A cache kept in APCu or WinCache can\'t be cleaned from the command line: those keep a separate memory for each '
        . 'kind of PHP process, so the command refuses instead of cleaning a cache the site doesn\'t use ("expired" has nothing '
        . 'to do there: they remove expired entries themselves).';

    /**
     * @var    boolean
     * @since  3.17.0
     */
    protected $dryRun = true;

    /**
     * @var    boolean
     * @since  3.17.0
     */
    protected $logged = false;

    /**
     * @return  void
     *
     * @since   3.17.0
     */
    protected function configure()
    {
        $this->addArgument('expired', self::ARGUMENT_OPTIONAL, 'Give "expired" to only remove expired entries');
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
        $expired = (string) $io->getArgument('expired');

        if ($expired !== '' && $expired !== 'expired') {
            $io->error(sprintf('Unknown argument "%s". Give "expired" or nothing.', $expired));

            return self::INVALID;
        }

        $io->title('Cleaning System Cache');

        $handler = (string) (ConfigSetCommand::readConfiguration()['cache_handler'] ?? '');

        if (in_array($handler, array('apcu', 'wincache'), true)) {
            $name = $handler === 'apcu' ? 'APCu' : 'WinCache';

            if ($expired !== '') {
                $io->success(sprintf('Nothing to do: the cache is kept in %s, which removes expired entries itself.', $name));

                return self::SUCCESS;
            }

            $io->error(sprintf(
                'The cache is kept in %s, which keeps a separate memory for each kind of PHP process: the command line '
                . 'can\'t reach the web server\'s cache. Clean it in the administrator (System > Clear Cache), or restart '
                . 'the web server\'s PHP.',
                $name
            ));

            return self::FAILURE;
        }

        /** @var \CacheModelCache $model */
        $model   = $this->getAdministratorModel('com_cache', 'Cache', 'CacheModel');
        $cleaned = true;
        $groups  = 0;

        foreach (array(0, 1) as $clientId) {
            $cache = $model->getCache($clientId);

            if ($io->isDryRun()) {
                $client = $clientId ? 'administrator' : 'site';

                if ($expired !== '') {
                    $io->plan(sprintf('Remove the expired entries of the %s cache', $client), array('action' => 'gc', 'client' => $client));

                    continue;
                }

                foreach ($cache->getAll() ?: array() as $group) {
                    $groups++;
                    $io->plan(
                        sprintf('Clean the %s cache group "%s" (%d entries)', $client, $group->group, $group->count),
                        array('action' => 'clean', 'client' => $client, 'group' => $group->group, 'entries' => (int) $group->count)
                    );
                }

                continue;
            }

            if ($expired !== '') {
                $cleaned = $cache->gc() !== false && $cleaned;

                continue;
            }

            // Clean every group: the Redis, Memcached, APCu and WinCache handlers clean nothing for an empty group name
            foreach ($cache->getAll() ?: array() as $group) {
                $cleaned = $cache->clean($group->group) !== false && $cleaned;
                $groups++;
            }
        }

        if ($expired === '') {
            $io->setData('groups', $groups);
        }

        if (!$cleaned) {
            $io->error($expired !== '' ? 'Expired cache not cleaned.' : 'Cache not cleaned.');

            return self::FAILURE;
        }

        if (!$io->isDryRun()) {
            $io->success($expired !== '' ? 'Expired cache cleaned.' : 'Cache cleaned.');
        }

        return self::SUCCESS;
    }
}
