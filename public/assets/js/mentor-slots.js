(function () {
    'use strict';

    var buttons = document.querySelectorAll('[data-start]');
    var form = document.getElementById('book-form');

    if (!form) {
        return;
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            buttons.forEach(function (b) { b.classList.remove('selected'); });
            btn.classList.add('selected');

            document.getElementById('book-start').value = btn.getAttribute('data-start');
            document.getElementById('book-end').value = btn.getAttribute('data-end');
            document.getElementById('selected-label').textContent = btn.getAttribute('data-label');
            form.hidden = false;
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
})();
