<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use IconCaptcha\Attempts\Attempts;
use IconCaptcha\Utils;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Factory;

/**
 * IconCaptcha's wrong answers counted per IP address, in Joomla's cache: the library counted them in the visitor's session,
 * so a script starting a new session for every guess was never stopped. The address is kept as a hash with the site's
 * secret. Loaded only where IconCaptcha runs (PHP 7.4+).
 *
 * @since  3.18.0
 */
class PlgCaptchaIconcaptchaAttempts extends Attempts
{
    /**
     * @var    Cache
     * @since  3.18.0
     */
    private $cache;

    /**
     * @var    string
     * @since  3.18.0
     */
    private $key;

    /**
     * @param   mixed   $storage    IconCaptcha's storage (not used: the counts are kept in Joomla's cache)
     * @param   array   $options    The attempts' options (amount, timeout, valid)
     * @param   string  $ipAddress  The visitor's address
     *
     * @since   3.18.0
     */
    public function __construct($storage, array $options, string $ipAddress)
    {
        parent::__construct($options);

        $this->key = hash('sha256', Factory::getConfig()->get('secret') . '|' . $ipAddress);

        // Kept as long as a lockout or a count lasts, whatever the site's own caching (as Redirects keeps its flag)
        $this->cache = new Cache(array(
            'defaultgroup' => 'plg_captcha_iconcaptcha',
            'caching'      => true,
            'lifetime'     => (int) ceil(max($options['timeout'], $options['valid']) / 60) + 1,
        ));
    }

    /**
     * @return  void
     *
     * @since   3.18.0
     */
    public function increaseAttempts(): void
    {
        $count = (int) $this->getCurrentAttemptsCount() + 1;

        if ($count >= $this->options['amount']) {
            $this->issueTimeout();
        } else {
            $this->write(array('count' => $count, 'valid' => $this->getNewValidityTimestamp()));
        }
    }

    /**
     * @return  void
     *
     * @since   3.18.0
     */
    public function clearAttempts(): void
    {
        $this->cache->remove($this->key);
    }

    /**
     * @return  boolean
     *
     * @since   3.18.0
     */
    protected function issueTimeout(): bool
    {
        $timeout = $this->options['timeout'] + Utils::getCurrentTimeInSeconds();

        $this->write(array('timeout' => $timeout, 'valid' => $timeout));

        return true;
    }

    /**
     * @return  integer|null
     *
     * @since   3.18.0
     */
    protected function getActiveTimeoutTimestamp(): ?int
    {
        $data = $this->read();

        return $this->isAttemptsDataStillValid() && isset($data['timeout']) ? (int) $data['timeout'] : null;
    }

    /**
     * @return  integer|null
     *
     * @since   3.18.0
     */
    protected function getCurrentAttemptsCount(): ?int
    {
        $data = $this->read();

        return $this->isAttemptsDataStillValid() && isset($data['count']) ? (int) $data['count'] : null;
    }

    /**
     * @return  integer|null
     *
     * @since   3.18.0
     */
    protected function getAttemptsValidityTimestamp(): ?int
    {
        $data = $this->read();

        return isset($data['valid']) ? (int) $data['valid'] : null;
    }

    /**
     * @return  array
     *
     * @since   3.18.0
     */
    private function read(): array
    {
        $data = json_decode((string) $this->cache->get($this->key), true);

        return is_array($data) ? $data : array();
    }

    /**
     * @param   array  $data  What to keep
     *
     * @return  void
     *
     * @since   3.18.0
     */
    private function write(array $data): void
    {
        $this->cache->store(json_encode($data), $this->key);
    }
}
