(function () {
    'use strict';

    var form = document.querySelector('[data-autosave-form]');
    if (!form) {
        return;
    }

    var statusEl = document.querySelector('[data-autosave-status]');
    var url = form.getAttribute('data-autosave-url');
    var csrf = form.getAttribute('data-csrf');
    var requiredLabels = JSON.parse(form.getAttribute('data-required-labels') || '{}');
    var missingHint = document.querySelector('[data-missing-hint]');
    var missingList = document.querySelector('[data-missing-list]');
    var submitForm = document.querySelector('[data-submit-form]');
    var timer = null;

    function setStatus(text) {
        if (statusEl) {
            statusEl.textContent = text;
        }
    }

    function refreshRequiredState() {
        if (!missingHint || !submitForm) {
            return;
        }

        var missing = Object.keys(requiredLabels).filter(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            return !el || el.value.trim() === '';
        });

        if (missing.length === 0) {
            missingHint.hidden = true;
            submitForm.hidden = false;
        } else {
            missingList.textContent = missing.map(function (name) { return requiredLabels[name]; }).join('، ');
            missingHint.hidden = false;
            submitForm.hidden = true;
        }
    }

    function collect() {
        var fields = {};
        form.querySelectorAll('[data-field]').forEach(function (el) {
            fields[el.name] = el.value;
        });

        return fields;
    }

    function save() {
        setStatus('در حال ذخیره...');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(collect()),
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json.success) {
                    var now = new Date();
                    setStatus('ذخیره شد ساعت ' + now.toLocaleTimeString('fa-IR'));
                } else {
                    setStatus(json.message || 'ذخیره ناموفق بود.');
                }
            })
            .catch(function () {
                setStatus('خطا در ارتباط با سرور.');
            });
    }

    form.querySelectorAll('[data-field]').forEach(function (el) {
        el.addEventListener('input', function () {
            setStatus('در حال تایپ...');
            refreshRequiredState();
            clearTimeout(timer);
            timer = setTimeout(save, 1200);
        });
    });

    refreshRequiredState();

    window.addEventListener('beforeunload', function () {
        if (timer) {
            clearTimeout(timer);
            save();
        }
    });
})();
