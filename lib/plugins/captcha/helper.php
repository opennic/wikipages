<?php

use dokuwiki\Extension\Plugin;
use dokuwiki\plugin\captcha\FileCookie;
use dokuwiki\Utf8\PhpString;

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

/**
 * Class helper_plugin_captcha
 */
class helper_plugin_captcha extends Plugin
{
    protected $field_in = 'plugin__captcha';
    protected $field_sec = 'plugin__captcha_secret';
    protected $field_hp = 'plugin__captcha_honeypot';

    // region Public API

    /**
     * Constructor. Initializes field names
     */
    public function __construct()
    {
        $this->field_in = md5($this->fixedIdent() . $this->field_in);
        $this->field_sec = md5($this->fixedIdent() . $this->field_sec);
        $this->field_hp = md5($this->fixedIdent() . $this->field_hp);
    }

    /**
     * Check if the CAPTCHA should be used. Always check this before using the methods below.
     *
     * @return bool true when the CAPTCHA should be used
     */
    public function isEnabled()
    {
        global $INPUT;
        if (!$this->getConf('forusers') && $INPUT->server->str('REMOTE_USER')) return false;
        return true;
    }

    /**
     * Returns the HTML to display the CAPTCHA with the chosen method
     *
     * @return string The HTML to display the CAPTCHA
     */
    public function getHTML()
    {
        global $ID;

        $rand = (float)(random_int(0, 10000)) / 10000;
        $cookie = new FileCookie($this->fixedIdent(), $rand);
        $cookie->set();

        if ($this->getConf('mode') == 'math') {
            $code = $this->generateMath($this->fixedIdent(), $rand);
            $code = $code[0];
            $text = $this->getLang('fillmath');
        } elseif ($this->getConf('mode') == 'question') {
            $code = ''; // not used
            $text = $this->getConf('question');
        } else {
            $code = $this->generateCaptchaCode($this->fixedIdent(), $rand);
            $text = $this->getLang('fillcaptcha');
        }
        $secret = $this->encrypt($rand);

        $txtlen = $this->getConf('lettercount');

        $out = '';
        $out .= '<div id="plugin__captcha_wrapper">';
        $out .= '<input type="hidden" name="' . $this->field_sec . '" value="' . hsc($secret) . '" />';
        $out .= '<label for="plugin__captcha">' . $text . '</label> ';

        switch ($this->getConf('mode')) {
            case 'math':
            case 'text':
                $out .= $this->obfuscateText($code);
                break;
            case 'js':
                $out .= sprintf('<span id="plugin__captcha_code">%s</span>', $this->obfuscateText($code));
                break;
            case 'svg':
                $out .= $this->htmlSvg($code);
                break;
            case 'svgaudio':
                $out .= $this->htmlSvg($code);
                $out .= $this->htmlAudioLink($secret, $ID);
                break;
            case 'image':
                $out .= $this->htmlImage($ID, $secret);
                break;
            case 'audio':
                $out .= $this->htmlImage($ID, $secret);
                $out .= $this->htmlAudioLink($secret, $ID);
                break;
            case 'figlet':
                $out .= $this->htmlFiglet($code);
                break;
        }
        $out .= ' <input type="text" size="' . $txtlen . '" name="' . $this->field_in . '" class="edit" /> ';

        // add honeypot field
        $out .= sprintf(
            '<label class="no">%s<input type="text" name="%s" /></label>',
            $this->getLang('honeypot'),
            $this->field_hp
        );
        $out .= '</div>';
        return $out;
    }

    /**
     * Checks if the CAPTCHA was solved correctly
     *
     * @param bool $msg when true, an error will be signalled through the msg() method
     * @return bool true when the answer was correct, otherwise false
     */
    public function check($msg = true)
    {
        global $INPUT;

        $field_sec = $INPUT->str($this->field_sec);
        $field_in = $INPUT->str($this->field_in);
        $field_hp = $INPUT->str($this->field_hp);

        // reconstruct captcha from provided $field_sec
        $rand = $this->decrypt($field_sec);

        if ($this->getConf('mode') == 'math') {
            $code = $this->generateMath($this->fixedIdent(), $rand);
            $code = $code[1];
        } elseif ($this->getConf('mode') == 'question') {
            $code = $this->getConf('answer');
        } else {
            $code = $this->generateCaptchaCode($this->fixedIdent(), $rand);
        }

        // compare values
        if (
            !$field_sec ||
            !$field_in ||
            $rand === false ||
            PhpString::strtolower($field_in) != PhpString::strtolower($code) ||
            trim($field_hp) !== '' ||
            !(new FileCookie($this->fixedIdent(), $rand))->check()
        ) {
            if ($msg) msg($this->getLang('testfailed'), -1);
            return false;
        }
        return true;
    }

