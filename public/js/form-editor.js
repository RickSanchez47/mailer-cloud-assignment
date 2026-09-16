(function () {
    var FIELD_TYPES = ['text', 'email', 'number', 'date', 'select', 'multi-select', 'radio', 'checkbox'];

    var fields = (window.__INITIAL_FIELDS__ && window.__INITIAL_FIELDS__.length) ? window.__INITIAL_FIELDS__ : [];

    var editor = document.getElementById('field-editor');
    var addBtn = document.getElementById('add-field-btn');
    var statusEl = document.getElementById('status-message');

    function needsOptions(type) {
        return ['select', 'multi-select', 'radio', 'checkbox'].indexOf(type) !== -1;
    }

    function escapeAttr(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function render() {
        editor.innerHTML = '';
        fields.forEach(function (field, index) {
            var row = document.createElement('div');
            row.style.cssText = 'border:1px solid var(--border); border-radius:6px; padding:14px; margin-bottom:12px;';

            var typeOptions = FIELD_TYPES.map(function (t) {
                return '<option value="' + t + '"' + (field.type === t ? ' selected' : '') + '>' + t + '</option>';
            }).join('');

            var visibilityOptions = fields
                .filter(function (f, i) { return i !== index && f.key; })
                .map(function (f) {
                    var selected = field.visible_if && field.visible_if.field === f.key ? ' selected' : '';
                    return '<option value="' + escapeAttr(f.key) + '"' + selected + '>' + escapeAttr(f.key) + '</option>';
                })
                .join('');

            row.innerHTML =
                '<div style="display:grid; grid-template-columns: 1fr 1fr 140px auto; gap:10px; align-items:end;">' +
                    '<div><label>Key</label><input type="text" class="field-key" data-idx="' + index + '" data-prop="key" value="' + escapeAttr(field.key) + '" placeholder="email"></div>' +
                    '<div><label>Label</label><input type="text" data-idx="' + index + '" data-prop="label" value="' + escapeAttr(field.label) + '" placeholder="Email address"></div>' +
                    '<div><label>Type</label><select data-idx="' + index + '" data-prop="type">' + typeOptions + '</select></div>' +
                    '<div><label style="visibility:hidden;">.</label><label style="display:flex; align-items:center; gap:6px; font-weight:400;"><input type="checkbox" data-idx="' + index + '" data-prop="required" style="width:auto;"' + (field.required ? ' checked' : '') + '> Required</label></div>' +
                '</div>' +
                '<div style="margin-top:10px;"><label>Help text</label><input type="text" data-idx="' + index + '" data-prop="help_text" value="' + escapeAttr(field.help_text) + '" placeholder="Optional hint shown under the field"></div>' +
                (needsOptions(field.type)
                    ? '<div style="margin-top:10px;"><label>Options (comma separated)</label><input type="text" data-idx="' + index + '" data-prop="options" value="' + escapeAttr((field.options || []).join(', ')) + '" placeholder="Ad, Friend, Search"></div>'
                    : '') +
                '<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px;">' +
                    '<div><label>Only show if</label><select data-idx="' + index + '" data-prop="visible_if_field"><option value="">Always visible</option>' + visibilityOptions + '</select></div>' +
                    '<div><label>equals</label><input type="text" data-idx="' + index + '" data-prop="visible_if_equals" value="' + escapeAttr(field.visible_if ? field.visible_if.equals : '') + '" placeholder="value"' + (!field.visible_if ? ' disabled' : '') + '></div>' +
                '</div>' +
                '<button type="button" class="danger-link remove-field-btn" data-idx="' + index + '" style="margin-top:10px;">Remove field</button>';

            editor.appendChild(row);
        });
    }

    addBtn.addEventListener('click', function () {
        fields.push({ key: '', label: '', type: 'text', required: false, help_text: '', options: [] });
        render();
    });

    editor.addEventListener('input', function (e) {
        var idx = e.target.getAttribute('data-idx');
        var prop = e.target.getAttribute('data-prop');
        if (idx === null || !prop) return;
        var field = fields[idx];

        if (prop === 'required') {
            field.required = e.target.checked;
        } else if (prop === 'options') {
            field.options = e.target.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        } else if (prop === 'visible_if_field') {
            if (!e.target.value) {
                delete field.visible_if;
            } else {
                field.visible_if = { field: e.target.value, equals: (field.visible_if && field.visible_if.equals) || '' };
            }
            render();
            return;
        } else if (prop === 'visible_if_equals') {
            if (field.visible_if) field.visible_if.equals = e.target.value;
        } else if (prop === 'type') {
            field.type = e.target.value;
            render();
            return;
        } else {
            field[prop] = e.target.value;
        }
    });

    editor.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-field-btn')) {
            fields.splice(Number(e.target.getAttribute('data-idx')), 1);
            render();
        }
    });

    function collectSchema() {
        return {
            fields: fields.map(function (f) {
                return {
                    key: f.key,
                    label: f.label,
                    type: f.type,
                    required: !!f.required,
                    help_text: f.help_text || '',
                    options: f.options || [],
                    visible_if: f.visible_if || null,
                };
            }),
        };
    }

    function setStatus(text) {
        statusEl.textContent = text;
    }

    function saveDraft() {
        var schema = collectSchema();
        setStatus('Saving…');

        var request;
        if (window.__IS_NEW__) {
            var name = document.getElementById('form-name').value.trim();
            if (!name) {
                setStatus('Give the form a name first.');
                return Promise.resolve(null);
            }
            request = fetch('/builder/forms', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.__CSRF__ },
                body: JSON.stringify({ name: name, schema: schema }),
            });
        } else {
            request = fetch('/builder/forms/' + window.__FORM_ID__ + '/draft', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.__CSRF__ },
                body: JSON.stringify({ schema: schema }),
            });
        }

        return request
            .then(function (response) {
                return response.json().then(function (body) { return { response: response, body: body }; });
            })
            .then(function (result) {
                if (!result.response.ok) {
                    setStatus('Could not save: ' + (result.body.message || result.response.status));
                    return null;
                }
                if (window.__IS_NEW__) {
                    window.location.href = '/builder/forms/' + result.body.id + '/edit';
                    return null;
                }
                setStatus('Draft saved.');
                return result.body;
            })
            .catch(function () {
                setStatus('Network error saving draft.');
                return null;
            });
    }

    document.getElementById('save-draft-btn').addEventListener('click', saveDraft);

    var publishBtn = document.getElementById('publish-btn');
    publishBtn.addEventListener('click', function () {
        if (window.__IS_NEW__) {
            setStatus('Save the draft first, then publish from the edit page.');
            return;
        }
        saveDraft().then(function (saved) {
            if (!saved && !window.__FORM_ID__) return;
            setStatus('Publishing…');
            fetch('/builder/forms/' + window.__FORM_ID__ + '/publish', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': window.__CSRF__ },
            })
                .then(function (response) {
                    return response.json().then(function (body) { return { response: response, body: body }; });
                })
                .then(function (result) {
                    if (result.response.ok) {
                        setStatus('Published.');
                        window.location.reload();
                    } else {
                        setStatus('Could not publish: ' + (result.body.message || result.response.status));
                    }
                })
                .catch(function () {
                    setStatus('Network error publishing.');
                });
        });
    });

    render();
})();
