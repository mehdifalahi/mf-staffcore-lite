(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="asset-categories"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';

    var formModalEl = document.getElementById('staffcore-asset-category-form-modal');
    var deleteModalEl = document.getElementById('staffcore-asset-category-delete-modal');
    var tableBody = root.querySelector('[data-role="categories-table"] tbody');
    var form = root.querySelector('[data-role="category-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var deleteError = root.querySelector('[data-role="delete-error"]');
    var deleteNameEl = root.querySelector('[data-role="delete-name"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');

    var ui = window.mfStaffCoreUi || null;
    var bulkDelete = ui && ui.initBulkDelete ? ui.initBulkDelete(root) : null;
    var formModal = formModalEl && ui ? ui.modal(formModalEl) : null;
    var deleteModal = deleteModalEl && ui ? ui.modal(deleteModalEl) : null;

    var pendingDeleteId = null;

    function post(action, data) {
        var body = new FormData();
        body.append('action', action);
        body.append('nonce', nonce);

        Object.keys(data).forEach(function (key) {
            body.append(key, data[key]);
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

    function applyClientValidationRules() {
        if (!form) {
            return;
        }

        var nameEl = form.querySelector('[name="name"]');
        if (!nameEl) {
            return;
        }

        if (nameEl.value.trim() === '') {
            nameEl.setCustomValidity('Category name is required.');
            return;
        }

        nameEl.setCustomValidity('');
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
        div.textContent = text;
        return div.innerHTML;
    }

    function removeEmptyRow() {
        var emptyRow = tableBody.querySelector('[data-role="empty-row"]');
        if (emptyRow) {
            emptyRow.remove();
        }
    }

    function ensureEmptyRow() {
        if (tableBody.querySelector('tr[data-category-id]')) {
            return;
        }

        var row = document.createElement('tr');
        row.setAttribute('data-role', 'empty-row');
        row.innerHTML =
            '<td colspan="3" class="text-secondary text-center py-4">No categories yet. Add your first category.</td>';
        tableBody.appendChild(row);
    }

    function buildRow(category) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-category-id', String(category.id));

        var description = category.description ? escapeHtml(category.description) : '—';
        var html = '';

        if (bulkDelete && bulkDelete.enabled) {
            html += bulkDelete.checkboxCellHtml(category.id);
        }

        html +=
            '<td class="fw-medium">' + escapeHtml(category.name) + '</td>' +
            '<td class="text-secondary">' + description + '</td>' +
            '<td>' +
            '<div class="btn-list flex-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-edit" data-id="' +
            category.id +
            '" title="Edit"><i class="ti ti-edit" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline btn-danger" data-action="open-delete" data-id="' +
            category.id +
            '" data-name="' +
            escapeHtml(category.name) +
            '" title="Delete"><i class="ti ti-trash" aria-hidden="true"></i></button>' +
            '</div>' +
            '</td>';

        tr.innerHTML = html;

        return tr;
    }

    function upsertRow(category) {
        removeEmptyRow();

        var existing = tableBody.querySelector('tr[data-category-id="' + category.id + '"]');
        var row = buildRow(category);

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

    function fillCategoryForm(category) {
        form.querySelector('[name="id"]').value = category.id ? String(category.id) : '';
        form.querySelector('[name="name"]').value = category.name || '';
        form.querySelector('[name="description"]').value = category.description || '';
    }

    function focusNameField() {
        setTimeout(function () {
            var nameEl = form.querySelector('[name="name"]');
            if (nameEl) {
                nameEl.focus();
            }
        }, 200);
    }

    function showFormModal() {
        if (formModal) {
            formModal.show();
        }
        focusNameField();
    }

    /** Read category fields from the table row (instant; no server round-trip). */
    function getCategoryFromRow(id) {
        var row = tableBody.querySelector('tr[data-category-id="' + id + '"]');
        if (!row) {
            return null;
        }

        var nameCell = row.querySelector('td.fw-medium');
        var descriptionCell = row.querySelector('td.text-secondary');
        if (!nameCell || !descriptionCell) {
            return null;
        }

        var name = nameCell.textContent.trim();
        var description = descriptionCell.textContent.trim();
        if (description === '—') {
            description = '';
        }

        return {
            id: id,
            name: name,
            description: description,
        };
    }

    function openCreateModal() {
        clearFormError();
        fillCategoryForm({ id: '', name: '', description: '' });
        setFormModalTitle('Add category');
        showFormModal();
    }

    function openEditModal(id) {
        clearFormError();

        var category = getCategoryFromRow(id);
        if (!category) {
            showToast('Could not load category from the table.', 'error');
            return;
        }

        fillCategoryForm(category);
        setFormModalTitle('Edit category');
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

        var id = form.querySelector('[name="id"]').value;
        var nameEl = form.querySelector('[name="name"]');
        var payload = {
            name: nameEl ? nameEl.value.trim() : '',
            description: form.querySelector('[name="description"]').value,
        };

        if (id) {
            payload.id = id;
        }

        post('mf_staffcore_asset_category_save', payload)
            .then(function (result) {
                setSubmitLoading(false);

                if (!result.success) {
                    showFormError(result.data && result.data.message ? result.data.message : 'Save failed.');
                    return;
                }

                if (formModal) {
                    formModal.hide();
                }

                if (!id) {
                    window.location.reload();
                    return;
                }

                upsertRow(result.data.category);
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

        post('mf_staffcore_asset_category_delete', { id: pendingDeleteId })
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
