<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$id = empty($displayData['id']) ? '' : $displayData['id'];
$active = empty($displayData['active']) ? '' : $displayData['active'];
$title = empty($displayData['title']) ? '' : $displayData['title'];

// The tab's title (HTML) goes with its pane: global.js writes the set's list from them (CMS.bootstrap.buildTabs())
?>
<div id="<?php echo $id; ?>" class="tab-pane<?php echo $active; ?>" data-tab-title="<?php echo htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8'); ?>">
