
jQuery(function () {
    var $wrap = jQuery('#plugin__captcha_wrapper');
    if(!$wrap.length) return;

    /**
     * Autofill and hide the whole CAPTCHA stuff in the simple JS mode
     */
    var $code = jQuery('#plugin__captcha_code');
    if ($code.length) {
        var $box = $wrap.find('input[type=text]');
        $box.first().val($code.text().replace(/([^A-Z])+/g, ''));
        $wrap.hide();
    }

    /**
     * Add a HTML5 player for the audio version of the CAPTCHA
     */
    var $audiolink = $wrap.find('a');
    if($audiolink.length) {
        var audio = document.createElement('audio');
        if(audio) {
            audio.src = $audiolink.attr('href');
            $wrap.append(audio);
            $audiolink.click(function (e) {
                audio.play();
                e.preventDefault();
                e.stopPropagation();
            });
        }
    }
});
