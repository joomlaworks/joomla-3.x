<?php

/**
 * PHP CS Fixer configuration: PSR-12 for the project's own PHP (maintainer's decision, 3.18), third-party code left as
 * its upstream ships it so it can still be compared and updated. Run from the repository's root:
 *
 *     php-cs-fixer fix --config=docs/php-cs-fixer.dist.php
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

$finder = PhpCsFixer\Finder::create()
    ->in(dirname(__DIR__))
    ->name('*.php')
    ->ignoreVCSIgnored(true)
    ->exclude([
        'libraries/vendor',
        'libraries/fof',
        'libraries/idna_convert',
        'libraries/phpass',
        'libraries/php-encryption',
        'plugins/captcha/iconcaptcha/lib',
    ])
    // Akeeba Kickstart (Joomla Update's extraction script)
    ->notPath('administrator/components/com_joomlaupdate/restore.php');

return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true])
    ->setFinder($finder)
    ->setUsingCache(false)
    ->setUnsupportedPhpVersionAllowed(true);
