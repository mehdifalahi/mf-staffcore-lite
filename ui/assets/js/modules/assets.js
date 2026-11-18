(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="assets"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';

    var formModalEl = document.getElementById('staffcore-asset-form-modal');
    var deleteModalEl = document.getElementById('staffcore-asset-delete-modal');
    var tableBody = root.querySelector('[data-role="assets-table"] tbody');
    var form = root.querySelector('[data-role="asset-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var deleteError = root.querySelector('[data-role="delete-error"]');
    var deleteNameEl = root.querySelector('[data-role="delete-name"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');
    var submitBtn = root.querySelector('[data-action="submit-form"]');
    var imageCurrentWrap = root.querySelector('[data-role="image-current-wrap"]');
    var imageCurrentLink = root.querySelector('[data-role="image-current-link"]');

    var ui = window.mfStaffCoreUi || null;
    var bulkDelete = ui && ui.initBulkDelete ? ui.initBulkDelete(root) : null;
    var formModal = formModalEl && ui ? ui.modal(formModalEl) : null;
    var deleteModal = deleteModalEl && ui ? ui.modal(deleteModalEl) : null;

    var pendingDeleteId = null;

    var FIELD_NAMES = [
        'code',
        'name',
        'category_id',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'serial_number',
        'warranty_expiry',
        'status',
        'condition',
        'assigned_to',
        'location',
        'supplier_id',
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

    function postForm(action, formData) {
        formData.append('action', action);
        formData.append('nonce', nonce);

        return fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
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

    function statusBadge(status) {
        if (status === 'ACTIVE') {
            return '<span class="badge bg-success-lt">Active</span>';
        }
        if (status === 'UNDER_REPAIR') {
            return '<span class="badge bg-warning-lt">Under repair</span>';
        }
        if (status === 'DISPOSED') {
            return '<span class="badge bg-danger-lt">Disposed</span>';
        }
        return '<span class="badge bg-secondary-lt">Inactive</span>';
    }

    function categoryBadge(name) {
        if (!name) {
            return '—';
        }
        return '<span class="badge bg-secondary-lt">' + escapeHtml(name) + '</span>';
    }

    function conditionLabel(condition) {
        var map = {
            EXCELLENT: 'Excellent',
            GOOD: 'Good',
            FAIR: 'Fair',
            POOR: 'Poor',
        };
        return escapeHtml(map[condition] || condition || '—');
    }

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }
        var num = parseFloat(value);
        if (isNaN(num)) {
            return escapeHtml(value);
        }
        return escapeHtml(num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
    }

    function assignedLabel(name) {
        if (!name) {
            return '—';
        }
        return escapeHtml(String(name).toUpperCase());
    }

    function imageLinkLabel(url) {
        if (!url) {
            return '';
        }
        try {
            var path = new URL(url, window.location.origin).pathname;
            var parts = path.split('/');
            var base = parts[parts.length - 1] || url;
            return base;
        } catch (e) {
            return url;
        }
    }

    function buildNameCell(asset) {
        var html = '<td><div class="d-flex align-items-center gap-2 staffcore-asset-name-cell">';

        if (asset.image) {
            var safeUrl = String(asset.image).replace(/\\/g, '\\\\').replace(/'/g, '\\\'');
            html +=
                '<span class="avatar avatar-sm rounded staffcore-asset-thumb" style="background-image:url(\'' +
                safeUrl +
                '\')" role="img" aria-hidden="true"></span>';
        }

        html += '<div><div class="fw-medium">' + escapeHtml(asset.name) + '</div>';

        if (asset.purchase_date_line) {
            html += '<div class="text-secondary small">' + escapeHtml(asset.purchase_date_line) + '</div>';
        }

        html += '</div></div></td>';

        return html;
    }

    function removeEmptyRow() {
        var emptyRow = tableBody.querySelector('[data-role="empty-row"]');
        if (emptyRow) {
            emptyRow.remove();
        }
    }

    function buildRow(asset) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-asset-id', String(asset.id));
        tr.dataset.asset = JSON.stringify(asset);

        var html = '';

        if (bulkDelete && bulkDelete.enabled) {
            html += bulkDelete.checkboxCellHtml(asset.id);
        }

        html +=
            '<td class="text-secondary">' +
            escapeHtml(asset.code) +
            '</td>' +
            buildNameCell(asset) +
            '<td>' +
            categoryBadge(asset.category_name) +
            '</td>' +
            '<td>' +
            statusBadge(asset.status) +
            '</td>' +
            '<td class="text-secondary">' +
            conditionLabel(asset.condition) +
            '</td>' +
            '<td>' +
            formatMoney(asset.current_value) +
            '</td>' +
            '<td>' +
            assignedLabel(asset.assigned_to_display || asset.assigned_to_name) +
            '</td>' +
            '<td>' +
            '<div class="btn-list flex-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-edit" data-id="' +
            asset.id +
            '" title="Edit"><i class="ti ti-edit" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline btn-danger" data-action="open-delete" data-id="' +
            asset.id +
            '" data-name="' +
            escapeHtml(asset.name) +
            '" title="Delete"><i class="ti ti-trash" aria-hidden="true"></i></button>' +
            '</div>' +
            '</td>';

        tr.innerHTML = html;

        return tr;
    }

    function upsertRow(asset) {
        removeEmptyRow();

        var existing = tableBody.querySelector('tr[data-asset-id="' + asset.id + '"]');
        var row = buildRow(asset);

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

    function emptyAsset() {
        return {
            id: '',
            code: '',
            name: '',
            category_id: '',
            purchase_date: '',
            purchase_cost: '',
            current_value: '',
            serial_number: '',
            warranty_expiry: '',
            status: 'ACTIVE',
            condition: 'GOOD',
            assigned_to: '',
            location: '',
            supplier_id: '',
            image: '',
            notes: '',
        };
    }

    function updateImageCurrent(url) {
        if (!imageCurrentWrap || !imageCurrentLink) {
            return;
        }

        if (url) {
            imageCurrentWrap.classList.remove('d-none');
            imageCurrentLink.href = url;
            imageCurrentLink.textContent = imageLinkLabel(url);
            imageCurrentLink.dataset.imageUrl = url;
        } else {
            imageCurrentWrap.classList.add('d-none');
            imageCurrentLink.href = '#';
            imageCurrentLink.textContent = '';
            imageCurrentLink.dataset.imageUrl = '';
        }
    }

    function openImagePreview(url) {
        if (!url || !ui || typeof ui.imageLightbox !== 'function') {
            return;
        }

        var assetId = form.querySelector('[name="id"]').value;

        ui.imageLightbox({
            url: url,
            deleteLabel: 'Remove image',
            onDelete: assetId
                ? function (close) {
                      post('mf_staffcore_asset_remove_image', { id: assetId })
                          .then(function (result) {
                              if (!result.success) {
                                  showToast(
                                      result.data && result.data.message
                                          ? result.data.message
                                          : 'Could not remove image.',
                                      'error',
                                  );
                                  return;
                              }

                              close();
                              form.querySelector('[name="image_current"]').value = '';
                              updateImageCurrent('');
                              showToast(result.data.message || 'Image removed.', 'success');

                              if (result.data.asset) {
                                  upsertRow(result.data.asset);
                                  fillAssetForm(result.data.asset);
                              }
                          })
                          .catch(function () {
                              showToast('Network error. Please try again.', 'error');
                          });
                  }
                : null,
        });
    }

    function fillAssetForm(asset) {
        form.querySelector('[name="id"]').value = asset.id ? String(asset.id) : '';
        form.querySelector('[name="image_current"]').value = asset.image || '';

        if (ui && ui.fillFormFields) {
            ui.fillFormFields(form, asset, FIELD_NAMES);
        } else {
            FIELD_NAMES.forEach(function (name) {
                var el = form.querySelector('[name="' + name + '"]');
                if (!el) {
                    return;
                }
                el.value = asset[name] == null ? '' : String(asset[name]);
            });
        }

        var fileEl = form.querySelector('[name="image"]');
        if (fileEl) {
            fileEl.value = '';
        }

        updateImageCurrent(asset.image || '');
    }

    function focusFirstField() {
        setTimeout(function () {
            var el = form.querySelector('[name="code"]');
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

    function getAssetFromRow(id) {
        var row = tableBody.querySelector('tr[data-asset-id="' + id + '"]');
        if (!row || !row.dataset.asset) {
            return null;
        }

        try {
            return JSON.parse(row.dataset.asset);
        } catch (e) {
            return null;
        }
    }

    function applyClientValidationRules() {
        var codeEl = form.querySelector('[name="code"]');
        if (codeEl) {
            codeEl.setCustomValidity(codeEl.value.trim() === '' ? 'Asset code is required.' : '');
        }

        var nameEl = form.querySelector('[name="name"]');
        if (nameEl) {
            nameEl.setCustomValidity(nameEl.value.trim() === '' ? 'Asset name is required.' : '');
        }

        var categoryEl = form.querySelector('[name="category_id"]');
        if (categoryEl) {
            categoryEl.setCustomValidity(categoryEl.value === '' ? 'Asset category is required.' : '');
        }
    }

    function collectFormData() {
        var formData = new FormData();

        FIELD_NAMES.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el) {
                return;
            }
            formData.append(name, el.value.trim());
        });

        var id = form.querySelector('[name="id"]').value;
        if (id) {
            formData.append('id', id);
        }

        formData.append('image_current', form.querySelector('[name="image_current"]').value || '');

        var fileEl = form.querySelector('[name="image"]');
        if (fileEl && fileEl.files && fileEl.files[0]) {
            formData.append('image', fileEl.files[0]);
        }

        return formData;
    }

    function openCreateModal() {
        clearFormError();
        fillAssetForm(emptyAsset());
        setFormModalTitle('Add asset');
        showFormModal();
    }

    function openEditModal(id) {
        clearFormError();

        var asset = getAssetFromRow(id);
        if (!asset) {
            showToast('Could not load asset from the table.', 'error');
            return;
        }

        fillAssetForm(asset);
        setFormModalTitle('Edit asset');
        showFormModal();
    }

    function setSubmitLoading(loading) {
        if (submitBtn) {
            submitBtn.disabled = loading;
        }
        var spinner = root.querySelector('[data-role="submit-spinner"]');
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

        var formData = collectFormData();
        var isCreate = !form.querySelector('[name="id"]').value;

        postForm('mf_staffcore_asset_save', formData)
            .then(function (result) {
                setSubmitLoading(false);

                if (!result.success) {
                    showFormError(result.data && result.data.message ? result.data.message : 'Save failed.');
                    return;
                }

                if (formModal) {
                    formModal.hide();
                }

                if (isCreate) {
                    window.location.reload();
                    return;
                }

                upsertRow(result.data.asset);
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

        post('mf_staffcore_asset_delete', { id: pendingDeleteId })
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

    if (imageCurrentLink) {
        imageCurrentLink.addEventListener('click', function (event) {
            event.preventDefault();
            var url = imageCurrentLink.dataset.imageUrl || imageCurrentLink.href;
            if (!url || url === '#') {
                return;
            }
            openImagePreview(url);
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