    // endregion

    // region Captcha Generation methods

    /**
     * Build a semi-secret fixed string identifying the current page and user
     *
     * This string is always the same for the current user when editing the same
     * page revision, but only for one day. Editing a page before midnight and saving
     * after midnight will result in a failed CAPTCHA once, but makes sure it can
     * not be reused which is especially important for the registration form where the
     * $ID usually won't change.
     *
     * @return string
     */
    public function fixedIdent()
    {
        global $ID;
        $lm = @filemtime(wikiFN($ID));
        $td = date('Y-m-d');
        $ip = clientIP();
        $salt = auth_cookiesalt();

        return sha1(implode("\n", [$ID, $lm, $td, $ip, $salt]));
    }

    /**
     * Generate a magic code based on the given data
     *
     * This "magic" code represents the given fixed identifier (see fixedIdent()) and the given
     * random number. It is used to generate the actual CAPTCHA code.
     *
     * @param $ident string the fixed part, any string
     * @param $rand  float  some random number between 0 and 1
     * @return string
     */
    protected function generateMagicCode($ident, $rand)
    {
        $ident = hexdec(substr(md5($ident), 5, 5)); // use part of the md5 to generate an int
        $rand *= 0xFFFFF; // bitmask from the random number
        $comb = (int)$rand ^ $ident; // combine both values
        return md5($comb);
    }

    /**
     * Generates a char string based on the given data
     *
     * The string is pseudo random based on a fixed identifier (see fixedIdent()) and a random number.
     *
     * @param $ident string the fixed part, any string
     * @param $rand  float  some random number between 0 and 1
     * @return string
     */
    public function generateCaptchaCode($ident, $rand)
    {
        $numbers = $this->generateMagicCode($ident, $rand);

        // now create the letters
        $code = '';
        $lettercount = $this->getConf('lettercount') * 2;
        if ($lettercount > strlen($numbers)) $lettercount = strlen($numbers);
        for ($i = 0; $i < $lettercount; $i += 2) {
            $code .= chr(floor(hexdec($numbers[$i] . $numbers[$i + 1]) / 10) + 65);
        }

        return $code;
    }

    /**
     * Create a mathematical task and its result
     *
     * @param $ident string the fixed part, any string
     * @param $rand  float  some random number between 0 and 1
     * @return array [task, result]
     */
    protected function generateMath($ident, $rand)
    {
        $numbers = $this->generateMagicCode($ident, $rand);

        // first letter is the operator (+/-)
        $op = (hexdec($numbers[0]) > 8) ? -1 : 1;
        $num = [hexdec($numbers[1] . $numbers[2]), hexdec($numbers[3])];

        // we only want positive results
        if (($op < 0) && ($num[0] < $num[1])) rsort($num);

        // prepare result and task text
        $res = $num[0] + ($num[1] * $op);
        $task = $num[0] . (($op < 0) ? '-' : '+') . $num[1] . '= ';

        return [$task, $res];
    }

    // endregion

    // region Output Builders

    /**
     * Create a CAPTCHA image
     *
     * @param string $text the letters to display
     * @return string The image data
     */
    public function imageCaptcha($text)
    {
        $w = $this->getConf('width');
        $h = $this->getConf('height');

        $fonts = glob(__DIR__ . '/fonts/*.ttf');

        // create a white image
        $img = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);

