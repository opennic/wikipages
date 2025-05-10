<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Form\Form;
use dokuwiki\plugin\captcha\FileCookie;
use dokuwiki\plugin\captcha\IpCounter;

/**
 * CAPTCHA antispam plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */
class action_plugin_captcha extends ActionPlugin
{
    /**
     * register the eventhandlers
     */
    public function register(EventHandler $controller)
    {
        // check CAPTCHA success
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleCaptchaInput', []);

        // inject in edit form
        $controller->register_hook('FORM_EDIT_OUTPUT', 'BEFORE', $this, 'handleFormOutput', []);

        // inject in user registration
        $controller->register_hook('FORM_REGISTER_OUTPUT', 'BEFORE', $this, 'handleFormOutput', []);

        // inject in password reset
        $controller->register_hook('FORM_RESENDPWD_OUTPUT', 'BEFORE', $this, 'handleFormOutput', []);

        // inject in login form
        $controller->register_hook('FORM_LOGIN_OUTPUT', 'BEFORE', $this, 'handleFormOutput', []);

        // check on login
        $controller->register_hook('AUTH_LOGIN_CHECK', 'BEFORE', $this, 'handleLogin', []);

        // clean up captcha cookies
        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'handleIndexer', []);

        // log authentication failures
        if ((int)$this->getConf('loginprotect') > 1) {
            $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleAuth', []);
        }
    }

    /**
     * Check if the current mode should be handled by CAPTCHA
     *
     * Note: checking needs to be done when a form has been submitted, not when the form
     * is shown for the first time. Except for the editing process this is not determined
     * by $act alone but needs to inspect other input variables.
     *
     * @param string $act cleaned action mode
     * @return bool
     */
    protected function needsChecking($act)
    {
        global $INPUT;

        switch ($act) {
            case 'save':
                return true;
            case 'register':
            case 'resendpwd':
                return $INPUT->bool('save');
            case 'login':
                // we do not handle this here, but in handle_login()
            default:
                return false;
        }
    }

    /**
     * Aborts the given mode
     *
     * Aborting depends on the mode. It might unset certain input parameters or simply switch
     * the mode to something else (giving as return which needs to be passed back to the
     * ACTION_ACT_PREPROCESS event)
     *
     * @param string $act cleaned action mode
     * @return string the new mode to use
     */
    protected function abortAction($act)
    {
        global $INPUT;

        switch ($act) {
            case 'save':
                return 'preview';
            case 'register':
            case 'resendpwd':
                $INPUT->post->set('save', false);
                return $act;
            case 'login':
                // we do not handle this here, but in handle_login()
            default:
                return $act;
        }
    }

    /**
     * Should a login CAPTCHA be used?
     *
     * @return bool
     */
    protected function protectLogin()
    {
        $config = (int)$this->getConf('loginprotect');
        if ($config < 1) return false; // not wanted
        if ($config === 1) return true; // always wanted
        $count = (new IpCounter())->get();
        return $count > 2; // only after 3 failed attempts
    }

    /**
     * Handles CAPTCHA check in login
     *
     * Logins happen very early in the DokuWiki lifecycle, so we have to intercept them
     * in their own event.
     *
     * @param Event $event
     * @param $param
     */
    public function handleLogin(Event $event, $param)
    {
        global $INPUT;
        if (!$this->protectLogin()) return; // no protection wanted
        if (!$INPUT->bool('u')) return; // this login was not triggered by a form

        // we need to have $ID set for the captcha check
        global $ID;
        $ID = getID();

        /** @var helper_plugin_captcha $helper */
        $helper = plugin_load('helper', 'captcha');
        if (!$helper->check()) {
            $event->data['silent'] = true; // we have our own message
            $event->result = false; // login fail
            $event->preventDefault();
            $event->stopPropagation();
        }
    }

    /**
     * Intercept all actions and check for CAPTCHA first.
     */
    public function handleCaptchaInput(Event $event, $param)
    {
        global $INPUT;

        $act = act_clean($event->data);
        if (!$this->needsChecking($act)) return;

        // do nothing if logged in user and no CAPTCHA required
        if (!$this->getConf('forusers') && $INPUT->server->str('REMOTE_USER')) {
            return;
        }

        // check captcha
        /** @var helper_plugin_captcha $helper */
        $helper = plugin_load('helper', 'captcha');
        if (!$helper->check()) {
            $event->data = $this->abortAction($act);
        }
    }

    /**
     * Inject the CAPTCHA in a \dokuwiki\Form\Form
     */
    public function handleFormOutput(Event $event, $param)
    {
        global $INPUT;

        if ($event->name === 'FORM_LOGIN_OUTPUT' && !$this->protectLogin()) {
            // no login protection wanted
            return;
        }

        /** @var Form|\Doku_Form $form */
        $form = $event->data;

        // get position of submit button
        $pos = $form->findPositionByAttribute('type', 'submit');
        if (!$pos) return; // no button -> source view mode

        // do nothing if logged in user and no CAPTCHA required
        if (!$this->getConf('forusers') && $INPUT->server->str('REMOTE_USER')) {
            return;
        }

        // get the CAPTCHA
        /** @var helper_plugin_captcha $helper */
        $helper = plugin_load('helper', 'captcha');
        $out = $helper->getHTML();

        // insert before the submit button
        $form->addHTML($out, $pos);
    }

    /**
     * Clean cookies once per day
     */
    public function handleIndexer(Event $event, $param)
    {
        $lastrun = getCacheName('captcha', '.captcha');
        $last = @filemtime($lastrun);
        if (time() - $last < 24 * 60 * 60) return;

        FileCookie::clean();
        touch($lastrun);

        $event->preventDefault();
        $event->stopPropagation();
    }

    /**
     * Count failed login attempts
     */
    public function handleAuth(Event $event, $param)
    {
        global $INPUT;
        $act = act_clean($event->data);
        if (
            $act != 'logout' &&
            $INPUT->str('u') !== '' &&
            empty($INPUT->server->str('http_credentials')) &&
            empty($INPUT->server->str('REMOTE_USER'))
        ) {
            // This is a failed authentication attempt, count it
            (new IpCounter())->increment();
        }

        if (
            $act == 'login' &&
            !empty($INPUT->server->str('REMOTE_USER'))
        ) {
            // This is a successful login, reset the counter
            (new IpCounter())->reset();
        }
    }
}
