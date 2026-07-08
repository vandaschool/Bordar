(function () {
    'use strict';

    var root = document.querySelector('[data-guided-tour]');
    if (!root) {
        return;
    }

    var steps = JSON.parse(root.getAttribute('data-steps') || '[]');
    var completeUrl = root.getAttribute('data-complete-url');
    var csrf = root.getAttribute('data-csrf');
    var index = 0;

    var overlay = document.createElement('div');
    overlay.className = 'tour-overlay';
    overlay.innerHTML =
        '<div class="tour-box">' +
        '<h3 data-tour-title></h3>' +
        '<p data-tour-text></p>' +
        '<div class="tour-actions">' +
        '<span class="tour-progress" data-tour-progress></span>' +
        '<div>' +
        '<button type="button" class="btn btn-secondary" data-tour-skip style="width:auto">رد کردن</button>' +
        '<button type="button" class="btn" data-tour-next style="width:auto">بعدی</button>' +
        '</div>' +
        '</div>' +
        '</div>';
    document.body.appendChild(overlay);

    function render() {
        var step = steps[index];
        overlay.querySelector('[data-tour-title]').textContent = step.title;
        overlay.querySelector('[data-tour-text]').textContent = step.text;
        overlay.querySelector('[data-tour-progress]').textContent = (index + 1) + ' / ' + steps.length;
        overlay.querySelector('[data-tour-next]').textContent = index === steps.length - 1 ? 'پایان' : 'بعدی';
    }

    function finish() {
        overlay.remove();

        fetch(completeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf=' + encodeURIComponent(csrf),
        });
    }

    overlay.querySelector('[data-tour-skip]').addEventListener('click', finish);
    overlay.querySelector('[data-tour-next]').addEventListener('click', function () {
        index += 1;
        if (index >= steps.length) {
            finish();
        } else {
            render();
        }
    });

    if (steps.length > 0) {
        render();
    } else {
        overlay.remove();
    }
})();
