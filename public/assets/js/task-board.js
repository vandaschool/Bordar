(function () {
    'use strict';

    var board = document.querySelector('[data-task-board]');
    if (!board) {
        return;
    }

    var csrf = board.getAttribute('data-csrf');

    // --- Mobile column tabs ---
    var tabs = document.querySelectorAll('[data-board-tab]');
    var columns = document.querySelectorAll('[data-board-column]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-board-tab');

            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');

            columns.forEach(function (col) {
                col.classList.toggle('active', col.getAttribute('data-board-column') === target);
            });
        });
    });

    // --- Drag and drop between columns ---
    var dragged = null;

    board.querySelectorAll('[data-task-card]').forEach(function (card) {
        card.addEventListener('dragstart', function () {
            dragged = card;
            card.classList.add('dragging');
        });
        card.addEventListener('dragend', function () {
            card.classList.remove('dragging');
        });
    });

    columns.forEach(function (col) {
        var body = col.querySelector('[data-column-body]');
        if (!body) {
            return;
        }

        body.addEventListener('dragover', function (e) {
            e.preventDefault();
        });

        body.addEventListener('drop', function (e) {
            e.preventDefault();
            if (!dragged) {
                return;
            }

            var newStatus = col.getAttribute('data-board-column');
            var taskId = dragged.getAttribute('data-task-id');
            var previousParent = dragged.parentElement;

            body.appendChild(dragged);

            fetch('/tasks/' + taskId + '/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_csrf=' + encodeURIComponent(csrf) + '&status=' + encodeURIComponent(newStatus),
            })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (!json.success) {
                        previousParent.appendChild(dragged);
                    }
                })
                .catch(function () {
                    previousParent.appendChild(dragged);
                });
        });
    });
})();
