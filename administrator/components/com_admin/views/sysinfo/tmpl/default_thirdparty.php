<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_admin
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var  AdminViewSysinfo  $this */
?>
<fieldset class="adminform">
	<legend><?php echo JText::_('COM_ADMIN_THIRD_PARTY_SOFTWARE'); ?></legend>
	<p><?php echo JText::_('COM_ADMIN_THIRD_PARTY_SOFTWARE_DESC'); ?></p>
	<?php foreach ($this->thirdParty as $group => $entries) : ?>
		<table class="table table-striped">
			<caption class="text-left"><strong><?php echo $this->escape($group); ?></strong></caption>
			<thead>
				<tr>
					<th scope="col" style="width:26%"><?php echo JText::_('COM_ADMIN_THIRD_PARTY_NAME'); ?></th>
					<th scope="col" style="width:10%"><?php echo JText::_('COM_ADMIN_THIRD_PARTY_VERSION'); ?></th>
					<th scope="col" style="width:10%"><?php echo JText::_('COM_ADMIN_THIRD_PARTY_DATE'); ?></th>
					<th scope="col" class="hidden-phone" style="width:12%"><?php echo JText::_('COM_ADMIN_THIRD_PARTY_LICENSE'); ?></th>
					<th scope="col" style="width:13%"><?php echo JText::_('COM_ADMIN_THIRD_PARTY_USED_BY'); ?></th>
					<th scope="col" class="hidden-phone"><?php echo JText::_('COM_ADMIN_THIRD_PARTY_LOCATION'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($entries as $entry) : ?>
					<tr<?php echo !empty($entry['isLatest']) ? ' class="success"' : ''; ?>>
						<td>
							<a href="<?php echo $this->escape($entry['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo $this->escape($entry['name']); ?></a>
							<?php if (!empty($entry['note'])) : ?>
								<br /><small class="muted"><?php echo $this->escape($entry['note']); ?></small>
							<?php endif; ?>
						</td>
						<?php
                        // Versions in bold, green when current (the latest release, or the latest of a series still maintained)
                        $current = !empty($entry['isLatest']) || !empty($entry['series']);
				    $version = '<strong' . ($current ? ' class="text-success"' : '') . '>' . ($entry['version'] !== '' ? $this->escape($entry['version']) : '&#8212;') . '</strong>';
				    $title   = '';

				    if (!empty($entry['series'])) {
				        $title = JText::sprintf('COM_ADMIN_THIRD_PARTY_SERIES', $entry['series'], $entry['latest'], $entry['checked']);
				    } elseif (empty($entry['isLatest']) && !empty($entry['latest'])) {
				        $title = JText::sprintf('COM_ADMIN_THIRD_PARTY_LATEST', $entry['latest'], $entry['checked'])
				            . (!empty($entry['seriesLatest']) ? ' ' . JText::sprintf('COM_ADMIN_THIRD_PARTY_SERIES_LATEST', $entry['seriesLatest']) : '');
				    }
				    ?>
						<td><?php echo $title !== '' ? '<span class="hasTooltip" title="' . $this->escape($title) . '">' . $version . '</span>' : $version; ?></td>
						<?php if ($entry['date'] !== '' && !empty($entry['dateSource'])) : ?>
							<td><span class="hasTooltip" title="<?php echo $this->escape(JText::sprintf('COM_ADMIN_THIRD_PARTY_DATE_SOURCE', $entry['dateSource'])); ?>"><?php echo $this->escape($entry['date']); ?></span></td>
						<?php else : ?>
							<td><?php echo $entry['date'] !== '' ? $this->escape($entry['date']) : '&#8212;'; ?></td>
						<?php endif; ?>
						<td class="hidden-phone"><?php echo $this->escape($entry['license']); ?></td>
						<td><?php echo $this->escape($this->getModel()->getThirdPartyUse($entry)); ?></td>
						<td class="hidden-phone"><code><?php echo $this->escape($entry['path']); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
</fieldset>
