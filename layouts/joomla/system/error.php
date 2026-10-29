<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * The error page of a template without error.php: what went wrong in plain words, a search form and the way home, the
 * technical detail only in debug mode. It may be shown because the database or the language failed, so nothing here
 * depends on them: texts fall back to English, the search form is offered only when a search component is known to be
 * enabled.
 *
 * @var  JDocumentError  $this
 */

if (!isset($this->error))
{
	$this->error = JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
	$this->debug = false;
}

$text = function ($key, $english)
{
	try
	{
		$string = JText::_($key);
	}
	catch (Throwable $e)
	{
		return $english;
	}

	return $string === $key ? $english : $string;
};

$escape = function ($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$code = (int) $this->error->getCode();
$root = JUri::root(true) . '/';

try
{
	$siteName = (string) JFactory::getConfig()->get('sitename');
}
catch (Throwable $e)
{
	$siteName = '';
}

// What went wrong, by kind
if ($code === 404)
{
	$title   = $text('JERROR_LAYOUT_NOT_FOUND_TITLE', 'Page not found');
	$message = $text('JERROR_LAYOUT_NOT_FOUND_TEXT', 'The page you are looking for doesn\'t exist, or has moved. Check the address, search the site or go to the home page.');
}
elseif ($code === 401 || $code === 403)
{
	$title   = $text('JERROR_LAYOUT_NO_ACCESS_TITLE', 'Access denied');
	$message = $text('JERROR_LAYOUT_NO_ACCESS_TEXT', 'You don\'t have permission to view this page. Logging in may give you access.');
}
else
{
	$title   = $text('JERROR_LAYOUT_SERVER_ERROR_TITLE', 'Something went wrong');
	$message = $text('JERROR_LAYOUT_SERVER_ERROR_TEXT', 'The site couldn\'t complete your request. Please try again in a few minutes. If it keeps happening, let the site\'s administrator know.');
}

// The search form, when a search component is enabled (Smart Search first)
$search = null;

try
{
	if (JComponentHelper::isEnabled('com_finder'))
	{
		$search = array('option' => 'com_finder', 'view' => 'search', 'field' => 'q');
	}
	elseif (JComponentHelper::isEnabled('com_search'))
	{
		$search = array('option' => 'com_search', 'view' => 'search', 'field' => 'searchword');
	}
}
catch (Throwable $e)
{
	$search = null;
}

// A guest denied access may log in
try
{
	$login = ($code === 401 || $code === 403) && JFactory::getUser()->guest && JComponentHelper::isEnabled('com_users');
}
catch (Throwable $e)
{
	$login = false;
}

try
{
	$debugStyles = JFactory::getApplication()->get('debug_lang', '0') == '1' || JFactory::getApplication()->get('debug', '0') == '1';
}
catch (Throwable $e)
{
	$debugStyles = false;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $escape($this->language); ?>" dir="<?php echo $escape($this->direction); ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="robots" content="noindex" />
	<title><?php echo $code; ?> - <?php echo $escape($title); ?><?php echo $siteName !== '' ? ' | ' . $escape($siteName) : ''; ?></title>
	<link href="<?php echo $root; ?>media/system/css/frontend.min.css" rel="stylesheet" />
	<?php if ($debugStyles) : ?>
		<link href="<?php echo $root; ?>media/system/css/debug.css" rel="stylesheet" />
	<?php endif; ?>
</head>
<body class="tmpl-error">
	<main class="sys-page">
		<div class="sys-card">
			<?php if ($siteName !== '') : ?>
				<p class="sys-site"><a href="<?php echo $root; ?>"><?php echo $escape($siteName); ?></a></p>
			<?php endif; ?>
			<p class="sys-code"><?php echo $code; ?></p>
			<h1 class="sys-title"><?php echo $escape($title); ?></h1>
			<p class="sys-text"><?php echo $escape($message); ?></p>

			<?php if ($search) : ?>
				<form class="sys-search" action="<?php echo $root; ?>index.php" method="get" role="search">
					<label for="sys-search-field"><?php echo $escape($text('JERROR_LAYOUT_SEARCH_PAGE', 'Search this site')); ?></label>
					<div class="sys-search-row">
						<input type="search" id="sys-search-field" name="<?php echo $search['field']; ?>" required />
						<input type="hidden" name="option" value="<?php echo $search['option']; ?>" />
						<input type="hidden" name="view" value="<?php echo $search['view']; ?>" />
						<button type="submit" class="sys-button"><?php echo $escape($text('JERROR_LAYOUT_SEARCH_BUTTON', 'Search')); ?></button>
					</div>
				</form>
			<?php endif; ?>

			<p class="sys-actions">
				<a class="sys-button sys-button-primary" href="<?php echo $root; ?>"><?php echo $escape($text('JERROR_LAYOUT_HOME_PAGE', 'Home Page')); ?></a>
				<?php if ($login) : ?>
					<a class="sys-button" href="<?php echo $root; ?>index.php?option=com_users&amp;view=login"><?php echo $escape($text('JLOGIN', 'Log in')); ?></a>
				<?php endif; ?>
			</p>

			<?php if ($this->debug) : ?>
				<details class="sys-details" open>
					<summary><?php echo $escape($text('JERROR_LAYOUT_TECHNICAL_DETAILS', 'Technical details')); ?></summary>
					<p><?php echo $escape($this->error->getMessage()); ?><br /><?php echo $escape($this->error->getFile()); ?>:<?php echo $this->error->getLine(); ?></p>
					<?php echo $this->renderBacktrace(); ?>
					<?php // Earlier exceptions, each with its data and backtrace ?>
					<?php if ($this->error->getPrevious()) : ?>
						<?php $loop = true; ?>
						<?php // setError() assigns to $this->_error, which the backtrace reads; the first assignment outside the loop so none is skipped ?>
						<?php $this->setError($this->_error->getPrevious()); ?>
						<?php while ($loop === true) : ?>
							<p><strong><?php echo $escape($text('JERROR_LAYOUT_PREVIOUS_ERROR', 'Previous Error')); ?></strong></p>
							<p><?php echo $escape($this->_error->getMessage()); ?><br /><?php echo $escape($this->_error->getFile()); ?>:<?php echo $this->_error->getLine(); ?></p>
							<?php echo $this->renderBacktrace(); ?>
							<?php $loop = $this->setError($this->_error->getPrevious()); ?>
						<?php endwhile; ?>
						<?php $this->setError($this->error); ?>
					<?php endif; ?>
				</details>
			<?php else : ?>
				<p class="sys-reference"><?php echo $escape($this->error->getMessage()); ?></p>
			<?php endif; ?>
		</div>
	</main>
</body>
</html>
