<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var  array  $displayData */
$data = $displayData;

if ($data['view'] instanceof MenusViewItems)
{
	// We will get the menutype filter & remove it from the form filters
	$menuTypeField = $data['view']->filterForm->getField('menutype');

	// The client selector is a row of tabs above the search tools (layout joomla.searchtools.default.client)
	?>
	<div class="js-stools-field-filter js-stools-menutype">
		<?php echo $menuTypeField->input; ?>
	</div>
	<?php
}

// Display the main joomla layout
echo JLayoutHelper::render('joomla.searchtools.default.bar', $data, null, array('component' => 'none'));
