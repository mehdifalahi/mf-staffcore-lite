(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="leave-requests"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';

    var formModalEl = document.getElementById('staffcore-leave-request-form-modal');
    var viewModalEl = document.getElementById('staffcore-leave-request-view-modal');
    var deleteModalEl = document.getElementById('staffcore-leave-request-delete-modal');
    var approveModalEl = document.getElementById('staffcore-leave-request-approve-modal');
    var tableBody = root.querySelector('[data-role="leave-requests-table"] tbody');
    var form = root.querySelector('[data-role="leave-request-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var deleteError = root.querySelector('[data-role="delete-error"]');
    var deleteLabelEl = root.querySelector('[data-role="delete-label"]');
    var approveError = root.querySelector('[data-role="approve-error"]');
    var approveLabelEl = root.querySelector('[data-role="approve-label"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');

    var ui = window.mfStaffCoreUi || null;
    var bulkDelete = ui && ui.initBulkDelete ? ui.initBulkDelete(root) : null;
    var formModal = formModalEl && ui ? ui.modal(formModalEl) : null;
    var viewModal = viewModalEl && ui ? ui.modal(viewModalEl) : null;
    var deleteModal = deleteModalEl && ui ? ui.modal(deleteModalEl) : null;
    var approveModal = approveModalEl && ui ? ui.modal(approveModalEl) : null;

    var pendingDeleteId = null;
    var pendingApproveId = null;
    var canView = root.getAttribute('data-can-view') === '1';
    var canEdit = root.getAttribute('data-can-edit') === '1';
    var canDelete = root.getAttribute('data-can-delete') === '1';
    var canApprove = root.getAttribute('data-can-approve') === '1';
    var isEmployeeScoped = root.getAttribute('data-is-employee-scoped') === '1';

    var FIELD_NAMES = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'status',
        'reason',
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

    function displayValuePlain(value) {
        return value == null || value === '' ? '—' : String(value);
    }

    function setViewField(role, value, asHtml) {
        var el = root.querySelector('[data-role="' + role + '"]');

        if (!el) {
            return;
        }

        if (asHtml) {
            el.innerHTML = value;
        } else {
            el.textContent = displayValuePlain(value);
        }
    }

    function employeeLabel(record) {
        var name = record.employee_name || '';
        var code = record.employee_code || '';
        return code !== '' ? name + ' (' + code + ')' : name;
    }

    function formatDateTime(value) {
        if (value == null || value === '') {
            return '—';
        }

        return String(value).replace('T', ' ');
    }

    function statusBadge(status) {
        if (status === 'PENDING') {
            return '<span class="badge bg-warning-lt">Pending</span>';
        }
        if (status === 'APPROVED') {
            return '<span class="badge bg-success-lt">Approved</span>';
        }
        if (status === 'REJECTED') {
            return '<span class="badge bg-danger-lt">Rejected</span>';
        }
        if (status === 'CANCELLED') {
            return '<span class="badge bg-secondary-lt">Cancelled</span>';
        }
        return '<span class="badge bg-secondary-lt">' + escapeHtml(status) + '</span>';
    }

    function fillViewDetails(record) {
        var employeeRow = root.querySelector('[data-role="view-employee-row"]');
        if (employeeRow) {
            employeeRow.classList.toggle('d-none', isEmployeeScoped);
        }

        setViewField('view-employee', employeeLabel(record));
        setViewField('view-leave-type', record.leave_type);
        setViewField('view-start-date', record.start_date);
        setViewField('view-end-date', record.end_date);
        setViewField('view-total-days', record.total_days);
        setViewField('view-status', statusBadge(record.status), true);
        setViewField(
            'view-period',
            record.start_date && record.end_date
                ? record.start_date + ' — ' + record.end_date
                : ''
        );
        setViewField('view-reason', record.reason);
        setViewField('view-notes', record.notes);
        setViewField('view-created-at', formatDateTime(record.created_at));
        setViewField('view-updated-at', formatDateTime(record.updated_at));
    }

    function openViewModal(id) {
        var record = getLeaveRequestFromRow(id);

        if (!record) {
            showToast('Could not load leave request from the table.', 'error');
            return;
        }

        fillViewDetails(record);

        if (viewModal) {
            viewModal.show();
        }
    }

    function rowHasActions(record) {
        if (canView) {
            return true;
        }
        if (canDelete) {
            return true;
        }
        if (canApprove && record.status === 'PENDING') {
            return true;
        }
        if (canEdit && (!isEmployeeScoped || record.status === 'PENDING')) {
            return true;
        }

        return false;
    }

    function buildActionsCell(record) {
        if (!rowHasActions(record)) {
            return '';
        }

        var html =
            '<td>' +
            '<div class="btn-list flex-nowrap">';

        if (canView) {
            html +=
                '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-view" data-id="' +
                record.id +
                '" title="View"><i class="ti ti-eye" aria-hidden="true"></i></button>';
        }

        if (canEdit && (!isEmployeeScoped || record.status === 'PENDING')) {
            html +=
                '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-edit" data-id="' +
                record.id +
                '" title="Edit"><i class="ti ti-edit" aria-hidden="true"></i></button>';
        }

        if (canApprove && record.status === 'PENDING') {
            html +=
                '<button type="button" class="btn btn-sm btn-outline btn-success" data-action="open-approve" data-id="' +
                record.id +
                '" data-label="' +
                escapeHtml((record.employee_name || '') + ' — ' + record.leave_type + ' (' + record.start_date + ' — ' + record.end_date + ')') +
                '" title="Approve"><i class="ti ti-check" aria-hidden="true"></i></button>';
        }

        if (canDelete) {
            html +=
                '<button type="button" class="btn btn-sm btn-outline btn-danger" data-action="open-delete" data-id="' +
                record.id +
                '" data-label="' +
                escapeHtml((record.employee_name || '') + ' — ' + record.leave_type) +
                '" title="Delete"><i class="ti ti-trash" aria-hidden="true"></i></button>';
        }

        html += '</div></td>';

        return html;
    }

    function calculateTotalDays(startDate, endDate) {
        if (!startDate || !endDate) {
            return 0;
        }

        var start = new Date(startDate + 'T00:00:00');
        var end = new Date(endDate + 'T00:00:00');

        if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) {
            return 0;
        }

        var diffMs = end.getTime() - start.getTime();
        return Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
    }

    function updateTotalDaysField() {
        var startEl = form.querySelector('[name="start_date"]');
        var endEl = form.querySelector('[name="end_date"]');
        var totalEl = form.querySelector('[name="total_days"]');

        if (!startEl || !endEl || !totalEl) {
            return;
        }

        var days = calculateTotalDays(startEl.value, endEl.value);
        totalEl.value = days > 0 ? String(days) : '';
    }

    function removeEmptyRow() {
        var emptyRow = tableBody.querySelector('[data-role="empty-row"]');
        if (emptyRow) {
            emptyRow.remove();
        }
    }

    function buildRow(record) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-leave-request-id', String(record.id));
        tr.dataset.leaveRequest = JSON.stringify(record);

        var employeeCell = displayValue(record.employee_name);
        if (record.employee_code) {
            employeeCell +=
                ' <span class="text-secondary ms-1">(' + escapeHtml(record.employee_code) + ')</span>';
        }

        var html = '';

        if (bulkDelete && bulkDelete.enabled) {
            html += bulkDelete.checkboxCellHtml(record.id);
        }

        html +=
            '<td class="fw-medium">' +
            employeeCell +
            '</td>' +
            '<td>' +
            escapeHtml(record.leave_type) +
            '</td>' +
            '<td class="text-secondary">' +
            escapeHtml(record.start_date + ' — ' + record.end_date) +
            '</td>' +
            '<td>' +
            escapeHtml(String(record.total_days)) +
            '</td>' +
            '<td>' +
            statusBadge(record.status) +
            '</td>' +
            buildActionsCell(record);

        tr.innerHTML = html;

        return tr;
    }

    function upsertRow(record) {
        removeEmptyRow();

        var existing = tableBody.querySelector('tr[data-leave-request-id="' + record.id + '"]');
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
            employee_id: '',
            leave_type: '',
            start_date: '',
            end_date: '',
            total_days: '',
            status: 'PENDING',
            reason: '',
            notes: '',
        };
    }

    function fillLeaveRequestForm(record) {
        form.querySelector('[name="id"]').value = record.id ? String(record.id) : '';

        if (ui && ui.fillFormFields) {
            ui.fillFormFields(form, record, FIELD_NAMES);
        } else {
            FIELD_NAMES.forEach(function (name) {
                var el = form.querySelector('[name="' + name + '"]');
                if (!el) {
                    return;
                }
                el.value = record[name] == null ? '' : String(record[name]);
            });
        }

        updateTotalDaysField();
    }

    function focusFirstField() {
        setTimeout(function () {
            var el =
                form.querySelector('[name="leave_type"]') ||
                form.querySelector('[name="employee_id"]:not([type="hidden"])');
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

    function getLeaveRequestFromRow(id) {
        var row = tableBody.querySelector('tr[data-leave-request-id="' + id + '"]');
        if (!row || !row.dataset.leaveRequest) {
            return null;
        }

        try {
            return JSON.parse(row.dataset.leaveRequest);
        } catch (e) {
            return null;
        }
    }

    function applyClientValidationRules() {
        var employeeEl = form.querySelector('[name="employee_id"]');
        if (employeeEl) {
            employeeEl.setCustomValidity(employeeEl.value === '' ? 'Employee is required.' : '');
        }

        var typeEl = form.querySelector('[name="leave_type"]');
        if (typeEl) {
            typeEl.setCustomValidity(typeEl.value === '' ? 'Leave type is required.' : '');
        }

        var startEl = form.querySelector('[name="start_date"]');
        if (startEl) {
            startEl.setCustomValidity(startEl.value.trim() === '' ? 'Start date is required.' : '');
        }

        var endEl = form.querySelector('[name="end_date"]');
        if (endEl) {
            var endInvalid = endEl.value.trim() === '';
            if (!endInvalid && startEl && startEl.value && endEl.value < startEl.value) {
                endEl.setCustomValidity('End date must be on or after start date.');
            } else {
                endEl.setCustomValidity(endInvalid ? 'End date is required.' : '');
            }
        }

        updateTotalDaysField();
    }

    function collectFormPayload() {
        updateTotalDaysField();

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
        fillLeaveRequestForm(emptyRecord());

        var defaultEmployeeId = root.getAttribute('data-default-employee-id') || '';
        if (defaultEmployeeId && ui && ui.setFormFieldValue) {
            ui.setFormFieldValue(form, 'employee_id', defaultEmployeeId);
        } else if (defaultEmployeeId) {
            var employeeEl = form.querySelector('[name="employee_id"]');
            if (employeeEl) {
                employeeEl.value = defaultEmployeeId;
            }
        }

        setFormModalTitle('Add leave request');
        showFormModal();
    }

    function openEditModal(id) {
        clearFormError();

        var record = getLeaveRequestFromRow(id);
        if (!record) {
            showToast('Could not load leave request from the table.', 'error');
            return;
        }

        fillLeaveRequestForm(record);
        setFormModalTitle('Edit leave request');
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

        post('mf_staffcore_leave_request_save', payload)
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

                upsertRow(result.data.leave_request);
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

        post('mf_staffcore_leave_request_delete', { id: pendingDeleteId })
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

    function openApproveModal(id, label) {
        pendingApproveId = id;
        if (approveLabelEl) {
            approveLabelEl.textContent = label;
        }
        if (approveError) {
            approveError.classList.add('d-none');
            approveError.textContent = '';
        }
        if (approveModal) {
            approveModal.show();
        }
    }

    function setApproveLoading(loading) {
        var btn = root.querySelector('[data-action="confirm-approve"]');
        var spinner = root.querySelector('[data-role="approve-spinner"]');
        if (btn) {
            btn.disabled = loading;
        }
        if (spinner) {
            spinner.classList.toggle('d-none', !loading);
        }
    }

    function confirmApprove() {
        if (!pendingApproveId) {
            return;
        }

        setApproveLoading(true);

        post('mf_staffcore_leave_request_approve', { id: pendingApproveId })
            .then(function (result) {
                setApproveLoading(false);

                if (!result.success) {
                    if (approveError) {
                        approveError.textContent =
                            result.data && result.data.message ? result.data.message : 'Approve failed.';
                        approveError.classList.remove('d-none');
                    }
                    return;
                }

                if (approveModal) {
                    approveModal.hide();
                }

                pendingApproveId = null;
                showToast(result.data.message || 'Approved.', 'success');
                window.location.reload();
            })
            .catch(function () {
                setApproveLoading(false);
                if (approveError) {
                    approveError.textContent = 'Network error. Please try again.';
                    approveError.classList.remove('d-none');
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

        if (action === 'open-view') {
            openViewModal(target.getAttribute('data-id'));
            return;
        }

        if (action === 'open-delete') {
            openDeleteModal(target.getAttribute('data-id'), target.getAttribute('data-label') || '');
            return;
        }

        if (action === 'open-approve') {
            openApproveModal(target.getAttribute('data-id'), target.getAttribute('data-label') || '');
            return;
        }

        if (action === 'submit-form') {
            submitForm();
            return;
        }

        if (action === 'confirm-delete') {
            confirmDelete();
            return;
        }

        if (action === 'confirm-approve') {
            confirmApprove();
        }
    });

    if (form) {
        form.addEventListener('change', function (event) {
            var name = event.target && event.target.name;
            if (name === 'start_date' || name === 'end_date') {
                updateTotalDaysField();
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitForm();
        });
    }
})();
