<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Cache\CleanRequests;
use Joomla\CMS\Cache\Storage\CommandlineStorage;

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
        . 'With the "expired" argument it only removes expired entries, like the Clear Expired Cache button. '
        . 'A cache kept in APCu or WinCache is in the web server\'s own memory, which the command line can\'t reach: the '
        . 'command asks the web server to clean it, which it does on its next request ("expired" has nothing to do there: '
        . 'they remove expired entries themselves).';

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

        /** @var \CacheModelCache $model */
        $model   = $this->getAdministratorModel('com_cache', 'Cache', 'CacheModel');
        $storage = $model->getCache(0)->_getStorage();

        // APCu and WinCache keep the cache in the web server's own memory, which the command line can't reach
        if ($storage instanceof CommandlineStorage) {
            $name = $storage->handler === 'wincache' ? 'WinCache' : 'APCu';

            if ($expired !== '') {
                $io->success(sprintf('Nothing to do: the cache is kept in %s, which removes expired entries itself.', $name));

                return self::SUCCESS;
            }

            if ($io->isDryRun()) {
                $io->plan(
                    sprintf('Ask the web server to clean its %s cache (every group) on its next request', $name),
                    array('action' => 'clean', 'handler' => $storage->handler)
                );

                return self::SUCCESS;
            }

            if (!$storage->cleanAll()) {
                $io->error(sprintf('The web server\'s %s cache could not be cleaned: the cache folder\'s %s could not be written.', $name, basename(CleanRequests::getFile())));

                return self::FAILURE;
            }

            $io->success(sprintf('Cache cleaned: the web server keeps it in %s and cleans it on its next request.', $name));

            return self::SUCCESS;
        }

        $cleaned = true;
        $groups  = 0;

        // Handlers keeping one cache for both would be listed and counted twice
        foreach ($model->getSharedHandler() ? array(0) : array(0, 1) as $clientId) {
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
