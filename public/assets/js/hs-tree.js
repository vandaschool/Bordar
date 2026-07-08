(function () {
    'use strict';

    function initHsTree(root) {
        var search = root.querySelector('[data-hs-search]');
        var chapters = root.querySelectorAll('[data-hs-chapter]');

        if (search) {
            search.addEventListener('input', function () {
                var term = search.value.trim().toLowerCase();

                chapters.forEach(function (chapter) {
                    var items = chapter.querySelectorAll('[data-hs-item]');
                    var chapterVisible = term === '' || chapter.textContent.toLowerCase().indexOf(term) !== -1;
                    var anyChildVisible = false;

                    items.forEach(function (item) {
                        var visible = term === '' || item.textContent.toLowerCase().indexOf(term) !== -1;
                        item.style.display = visible ? '' : 'none';
                        if (visible) {
                            anyChildVisible = true;
                        }
                    });

                    chapter.style.display = (chapterVisible || anyChildVisible) ? '' : 'none';

                    if (term !== '' && anyChildVisible) {
                        chapter.open = true;
                    }
                });
            });
        }

        root.querySelectorAll('[data-hs-chapter-toggle]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var chapter = checkbox.closest('[data-hs-chapter]');
                chapter.querySelectorAll('[data-hs-child]').forEach(function (child) {
                    child.checked = checkbox.checked;
                });
            });
        });
    }

    document.querySelectorAll('[data-hs-tree]').forEach(initHsTree);
})();
