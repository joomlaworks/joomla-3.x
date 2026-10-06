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
 * Lists a template's files.
 *
 * @since  3.17.0
 */
class TemplateFileListCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:file:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List a template\'s files';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the files in a template\'s folder (or one of its folders, with --path): their path, size, last change, and '
		. 'whether they are code (PHP, XML, dot files) or assets (CSS, JavaScript, images). template:file:get reads one.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('template', self::ARGUMENT_REQUIRED, 'The template\'s name (its folder), e.g. hammond');
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
		$this->addOption('path', null, self::OPTION_REQUIRED, 'Only this folder of the template, e.g. css or html/com_content');
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

		$folder = $template['path'];
		$prefix = '';

		if ((string) $io->getOption('path') !== '')
		{
			$path = trim(str_replace('\\', '/', (string) $io->getOption('path')), '/');

			if (in_array('..', explode('/', $path), true) || !is_dir($folder . '/' . $path) || is_link($folder . '/' . $path)
				|| strpos((string) realpath($folder . '/' . $path), $folder) !== 0)
			{
				$io->error(sprintf('The %s template has no folder "%s".', $template['name'], $path));

				return self::NOT_FOUND;
			}

			$folder .= '/' . $path;
			$prefix  = $path . '/';
		}

		$rows = array();

		foreach (static::listFiles($folder) as $relative)
		{
			$file   = $folder . '/' . $relative;
			$rows[] = array(
				'path'     => $prefix . $relative,
				'size'     => (int) filesize($file),
				'modified' => date('Y-m-d H:i', (int) filemtime($file)),
				'kind'     => static::isCode($relative) ? 'code' : 'asset',
			);
		}

		$io->title(sprintf('Files of the %s template %s%s', $template['client'], $template['name'], $prefix !== '' ? ' (' . rtrim($prefix, '/') . ')' : ''));

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No files.');

			return self::SUCCESS;
		}

		$io->table(array('path' => 'Path', 'size' => 'Size', 'modified' => 'Modified', 'kind' => 'Kind'), $rows);

		return self::SUCCESS;
	}
}
