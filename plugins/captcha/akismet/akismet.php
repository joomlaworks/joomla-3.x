<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Captcha\CaptchaHttp;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\IpHelper;

/**
 * Akismet (by Automattic): no challenge; what a form sends (name, email address, subject, message) is checked with
 * Akismet's comment-check API (CaptchaHttp: cURL) and spam is refused.
 *
 * @since  3.18.0
 */
class PlgCaptchaAkismet extends JPlugin
{
    /**
     * Akismet's comment-check address
     *
     * @var    string
     * @since  3.18.0
     */
    public const CHECK_URL = 'https://rest.akismet.com/1.1/comment-check';

    /**
     * The form fields read, in order of preference (the jform fields of Joomla's forms, then plain ones)
     *
     * @var    array
     * @since  3.18.0
     */
    public const FIELDS = array(
        'comment_author'       => array('contact_name', 'name', 'sender', 'username'),
        'comment_author_email' => array('contact_email', 'email1', 'email', 'emailfrom', 'from'),
        'comment_author_url'   => array('website', 'url'),
        'subject'              => array('contact_subject', 'subject', 'title'),
        'comment_content'      => array('contact_message', 'articletext', 'message', 'text', 'body', 'description'),
    );

    /**
     * @var    boolean
     * @since  3.18.0
     */
    protected $autoloadLanguage = true;

    /**
     * Reports the privacy related capabilities for this plugin to site administrators.
     *
     * @return  array
     *
     * @since   3.18.0
     */
    public function onPrivacyCollectAdminCapabilities()
    {
        $this->loadLanguage();

        return array(
            JText::_('PLG_CAPTCHA_AKISMET') => array(
                JText::_('PLG_CAPTCHA_AKISMET_PRIVACY_CAPABILITY'),
            )
        );
    }

    /**
     * Nothing to load: Akismet only checks what's sent.
     *
     * @param   string  $id  The id of the field.
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function onInit($id = 'dynamic_akismet_1')
    {
        return true;
    }

    /**
     * @param   string  $name   The name of the field. Not used.
     * @param   string  $id     The id of the field.
     * @param   string  $class  The class of the field.
     *
     * @return  string  Nothing: the form shows no challenge
     *
     * @since   3.18.0
     */
    public function onDisplay($name = null, $id = 'dynamic_akismet_1', $class = '')
    {
        return '';
    }

    /**
     * Hides the field's label: there's nothing to see.
     *
     * @param   \Joomla\CMS\Form\Field\CaptchaField  $field    Captcha field instance
     * @param   \SimpleXMLElement                    $element  XML form definition
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function onSetupField(\Joomla\CMS\Form\Field\CaptchaField $field, \SimpleXMLElement $element)
    {
        $element['hiddenLabel'] = true;
    }

    /**
     * Asks Akismet whether what the form sent is spam.
     *
     * @param   string  $code  Not used
     *
     * @return  boolean  True when it isn't
     *
     * @since   3.18.0
     * @throws  \RuntimeException
     */
    public function onCheckAnswer($code = null)
    {
        $key = trim((string) $this->params->get('api_key', ''));

        if ($key === '') {
            return $this->unanswered('no API key is set');
        }

        $answer = CaptchaHttp::post(self::CHECK_URL, $this->getCheckData($key));

        if ($answer === null || $answer['code'] !== 200) {
            return $this->unanswered('Akismet could not be reached');
        }

        $body = trim($answer['body']);

        if ($body === 'false') {
            return true;
        }

        if ($body !== 'true') {
            $help = isset($answer['headers']['x-akismet-debug-help']) ? ' (' . $answer['headers']['x-akismet-debug-help'] . ')' : '';

            return $this->unanswered('Akismet answered "' . substr($body, 0, 40) . '"' . $help);
        }

        // Spam. With "blatant spam only", the rest goes through
        $blatant = isset($answer['headers']['x-akismet-pro-tip']) && $answer['headers']['x-akismet-pro-tip'] === 'discard';

        if ($this->params->get('block', 'all') === 'blatant' && !$blatant) {
            return true;
        }

        throw new \RuntimeException(JText::_('PLG_CAPTCHA_AKISMET_ERROR_SPAM'));
    }

    /**
     * What Akismet is told: the site, the visitor, the kind of form and what it sent.
     *
     * @param   string  $key  The API key
     *
     * @return  array
     *
     * @since   3.18.0
     */
    private function getCheckData($key)
    {
        $app   = JFactory::getApplication();
        $input = $app->input;
        $user  = JFactory::getUser();
        $form  = $input->post->get('jform', array(), 'array');
        $plain = $input->post->getArray();
        $data  = array(
            'api_key'      => $key,
            'blog'         => Uri::root(),
            'user_ip'      => (string) IpHelper::getIp(),
            'user_agent'   => $input->server->getString('HTTP_USER_AGENT', ''),
            'referrer'     => $input->server->getString('HTTP_REFERER', ''),
            'permalink'    => Uri::getInstance()->toString(),
            'comment_type' => $this->getCommentType($input->getCmd('option'), $input->getCmd('view'), $input->getCmd('task')),
            'blog_lang'    => str_replace('-', '_', JFactory::getLanguage()->getTag()),
            'blog_charset' => 'UTF-8',
        );

        foreach (self::FIELDS as $name => $fields) {
            foreach ($fields as $field) {
                $value = isset($form[$field]) ? $form[$field] : (isset($plain[$field]) ? $plain[$field] : null);

                if (is_string($value) && trim($value) !== '') {
                    $data[$name] = trim($value);
                    break;
                }
            }
        }

        // Akismet reads one text: the subject leads it
        if (isset($data['subject'])) {
            $data['comment_content'] = $data['subject'] . (isset($data['comment_content']) ? "\n\n" . $data['comment_content'] : '');
            unset($data['subject']);
        }

        // Site administrators are never spammers, as Akismet's own plugins tell it
        if (!$user->guest && $user->authorise('core.admin')) {
            $data['user_role'] = 'administrator';
        }

        if ($this->params->get('test', 0)) {
            $data['is_test'] = 1;
        }

        return $data;
    }

    /**
     * @param   string  $option  The component
     * @param   string  $view    The view
     * @param   string  $task    The task
     *
     * @return  string  The kind of form, as Akismet names it
     *
     * @since   3.18.0
     */
    private function getCommentType($option, $view, $task)
    {
        switch ($option) {
            case 'com_contact':
                return 'contact-form';
            case 'com_users':
                return strpos((string) $task, 'registration') === 0 || $view === 'registration' ? 'signup' : 'message';
            case 'com_content':
                return 'blog-post';
            case 'com_mailto':
                return 'message';
            default:
                return 'comment';
        }
    }

    /**
     * Akismet couldn't judge: the form goes through, or is refused when the site chose that, and the reason is logged.
     *
     * @param   string  $reason  Why, for the log
     *
     * @return  boolean  True when the form goes through
     *
     * @since   3.18.0
     * @throws  \RuntimeException
     */
    private function unanswered($reason)
    {
        try {
            Log::addLogger(array('text_file' => 'akismet.php'), Log::WARNING, array('akismet'));
            Log::add('Not checked: ' . $reason, Log::WARNING, 'akismet');
        } catch (\Exception $e) {
            // An unwritable log doesn't change the answer
        }

        if ($this->params->get('unanswered', 'allow') === 'refuse') {
            throw new \RuntimeException(JText::_('PLG_CAPTCHA_AKISMET_ERROR_UNAVAILABLE'));
        }

        return true;
    }
}
