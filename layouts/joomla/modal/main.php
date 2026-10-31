<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

// The modal's tooltips (.modalTooltip): the CMS's (CMS.tip), which keep inside the window and above the modal
JHtml::_('bootstrap.tooltip', '.modalTooltip', array('placement' => 'top'));

extract($displayData);

/**
 * Layout variables
 * ------------------
 * @param   string  $selector  Unique DOM identifier for the modal. CSS id without #
 * @param   array   $params    Modal parameters. Default supported parameters:
 *                             - title        string   The modal title
 *                             - backdrop     mixed    A boolean select if a modal-backdrop element should be included (default = true)
 *                                                     The string 'static' includes a backdrop which doesn't close the modal on click.
 *                             - keyboard     boolean  Closes the modal when escape key is pressed (default = true)
 *                             - closeButton  boolean  Display modal close button (default = true)
 *                             - animation    boolean  Fade in from the top of the page (default = true)
 *                             - url          string   URL of a resource to be inserted as an <iframe> inside the modal body
 *                             - height       string   height of the <iframe> containing the remote resource
 *                             - width        string   width of the <iframe> containing the remote resource
 *                             - bodyHeight   int      Optional height of the modal body in viewport units (vh)
 *                             - modalWidth   int      Optional width of the modal in viewport units (vh)
 *                             - footer       string   Optional markup for the modal footer
 * @param   string  $body      Markup for the modal body. Appended after the <iframe> if the URL option is set
 *
 */

$modalClasses = array('modal', 'hide');

if (!isset($params['animation']) || $params['animation']) {
    $modalClasses[] = 'fade';
}

$modalWidth = isset($params['modalWidth']) ? round((int) $params['modalWidth'], -1) : '';

if ($modalWidth && $modalWidth > 0 && $modalWidth <= 100) {
    $modalClasses[] = 'jviewport-width' . $modalWidth;
}

$modalAttributes = array(
    'tabindex' => '-1',
    'class'    => implode(' ', $modalClasses)
);

if (isset($params['backdrop'])) {
    $modalAttributes['data-backdrop'] = (is_bool($params['backdrop']) ? ($params['backdrop'] ? 'true' : 'false') : $params['backdrop']);
}

if (isset($params['keyboard'])) {
    $modalAttributes['data-keyboard'] = (is_bool($params['keyboard']) ? ($params['keyboard'] ? 'true' : 'false') : 'true');
}

/*
 * The CMS's modal (CMS.bootstrap.modal, global.js) acts on data-cms-modal: body.modal-open while it's open, the url's page
 * in a new iframe each time it opens (the body layout's <template class="modal-iframe">), and a body which would leave the
 * window scrolling instead; tooltips hidden when it closes.
 */
$modalAttributes['data-cms-modal'] = '';
?>
<div id="<?php echo $selector; ?>" <?php echo ArrayHelper::toString($modalAttributes); ?>>
	<?php
    // Header
    if (!isset($params['closeButton']) || isset($params['title']) || $params['closeButton']) {
        echo JLayoutHelper::render('joomla.modal.header', $displayData);
    }

// Body
echo JLayoutHelper::render('joomla.modal.body', $displayData);

// Footer
if (isset($params['footer'])) {
    echo JLayoutHelper::render('joomla.modal.footer', $displayData);
}
?>
</div>