        // add some lines as background noise
        for ($i = 0; $i < 30; $i++) {
            $color = imagecolorallocate($img, random_int(100, 250), random_int(100, 250), random_int(100, 250));
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $color);
        }

        // draw the letters
        $txtlen = strlen($text);
        for ($i = 0; $i < $txtlen; $i++) {
            $font = $fonts[array_rand($fonts)];
            $color = imagecolorallocate($img, random_int(0, 100), random_int(0, 100), random_int(0, 100));
            $size = random_int(floor($h / 1.8), floor($h * 0.7));
            $angle = random_int(-35, 35);

            $x = ($w * 0.05) + $i * floor($w * 0.9 / $txtlen);
            $cheight = $size + ($size * 0.5);
            $y = floor($h / 2 + $cheight / 3.8);

            imagettftext($img, $size, $angle, $x, $y, $color, $font, $text[$i]);
        }

        ob_start();
        imagepng($img);
        $image = ob_get_clean();
        imagedestroy($img);
        return $image;
    }

    /**
     * Generate an audio captcha
     *
     * @param string $text
     * @return string The joined wav files
     */
    public function audioCaptcha($text)
    {
        global $conf;

        $lc = __DIR__ . '/lang/' . $conf['lang'] . '/audio/';
        $en = __DIR__ . '/lang/en/audio/';

        $wavs = [];

        $text = strtolower($text);
        $txtlen = strlen($text);
        for ($i = 0; $i < $txtlen; $i++) {
            $char = $text[$i];
            $file = $lc . $char . '.wav';
            if (!@file_exists($file)) $file = $en . $char . '.wav';
            $wavs[] = $file;
        }

        return $this->joinwavs($wavs);
    }

    /**
     * Create an SVG of the given text
     *
     * @param string $text
     * @return string
     */
    public function svgCaptcha($text)
    {
        require_once(__DIR__ . '/EasySVG.php');

        $fonts = glob(__DIR__ . '/fonts/*.svg');

        $x = 0; // where we start to draw
        $y = 100; // our max height

        $svg = new EasySVG();

        // draw the letters
        $txtlen = strlen($text);
        for ($i = 0; $i < $txtlen; $i++) {
            $char = $text[$i];
            $size = random_int($y / 2, $y - $y * 0.1); // 50-90%
            $svg->setFontSVG($fonts[array_rand($fonts)]);

            $svg->setFontSize($size);
            $svg->setLetterSpacing(round(random_int(1, 4) / 10, 2)); // 0.1 - 0.4
            $svg->addText($char, $x, random_int(0, round($y - $size))); // random up and down

            [$w] = $svg->textDimensions($char);
            $x += $w;
        }

        $svg->addAttribute('width', $x . 'px');
        $svg->addAttribute('height', $y . 'px');
        $svg->addAttribute('viewbox', "0 0 $x $y");
        return $svg->asXML();
    }

    /**
     * Inline SVG showing the given code
     *
     * @param string $code
     * @return string
     */
    protected function htmlSvg($code)
    {
        return sprintf(
            '<span class="svg" style="width:%spx; height:%spx">%s</span>',
            $this->getConf('width'),
            $this->getConf('height'),
            $this->svgCaptcha($code)
        );
    }

    /**
     * HTML for an img tag for the image captcha
     *
     * @param string $ID the page ID this is displayed on
     * @param string $secret the encrypted random number
     * @return string
     */
    protected function htmlImage($ID, $secret)
    {
        $img = DOKU_BASE . 'lib/plugins/captcha/img.php';
        $param = buildURLparams([
            'secret' => $secret,
            'id' => $ID,
        ]);

        return sprintf(
            '<img src="%s?%s" width="%d" height="%d" alt="" />',
            $img,
            $param,
            $this->getConf('width'),
            $this->getConf('height')
        );
    }

    /**
     * HTML for a link to the audio captcha
     *
     * @param string $secret the encrypted random number
     * @param string $ID the page ID this is displayed on
     * @return string
     */
    protected function htmlAudioLink($secret, $ID)
    {

        $url = DOKU_BASE . 'lib/plugins/captcha/wav.php';
        $param = buildURLparams([
            'secret' => $secret,
            'id' => $ID,
        ]);

        $icon = inlineSVG(__DIR__ . '/ear-hearing.svg');

        return sprintf(
            '<a href="%s?%s" class="JSnocheck audiolink" title="%s" style="height: %spx">%s</a>',
            $url,
            $param,
            $this->getLang('soundlink'),
            $this->getConf('height'),
            $icon
        );
    }

    /**
     * The HTML to show a figlet captcha
     *
     * @param string $code the code to display
     * @return string
     */
    protected function htmlFiglet($code)
    {
        require_once(__DIR__ . '/figlet.php');
        $figlet = new phpFiglet();
        if ($figlet->loadfont(__DIR__ . '/figlet.flf')) {
            return '<pre>' . rtrim($figlet->fetch($code)) . '</pre>';
        } else {
            msg('Failed to load figlet.flf font file. CAPTCHA broken', -1);
        }
        return 'FAIL';
    }

    // endregion

    // region Utilities

    /**
     * Encrypt the given string with the cookie salt
     *
     * @param string $data
     * @return string
     */
    public function encrypt($data)
    {
        $data = auth_encrypt($data, auth_cookiesalt());
        return base64_encode($data);
    }

    /**
     * Decrypt the given string with the cookie salt
     *
     * @param string $data
     * @return string
     */
    public function decrypt($data)
    {
        $data = base64_decode($data);
        if ($data === false || $data === '') return false;

        return auth_decrypt($data, auth_cookiesalt());
    }

    /**
     * Adds random space characters within the given text
     *
     * Keeps subsequent numbers without spaces (for math problem)
     *
     * @param $text
     * @return string
     */
    protected function obfuscateText($text)
    {
        $new = '';

        $spaces = [
            "\r",
            "\n",
            "\r\n",
            ' ',
            "\xC2\xA0",
            // \u00A0    NO-BREAK SPACE
            "\xE2\x80\x80",
            // \u2000    EN QUAD
            "\xE2\x80\x81",
            // \u2001    EM QUAD
            "\xE2\x80\x82",
            // \u2002    EN SPACE
            //         "\xE2\x80\x83", // \u2003    EM SPACE
            "\xE2\x80\x84",
            // \u2004    THREE-PER-EM SPACE
            "\xE2\x80\x85",
            // \u2005    FOUR-PER-EM SPACE
            "\xE2\x80\x86",
            // \u2006    SIX-PER-EM SPACE
            "\xE2\x80\x87",
            // \u2007    FIGURE SPACE
            "\xE2\x80\x88",
            // \u2008    PUNCTUATION SPACE
            "\xE2\x80\x89",
            // \u2009    THIN SPACE
            "\xE2\x80\x8A",
            // \u200A    HAIR SPACE
            "\xE2\x80\xAF",
            // \u202F    NARROW NO-BREAK SPACE
            "\xE2\x81\x9F",
            // \u205F    MEDIUM MATHEMATICAL SPACE
            "\xE1\xA0\x8E\r\n",
            // \u180E    MONGOLIAN VOWEL SEPARATOR
            "\xE2\x80\x8B\r\n",
            // \u200B    ZERO WIDTH SPACE
            "\xEF\xBB\xBF\r\n",
        ];

        $len = strlen($text);
        for ($i = 0; $i < $len - 1; $i++) {
            $new .= $text[$i];

            if (!is_numeric($text[$i + 1])) {
                $new .= $spaces[array_rand($spaces)];
            }
        }
        $new .= $text[$len - 1];
        return $new;
    }


    /**
     * Join multiple wav files
     *
     * All wave files need to have the same format and need to be uncompressed.
     * The headers of the last file will be used (with recalculated datasize
     * of course)
     *
     * @link http://ccrma.stanford.edu/CCRMA/Courses/422/projects/WaveFormat/
     * @link http://www.thescripts.com/forum/thread3770.html
     */
    protected function joinwavs($wavs)
    {
        $fields = implode(
            '/',
            [
                'H8ChunkID',
                'VChunkSize',
                'H8Format',
                'H8Subchunk1ID',
                'VSubchunk1Size',
                'vAudioFormat',
                'vNumChannels',
                'VSampleRate',
                'VByteRate',
                'vBlockAlign',
                'vBitsPerSample'
            ]
        );

        $data = '';
        foreach ($wavs as $wav) {
            $fp = fopen($wav, 'rb');
            $header = fread($fp, 36);
            $info = unpack($fields, $header);

            // read optional extra stuff
            if ($info['Subchunk1Size'] > 16) {
                $header .= fread($fp, ($info['Subchunk1Size'] - 16));
            }

            // read SubChunk2ID
            $header .= fread($fp, 4);

            // read Subchunk2Size
            $size = unpack('vsize', fread($fp, 4));
            $size = $size['size'];

            // read data
            $data .= fread($fp, $size);
        }

        return $header . pack('V', strlen($data)) . $data;
    }

    // endregion
}
