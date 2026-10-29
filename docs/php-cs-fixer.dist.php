<?php

/**
 * PHP CS Fixer configuration: PSR-12 for the project's own PHP (maintainer's decision, 3.18), FOF and Joomla Update's
 * restore.php (Akeeba Kickstart) included, as Joomla's own; third-party code left as its upstream ships it so it can
 * still be compared and updated. Run from the repository's root:
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
        'libraries/idna_convert',
        'libraries/phpass',
        'libraries/php-encryption',
        'plugins/captcha/iconcaptcha/lib',
    ]);

return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true])
    ->setFinder($finder)
    ->setUsingCache(false)
    ->setUnsupportedPhpVersionAllowed(true);
