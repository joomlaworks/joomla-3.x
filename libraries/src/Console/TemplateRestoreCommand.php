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
 * Puts a template backup back.
 *
 * @since  3.17.0
 */
class TemplateRestoreCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:restore';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Restore a template backup';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Makes a template\'s folder what it was in a backup made by template:backup: changed files get their backed up '
		. 'contents, removed ones come back, and files added since are deleted. The current state is backed up first, so a restore '
		. 'can be undone by restoring that backup. Without a backup name, the template\'s latest backup is restored; '
		. 'template:backup:list shows them. --dry-run shows which files would change.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('template', self::ARGUMENT_REQUIRED, 'The template\'s name (its folder), e.g. hammond');
		$this->addArgument('backup', self::ARGUMENT_OPTIONAL, 'The backup (as template:backup:list shows it; default: the latest)');
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
	}

	/**
	 * A restore writes the template's PHP files too.
	 *
	 * @param   array|null  $options    The options of a run
	 * @param   array|null  $arguments  The arguments of a run
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function writesCode(?array $options = null, ?array $arguments = null)
	{
		return true;
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
		$template = $this->findTemplate($io, $io->getArgument('template'), (string) $io->getOption('client'));

		if (!is_array($template))
		{
			return $template;
		}

		$backups = static::listBackups($template['name'], $template['client']);
		$name    = trim((string) $io->getArgument('backup'));
		$backup  = null;

		foreach ($backups as $candidate)
		{
			if ($name === '' || $name === 'latest' || $candidate['name'] === $name || $candidate['name'] === $name . '.zip')
			{
				$backup = $candidate;

				break;
			}
		}

		if ($backup === null)
		{
			$io->error($name === '' || $name === 'latest'
				? sprintf('There is no backup of the %s template %s. template:backup makes one.', $template['client'], $template['name'])
				: sprintf('There is no backup "%s" of the %s template %s. template:backup:list shows them.', $name, $template['client'], $template['name']));

			return self::NOT_FOUND;
		}

		$entries = static::readBackup($backup['file']);

		if (!is_array($entries))
		{
			$io->error($entries);

			return self::FAILURE;
		}

		if (!isset($entries['templateDetails.xml']) || !isset($entries['index.php']))
		{
			$io->error(sprintf('%s has no index.php and templateDetails.xml, so it is not a backup of a template.', $backup['name']));

			return self::INVALID;
		}

		// What changes: files the backup has which differ or are missing, and files added since
		$current = static::listFiles($template['path']);
		$changes = array('changed' => array(), 'added' => array(), 'removed' => array());

		foreach ($entries as $relative => $content)
		{
			$file = $template['path'] . '/' . $relative;

			if (!is_file($file))
			{
				$changes['added'][] = $relative;
			}
			elseif (sha1_file($file) !== sha1($content))
			{
				$changes['changed'][] = $relative;
			}
		}

		$changes['removed'] = array_values(array_diff($current, array_keys($entries)));

		$io->setData('backup', $backup['name']);
		$io->setData('changes', $changes);

		if (!array_filter($changes))
		{
			$io->success(sprintf('The %s template %s is already as in %s.', $template['client'], $template['name'], $backup['name']));

			return self::SUCCESS;
		}

		$summary = sprintf('%d changed, %d restored, %d removed', count($changes['changed']), count($changes['added']), count($changes['removed']));

		if ($io->isDryRun())
		{
			$io->plan(sprintf('Restore the %s template %s from %s (%s), after backing up its current files', $template['client'], $template['name'],
				$backup['name'], $summary), array('action' => 'restore', 'backup' => $backup['name']));

			foreach (array('changed' => 'Change', 'added' => 'Restore', 'removed' => 'Remove') as $kind => $verb)
			{
				foreach ($changes[$kind] as $relative)
				{
					$io->plan($verb . ' ' . $relative, array('action' => $kind, 'path' => $relative));
				}
			}

			return self::SUCCESS;
		}

		$saved = static::createBackup($template);

		if (!is_array($saved))
		{
			$io->error('The current files could not be backed up first, so nothing was restored: ' . $saved);

			return self::FAILURE;
		}

		$failed = array();

		foreach (array_merge($changes['changed'], $changes['added']) as $relative)
		{
			if (!static::writeFile($template['path'] . '/' . $relative, $entries[$relative]))
			{
				$failed[] = $relative;
			}
		}

		foreach ($changes['removed'] as $relative)
		{
			$file = $template['path'] . '/' . $relative;

			if (!@unlink($file))
			{
				$failed[] = $relative;
			}
			elseif (function_exists('opcache_invalidate') && static::isCode($file))
			{
				@opcache_invalidate($file, true);
			}
		}

		$io->setData('previousState', $saved['name']);
		$io->setData('failed', $failed);

		if ($failed)
		{
			$io->error(sprintf('%d files could not be written or removed: %s. The state before the restore is in %s.', count($failed),
				implode(', ', $failed), $saved['name']));

			return self::FAILURE;
		}

		$io->success(sprintf('The %s template %s is restored from %s (%s).', $template['client'], $template['name'], $backup['name'], $summary));
		$io->text('The files before the restore are in ' . $saved['name'] . ': template:restore ' . $template['name'] . ' ' . $saved['name']
			. ($template['client'] === 'site' ? '' : ' --client=administrator') . ' undoes it.');

		return self::SUCCESS;
	}
}
