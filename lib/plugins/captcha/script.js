jQuery(() => {
    const wrappers = document.querySelectorAll('.plugin__captcha_wrapper');

    wrappers.forEach((wrap) => {
        /**
         * Autofill and hide the whole CAPTCHA stuff in the simple JS mode
         */
        const code = wrap.querySelector('.plugin__captcha_code');
        if (code) {
            const box = wrap.querySelector('input[type=text]');
            if (box) {
                box.value = code.textContent.replace(/([^A-Z])+/g, '');
            }
            wrap.style.display = 'none';
        }

        /**
         * Add a HTML5 player for the audio version of the CAPTCHA
         */
        const audiolink = wrap.querySelector('a.audiolink');
        if (audiolink) {
            const audio = document.createElement('audio');
            audio.src = audiolink.getAttribute('href');
            wrap.appendChild(audio);
            audiolink.addEventListener('click', (e) => {
                audio.play();
                e.preventDefault();
                e.stopPropagation();
            });
        }
    });
});
