(function () {
    var image = document.getElementById('captchaImage');
    var button = document.getElementById('refreshCaptcha');

    if (!image || !button) {
        return;
    }

    var baseSrc = image.getAttribute('data-captcha-src');

    var refresh = function () {
        if (!baseSrc) {
            return;
        }
        image.setAttribute('src', baseSrc + '?t=' + Date.now());
    };

    button.addEventListener('click', refresh);
})();
