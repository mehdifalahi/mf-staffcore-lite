(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="suppliers"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';

    var formModalEl = document.getElementById('staffcore-supplier-form-modal');
    var deleteModalEl = document.getElementById('staffcore-supplier-delete-modal');
    var tableBody = root.querySelector('[data-role="suppliers-table"] tbody');
    var form = root.querySelector('[data-role="supplier-form"]');
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
        'supplier_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'payment_terms',
        'status',
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

    function removeEmptyRow() {
        var emptyRow = tableBody.querySelector('[data-role="empty-row"]');
        if (emptyRow) {
            emptyRow.remove();
        }
    }

    function ensureEmptyRow() {
        if (tableBody.querySelector('tr[data-supplier-id]')) {
            return;
        }

        var row = document.createElement('tr');
        row.setAttribute('data-role', 'empty-row');
        row.innerHTML =
            '<td colspan="6" class="text-secondary text-center py-4">No suppliers yet. Add your first supplier.</td>';
        tableBody.appendChild(row);
    }

    function buildRow(supplier) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-supplier-id', String(supplier.id));
        tr.dataset.supplier = JSON.stringify(supplier);

        var html = '';

        if (bulkDelete && bulkDelete.enabled) {
            html += bulkDelete.checkboxCellHtml(supplier.id);
        }

        html +=
            '<td class="fw-medium">' + escapeHtml(supplier.supplier_name) + '</td>' +
            '<td class="text-secondary">' + displayValue(supplier.contact_person) + '</td>' +
            '<td class="text-secondary">' + displayValue(supplier.email) + '</td>' +
            '<td class="text-secondary">' + displayValue(supplier.phone) + '</td>' +
            '<td>' + statusBadge(supplier.status) + '</td>' +
            '<td>' +
            '<div class="btn-list flex-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-edit" data-id="' +
            supplier.id +
            '" title="Edit"><i class="ti ti-edit" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline btn-danger" data-action="open-delete" data-id="' +
            supplier.id +
            '" data-name="' +
            escapeHtml(supplier.supplier_name) +
            '" title="Delete"><i class="ti ti-trash" aria-hidden="true"></i></button>' +
            '</div>' +
            '</td>';

        tr.innerHTML = html;

        return tr;
    }

    function upsertRow(supplier) {
        removeEmptyRow();

        var existing = tableBody.querySelector('tr[data-supplier-id="' + supplier.id + '"]');
        var row = buildRow(supplier);

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

    function emptySupplier() {
        return {
            id: '',
            supplier_name: '',
            contact_person: '',
            email: '',
            phone: '',
            address: '',
            city: '',
            state: '',
            country: '',
            postal_code: '',
            website: '',
            payment_terms: '',
            status: 'ACTIVE',
            notes: '',
        };
    }

    function fillSupplierForm(supplier) {
        form.querySelector('[name="id"]').value = supplier.id ? String(supplier.id) : '';

        if (ui && ui.fillFormFields) {
            ui.fillFormFields(form, supplier, FIELD_NAMES);
            return;
        }

        FIELD_NAMES.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el) {
                return;
            }
            el.value = supplier[name] == null ? '' : String(supplier[name]);
        });
    }

    function focusFirstField() {
        setTimeout(function () {
            var el = form.querySelector('[name="supplier_name"]');
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

    function getSupplierFromRow(id) {
        var row = tableBody.querySelector('tr[data-supplier-id="' + id + '"]');
        if (!row || !row.dataset.supplier) {
            return null;
        }

        try {
            return JSON.parse(row.dataset.supplier);
        } catch (e) {
            return null;
        }
    }

    function applyClientValidationRules() {
        var nameEl = form.querySelector('[name="supplier_name"]');
        if (nameEl) {
            if (nameEl.value.trim() === '') {
                nameEl.setCustomValidity('Supplier name is required.');
            } else {
                nameEl.setCustomValidity('');
            }
        }

        var emailEl = form.querySelector('[name="email"]');
        if (emailEl && emailEl.value.trim() !== '' && !emailEl.checkValidity()) {
            emailEl.setCustomValidity('Enter a valid email address.');
        } else if (emailEl) {
            emailEl.setCustomValidity('');
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
        fillSupplierForm(emptySupplier());
        setFormModalTitle('Add supplier');
        showFormModal();
    }

    function openEditModal(id) {
        clearFormError();

        var supplier = getSupplierFromRow(id);
        if (!supplier) {
            showToast('Could not load supplier from the table.', 'error');
            return;
        }

        fillSupplierForm(supplier);
        setFormModalTitle('Edit supplier');
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

        post('mf_staffcore_supplier_save', payload)
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

                upsertRow(result.data.supplier);
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

        post('mf_staffcore_supplier_delete', { id: pendingDeleteId })
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
