(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="maintenance"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';

    var formModalEl = document.getElementById('staffcore-maintenance-form-modal');
    var deleteModalEl = document.getElementById('staffcore-maintenance-delete-modal');
    var tableBody = root.querySelector('[data-role="maintenance-table"] tbody');
    var form = root.querySelector('[data-role="maintenance-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var deleteError = root.querySelector('[data-role="delete-error"]');
    var deleteLabelEl = root.querySelector('[data-role="delete-label"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');

    var ui = window.mfStaffCoreUi || null;
    var bulkDelete = ui && ui.initBulkDelete ? ui.initBulkDelete(root) : null;
    var formModal = formModalEl && ui ? ui.modal(formModalEl) : null;
    var deleteModal = deleteModalEl && ui ? ui.modal(deleteModalEl) : null;

    var pendingDeleteId = null;

    var FIELD_NAMES = [
        'asset_id',
        'maintenance_date',
        'type',
        'cost',
        'performed_by_employee_id',
        'next_service_date',
        'notes',
    ];

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
        toastEl.className =
            'toast align-items-center text-bg-' + (type === 'error' ? 'danger' : 'success') + ' border-0';
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

    function formatMoney(value) {
        if (value == null || value === '') {
            return '—';
        }

        var num = parseFloat(value);

        if (isNaN(num)) {
            return '—';
        }

        return num.toFixed(2);
    }

    function typeBadge(type) {
        if (type === 'REPAIR') {
            return '<span class="badge bg-danger-lt">Repair</span>';
        }
        if (type === 'SERVICE') {
            return '<span class="badge bg-azure-lt">Service</span>';
        }
        if (type === 'INSPECTION') {
            return '<span class="badge bg-warning-lt">Inspection</span>';
        }
        return '<span class="badge bg-secondary-lt">' + escapeHtml(type) + '</span>';
    }

    function removeEmptyRow() {
        var emptyRow = tableBody.querySelector('[data-role="empty-row"]');
        if (emptyRow) {
            emptyRow.remove();
        }
    }

    function buildRow(record) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-maintenance-id', String(record.id));
        tr.dataset.maintenance = JSON.stringify(record);

        var employeeCell = displayValue(record.asset_name);
        if (record.asset_code) {
            employeeCell +=
                ' <span class="text-secondary ms-1">(' + escapeHtml(record.asset_code) + ')</span>';
        }

        var html = '';

        if (bulkDelete && bulkDelete.enabled) {
            html += bulkDelete.checkboxCellHtml(record.id);
        }

        html +=
            '<td class="text-secondary">' +
            escapeHtml(record.maintenance_date) +
            '</td>' +
            '<td class="fw-medium">' +
            employeeCell +
            '</td>' +
            '<td>' +
            typeBadge(record.type) +
            '</td>' +
            '<td class="text-secondary">' +
            escapeHtml(formatMoney(record.cost)) +
            '</td>' +
            '<td class="text-secondary">' +
            displayValue(record.performed_by) +
            '</td>' +
            '<td class="text-secondary">' +
            displayValue(record.next_service_date) +
            '</td>' +
            '<td>' +
            '<div class="btn-list flex-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-edit" data-id="' +
            record.id +
            '" title="Edit"><i class="ti ti-edit" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline btn-danger" data-action="open-delete" data-id="' +
            record.id +
            '" data-label="' +
            escapeHtml((record.asset_name || '') + ' — ' + record.maintenance_date) +
            '" title="Delete"><i class="ti ti-trash" aria-hidden="true"></i></button>' +
            '</div>' +
            '</td>';

        tr.innerHTML = html;

        return tr;
    }

    function upsertRow(record) {
        removeEmptyRow();

        var existing = tableBody.querySelector('tr[data-maintenance-id="' + record.id + '"]');
        var row = buildRow(record);

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

    function emptyRecord() {
        return {
            id: '',
            asset_id: '',
            maintenance_date: '',
            type: 'SERVICE',
            cost: '',
            performed_by_employee_id: '',
            next_service_date: '',
            notes: '',
        };
    }

    function todayDate() {
        var now = new Date();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        return now.getFullYear() + '-' + month + '-' + day;
    }

    function fillMaintenanceForm(record) {
        form.querySelector('[name="id"]').value = record.id ? String(record.id) : '';

        if (ui && ui.fillFormFields) {
            ui.fillFormFields(form, record, FIELD_NAMES);
            return;
        }

        FIELD_NAMES.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el) {
                return;
            }
            el.value = record[name] == null ? '' : String(record[name]);
        });
    }

    function focusFirstField() {
        setTimeout(function () {
            var el = form.querySelector('[name="asset_id"]');
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

    function getMaintenanceFromRow(id) {
        var row = tableBody.querySelector('tr[data-maintenance-id="' + id + '"]');
        if (!row || !row.dataset.maintenance) {
            return null;
        }

        try {
            return JSON.parse(row.dataset.maintenance);
        } catch (e) {
            return null;
        }
    }

    function applyClientValidationRules() {
        var assetEl = form.querySelector('[name="asset_id"]');
        if (assetEl) {
            assetEl.setCustomValidity(assetEl.value === '' ? 'Asset is required.' : '');
        }

        var dateEl = form.querySelector('[name="maintenance_date"]');
        if (dateEl) {
            dateEl.setCustomValidity(dateEl.value.trim() === '' ? 'Maintenance date is required.' : '');
        }

        var nextEl = form.querySelector('[name="next_service_date"]');
        var maintenanceDate = form.querySelector('[name="maintenance_date"]');

        if (nextEl && maintenanceDate && nextEl.value && maintenanceDate.value) {
            nextEl.setCustomValidity(
                nextEl.value < maintenanceDate.value
                    ? 'Next service date must be on or after maintenance date.'
                    : ''
            );
        } else if (nextEl) {
            nextEl.setCustomValidity('');
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
        var record = emptyRecord();
        record.maintenance_date = todayDate();
        fillMaintenanceForm(record);
        setFormModalTitle('Add maintenance record');
        showFormModal();
    }

    function openEditModal(id) {
        clearFormError();

        var record = getMaintenanceFromRow(id);
        if (!record) {
            showToast('Could not load maintenance record from the table.', 'error');
            return;
        }

        fillMaintenanceForm(record);
        setFormModalTitle('Edit maintenance record');
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

        post('mf_staffcore_maintenance_save', payload)
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

                upsertRow(result.data.maintenance);
                showToast(result.data.message || 'Saved.', 'success');
            })
            .catch(function () {
                setSubmitLoading(false);
                showFormError('Network error. Please try again.');
            });
    }

    function openDeleteModal(id, label) {
        pendingDeleteId = id;
        if (deleteLabelEl) {
            deleteLabelEl.textContent = label;
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

        post('mf_staffcore_maintenance_delete', { id: pendingDeleteId })
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
            openDeleteModal(target.getAttribute('data-id'), target.getAttribute('data-label') || '');
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
