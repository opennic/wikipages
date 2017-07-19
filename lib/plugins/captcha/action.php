<?php
/**
 * CAPTCHA antispam plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class action_plugin_captcha extends DokuWiki_Action_Plugin {

    /**
     * register the eventhandlers
     */
    public function register(Doku_Event_Handler $controller) {
        // check CAPTCHA success
        $controller->register_hook(
            'ACTION_ACT_PREPROCESS',
            'BEFORE',
            $this,
            'handle_captcha_input',
            array()
        );

        // inject in edit form
        $controller->register_hook(
            'HTML_EDITFORM_OUTPUT',
            'BEFORE',
            $this,
            'handle_form_output',
            array()
        );

        // inject in user registration
        $controller->register_hook(
            'HTML_REGISTERFORM_OUTPUT',
            'BEFORE',
            $this,
            'handle_form_output',
            array()
        );

        // inject in password reset
        $controller->register_hook(
            'HTML_RESENDPWDFORM_OUTPUT',
            'BEFORE',
            $this,
            'handle_form_output',
            array()
        );

        if($this->getConf('loginprotect')) {
            // inject in login form
            $controller->register_hook(
                'HTML_LOGINFORM_OUTPUT',
                'BEFORE',
                $this,
                'handle_form_output',
                array()
            );
            // check on login
            $controller->register_hook(
                'AUTH_LOGIN_CHECK',
                'BEFORE',
                $this,
                'handle_login',
                array()
            );
        }

        // clean up captcha cookies
        $controller->register_hook(
            'INDEXER_TASKS_RUN',
            'AFTER',
            $this,
            'handle_indexer',
            array()
        );
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
    protected function needs_checking($act) {
        global $INPUT;

        switch($act) {
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
    protected function abort_action($act) {
        global $INPUT;

        switch($act) {
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
     * Handles CAPTCHA check in login
     *
     * Logins happen very early in the DokuWiki lifecycle, so we have to intercept them
     * in their own event.
     *
     * @param Doku_Event $event
     * @param $param
     */
    public function handle_login(Doku_Event $event, $param) {
        global $INPUT;
        if(!$this->getConf('loginprotect')) return; // no protection wanted
        if(!$INPUT->bool('u')) return; // this login was not triggered by a form

        // we need to have $ID set for the captcha check
        global $ID;
        $ID = getID();

        /** @var helper_plugin_captcha $helper */
        $helper = plugin_load('helper', 'captcha');
        if(!$helper->check()) {
            $event->data['silent'] = true; // we have our own message
            $event->result = false; // login fail
            $event->preventDefault();
            $event->stopPropagation();
        }
    }

    /**
     * Intercept all actions and check for CAPTCHA first.
     */
    public function handle_captcha_input(Doku_Event $event, $param) {
        $act = act_clean($event->data);
        if(!$this->needs_checking($act)) return;

        // do nothing if logged in user and no CAPTCHA required
        if(!$this->getConf('forusers') && $_SERVER['REMOTE_USER']) {
            return;
        }

        // check captcha
        /** @var helper_plugin_captcha $helper */
        $helper = plugin_load('helper', 'captcha');
        if(!$helper->check()) {
            $event->data = $this->abort_action($act);
        }
    }

    /**
     * Inject the CAPTCHA in a DokuForm
     */
    public function handle_form_output(Doku_Event $event, $param) {
        // get position of submit button
        $pos = $event->data->findElementByAttribute('type', 'submit');
        if(!$pos) return; // no button -> source view mode

        // do nothing if logged in user and no CAPTCHA required
        if(!$this->getConf('forusers') && $_SERVER['REMOTE_USER']) {
            return;
        }

        // get the CAPTCHA
        /** @var helper_plugin_captcha $helper */
        $helper = plugin_load('helper', 'captcha');
        $out = $helper->getHTML();

        // new wiki - insert before the submit button
        $event->data->insertElement($pos, $out);
    }

    /**
     * Clean cookies once per day
     */
    public function handle_indexer(Doku_Event $event, $param) {
        $lastrun = getCacheName('captcha', '.captcha');
        $last = @filemtime($lastrun);
        if(time() - $last < 24 * 60 * 60) return;

        /** @var helper_plugin_captcha $helper */
        $helper = plugin_load('helper', 'captcha');
        $helper->_cleanCaptchaCookies();
        touch($lastrun);

        $event->preventDefault();
        $event->stopPropagation();
    }
}

