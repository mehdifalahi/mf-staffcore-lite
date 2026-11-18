(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="users"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';

    var formModalEl = document.getElementById('staffcore-user-form-modal');
    var linkModalEl = document.getElementById('staffcore-user-link-modal');
    var bulkLinkModalEl = document.getElementById('staffcore-user-bulk-link-modal');
    var passwordModalEl = document.getElementById('staffcore-user-password-modal');
    var deleteModalEl = document.getElementById('staffcore-user-delete-modal');
    var tableBody = root.querySelector('[data-role="users-table"] tbody');
    var form = root.querySelector('[data-role="user-form"]');
    var linkForm = root.querySelector('[data-role="link-form"]');
    var bulkLinkForm = root.querySelector('[data-role="bulk-link-form"]');
    var passwordForm = root.querySelector('[data-role="password-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var formWarning = root.querySelector('[data-role="form-warning"]');
    var managerScopeWrap = root.querySelector('[data-role="manager-scope-wrap"]');
    var roleSelect = form ? form.querySelector('#user-role') : null;
    var managerScopeSelect = form ? form.querySelector('#user-manager-scope') : null;
    var linkFormError = root.querySelector('[data-role="link-form-error"]');
    var linkSearchInput = root.querySelector('#link-user-search');
    var linkResultsEl = root.querySelector('[data-role="link-user-results"]');
    var linkEmptyEl = root.querySelector('[data-role="link-user-empty"]');
    var linkSelectedEl = root.querySelector('[data-role="link-user-selected"]');
    var linkValidationEl = root.querySelector('[data-role="link-user-validation"]');
    var bulkLinkTableBody = root.querySelector('[data-role="bulk-link-table-body"]');
    var bulkLinkEmptyRow = root.querySelector('[data-role="bulk-link-empty-row"]');
    var bulkLinkPaginationEl = root.querySelector('[data-role="bulk-link-pagination"]');
    var bulkLinkPaginationSummary = root.querySelector('[data-role="bulk-link-pagination-summary"]');
    var bulkLinkPaginationList = root.querySelector('[data-role="bulk-link-pagination-list"]');
    var bulkLinkSelectionSummary = root.querySelector('[data-role="bulk-link-selection-summary"]');
    var bulkLinkLoadingEl = root.querySelector('[data-role="bulk-link-loading"]');
    var bulkLinkFormError = root.querySelector('[data-role="bulk-link-form-error"]');
    var bulkLinkSelectionValidation = root.querySelector('[data-role="bulk-link-selection-validation"]');
    var bulkLinkSearchInput = root.querySelector('#bulk-link-search');
    var bulkLinkSourceSelect = root.querySelector('#bulk-link-source');
    var bulkLinkSelectPageCheckbox = root.querySelector('[data-action="bulk-select-page"]');
    var passwordError = root.querySelector('[data-role="password-error"]');
    var deleteError = root.querySelector('[data-role="delete-error"]');
    var deleteLabelEl = root.querySelector('[data-role="delete-label"]');
    var passwordUserLabelEl = root.querySelector('[data-role="password-user-label"]');
    var passwordFieldWrap = root.querySelector('[data-role="password-field-wrap"]');
    var loginHint = root.querySelector('[data-role="login-readonly-hint"]');
    var loginInput = form ? form.querySelector('[name="user_login"]') : null;
    var toastContainer = root.querySelector('[data-role="toast-container"]');

    var ui = window.mfStaffCoreUi || null;
    var formModal = formModalEl && ui ? ui.modal(formModalEl) : null;
    var linkModal = linkModalEl && ui ? ui.modal(linkModalEl) : null;
    var bulkLinkModal = bulkLinkModalEl && ui ? ui.modal(bulkLinkModalEl) : null;
    var passwordModal = passwordModalEl && ui ? ui.modal(passwordModalEl) : null;
    var deleteModal = deleteModalEl && ui ? ui.modal(deleteModalEl) : null;

    var pendingDeleteId = null;
    var linkSearchTimer = null;
    var bulkLinkSearchTimer = null;
    var selectedPlatformUser = null;
    var bulkSelectedIds = {};
    var bulkCurrentPage = 1;
    var bulkPagination = null;

    var FIELD_NAMES = ['display_name', 'user_email', 'user_login', 'role', 'phone', 'status'];
    var ROLE_MANAGER = 'hr_manager';
    var SCOPE_MODE_AUTO = 'auto';

    function post(action, data) {
        var body = data instanceof FormData ? data : new FormData();

        if (!(data instanceof FormData)) {
            Object.keys(data).forEach(function (key) {
                if (data[key] !== undefined && data[key] !== null) {
                    body.append(key, data[key]);
                }
            });
        }

        body.append('action', action);
        body.append('nonce', nonce);

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
        var tone = type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'success';
        toastEl.className =
            'toast align-items-center text-bg-' + tone + ' border-0';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body"></div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>';

        toastEl.querySelector('.toast-body').textContent = message;
        toastContainer.appendChild(toastEl);

        var toast = ui.toast(toastEl, { delay: 4000 });
        if (toast) {
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }
    }

    function clearFormError() {
        if (formError) {
            formError.classList.add('d-none');
            formError.textContent = '';
        }
        if (formWarning) {
            formWarning.classList.add('d-none');
            formWarning.textContent = '';
        }
        if (ui && form) {
            ui.resetFormValidation(form);
        }
    }

    function showFormWarning(message) {
        if (!formWarning) {
            return;
        }
        formWarning.textContent = message;
        formWarning.classList.remove('d-none');
    }

    function isEditMode() {
        if (!form) {
            return false;
        }

        var idEl = form.querySelector('[name="id"]');
        return idEl && String(idEl.value || '').trim() !== '';
    }

    function toggleManagerScopeField() {
        if (!managerScopeWrap || !roleSelect) {
            return;
        }

        var show = isEditMode() && roleSelect.value === ROLE_MANAGER;
        managerScopeWrap.classList.toggle('d-none', !show);

        if (!show && managerScopeSelect) {
            managerScopeSelect.value = SCOPE_MODE_AUTO;
        }
    }

    function showFormError(message) {
        if (!formError) {
            return;
        }
        formError.textContent = message;
        formError.classList.remove('d-none');
    }

    function getUserFromRow(id) {
        var row = tableBody.querySelector('tr[data-user-id="' + id + '"]');
        if (!row || !row.dataset.user) {
            return null;
        }

        try {
            return JSON.parse(row.dataset.user);
        } catch (e) {
            return null;
        }
    }

    function emptyRecord() {
        return {
            id: '',
            display_name: '',
            user_email: '',
            user_login: '',
            role: '',
            phone: '',
            status: 'active',
            manager_scope_mode: SCOPE_MODE_AUTO,
        };
    }

    function setCreateMode(isCreate) {
        if (passwordFieldWrap) {
            passwordFieldWrap.classList.toggle('d-none', !isCreate);
        }

        var passwordEl = form ? form.querySelector('[name="password"]') : null;
        if (passwordEl) {
            passwordEl.required = isCreate;
            passwordEl.value = '';
        }

        if (loginInput) {
            loginInput.readOnly = !isCreate;
        }

        if (loginHint) {
            loginHint.classList.toggle('d-none', isCreate);
        }
    }

    function fillForm(record) {
        form.querySelector('[name="id"]').value = record.id ? String(record.id) : '';

        FIELD_NAMES.forEach(function (name) {
            var value = record[name];
            if (name === 'user_email' && record.email != null) {
                value = record.email;
            } else if (name === 'user_login' && record.login) {
                value = record.login;
            }

            if (ui && ui.setFormFieldValue) {
                ui.setFormFieldValue(form, name, value);
                return;
            }

            var el = form.querySelector('[name="' + name + '"]');
            if (!el) {
                return;
            }
            el.value = value == null ? '' : String(value);
        });

        if (managerScopeSelect) {
            if (ui && ui.setSelectValue) {
                ui.setSelectValue(managerScopeSelect, record.manager_scope_mode || SCOPE_MODE_AUTO);
            } else {
                managerScopeSelect.value = record.manager_scope_mode || SCOPE_MODE_AUTO;
            }
        }

        toggleManagerScopeField();
    }

    function setFormModalTitle(text) {
        var title = formModalEl ? formModalEl.querySelector('.modal-title') : null;
        if (title) {
            title.textContent = text;
        }
    }

    function clearLinkFormError() {
        if (linkFormError) {
            linkFormError.classList.add('d-none');
            linkFormError.textContent = '';
        }
        if (linkValidationEl) {
            linkValidationEl.classList.add('d-none');
        }
        if (ui && linkForm) {
            ui.resetFormValidation(linkForm);
        }
    }

    function showLinkFormError(message) {
        if (!linkFormError) {
            return;
        }
        linkFormError.textContent = message;
        linkFormError.classList.remove('d-none');
    }

    function clearLinkSelection() {
        selectedPlatformUser = null;

        if (linkForm) {
            linkForm.querySelector('[name="platform_user_id"]').value = '';
        }

        if (linkSelectedEl) {
            linkSelectedEl.classList.add('d-none');
            linkSelectedEl.textContent = '';
        }

        if (linkResultsEl) {
            linkResultsEl.querySelectorAll('.list-group-item').forEach(function (item) {
                item.classList.remove('active');
            });
        }
    }

    function renderLinkResults(users) {
        if (!linkResultsEl) {
            return;
        }

        linkResultsEl.innerHTML = '';

        if (!users || users.length === 0) {
            linkResultsEl.classList.add('d-none');
            if (linkEmptyEl) {
                linkEmptyEl.classList.remove('d-none');
            }
            return;
        }

        if (linkEmptyEl) {
            linkEmptyEl.classList.add('d-none');
        }

        users.forEach(function (user) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            button.setAttribute('role', 'option');
            button.dataset.platformUserId = String(user.id);
            button.dataset.user = JSON.stringify(user);

            var title = document.createElement('div');
            title.className = 'fw-medium';
            title.textContent = user.display_name || user.label || '';

            var meta = document.createElement('div');
            meta.className = 'text-secondary small';
            meta.textContent = (user.login || '') + (user.email ? ' · ' + user.email : '');

            button.appendChild(title);
            button.appendChild(meta);
            linkResultsEl.appendChild(button);
        });

        linkResultsEl.classList.remove('d-none');
    }

    function searchPlatformUsers(query) {
        post('mf_staffcore_users_search_platform', { q: query })
            .then(function (result) {
                if (!result.success) {
                    renderLinkResults([]);
                    return;
                }

                renderLinkResults(result.data && result.data.users ? result.data.users : []);
            })
            .catch(function () {
                renderLinkResults([]);
            });
    }

    function schedulePlatformUserSearch() {
        if (!linkSearchInput) {
            return;
        }

        if (linkSearchTimer) {
            clearTimeout(linkSearchTimer);
        }

        linkSearchTimer = setTimeout(function () {
            searchPlatformUsers(linkSearchInput.value.trim());
        }, 300);
    }

    function selectPlatformUser(user) {
        selectedPlatformUser = user;

        if (linkForm) {
            linkForm.querySelector('[name="platform_user_id"]').value = String(user.id);
        }

        if (linkSelectedEl) {
            linkSelectedEl.textContent =
                'Selected: ' + (user.label || user.display_name || user.login || '');
            linkSelectedEl.classList.remove('d-none');
        }

        if (linkValidationEl) {
            linkValidationEl.classList.add('d-none');
        }

        if (linkResultsEl) {
            linkResultsEl.querySelectorAll('.list-group-item').forEach(function (item) {
                var isActive = item.dataset.platformUserId === String(user.id);
                item.classList.toggle('active', isActive);
            });
        }
    }

    function openLinkModal() {
        clearLinkFormError();
        clearLinkSelection();

        if (linkForm) {
            linkForm.querySelector('[name="role"]').value = '';
            linkForm.querySelector('[name="phone"]').value = '';
            linkForm.querySelector('[name="status"]').value = 'active';
        }

        if (linkSearchInput) {
            linkSearchInput.value = '';
        }

        renderLinkResults([]);
        if (linkEmptyEl) {
            linkEmptyEl.classList.add('d-none');
        }

        if (linkModal) {
            linkModal.show();
        }

        searchPlatformUsers('');
    }

    function applyLinkValidationRules() {
        var roleEl = linkForm ? linkForm.querySelector('[name="role"]') : null;
        if (roleEl) {
            roleEl.setCustomValidity(roleEl.value === '' ? 'Role is required.' : '');
        }

        if (linkValidationEl && linkForm) {
            var hasUser = linkForm.querySelector('[name="platform_user_id"]').value !== '';
            if (!hasUser) {
                linkValidationEl.classList.remove('d-none');
            }
        }
    }

    function collectLinkPayload() {
        return {
            platform_user_id: linkForm.querySelector('[name="platform_user_id"]').value,
            role: linkForm.querySelector('[name="role"]').value.trim(),
            phone: linkForm.querySelector('[name="phone"]').value.trim(),
            status: linkForm.querySelector('[name="status"]').value,
        };
    }

    function submitLink() {
        clearLinkFormError();

        if (!linkForm.querySelector('[name="platform_user_id"]').value) {
            if (linkValidationEl) {
                linkValidationEl.classList.remove('d-none');
            }
            return;
        }

        if (ui && !ui.validateForm(linkForm, { custom: [applyLinkValidationRules] })) {
            return;
        }

        var btn = root.querySelector('[data-action="submit-link"]');
        var spinner = root.querySelector('[data-role="link-spinner"]');
        if (btn) {
            btn.disabled = true;
        }
        if (spinner) {
            spinner.classList.remove('d-none');
        }

        post('mf_staffcore_users_link', collectLinkPayload())
            .then(function (result) {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }

                if (!result.success) {
                    showLinkFormError(
                        result.data && result.data.message ? result.data.message : 'Link failed.',
                    );
                    return;
                }

                if (linkModal) {
                    linkModal.hide();
                }

                window.location.reload();
            })
            .catch(function () {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }
                showLinkFormError('Network error. Please try again.');
            });
    }

    function bulkSelectedCount() {
        return Object.keys(bulkSelectedIds).length;
    }

    function updateBulkSelectionSummary() {
        if (!bulkLinkSelectionSummary) {
            return;
        }

        var count = bulkSelectedCount();
        bulkLinkSelectionSummary.textContent =
            count === 1 ? '1 selected' : count + ' selected';
    }

    function clearBulkLinkFormError() {
        if (bulkLinkFormError) {
            if (ui && ui.clearPlanAwareAlert) {
                ui.clearPlanAwareAlert(bulkLinkFormError);
            } else {
                bulkLinkFormError.classList.add('d-none');
                bulkLinkFormError.textContent = '';
            }
        }
        if (bulkLinkSelectionValidation) {
            bulkLinkSelectionValidation.classList.add('d-none');
        }
        if (ui && bulkLinkForm) {
            ui.resetFormValidation(bulkLinkForm);
        }
    }

    function showBulkLinkFormError(messageOrData) {
        if (ui && ui.showPlanAwareAlert) {
            ui.showPlanAwareAlert(
                bulkLinkFormError,
                typeof messageOrData === 'object' ? messageOrData : { message: messageOrData },
                'Bulk link failed.',
            );
            return;
        }

        if (!bulkLinkFormError) {
            return;
        }

        bulkLinkFormError.textContent =
            typeof messageOrData === 'string' ? messageOrData : 'Bulk link failed.';
        bulkLinkFormError.classList.remove('d-none');
    }

    function resetBulkSelection() {
        bulkSelectedIds = {};
        updateBulkSelectionSummary();

        if (bulkLinkSelectPageCheckbox) {
            bulkLinkSelectPageCheckbox.checked = false;
            bulkLinkSelectPageCheckbox.indeterminate = false;
        }
    }

    function syncBulkPageSelectCheckbox() {
        if (!bulkLinkSelectPageCheckbox || !bulkLinkTableBody) {
            return;
        }

        var checkboxes = bulkLinkTableBody.querySelectorAll('[data-role="bulk-link-row-checkbox"]');
        if (checkboxes.length === 0) {
            bulkLinkSelectPageCheckbox.checked = false;
            bulkLinkSelectPageCheckbox.indeterminate = false;
            return;
        }

        var checkedCount = 0;
        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked) {
                checkedCount += 1;
            }
        });

        bulkLinkSelectPageCheckbox.checked = checkedCount === checkboxes.length;
        bulkLinkSelectPageCheckbox.indeterminate =
            checkedCount > 0 && checkedCount < checkboxes.length;
    }

    function renderBulkPagination(pagination) {
        bulkPagination = pagination || null;

        if (!bulkLinkPaginationEl || !bulkLinkPaginationSummary || !bulkLinkPaginationList) {
            return;
        }

        if (!pagination || pagination.total <= 0) {
            bulkLinkPaginationEl.classList.add('d-none');
            bulkLinkPaginationList.innerHTML = '';
            bulkLinkPaginationSummary.textContent = '';
            return;
        }

        var from = (pagination.page - 1) * pagination.per_page + 1;
        var to = Math.min(pagination.page * pagination.per_page, pagination.total);
        bulkLinkPaginationSummary.textContent =
            'Showing ' + from + '–' + to + ' of ' + pagination.total;

        bulkLinkPaginationList.innerHTML = '';

        if (pagination.total_pages <= 1) {
            bulkLinkPaginationEl.classList.remove('d-none');
            return;
        }

        function addPageItem(page, label, disabled, active) {
            var li = document.createElement('li');
            li.className =
                'page-item' +
                (disabled ? ' disabled' : '') +
                (active ? ' active' : '');

            if (disabled || active) {
                var span = document.createElement('span');
                span.className = 'page-link';
                span.textContent = label;
                li.appendChild(span);
            } else {
                var link = document.createElement('button');
                link.type = 'button';
                link.className = 'page-link';
                link.textContent = label;
                link.setAttribute('data-action', 'bulk-link-page');
                link.setAttribute('data-page', String(page));
                li.appendChild(link);
            }

            bulkLinkPaginationList.appendChild(li);
        }

        addPageItem(
            Math.max(1, pagination.page - 1),
            '‹',
            pagination.page <= 1,
            false,
        );

        for (var page = 1; page <= pagination.total_pages; page += 1) {
            addPageItem(page, String(page), false, page === pagination.page);
        }

        addPageItem(
            Math.min(pagination.total_pages, pagination.page + 1),
            '›',
            pagination.page >= pagination.total_pages,
            false,
        );

        bulkLinkPaginationEl.classList.remove('d-none');
    }

    function renderBulkCandidates(candidates, pagination) {
        if (!bulkLinkTableBody) {
            return;
        }

        bulkLinkTableBody.innerHTML = '';
        renderBulkPagination(pagination);

        if (!candidates || candidates.length === 0) {
            var emptyRow = document.createElement('tr');
            emptyRow.setAttribute('data-role', 'bulk-link-empty-row');
            emptyRow.innerHTML =
                '<td colspan="4" class="text-secondary text-center py-4">No candidates found.</td>';
            bulkLinkTableBody.appendChild(emptyRow);
            syncBulkPageSelectCheckbox();
            return;
        }

        candidates.forEach(function (candidate) {
            var id = String(candidate.platform_user_id);
            var row = document.createElement('tr');
            row.dataset.platformUserId = id;

            var checkCell = document.createElement('td');
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input m-0 align-middle';
            checkbox.setAttribute('data-role', 'bulk-link-row-checkbox');
            checkbox.checked = !!bulkSelectedIds[id];
            checkbox.addEventListener('change', function () {
                if (checkbox.checked) {
                    bulkSelectedIds[id] = true;
                } else {
                    delete bulkSelectedIds[id];
                }
                updateBulkSelectionSummary();
                syncBulkPageSelectCheckbox();
                if (bulkLinkSelectionValidation && bulkSelectedCount() > 0) {
                    bulkLinkSelectionValidation.classList.add('d-none');
                }
            });
            checkCell.appendChild(checkbox);

            var userCell = document.createElement('td');
            var userTitle = document.createElement('div');
            userTitle.className = 'fw-medium';
            userTitle.textContent = candidate.display_name || candidate.login || '';
            var userMeta = document.createElement('div');
            userMeta.className = 'text-secondary small';
            userMeta.textContent =
                (candidate.login || '') +
                (candidate.email ? ' · ' + candidate.email : '');
            userCell.appendChild(userTitle);
            userCell.appendChild(userMeta);

            var employeeCell = document.createElement('td');
            if (candidate.has_employee) {
                employeeCell.textContent = candidate.department_name
                    ? 'Yes · ' + candidate.department_name
                    : 'Yes';
            } else {
                employeeCell.textContent = '—';
            }

            var roleCell = document.createElement('td');
            roleCell.textContent = candidate.platform_role_label || '—';

            row.appendChild(checkCell);
            row.appendChild(userCell);
            row.appendChild(employeeCell);
            row.appendChild(roleCell);
            bulkLinkTableBody.appendChild(row);
        });

        syncBulkPageSelectCheckbox();
    }

    function loadBulkCandidates(page) {
        if (!bulkLinkForm) {
            return;
        }

        bulkCurrentPage = page || 1;

        if (bulkLinkLoadingEl) {
            bulkLinkLoadingEl.classList.remove('d-none');
        }

        post('mf_staffcore_users_list_link_candidates', {
            source: bulkLinkSourceSelect ? bulkLinkSourceSelect.value : 'unlinked_with_employee',
            search: bulkLinkSearchInput ? bulkLinkSearchInput.value.trim() : '',
            page: bulkCurrentPage,
            per_page: 10,
        })
            .then(function (result) {
                if (bulkLinkLoadingEl) {
                    bulkLinkLoadingEl.classList.add('d-none');
                }

                if (!result.success) {
                    renderBulkCandidates([], null);
                    showBulkLinkFormError(
                        result.data && typeof result.data === 'object'
                            ? result.data
                            : { message: 'Could not load candidates.' },
                    );
                    return;
                }

                clearBulkLinkFormError();
                renderBulkCandidates(
                    result.data && result.data.candidates ? result.data.candidates : [],
                    result.data && result.data.pagination ? result.data.pagination : null,
                );
            })
            .catch(function () {
                if (bulkLinkLoadingEl) {
                    bulkLinkLoadingEl.classList.add('d-none');
                }
                renderBulkCandidates([], null);
                showBulkLinkFormError('Network error. Please try again.');
            });
    }

    function scheduleBulkCandidateSearch() {
        if (bulkLinkSearchTimer) {
            clearTimeout(bulkLinkSearchTimer);
        }

        bulkLinkSearchTimer = setTimeout(function () {
            loadBulkCandidates(1);
        }, 300);
    }

    function openBulkLinkModal() {
        clearBulkLinkFormError();
        resetBulkSelection();
        bulkCurrentPage = 1;

        if (bulkLinkForm) {
            bulkLinkForm.querySelector('[name="role"]').value = '';
            bulkLinkForm.querySelector('[name="phone"]').value = '';
            bulkLinkForm.querySelector('[name="status"]').value = 'active';
        }

        if (bulkLinkSourceSelect) {
            bulkLinkSourceSelect.value = 'unlinked_with_employee';
        }

        if (bulkLinkSearchInput) {
            bulkLinkSearchInput.value = '';
        }

        renderBulkCandidates([], null);

        if (bulkLinkModal) {
            bulkLinkModal.show();
        }

        loadBulkCandidates(1);
    }

    function toggleBulkPageSelection(checked) {
        if (!bulkLinkTableBody) {
            return;
        }

        bulkLinkTableBody.querySelectorAll('[data-role="bulk-link-row-checkbox"]').forEach(function (checkbox) {
            var row = checkbox.closest('tr');
            var id = row ? row.dataset.platformUserId : '';

            checkbox.checked = checked;

            if (!id) {
                return;
            }

            if (checked) {
                bulkSelectedIds[id] = true;
            } else {
                delete bulkSelectedIds[id];
            }
        });

        updateBulkSelectionSummary();
        syncBulkPageSelectCheckbox();

        if (bulkLinkSelectionValidation && bulkSelectedCount() > 0) {
            bulkLinkSelectionValidation.classList.add('d-none');
        }
    }

    function applyBulkLinkValidationRules() {
        var roleEl = bulkLinkForm ? bulkLinkForm.querySelector('[name="role"]') : null;
        if (roleEl) {
            roleEl.setCustomValidity(roleEl.value === '' ? 'Role is required.' : '');
        }

        if (bulkLinkSelectionValidation && bulkSelectedCount() === 0) {
            bulkLinkSelectionValidation.classList.remove('d-none');
        }
    }

    function collectBulkLinkPayload() {
        return {
            platform_user_ids: JSON.stringify(Object.keys(bulkSelectedIds).map(Number)),
            role: bulkLinkForm.querySelector('[name="role"]').value.trim(),
            phone: bulkLinkForm.querySelector('[name="phone"]').value.trim(),
            status: bulkLinkForm.querySelector('[name="status"]').value,
        };
    }

    function submitBulkLink() {
        clearBulkLinkFormError();

        if (bulkSelectedCount() === 0) {
            if (bulkLinkSelectionValidation) {
                bulkLinkSelectionValidation.classList.remove('d-none');
            }
            return;
        }

        if (ui && !ui.validateForm(bulkLinkForm, { custom: [applyBulkLinkValidationRules] })) {
            return;
        }

        var btn = root.querySelector('[data-action="submit-bulk-link"]');
        var spinner = root.querySelector('[data-role="bulk-link-spinner"]');
        if (btn) {
            btn.disabled = true;
        }
        if (spinner) {
            spinner.classList.remove('d-none');
        }

        post('mf_staffcore_users_bulk_link', collectBulkLinkPayload())
            .then(function (result) {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }

                if (!result.success) {
                    showBulkLinkFormError(
                        result.data && typeof result.data === 'object'
                            ? result.data
                            : { message: 'Bulk link failed.' },
                    );
                    return;
                }

                if (bulkLinkModal) {
                    bulkLinkModal.hide();
                }

                showToast(result.data.message || 'Users linked.', 'success');
                window.location.reload();
            })
            .catch(function () {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }
                showBulkLinkFormError('Network error. Please try again.');
            });
    }

    function openCreateModal() {
        clearFormError();
        fillForm(emptyRecord());
        setCreateMode(true);
        setFormModalTitle('Add User');
        toggleManagerScopeField();
        if (formModal) {
            formModal.show();
        }
    }

    function openEditModal(id) {
        clearFormError();
        var record = getUserFromRow(id);
        if (!record) {
            showToast('Could not load user from the table.', 'error');
            return;
        }

        fillForm(record);
        setCreateMode(false);
        setFormModalTitle('Edit User');
        if (formModal) {
            formModal.show();
        }
    }

    function applyClientValidationRules() {
        var nameEl = form.querySelector('[name="display_name"]');
        if (nameEl) {
            nameEl.setCustomValidity(nameEl.value.trim() === '' ? 'Display name is required.' : '');
        }

        var emailEl = form.querySelector('[name="user_email"]');
        if (emailEl) {
            emailEl.setCustomValidity(
                emailEl.value.trim() === ''
                    ? 'Email is required.'
                    : emailEl.validity.typeMismatch
                      ? 'Enter a valid email.'
                      : '',
            );
        }

        var loginEl = form.querySelector('[name="user_login"]');
        if (loginEl && !loginEl.readOnly) {
            loginEl.setCustomValidity(loginEl.value.trim() === '' ? 'Username is required.' : '');
        }

        var roleEl = form.querySelector('[name="role"]');
        if (roleEl) {
            roleEl.setCustomValidity(roleEl.value === '' ? 'Role is required.' : '');
        }

        var passwordEl = form.querySelector('[name="password"]');
        if (passwordEl && passwordEl.required) {
            passwordEl.setCustomValidity(
                passwordEl.value.length > 0 && passwordEl.value.length < 8
                    ? 'Password must be at least 8 characters.'
                    : passwordEl.value === ''
                      ? 'Password is required.'
                      : '',
            );
        }
    }

    function collectFormPayload() {
        var payload = { id: form.querySelector('[name="id"]').value };

        FIELD_NAMES.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el) {
                payload[name] = el.value.trim();
            }
        });

        var passwordEl = form.querySelector('[name="password"]');
        if (passwordEl && passwordEl.value) {
            payload.password = passwordEl.value;
        }

        if (isEditMode() && roleSelect && roleSelect.value === ROLE_MANAGER && managerScopeSelect) {
            payload.manager_scope_mode = managerScopeSelect.value || SCOPE_MODE_AUTO;
        }

        return payload;
    }

    function submitForm() {
        clearFormError();

        if (ui && !ui.validateForm(form, { custom: [applyClientValidationRules] })) {
            return;
        }

        var btn = root.querySelector('[data-action="submit-form"]');
        var spinner = root.querySelector('[data-role="submit-spinner"]');
        if (btn) {
            btn.disabled = true;
        }
        if (spinner) {
            spinner.classList.remove('d-none');
        }

        post('mf_staffcore_users_save', collectFormPayload())
            .then(function (result) {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }

                if (!result.success) {
                    showFormError(result.data && result.data.message ? result.data.message : 'Save failed.');
                    return;
                }

                if (result.data && result.data.warning) {
                    showFormWarning(result.data.warning);
                }

                if (formModal) {
                    formModal.hide();
                }

                if (result.data && result.data.warning) {
                    showToast(result.data.warning, 'warning');
                } else if (result.data && result.data.message) {
                    showToast(result.data.message, 'success');
                }

                window.location.reload();
            })
            .catch(function () {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }
                showFormError('Network error. Please try again.');
            });
    }

    function openPasswordModal(id, label) {
        if (passwordForm) {
            passwordForm.querySelector('[name="id"]').value = id;
            if (passwordUserLabelEl) {
                passwordUserLabelEl.textContent = label;
            }
            passwordForm.querySelector('[name="password"]').value = '';
            passwordForm.querySelector('[name="password_confirm"]').value = '';
        }
        if (passwordError) {
            passwordError.classList.add('d-none');
            passwordError.textContent = '';
        }
        if (ui && passwordForm) {
            ui.resetFormValidation(passwordForm);
        }
        if (passwordModal) {
            passwordModal.show();
        }
    }

    function applyPasswordValidation() {
        var passEl = passwordForm.querySelector('[name="password"]');
        var confirmEl = passwordForm.querySelector('[name="password_confirm"]');

        if (passEl) {
            passEl.setCustomValidity(
                passEl.value.length < 8 ? 'Password must be at least 8 characters.' : '',
            );
        }

        if (confirmEl && passEl) {
            confirmEl.setCustomValidity(
                confirmEl.value !== passEl.value ? 'Passwords must match.' : '',
            );
        }
    }

    function submitPassword() {
        if (passwordError) {
            passwordError.classList.add('d-none');
        }

        if (ui && !ui.validateForm(passwordForm, { custom: [applyPasswordValidation] })) {
            return;
        }

        var spinner = root.querySelector('[data-role="password-spinner"]');
        var btn = root.querySelector('[data-action="submit-password"]');
        if (btn) {
            btn.disabled = true;
        }
        if (spinner) {
            spinner.classList.remove('d-none');
        }

        post('mf_staffcore_users_change_password', {
            id: passwordForm.querySelector('[name="id"]').value,
            password: passwordForm.querySelector('[name="password"]').value,
            password_confirm: passwordForm.querySelector('[name="password_confirm"]').value,
        })
            .then(function (result) {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }

                if (!result.success) {
                    if (passwordError) {
                        passwordError.textContent =
                            result.data && result.data.message ? result.data.message : 'Update failed.';
                        passwordError.classList.remove('d-none');
                    }
                    return;
                }

                if (passwordModal) {
                    passwordModal.hide();
                }

                showToast(result.data.message || 'Password updated.', 'success');
            })
            .catch(function () {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }
                if (passwordError) {
                    passwordError.textContent = 'Network error. Please try again.';
                    passwordError.classList.remove('d-none');
                }
            });
    }

    function openDeleteModal(id, label) {
        pendingDeleteId = id;
        if (deleteLabelEl) {
            deleteLabelEl.textContent = label;
        }
        if (deleteError) {
            deleteError.classList.add('d-none');
        }
        if (deleteModal) {
            deleteModal.show();
        }
    }

    function confirmDelete() {
        if (!pendingDeleteId) {
            return;
        }

        var btn = root.querySelector('[data-action="confirm-delete"]');
        var spinner = root.querySelector('[data-role="delete-spinner"]');
        if (btn) {
            btn.disabled = true;
        }
        if (spinner) {
            spinner.classList.remove('d-none');
        }

        post('mf_staffcore_users_delete', { id: pendingDeleteId })
            .then(function (result) {
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }

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
                if (btn) {
                    btn.disabled = false;
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }
                if (deleteError) {
                    deleteError.textContent = 'Network error. Please try again.';
                    deleteError.classList.remove('d-none');
                }
            });
    }

    function resetLoginAttempts(id) {
        post('mf_staffcore_users_reset_login', { id: id })
            .then(function (result) {
                if (!result.success) {
                    showToast(
                        result.data && result.data.message ? result.data.message : 'Reset failed.',
                        'error',
                    );
                    return;
                }

                showToast(result.data.message || 'Login attempts reset.', 'success');
                window.location.reload();
            })
            .catch(function () {
                showToast('Network error. Please try again.', 'error');
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

        if (action === 'open-link') {
            openLinkModal();
            return;
        }

        if (action === 'open-bulk-link') {
            openBulkLinkModal();
            return;
        }

        if (action === 'submit-link') {
            submitLink();
            return;
        }

        if (action === 'submit-bulk-link') {
            submitBulkLink();
            return;
        }

        if (action === 'bulk-select-page') {
            toggleBulkPageSelection(target.checked);
            return;
        }

        if (action === 'bulk-link-page') {
            loadBulkCandidates(parseInt(target.getAttribute('data-page') || '1', 10));
            return;
        }

        if (action === 'open-edit') {
            openEditModal(target.getAttribute('data-id'));
            return;
        }

        if (action === 'open-password') {
            openPasswordModal(target.getAttribute('data-id'), target.getAttribute('data-label') || '');
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

        if (action === 'submit-password') {
            submitPassword();
            return;
        }

        if (action === 'confirm-delete') {
            confirmDelete();
            return;
        }

        if (action === 'reset-login-attempts') {
            resetLoginAttempts(target.getAttribute('data-id'));
        }
    });

    if (linkSearchInput) {
        linkSearchInput.addEventListener('input', function () {
            clearLinkSelection();
            schedulePlatformUserSearch();
        });
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', toggleManagerScopeField);
    }

    if (bulkLinkSearchInput) {
        bulkLinkSearchInput.addEventListener('input', function () {
            scheduleBulkCandidateSearch();
        });
    }

    if (bulkLinkSourceSelect) {
        bulkLinkSourceSelect.addEventListener('change', function () {
            loadBulkCandidates(1);
        });
    }

    if (linkResultsEl) {
        linkResultsEl.addEventListener('click', function (event) {
            var item = event.target.closest('.list-group-item');
            if (!item || !item.dataset.user) {
                return;
            }

            try {
                selectPlatformUser(JSON.parse(item.dataset.user));
            } catch (e) {
                return;
            }
        });
    }
})();
