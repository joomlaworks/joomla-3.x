<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');

/**
 * Bulk import of redirects: one "source|target" per line, each checked as the form checks an entry.
 *
 * @since  3.18.0
 */
class RedirectModelImport extends JModelLegacy
{
    /**
     * The separator between source and target, from the component's options.
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function getSeparator()
    {
        $separator = trim((string) JComponentHelper::getParams('com_redirect')->get('separator', '|'));

        return $separator === '' ? '|' : $separator;
    }

    /**
     * Imports the lines; blank lines are skipped silently, every other line is imported or reported with its reason.
     *
     * @param   string   $text   The lines
     * @param   integer  $state  The imported redirects' state: 1 enabled, 0 disabled
     *
     * @return  array  imported => count, skipped => array of {number, line, reason}
     *
     * @since   3.18.0
     */
    public function import($text, $state = 1)
    {
        $separator = $this->getSeparator();
        $state     = (int) $state === 1 ? 1 : 0;
        $report    = array('imported' => 0, 'skipped' => array());
        $seen      = array();

        JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_redirect/tables');

        foreach (preg_split('/\R/', (string) $text) as $index => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $skip = function ($reason) use (&$report, $index, $line) {
                $report['skipped'][] = array('number' => $index + 1, 'line' => $line, 'reason' => $reason);
            };

            if (strpos($line, $separator) === false) {
                $skip(JText::sprintf('COM_REDIRECT_IMPORT_NO_SEPARATOR', $separator));
                continue;
            }

            list($source, $target) = array_map('trim', explode($separator, $line, 2));
            $normalised            = RedirectHelper::normaliseSource($source);

            if ($normalised !== false && $normalised !== '' && isset($seen[RedirectHelper::hash($normalised)])) {
                $skip(JText::_('COM_REDIRECT_IMPORT_DUPLICATE_LINE'));
                continue;
            }

            $table = JTable::getInstance('Link', 'RedirectTable');
            $table->bind(array('old_url' => $source, 'new_url' => $target, 'published' => $state, 'header' => 0, 'comment' => ''));

            if (!$table->check() || !$table->store()) {
                $skip($table->getError());
                continue;
            }

            $seen[$table->old_url_hash] = true;
            $report['imported']++;
        }

        return $report;
    }
}
