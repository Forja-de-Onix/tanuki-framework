/**
 * Tanuki Framework — Base script
 *
 * Auto-dismisses flash messages (.flash) after a few seconds.
 */
document.addEventListener('DOMContentLoaded', function () {
    var DISPLAY_TIME = 3000; // ms before the fade-out starts
    var FADE_DURATION = 600; // must match the CSS animation duration

    var flashes = document.querySelectorAll('.flash');

    flashes.forEach(function (el) {
        setTimeout(function () {
            el.classList.add('fade-out');
            setTimeout(function () {
                el.remove();
            }, FADE_DURATION);
        }, DISPLAY_TIME);
    });
});