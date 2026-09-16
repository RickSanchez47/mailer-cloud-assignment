(function () {
    var root = document.getElementById('public-form-root');
    var accountApiKey = window.__ACCOUNT_API_KEY__;
    var slug = window.__SLUG__;

    fetch('/api/public/' + accountApiKey + '/forms/' + slug)
        .then(function (res) {
            if (!res.ok) throw new Error('not-found');
            return res.json();
        })
        .then(function (body) {
            buildForm(body.schema);
        })
        .catch(function () {
            root.innerHTML = '<p>This form is not available.</p>';
        });

    function el(tag, attrs, text) {
        var node = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) {
            var v = attrs[k];
            if (v === true) node.setAttribute(k, '');
            else if (v !== false && v !== null && v !== undefined) node.setAttribute(k, v);
        });
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function cssEscape(str) {
        return String(str).replace(/([^a-zA-Z0-9_-])/g, '\\$1');
    }

    function needsOptionsGroup(type) {
        return type === 'radio' || type === 'multi-select' || type === 'checkbox';
    }

    function buildInput(field) {
        var common = { id: field.key, name: field.key };
        if (field.required) common.required = true;

        switch (field.type) {
            case 'text':
                return el('input', Object.assign({ type: 'text' }, common));
            case 'email':
                return el('input', Object.assign({ type: 'email' }, common));
            case 'number':
                return el('input', Object.assign({ type: 'number' }, common));
            case 'date':
                return el('input', Object.assign({ type: 'date' }, common));
            case 'select': {
                var select = el('select', common);
                select.appendChild(el('option', { value: '' }, '– choose –'));
                (field.options || []).forEach(function (opt) {
                    select.appendChild(el('option', { value: opt }, opt));
                });
                return select;
            }
            case 'radio': {
                var group = document.createElement('div');
                (field.options || []).forEach(function (opt) {
                    var optLabel = document.createElement('label');
                    optLabel.className = 'inline-option';
                    var input = el('input', { type: 'radio', name: field.key, value: opt, required: !!field.required });
                    optLabel.appendChild(input);
                    optLabel.appendChild(document.createTextNode(' ' + opt));
                    group.appendChild(optLabel);
                });
                return group;
            }
            case 'multi-select':
            case 'checkbox': {
                var group2 = document.createElement('div');
                (field.options || []).forEach(function (opt) {
                    var optLabel = document.createElement('label');
                    optLabel.className = 'inline-option';
                    var input = el('input', { type: 'checkbox', name: field.key + '[]', value: opt });
                    optLabel.appendChild(input);
                    optLabel.appendChild(document.createTextNode(' ' + opt));
                    group2.appendChild(optLabel);
                });
                return group2;
            }
            default:
                return el('input', Object.assign({ type: 'text' }, common));
        }
    }

    function buildFieldWrapper(field) {
        var wrapper = document.createElement('div');
        wrapper.className = 'field-wrapper';
        wrapper.id = 'field-' + field.key;

        var label = document.createElement('label');
        label.textContent = field.label + (field.required ? ' *' : '');
        wrapper.appendChild(label);
        wrapper.appendChild(buildInput(field));

        if (field.help_text) {
            var help = document.createElement('small');
            help.textContent = field.help_text;
            wrapper.appendChild(help);
        }

        return wrapper;
    }

    function applyConditionalVisibility(schema, form) {
        (schema.fields || []).forEach(function (field) {
            if (!field.visible_if) return;
            var wrapper = document.getElementById('field-' + field.key);
            if (!wrapper) return;

            var dependsOn = form.querySelector('[name="' + cssEscape(field.visible_if.field) + '"]');
            var currentValue = dependsOn ? dependsOn.value : null;
            var visible = currentValue === field.visible_if.equals;

            wrapper.style.display = visible ? '' : 'none';
            wrapper.querySelectorAll('input, select').forEach(function (input) {
                input.disabled = !visible;
            });
        });
    }

    function summarizeErrors(body) {
        if (body && body.errors) {
            var messages = [];
            Object.keys(body.errors).forEach(function (k) {
                messages = messages.concat(body.errors[k]);
            });
            return messages.join(' ');
        }
        return (body && body.message) || 'Something went wrong.';
    }

    function buildForm(schema) {
        var form = document.createElement('form');
        form.noValidate = true;

        var honeypot = document.createElement('div');
        honeypot.className = 'hp-field';
        honeypot.innerHTML = '<label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>';
        form.appendChild(honeypot);

        (schema.fields || []).forEach(function (field) {
            form.appendChild(buildFieldWrapper(field));
        });

        var submitBtn = el('button', { type: 'submit' }, 'Submit');
        form.appendChild(submitBtn);

        var message = el('p', { id: 'form-message' });
        form.appendChild(message);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            message.textContent = '';
            message.className = '';

            var data = {};
            (schema.fields || []).forEach(function (field) {
                var wrapper = document.getElementById('field-' + field.key);
                if (wrapper && wrapper.style.display === 'none') return;

                if (field.type === 'multi-select' || field.type === 'checkbox') {
                    var checked = Array.prototype.slice
                        .call(form.querySelectorAll('[name="' + cssEscape(field.key) + '[]"]:checked'))
                        .map(function (x) { return x.value; });
                    data[field.key] = checked;
                } else if (field.type === 'radio') {
                    var picked = form.querySelector('[name="' + cssEscape(field.key) + '"]:checked');
                    data[field.key] = picked ? picked.value : '';
                } else {
                    var el2 = form.querySelector('[name="' + cssEscape(field.key) + '"]');
                    data[field.key] = el2 ? el2.value : '';
                }
            });

            var website = form.querySelector('[name="website"]').value;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting…';

            fetch('/api/public/' + accountApiKey + '/forms/' + slug + '/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ data: data, website: website }),
            })
                .then(function (res) {
                    return res.json().then(function (body) { return { status: res.status, body: body }; });
                })
                .then(function (result) {
                    if (result.status === 201) {
                        form.reset();
                        message.textContent = 'Thanks — your response was recorded.';
                        message.className = 'success';
                    } else if (result.status === 429) {
                        message.textContent = 'Too many submissions right now — try again in a minute.';
                        message.className = 'error';
                    } else {
                        message.textContent = summarizeErrors(result.body);
                        message.className = 'error';
                    }
                })
                .catch(function () {
                    message.textContent = 'Network error — please try again.';
                    message.className = 'error';
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                });
        });

        root.innerHTML = '';
        root.appendChild(form);

        applyConditionalVisibility(schema, form);
        form.addEventListener('input', function () { applyConditionalVisibility(schema, form); });
    }
})();
