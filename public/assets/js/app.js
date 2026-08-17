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

/**
 * User menu dropdown: toggles on click, closes on outside click.
 */
document.addEventListener('DOMContentLoaded', function () {
    var menu = document.querySelector('.user-menu');
    if (!menu) return;

    var toggle = menu.querySelector('.user-avatar');

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.classList.toggle('open');
    });

    document.addEventListener('click', function () {
        menu.classList.remove('open');
    });
});