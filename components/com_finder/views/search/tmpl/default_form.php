<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_finder
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$expanded = (bool) $this->params->get('expand_advanced', 0);

if ($this->params->get('show_advanced', 1)) {
    // The Advanced Search button shows and hides its panel (it was Bootstrap's collapse, which not every template loads)
    JFactory::getDocument()->addScriptDeclaration(
        "document.addEventListener('click', function (event) {\n"
        . "\tvar toggle = event.target.closest ? event.target.closest('[data-finder-toggle]') : null;\n"
        . "\tvar panel = toggle ? document.getElementById(toggle.getAttribute('data-finder-toggle')) : null;\n\n"
        . "\tif (panel) {\n"
        . "\t\tpanel.hidden = !panel.hidden;\n"
        . "\t\tpanel.classList.toggle('in', !panel.hidden);\n"
        . "\t\ttoggle.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');\n"
        . "\t}\n"
        . "});"
    );

    // Select boxes without a value are left out of the address (the same script as mod_finder's, written once)
    JFactory::getDocument()->addScriptDeclaration(
        "document.addEventListener('submit', function (event) {\n"
        . "\tvar selects = event.target.querySelectorAll('#finder-search #advancedSearch select, [id^=\"mod-finder-searchform\"] [id^=\"mod-finder-advanced\"] select');\n\n"
        . "\tfor (var i = 0; i < selects.length; i++) {\n"
        . "\t\tif (!selects[i].value) {\n"
        . "\t\t\tselects[i].disabled = true;\n"
        . "\t\t}\n"
        . "\t}\n"
        . "});"
    );
}

if ($this->params->get('show_autosuggest', 1)) {
    JHtml::_('behavior.core');
    JFactory::getDocument()->addScriptOptions('system.suggest', array(
        '#q' => array(
            'url'       => JRoute::_('index.php?option=com_finder&task=suggestions.suggest&format=json&tmpl=component', false),
            'paramName' => 'q',
            'minChars'  => 1,
            'maxHeight' => 400,
            'width'     => 300,
            'delay'     => 500,
        ),
    ));
}

?>
<form id="finder-search" action="<?php echo JRoute::_($this->query->toUri()); ?>" method="get" class="form-inline">
	<?php echo $this->getFields(); ?>
	<?php // DISABLED UNTIL WEIRD VALUES CAN BE TRACKED DOWN.?>
	<?php if (false && $this->state->get('list.ordering') !== 'relevance_dsc') : ?>
		<input type="hidden" name="o" value="<?php echo $this->escape($this->state->get('list.ordering')); ?>" />
	<?php endif; ?>
	<fieldset class="word">
		<label for="q">
			<?php echo JText::_('COM_FINDER_SEARCH_TERMS'); ?>
		</label>
		<input type="text" name="q" id="q" size="30" value="<?php echo $this->escape($this->query->input); ?>" class="inputbox" />
		<?php if ($this->escape($this->query->input) != '' || $this->params->get('allow_empty_query')) : ?>
			<button name="Search" type="submit" class="btn btn-primary">
				<span class="icon-search icon-white"></span>
				<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>
			</button>
		<?php else : ?>
			<button name="Search" type="submit" class="btn btn-primary disabled">
				<span class="icon-search icon-white"></span>
				<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>
			</button>
		<?php endif; ?>
		<?php if ($this->params->get('show_advanced', 1)) : ?>
			<button type="button" class="btn" data-finder-toggle="advancedSearch" aria-controls="advancedSearch" aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>">
				<span class="icon-list" aria-hidden="true"></span>
				<?php echo JText::_('COM_FINDER_ADVANCED_SEARCH_TOGGLE'); ?>
			</button>
		<?php endif; ?>
	</fieldset>
	<?php if ($this->params->get('show_advanced', 1)) : ?>
		<div id="advancedSearch" class="collapse<?php echo $expanded ? ' in' : ''; ?>"<?php echo $expanded ? '' : ' hidden="hidden"'; ?>>
			<hr />
			<?php if ($this->params->get('show_advanced_tips', 1)) : ?>
				<div id="search-query-explained">
					<div class="advanced-search-tip">
						<?php echo JText::_('COM_FINDER_ADVANCED_TIPS'); ?>
					</div>
					<hr />
				</div>
			<?php endif; ?>
			<div id="finder-filter-window">
				<?php echo JHtml::_('filter.select', $this->query, $this->params); ?>
			</div>
		</div>
	<?php endif; ?>
</form>
