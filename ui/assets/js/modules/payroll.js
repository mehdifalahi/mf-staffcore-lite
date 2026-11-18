(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="payroll"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';
    var payslipBaseUrl = root.getAttribute('data-payslip-base-url') || '';

    var formModalEl = document.getElementById('staffcore-payroll-form-modal');
    var deleteModalEl = document.getElementById('staffcore-payroll-delete-modal');
    var tableBody = root.querySelector('[data-role="payroll-table"] tbody');
    var form = root.querySelector('[data-role="payroll-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var deleteError = root.querySelector('[data-role="delete-error"]');
    var deleteLabelEl = root.querySelector('[data-role="delete-label"]');
    var netPreviewEl = root.querySelector('[data-role="net-preview"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');

    var ui = window.mfStaffCoreUi || null;
    var bulkDelete = ui && ui.initBulkDelete ? ui.initBulkDelete(root) : null;
    var formModal = formModalEl && ui ? ui.modal(formModalEl) : null;
    var deleteModal = deleteModalEl && ui ? ui.modal(deleteModalEl) : null;

    var pendingDeleteId = null;

    var AMOUNT_FIELDS = [
        'basic_salary',
        'allowances',
        'overtime_amount',
        'bonus',
        'commission',
        'tax',
        'insurance',
        'advance_deduction',
        'other_deductions',
    ];

    var FIELD_NAMES = [
        'employee_id',
        'payroll_month',
        'payroll_year',
        'basic_salary',
        'allowances',
        'overtime_amount',
        'bonus',
        'commission',
        'tax',
        'insurance',
        'advance_deduction',
        'other_deductions',
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'overtime_hours',
        'payment_status',
        'payment_date',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    var MONTH_NAMES = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
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

    function parseAmount(value) {
        var num = parseFloat(value);
        return isNaN(num) || num < 0 ? 0 : num;
    }

    function formatMoney(amount) {
        return amount.toFixed(2);
    }

    function calculateNetPay() {
        var gross =
            parseAmount(getFieldValue('basic_salary')) +
            parseAmount(getFieldValue('allowances')) +
            parseAmount(getFieldValue('overtime_amount')) +
            parseAmount(getFieldValue('bonus')) +
            parseAmount(getFieldValue('commission'));

        var deductions =
            parseAmount(getFieldValue('tax')) +
            parseAmount(getFieldValue('insurance')) +
            parseAmount(getFieldValue('advance_deduction')) +
            parseAmount(getFieldValue('other_deductions'));

        return Math.max(0, gross - deductions);
    }

    function updateNetPreview() {
        if (netPreviewEl) {
            netPreviewEl.value = formatMoney(calculateNetPay());
        }
    }

    function getFieldValue(name) {
        var el = form.querySelector('[name="' + name + '"]');
        return el ? el.value : '';
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

    function periodLabel(month, year) {
        var name = MONTH_NAMES[month - 1] || String(month);
        return escapeHtml(name + ' ' + year);
    }

    function statusBadge(status) {
        if (status === 'PENDING') {
            return '<span class="badge bg-warning-lt">Pending</span>';
        }
        if (status === 'PROCESSED') {
            return '<span class="badge bg-azure-lt">Processed</span>';
        }
        if (status === 'PAID') {
            return '<span class="badge bg-success-lt">Paid</span>';
        }
        if (status === 'ON_HOLD') {
            return '<span class="badge bg-secondary-lt">On hold</span>';
        }
        return '<span class="badge bg-secondary-lt">' + escapeHtml(status) + '</span>';
    }

    function payslipUrl(id) {
        if (!payslipBaseUrl) {
            return '';
        }
        var payrollId = String(id || '').trim();
        if (!payrollId) {
            return '';
        }

        var separator = payslipBaseUrl.indexOf('?') === -1 ? '?' : '&';
        return payslipBaseUrl + separator + 'payroll_id=' + encodeURIComponent(payrollId);
    }

    function grossPay(record) {
        return (
            parseAmount(record.basic_salary) +
            parseAmount(record.allowances) +
            parseAmount(record.overtime_amount) +
            parseAmount(record.bonus) +
            parseAmount(record.commission)
        );
    }

    function removeEmptyRow() {
        var emptyRow = tableBody.querySelector('[data-role="empty-row"]');
        if (emptyRow) {
            emptyRow.remove();
        }
    }

    function buildRow(record) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-payroll-id', String(record.id));
        tr.dataset.payroll = JSON.stringify(record);

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
            periodLabel(record.payroll_month, record.payroll_year) +
            '</td>' +
            '<td>' +
            employeeCell +
            '</td>' +
            '<td class="text-secondary">' +
            escapeHtml(formatMoney(grossPay(record))) +
            '</td>' +
            '<td class="fw-medium">' +
            escapeHtml(formatMoney(parseAmount(record.net_pay))) +
            '</td>' +
            '<td>' +
            statusBadge(record.payment_status) +
            '</td>' +
            '<td>' +
            '<div class="btn-list flex-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-payslip" data-id="' +
            record.id +
            '" title="Open payslip"><i class="ti ti-file-text" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-edit" data-id="' +
            record.id +
            '" title="Edit"><i class="ti ti-edit" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline btn-danger" data-action="open-delete" data-id="' +
            record.id +
            '" data-label="' +
            escapeHtml((record.employee_name || '') + ' — ' + record.payroll_month + '/' + record.payroll_year) +
            '" title="Delete"><i class="ti ti-trash" aria-hidden="true"></i></button>' +
            '</div>' +
            '</td>';

        tr.innerHTML = html;

        return tr;
    }

    function upsertRow(record) {
        removeEmptyRow();

        var existing = tableBody.querySelector('tr[data-payroll-id="' + record.id + '"]');
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
        var now = new Date();
        return {
            id: '',
            employee_id: '',
            payroll_month: String(now.getMonth() + 1),
            payroll_year: String(now.getFullYear()),
            basic_salary: '0',
            allowances: '0',
            overtime_amount: '0',
            bonus: '0',
            commission: '0',
            tax: '0',
            insurance: '0',
            advance_deduction: '0',
            other_deductions: '0',
            working_days: '',
            present_days: '0',
            absent_days: '0',
            leave_days: '0',
            overtime_hours: '0',
            payment_status: 'PENDING',
            payment_date: '',
            payment_method: '',
            payment_reference: '',
            notes: '',
        };
    }

    function fillPayrollForm(record) {
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

        updateNetPreview();
    }

    function focusFirstField() {
        setTimeout(function () {
            var el = form.querySelector('[name="employee_id"]');
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

    function getPayrollFromRow(id) {
        var row = tableBody.querySelector('tr[data-payroll-id="' + id + '"]');
        if (!row || !row.dataset.payroll) {
            return null;
        }

        try {
            return JSON.parse(row.dataset.payroll);
        } catch (e) {
            return null;
        }
    }

    function applyClientValidationRules() {
        var employeeEl = form.querySelector('[name="employee_id"]');
        if (employeeEl) {
            employeeEl.setCustomValidity(employeeEl.value === '' ? 'Employee is required.' : '');
        }

        var basicEl = form.querySelector('[name="basic_salary"]');
        if (basicEl) {
            basicEl.setCustomValidity(basicEl.value === '' ? 'Basic salary is required.' : '');
        }

        var workingEl = form.querySelector('[name="working_days"]');
        if (workingEl) {
            workingEl.setCustomValidity(
                workingEl.value === '' || parseInt(workingEl.value, 10) < 1 ? 'Working days is required.' : ''
            );
        }

        var presentEl = form.querySelector('[name="present_days"]');
        if (presentEl) {
            presentEl.setCustomValidity(presentEl.value === '' ? 'Present days is required.' : '');
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

    function runPrefill() {
        clearFormError();

        var employeeId = getFieldValue('employee_id');
        var year = getFieldValue('payroll_year');
        var month = getFieldValue('payroll_month');

        if (!employeeId || !year || !month) {
            showFormError('Select employee, month, and year before loading data.');
            return;
        }

        post('mf_staffcore_payroll_prefill', {
            employee_id: employeeId,
            payroll_year: year,
            payroll_month: month,
        })
            .then(function (result) {
                if (!result.success || !result.data || !result.data.prefill) {
                    showFormError(
                        result.data && result.data.message ? result.data.message : 'Could not load employee data.'
                    );
                    return;
                }

                var data = result.data.prefill;
                setFieldValue('basic_salary', data.basic_salary);
                setFieldValue('allowances', data.allowances);
                setFieldValue('working_days', data.working_days);
                setFieldValue('present_days', data.present_days);
                setFieldValue('absent_days', data.absent_days);
                setFieldValue('leave_days', data.leave_days);
                updateNetPreview();
                showToast('Employee data loaded.', 'success');
            })
            .catch(function () {
                showFormError('Network error. Please try again.');
            });
    }

    function setFieldValue(name, value) {
        if (ui && ui.setFormFieldValue) {
            ui.setFormFieldValue(form, name, value);
            return;
        }

        var el = form.querySelector('[name="' + name + '"]');
        if (el) {
            el.value = value == null ? '' : String(value);
        }
    }

    function openCreateModal() {
        clearFormError();
        fillPayrollForm(emptyRecord());
        setFormModalTitle('Add payroll record');
        showFormModal();
    }

    function openEditModal(id) {
        clearFormError();

        var record = getPayrollFromRow(id);
        if (!record) {
            showToast('Could not load payroll record from the table.', 'error');
            return;
        }

        fillPayrollForm(record);
        setFormModalTitle('Edit payroll record');
        showFormModal();
    }

    function openPayslip(id) {
        var url = payslipUrl(id);
        if (!url) {
            showToast('Payslip URL is not available.', 'error');
            return;
        }

        window.open(url, '_blank', 'noopener');
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
        updateNetPreview();

        if (ui && !ui.validateForm(form, { custom: [applyClientValidationRules] })) {
            return;
        }

        setSubmitLoading(true);

        var payload = collectFormPayload();

        post('mf_staffcore_payroll_save', payload)
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

                upsertRow(result.data.payroll);
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

        post('mf_staffcore_payroll_delete', { id: pendingDeleteId })
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

        if (action === 'open-payslip') {
            openPayslip(target.getAttribute('data-id'));
            return;
        }

        if (action === 'open-delete') {
            openDeleteModal(target.getAttribute('data-id'), target.getAttribute('data-label') || '');
            return;
        }

        if (action === 'prefill') {
            runPrefill();
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
        AMOUNT_FIELDS.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el) {
                el.addEventListener('input', updateNetPreview);
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitForm();
        });
    }
})();
