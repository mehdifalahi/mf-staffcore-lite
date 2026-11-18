(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="positions"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';

    var formModalEl = document.getElementById('staffcore-position-form-modal');
    var deleteModalEl = document.getElementById('staffcore-position-delete-modal');
    var tableBody = root.querySelector('[data-role="positions-table"] tbody');
    var form = root.querySelector('[data-role="position-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var deleteError = root.querySelector('[data-role="delete-error"]');
    var deleteNameEl = root.querySelector('[data-role="delete-name"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');

    var ui = window.mfStaffCoreUi || null;
    var bulkDelete = ui && ui.initBulkDelete ? ui.initBulkDelete(root) : null;
    var formModal = formModalEl && ui ? ui.modal(formModalEl) : null;
    var deleteModal = deleteModalEl && ui ? ui.modal(deleteModalEl) : null;

    var pendingDeleteId = null;

    var FIELD_NAMES = [
        'title',
        'code',
        'department_id',
        'level',
        'min_salary',
        'max_salary',
        'description',
        'status',
    ];

    var LEVEL_LABELS = {
        ENTRY: 'Entry',
        JUNIOR: 'Junior',
        MID: 'Mid',
        SENIOR: 'Senior',
        LEAD: 'Lead',
        MANAGER: 'Manager',
        DIRECTOR: 'Director',
        EXECUTIVE: 'Executive',
    };

    function post(action, data) {
        var body = new FormData();
        body.append('action', action);
        body.append('nonce', nonce);

        Object.keys(data).forEach(function (key) {
            if (data[key] !== undefined && data[key] !== null) {
                body.append(key, data[key]);
            }
        });

        return fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body,
        }).then(function (response) {
            return response.json();
        });
    }

    function showToast(message, type) {
        if (!toastContainer || !ui) {
            return;
        }

        var toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-bg-' + (type === 'error' ? 'danger' : 'success') + ' border-0';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body"></div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>';

        toastEl.querySelector('.toast-body').textContent = message;
        toastContainer.appendChild(toastEl);

        var toast = ui.toast(toastEl, { delay: 4000 });
        if (!toast) {
            return;
        }
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function clearFormError() {
        if (formError) {
            formError.classList.add('d-none');
            formError.textContent = '';
        }
        if (ui && form) {
            ui.resetFormValidation(form);
        }
    }

    function showFormError(message) {
        if (!formError) {
            return;
        }
        formError.textContent = message;
        formError.classList.remove('d-none');
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function displayValue(value) {
        return value ? escapeHtml(value) : '—';
    }

    function statusBadge(status) {
        if (status === 'ACTIVE') {
            return '<span class="badge bg-success-lt">Active</span>';
        }
        return '<span class="badge bg-secondary-lt">Inactive</span>';
    }

    function levelLabel(level) {
        return escapeHtml(LEVEL_LABELS[level] || level || '—');
    }

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }
        var num = parseFloat(value);
        if (isNaN(num)) {
            return escapeHtml(value);
        }
        return escapeHtml(num.toFixed(2));
    }

    function salaryRange(min, max) {
        var minStr = formatMoney(min);
        var maxStr = formatMoney(max);
        if (!minStr && !maxStr) {
            return '—';
        }
        if (minStr && maxStr) {
            return minStr + ' – ' + maxStr;
        }
        return minStr || maxStr;
    }

    function removeEmptyRow() {
        var emptyRow = tableBody.querySelector('[data-role="empty-row"]');
        if (emptyRow) {
            emptyRow.remove();
        }
    }

    function buildRow(position) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-position-id', String(position.id));
        tr.dataset.position = JSON.stringify(position);

        var html = '';

        if (bulkDelete && bulkDelete.enabled) {
            html += bulkDelete.checkboxCellHtml(position.id);
        }

        html +=
            '<td class="text-secondary">' +
            escapeHtml(position.code) +
            '</td>' +
            '<td class="fw-medium">' +
            escapeHtml(position.title) +
            '</td>' +
            '<td>' +
            displayValue(position.department_name) +
            '</td>' +
            '<td class="text-secondary">' +
            levelLabel(position.level) +
            '</td>' +
            '<td class="text-secondary">' +
            salaryRange(position.min_salary, position.max_salary) +
            '</td>' +
            '<td>' +
            statusBadge(position.status) +
            '</td>' +
            '<td>' +
            '<div class="btn-list flex-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-edit" data-id="' +
            position.id +
            '" title="Edit"><i class="ti ti-edit" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline btn-danger" data-action="open-delete" data-id="' +
            position.id +
            '" data-name="' +
            escapeHtml(position.title) +
            '" title="Delete"><i class="ti ti-trash" aria-hidden="true"></i></button>' +
            '</div>' +
            '</td>';

        tr.innerHTML = html;

        return tr;
    }

    function upsertRow(position) {
        removeEmptyRow();

        var existing = tableBody.querySelector('tr[data-position-id="' + position.id + '"]');
        var row = buildRow(position);

        if (existing) {
            existing.replaceWith(row);
        } else {
            tableBody.appendChild(row);
        }
    }

    function setFormModalTitle(text) {
        var title = formModalEl ? formModalEl.querySelector('.modal-title') : null;
        if (title) {
            title.textContent = text;
        }
    }

    function emptyPosition() {
        return {
            id: '',
            title: '',
            code: '',
            department_id: '',
            level: 'MID',
            min_salary: '',
            max_salary: '',
            description: '',
            status: 'ACTIVE',
        };
    }

    function fillPositionForm(position) {
        form.querySelector('[name="id"]').value = position.id ? String(position.id) : '';

        if (ui && ui.fillFormFields) {
            ui.fillFormFields(form, position, FIELD_NAMES);
            return;
        }

        FIELD_NAMES.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el) {
                return;
            }
            el.value = position[name] == null ? '' : String(position[name]);
        });
    }

    function focusFirstField() {
        setTimeout(function () {
            var el = form.querySelector('[name="title"]');
            if (el) {
                el.focus();
            }
        }, 200);
    }

    function showFormModal() {
        if (formModal) {
            formModal.show();
        }
        focusFirstField();
    }

    function getPositionFromRow(id) {
        var row = tableBody.querySelector('tr[data-position-id="' + id + '"]');
        if (!row || !row.dataset.position) {
            return null;
        }

        try {
            return JSON.parse(row.dataset.position);
        } catch (e) {
            return null;
        }
    }

    function applyClientValidationRules() {
        var titleEl = form.querySelector('[name="title"]');
        if (titleEl) {
            titleEl.setCustomValidity(titleEl.value.trim() === '' ? 'Position title is required.' : '');
        }

        var codeEl = form.querySelector('[name="code"]');
        if (codeEl) {
            codeEl.setCustomValidity(codeEl.value.trim() === '' ? 'Position code is required.' : '');
        }

        var departmentEl = form.querySelector('[name="department_id"]');
        if (departmentEl) {
            departmentEl.setCustomValidity(departmentEl.value === '' ? 'Department is required.' : '');
        }

        var levelEl = form.querySelector('[name="level"]');
        if (levelEl) {
            levelEl.setCustomValidity(levelEl.value === '' ? 'Level is required.' : '');
        }

        var minEl = form.querySelector('[name="min_salary"]');
        var maxEl = form.querySelector('[name="max_salary"]');
        if (minEl && maxEl && minEl.value !== '' && maxEl.value !== '') {
            if (parseFloat(minEl.value) > parseFloat(maxEl.value)) {
                maxEl.setCustomValidity('Maximum salary must be greater than or equal to minimum salary.');
            } else {
                maxEl.setCustomValidity('');
            }
        } else if (maxEl) {
            maxEl.setCustomValidity('');
        }
    }

    function collectFormPayload() {
        var payload = {};

        FIELD_NAMES.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el) {
                return;
            }
            payload[name] = el.value.trim();
        });

        var id = form.querySelector('[name="id"]').value;
        if (id) {
            payload.id = id;
        }

        return payload;
    }

    function openCreateModal() {
        clearFormError();
        fillPositionForm(emptyPosition());
        setFormModalTitle('Add position');
        showFormModal();
    }

    function openEditModal(id) {
        clearFormError();

        var position = getPositionFromRow(id);
        if (!position) {
            showToast('Could not load position from the table.', 'error');
            return;
        }

        fillPositionForm(position);
        setFormModalTitle('Edit position');
        showFormModal();
    }

    function setSubmitLoading(loading) {
        var btn = root.querySelector('[data-action="submit-form"]');
        var spinner = root.querySelector('[data-role="submit-spinner"]');
        if (btn) {
            btn.disabled = loading;
        }
        if (spinner) {
            spinner.classList.toggle('d-none', !loading);
        }
    }

    function submitForm() {
        clearFormError();

        if (ui && !ui.validateForm(form, { custom: [applyClientValidationRules] })) {
            return;
        }

        setSubmitLoading(true);

        var payload = collectFormPayload();

        post('mf_staffcore_position_save', payload)
            .then(function (result) {
                setSubmitLoading(false);

                if (!result.success) {
                    showFormError(result.data && result.data.message ? result.data.message : 'Save failed.');
                    return;
                }

                if (formModal) {
                    formModal.hide();
                }

                if (!payload.id) {
                    window.location.reload();
                    return;
                }

                upsertRow(result.data.position);
                showToast(result.data.message || 'Saved.', 'success');
            })
            .catch(function () {
                setSubmitLoading(false);
                showFormError('Network error. Please try again.');
            });
    }

    function openDeleteModal(id, name) {
        pendingDeleteId = id;
        if (deleteNameEl) {
            deleteNameEl.textContent = name;
        }
        if (deleteError) {
            deleteError.classList.add('d-none');
            deleteError.textContent = '';
        }
        if (deleteModal) {
            deleteModal.show();
        }
    }

    function setDeleteLoading(loading) {
        var btn = root.querySelector('[data-action="confirm-delete"]');
        var spinner = root.querySelector('[data-role="delete-spinner"]');
        if (btn) {
            btn.disabled = loading;
        }
        if (spinner) {
            spinner.classList.toggle('d-none', !loading);
        }
    }

    function confirmDelete() {
        if (!pendingDeleteId) {
            return;
        }

        setDeleteLoading(true);

        post('mf_staffcore_position_delete', { id: pendingDeleteId })
            .then(function (result) {
                setDeleteLoading(false);

                if (!result.success) {
                    if (deleteError) {
                        deleteError.textContent =
                            result.data && result.data.message ? result.data.message : 'Delete failed.';
                        deleteError.classList.remove('d-none');
                    }
                    return;
                }

                if (deleteModal) {
                    deleteModal.hide();
                }

                pendingDeleteId = null;
                window.location.reload();
            })
            .catch(function () {
                setDeleteLoading(false);
                if (deleteError) {
                    deleteError.textContent = 'Network error. Please try again.';
                    deleteError.classList.remove('d-none');
                }
            });
    }

    root.addEventListener('click', function (event) {
        var target = event.target.closest('[data-action]');
        if (!target) {
            return;
        }

        var action = target.getAttribute('data-action');

        if (action === 'open-create') {
            openCreateModal();
            return;
        }

        if (action === 'open-edit') {
            openEditModal(target.getAttribute('data-id'));
            return;
        }

        if (action === 'open-delete') {
            openDeleteModal(target.getAttribute('data-id'), target.getAttribute('data-name') || '');
            return;
        }

        if (action === 'submit-form') {
            submitForm();
            return;
        }

        if (action === 'confirm-delete') {
            confirmDelete();
        }
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitForm();
        });
    }
})();
