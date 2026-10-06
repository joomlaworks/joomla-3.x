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
 * Shows a file of a template.
 *
 * @since  3.17.0
 */
class TemplateFileGetCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:file:get';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show a file of a template';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Prints a file of a template\'s folder, e.g. css/template.css or html/com_content/article/default.php. With '
		. '--format=json, the contents are in "content" (base64 for a binary file such as an image, see "encoding").';

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
		$this->addArgument('path', self::ARGUMENT_REQUIRED, 'The file, relative to the template\'s folder, e.g. css/template.css');
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
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

		$file = $this->resolvePath($io, $template, $io->getArgument('path'));

		if (!is_array($file))
		{
			return $file;
		}

		if (filesize($file['full']) > self::MAX_FILE_SIZE)
		{
			$io->error(sprintf('%s is larger than %d MB.', $file['relative'], self::MAX_FILE_SIZE / 1048576));

			return self::REFUSED;
		}

		$content = file_get_contents($file['full']);

		if ($content === false)
		{
			$io->error(sprintf('Cannot read %s.', $file['relative']));

			return self::FAILURE;
		}

		$text = static::isText($content);

		$io->setData('path', $file['relative']);
		$io->setData('size', strlen($content));
		$io->setData('modified', date('Y-m-d H:i:s', (int) filemtime($file['full'])));
		$io->setData('kind', static::isCode($file['relative']) ? 'code' : 'asset');
		$io->setData('encoding', $text ? 'utf-8' : 'base64');
		$io->setData('content', $text ? $content : base64_encode($content));

		if (!$io->isJson())
		{
			if (!$text)
			{
				$io->error(sprintf('%s is a binary file (%d bytes); --format=json gives its contents in base64.', $file['relative'], strlen($content)));

				return self::REFUSED;
			}

			$io->writeln(rtrim($content, "\n"));
		}

		return self::SUCCESS;
	}
}
