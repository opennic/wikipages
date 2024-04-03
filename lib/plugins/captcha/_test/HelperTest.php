<?php

namespace dokuwiki\plugin\captcha\test;

use DokuWikiTest;

/**
 * @group plugin_captcha
 * @group plugins
 */
class HelperTest extends DokuWikiTest
{

    protected $pluginsEnabled = array('captcha');

    public function testConfig()
    {
        global $conf;
        $conf['plugin']['captcha']['lettercount'] = 20;

        $helper = new \helper_plugin_captcha();

        // generateCAPTCHA generates a maximum of 16 chars
        $code = $helper->_generateCAPTCHA("fixed", 0);
        $this->assertEquals(16, strlen($code));
    }

    public function testDecrypt()
    {
        $helper = new \helper_plugin_captcha();

        $rand = "12345";
        $secret = $helper->encrypt($rand);
        $this->assertNotSame(false, $secret);
        $this->assertSame($rand, $helper->decrypt($secret));

        $this->assertFalse($helper->decrypt(''));
        $this->assertFalse($helper->decrypt('X'));
    }

    public function testCheck()
    {

        global $INPUT, $ID;

        $helper = new \helper_plugin_captcha();

        $INPUT->set($this->getInaccessibleProperty($helper, 'field_hp'), '');
        $INPUT->set($this->getInaccessibleProperty($helper, 'field_in'), 'X');
        $INPUT->set($this->getInaccessibleProperty($helper, 'field_sec'), '');

        $this->assertFalse($helper->check(false));
        $INPUT->set($this->getInaccessibleProperty($helper, 'field_sec'), 'X');
        $this->assertFalse($helper->check(false));

        // create the captcha and store the cookie
        $rand = 0;
        $code = $helper->_generateCAPTCHA($helper->_fixedIdent(), $rand);

        $this->callInaccessibleMethod($helper, 'storeCaptchaCookie', [$helper->_fixedIdent(), $rand]);

        // check with missing secrect -> fail
        $INPUT->set($this->getInaccessibleProperty($helper, 'field_in'), $code);
        $this->assertFalse($helper->check(false));

        // set secret -> success
        $INPUT->set($this->getInaccessibleProperty($helper, 'field_sec'), $helper->encrypt($rand));
        $this->assertTrue($helper->check(false));

        // try again, cookie is gone -> fail
        $this->assertFalse($helper->check(true));

        // set the cookie but change the ID -> fail
        $this->callInaccessibleMethod($helper, 'storeCaptchaCookie', [$helper->_fixedIdent(), $rand]);
        $ID = 'test:fail';
        $this->assertFalse($helper->check(false));
    }

    public function testGenerate()
    {
        $helper = new \helper_plugin_captcha();

        $rand = 0;
        $code = $helper->_generateCAPTCHA($helper->_fixedIdent(), $rand);
        $newcode = $helper->_generateCAPTCHA($helper->_fixedIdent() . 'X', $rand);
        $this->assertNotEquals($newcode, $code);
        $newcode = $helper->_generateCAPTCHA($helper->_fixedIdent(), $rand + 0.1);
        $this->assertNotEquals($newcode, $code);
    }

    public function testCleanup()
    {
        // we need a complete fresh environment:
        $this->setUpBeforeClass();

        global $conf;
        $path = $conf['tmpdir'] . '/captcha/';
        $today = "$path/" . date('Y-m-d');

        $helper = new \helper_plugin_captcha();

        // nothing at all
        $dirs = glob("$path/*");
        $this->assertEquals(array(), $dirs);

        // store a cookie
        $this->callInaccessibleMethod($helper, 'storeCaptchaCookie', ['test', 0]);

        // nothing but today's data
        $dirs = glob("$path/*");
        $this->assertEquals(array($today), $dirs);

        // add some fake cookies
        io_saveFile("$path/2017-01-01/foo.cookie", '');
        io_saveFile("$path/2017-01-02/foo.cookie", '');
        io_saveFile("$path/2017-01-03/foo.cookie", '');
        io_saveFile("$path/2017-01-04/foo.cookie", '');

        // all directories there
        $dirs = glob("$path/*");
        $this->assertEquals(
            array(
                "$path/2017-01-01",
                "$path/2017-01-02",
                "$path/2017-01-03",
                "$path/2017-01-04",
                $today,
            ),
            $dirs
        );

        // clean up
        $helper->_cleanCaptchaCookies();

        // nothing but today's data
        $dirs = glob("$path/*");
        $this->assertEquals(array($today), $dirs);
    }
}
