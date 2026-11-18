(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="employees"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';

    var formModalEl = document.getElementById('staffcore-employee-form-modal');
    var viewModalEl = document.getElementById('staffcore-employee-view-modal');
    var deleteModalEl = document.getElementById('staffcore-employee-delete-modal');
    var tableBody = root.querySelector('[data-role="employees-table"] tbody');
    var form = root.querySelector('[data-role="employee-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var deleteError = root.querySelector('[data-role="delete-error"]');
    var deleteNameEl = root.querySelector('[data-role="delete-name"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');
    var submitBtn = root.querySelector('[data-action="submit-form"]');
    var departmentSelect = form ? form.querySelector('[name="department_id"]') : null;
    var positionSelect = form ? form.querySelector('[name="position_id"]') : null;
    var positionHint = root.querySelector('[data-role="position-hint"]');
    var allowancesEmpty = root.querySelector('[data-role="allowances-empty"]');
    var allowancesList = root.querySelector('[data-role="allowances-list"]');
    var photoCurrentWrap = root.querySelector('[data-role="photo-current-wrap"]');
    var photoCurrentLink = root.querySelector('[data-role="photo-current-link"]');

    var ui = window.mfStaffCoreUi || null;
    var bulkDelete = ui && ui.initBulkDelete ? ui.initBulkDelete(root) : null;
    var formModal = formModalEl && ui ? ui.modal(formModalEl) : null;
    var viewModal = viewModalEl && ui ? ui.modal(viewModalEl) : null;
    var deleteModal = deleteModalEl && ui ? ui.modal(deleteModalEl) : null;

    var pendingDeleteId = null;
    var positionsByDepartment = {};
    var initialUserAccounts = [];

    try {
        var userAccountsRaw = root.getAttribute('data-user-accounts') || '[]';
        var parsedAccounts = JSON.parse(userAccountsRaw);
        initialUserAccounts = Array.isArray(parsedAccounts) ? parsedAccounts : [];
    } catch (e) {
        initialUserAccounts = [];
    }

    try {
        var positionsRaw = root.getAttribute('data-positions') || '[]';
        var allPositions = JSON.parse(positionsRaw);

        if (Array.isArray(allPositions)) {
            allPositions.forEach(function (position) {
                var deptId = String(position.department_id);

                if (!positionsByDepartment[deptId]) {
                    positionsByDepartment[deptId] = [];
                }

                positionsByDepartment[deptId].push(position);
            });
        }
    } catch (e) {
        positionsByDepartment = {};
    }

    var FIELD_NAMES = [
        'code',
        'department_id',
        'position_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'user_id',
        'birth_date',
        'hire_date',
        'employment_status',
        'employment_type',
        'basic_salary',
        'annual_leave',
        'address',
        'city',
        'province',
        'country',
        'zip_code',
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
        if (ui && ui.clearPlanAwareAlert) {
            ui.clearPlanAwareAlert(formError);
            return;
        }

        if (formError) {
            formError.classList.add('d-none');
            formError.textContent = '';
        }
    }

    function showFormError(messageOrData) {
        if (ui && ui.showPlanAwareAlert && messageOrData && typeof messageOrData === 'object') {
            ui.showPlanAwareAlert(formError, messageOrData, 'Save failed.');
            return;
        }

        if (formError) {
            formError.textContent =
                typeof messageOrData === 'string' ? messageOrData : 'Save failed.';
            formError.classList.remove('d-none');
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function setPositionState(hasDepartment) {
        if (!positionSelect) {
            return;
        }

        positionSelect.disabled = !hasDepartment;

        if (positionHint) {
            positionHint.classList.toggle('d-none', hasDepartment);
        }
    }

    function resetPositionSelect() {
        if (!positionSelect) {
            return;
        }

        positionSelect.innerHTML =
            '<option value="">' + escapeHtml('Select Position') + '</option>';
        if (ui && ui.setSelectValue) {
            ui.setSelectValue(positionSelect, '');
        } else {
            positionSelect.value = '';
        }
        setPositionState(false);
    }

    function populatePositions(departmentId, selectedId, selectedTitle) {
        if (!positionSelect || !departmentId) {
            resetPositionSelect();
            return;
        }

        setPositionState(true);
        positionSelect.innerHTML =
            '<option value="">' + escapeHtml('Select Position') + '</option>';

        var list = positionsByDepartment[String(departmentId)] || [];

        list.forEach(function (position) {
            var option = document.createElement('option');
            option.value = String(position.id);
            option.textContent = position.title;
            positionSelect.appendChild(option);
        });

        if (selectedId) {
            if (ui && ui.setSelectValue) {
                ui.setSelectValue(positionSelect, String(selectedId));

                if (positionSelect.value !== String(selectedId) && selectedTitle) {
                    var fallback = document.createElement('option');
                    fallback.value = String(selectedId);
                    fallback.textContent = selectedTitle;
                    positionSelect.appendChild(fallback);
                    ui.setSelectValue(positionSelect, String(selectedId));
                }
            } else {
                positionSelect.value = String(selectedId);

                if (positionSelect.value !== String(selectedId) && selectedTitle) {
                    var fallbackOption = document.createElement('option');
                    fallbackOption.value = String(selectedId);
                    fallbackOption.textContent = selectedTitle;
                    positionSelect.appendChild(fallbackOption);
                    positionSelect.value = String(selectedId);
                }
            }
        }

        positionSelect.disabled = false;
    }

    function updateAllowancesEmptyState() {
        if (!allowancesEmpty || !allowancesList) {
            return;
        }

        var hasRows = allowancesList.querySelector('[data-role="allowance-row"]') !== null;
        allowancesEmpty.classList.toggle('d-none', hasRows);
        allowancesList.classList.toggle('d-none', !hasRows);
    }

    function addAllowanceRow(allowance) {
        if (!allowancesList) {
            return;
        }

        var row = document.createElement('div');
        row.className = 'row g-2 mb-2 align-items-end';
        row.setAttribute('data-role', 'allowance-row');
        row.innerHTML =
            '<div class="col-md-6">' +
            '<label class="form-label">Name</label>' +
            '<input type="text" class="form-control" data-role="allowance-name" maxlength="255" value="' +
            escapeHtml(allowance && allowance.name ? allowance.name : '') +
            '">' +
            '</div>' +
            '<div class="col-md-5">' +
            '<label class="form-label">Amount</label>' +
            '<input type="number" class="form-control" data-role="allowance-amount" min="0" step="0.01" value="' +
            escapeHtml(allowance && allowance.amount != null ? allowance.amount : '') +
            '">' +
            '</div>' +
            '<div class="col-md-1">' +
            '<button type="button" class="btn btn-outline btn-danger w-100" data-action="remove-allowance" title="Remove">' +
            '<i class="ti ti-trash" aria-hidden="true"></i>' +
            '</button>' +
            '</div>';

        allowancesList.appendChild(row);
        updateAllowancesEmptyState();
    }

    function clearAllowances() {
        if (allowancesList) {
            allowancesList.innerHTML = '';
        }
        updateAllowancesEmptyState();
    }

    function renderAllowances(allowances) {
        clearAllowances();

        if (!allowances || !allowances.length) {
            return;
        }

        allowances.forEach(function (item) {
            addAllowanceRow(item);
        });
    }

    function collectAllowances() {
        if (!allowancesList) {
            return [];
        }

        var items = [];
        allowancesList.querySelectorAll('[data-role="allowance-row"]').forEach(function (row) {
            var nameEl = row.querySelector('[data-role="allowance-name"]');
            var amountEl = row.querySelector('[data-role="allowance-amount"]');
            var name = nameEl ? nameEl.value.trim() : '';
            var amount = amountEl ? parseFloat(amountEl.value) : 0;

            if (name === '') {
                return;
            }

            if (isNaN(amount) || amount < 0) {
                amount = 0;
            }

            items.push({ name: name, amount: amount });
        });

        return items;
    }

    function ensureUserAccountOption(userId, label) {
        if (!form) {
            return;
        }

        var select = form.querySelector('[name="user_id"]');
        if (!select || !userId) {
            return;
        }

        var value = String(userId);
        var exists = false;

        Array.from(select.options).forEach(function (option) {
            if (option.value === value) {
                exists = true;
                if (label && option.textContent !== label) {
                    option.textContent = label;
                }
            }
        });

        if (!exists) {
            var option = document.createElement('option');
            option.value = value;
            option.textContent = label || 'Linked user #' + value;
            select.appendChild(option);
        }

        if (select.tomselect) {
            select.tomselect.addOption({ value: value, text: label || 'Linked user #' + value });
        }
    }

    function destroyUserAccountTomSelect() {
        if (!form) {
            return;
        }

        var select = form.querySelector('[name="user_id"]');
        if (!select || !select.tomselect) {
            return;
        }

        select.tomselect.destroy();
        select.removeAttribute('data-staffcore-search-initialized');
    }

    function populateUserAccountSelect(accounts, selectedUserId, selectedLabel) {
        if (!form) {
            return;
        }

        var select = form.querySelector('[name="user_id"]');
        if (!select) {
            return;
        }

        destroyUserAccountTomSelect();

        var list = Array.isArray(accounts) ? accounts : [];
        var emptyLabel = list.length === 0 ? 'No User Accounts' : 'No linked account';

        select.innerHTML = '<option value="">' + escapeHtml(emptyLabel) + '</option>';

        list.forEach(function (account) {
            if (!account || account.id == null) {
                return;
            }

            var option = document.createElement('option');
            option.value = String(account.id);
            option.textContent = account.label || 'User #' + account.id;
            select.appendChild(option);
        });

        if (selectedUserId) {
            ensureUserAccountOption(
                selectedUserId,
                selectedLabel || 'User #' + selectedUserId,
            );
        }

        var selectedValue = selectedUserId ? String(selectedUserId) : '';

        if (ui && ui.initSearchableSelects) {
            ui.initSearchableSelects(form);
        }

        if (ui && ui.setSelectValue) {
            ui.setSelectValue(select, selectedValue);
        } else {
            select.value = selectedValue;
        }
    }

    function updatePhotoCurrent(url) {
        if (!photoCurrentWrap || !photoCurrentLink) {
            return;
        }

        if (url) {
            photoCurrentWrap.classList.remove('d-none');
            photoCurrentLink.href = url;
            photoCurrentLink.textContent = url.split('/').pop() || url;
            photoCurrentLink.dataset.imageUrl = url;
        } else {
            photoCurrentWrap.classList.add('d-none');
            photoCurrentLink.href = '#';
            photoCurrentLink.textContent = '';
            photoCurrentLink.dataset.imageUrl = '';
        }
    }

    function openPhotoPreview(url, allowDelete) {
        if (!url || !ui || typeof ui.imageLightbox !== 'function') {
            return;
        }

        var employeeId = form.querySelector('[name="id"]').value;

        ui.imageLightbox({
            url: url,
            deleteLabel: 'Remove photo',
            onDelete:
                allowDelete && employeeId
                    ? function (close) {
                          post('mf_staffcore_employee_remove_photo', { id: employeeId })
                              .then(function (result) {
                                  if (!result.success) {
                                      showToast(
                                          result.data && result.data.message
                                              ? result.data.message
                                              : 'Could not remove photo.',
                                          'error',
                                      );
                                      return;
                                  }

                                  close();
                                  form.querySelector('[name="photo_current"]').value = '';
                                  updatePhotoCurrent('');
                                  showToast(result.data.message || 'Photo removed.', 'success');

                                  if (result.data.employee) {
                                      upsertRow(result.data.employee);
                                      fillEmployeeForm(result.data.employee);
                                  }
                              })
                              .catch(function () {
                                  showToast('Network error. Please try again.', 'error');
                              });
                      }
                    : null,
        });
    }

    function statusBadge(status, label) {
        var cls = 'bg-secondary-lt';

        if (status === 'ACTIVE') {
            cls = 'bg-success-lt';
        } else if (status === 'ON_LEAVE') {
            cls = 'bg-warning-lt';
        } else if (status === 'SUSPENDED') {
            cls = 'bg-orange-lt';
        } else if (status === 'TERMINATED') {
            cls = 'bg-danger-lt';
        }

        return '<span class="badge ' + cls + '">' + escapeHtml(label || status) + '</span>';
    }

    function leaveDaysBadge(days, tone) {
        var cls = tone === 'annual' ? 'bg-blue-lt' : 'bg-success-lt';
        var count = parseInt(days, 10) || 0;
        var label = count + ' day' + (count === 1 ? '' : 's');

        return '<span class="badge ' + cls + '">' + escapeHtml(label) + '</span>';
    }

    function departmentBadge(name) {
        if (!name) {
            return '—';
        }

        return '<span class="badge staffcore-department-badge">' + escapeHtml(name) + '</span>';
    }

    function employeeInitials(employee) {
        var first = employee.first_name ? String(employee.first_name).charAt(0).toUpperCase() : '';
        var last = employee.last_name ? String(employee.last_name).charAt(0).toUpperCase() : '';
        var initials = first + last;

        return initials || '?';
    }

    function nameCellHtml(employee) {
        if (ui && ui.personAvatarCellHtml) {
            return ui.personAvatarCellHtml(employee.full_name, employee.photo || '', employee.email || '');
        }

        var html = '<div class="d-flex align-items-center gap-2">';

        if (employee.photo) {
            html +=
                '<span class="avatar avatar-sm rounded" style="background-image: url(\'' +
                escapeHtml(employee.photo) +
                '\')" role="img" aria-hidden="true"></span>';
        } else {
            html += '<span class="avatar avatar-sm rounded">' + escapeHtml(employeeInitials(employee)) + '</span>';
        }

        html += '<div><div class="fw-medium">' + escapeHtml(employee.full_name) + '</div>';
        html += '<div class="text-secondary small">' + escapeHtml(employee.email || '') + '</div>';
        html += '</div></div>';

        return html;
    }

    function positionCellHtml(employee) {
        var title = employee.position_title || '—';
        var html = '<div class="fw-medium">' + escapeHtml(title) + '</div>';

        if (employee.position_level_label) {
            html += '<div class="text-secondary small">(' + escapeHtml(employee.position_level_label) + ')</div>';
        }

        return html;
    }

    function formatHireDate(date) {
        if (!date) {
            return '—';
        }

        return escapeHtml(String(date).substring(0, 10));
    }

    function removeEmptyRow() {
        var emptyRow = tableBody.querySelector('[data-role="empty-row"]');
        if (emptyRow) {
            emptyRow.remove();
        }
    }

    function buildRow(employee) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-employee-id', String(employee.id));
        tr.dataset.employee = JSON.stringify(employee);

        var html = '';

        if (bulkDelete && bulkDelete.enabled) {
            html += bulkDelete.checkboxCellHtml(employee.id);
        }

        html +=
            '<td class="text-secondary">' +
            escapeHtml(employee.code) +
            '</td>' +
            '<td>' +
            nameCellHtml(employee) +
            '</td>' +
            '<td>' +
            departmentBadge(employee.department_name) +
            '</td>' +
            '<td>' +
            positionCellHtml(employee) +
            '</td>' +
            '<td>' +
            leaveDaysBadge(employee.annual_leave, 'annual') +
            '</td>' +
            '<td>' +
            leaveDaysBadge(employee.remaining_leave, 'remaining') +
            '</td>' +
            '<td>' +
            statusBadge(employee.employment_status, employee.employment_status_label) +
            '</td>' +
            '<td class="text-secondary">' +
            formatHireDate(employee.hire_date) +
            '</td>' +
            '<td>' +
            '<div class="btn-list flex-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary" data-action="open-edit" data-id="' +
            employee.id +
            '" title="Edit"><i class="ti ti-edit" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline-success" data-action="open-records" data-id="' +
            employee.id +
            '" title="Records"><i class="ti ti-file-text" aria-hidden="true"></i></button>' +
            '<button type="button" class="btn btn-sm btn-outline btn-danger" data-action="open-delete" data-id="' +
            employee.id +
            '" data-name="' +
            escapeHtml(employee.full_name) +
            '" title="Delete"><i class="ti ti-trash" aria-hidden="true"></i></button>' +
            '</div>' +
            '</td>';

        tr.innerHTML = html;

        return tr;
    }

    function upsertRow(employee) {
        removeEmptyRow();

        var existing = tableBody.querySelector('tr[data-employee-id="' + employee.id + '"]');
        var row = buildRow(employee);

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

    function emptyEmployee() {
        return {
            id: '',
            code: '',
            department_id: '',
            position_id: '',
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            user_id: '',
            birth_date: '',
            hire_date: '',
            employment_status: 'ACTIVE',
            employment_type: '',
            basic_salary: '',
            annual_leave: '0',
            address: '',
            city: '',
            province: '',
            country: '',
            zip_code: '',
            photo: '',
            notes: '',
            allowances: [],
        };
    }

    function fillEmployeeForm(employee, userAccounts) {
        if (userAccounts) {
            populateUserAccountSelect(
                userAccounts,
                employee.user_id,
                employee.system_user_label,
            );
        }

        form.querySelector('[name="id"]').value = employee.id ? String(employee.id) : '';
        form.querySelector('[name="photo_current"]').value = employee.photo || '';

        if (ui && ui.fillFormFields) {
            ui.fillFormFields(form, employee, FIELD_NAMES);
        } else {
            FIELD_NAMES.forEach(function (name) {
                var el = form.querySelector('[name="' + name + '"]');
                if (!el) {
                    return;
                }
                el.value = employee[name] == null ? '' : String(employee[name]);
            });
        }

        if (employee.user_id) {
            if (ui && ui.setFormFieldValue) {
                ui.setFormFieldValue(form, 'user_id', employee.user_id);
            } else {
                form.querySelector('[name="user_id"]').value = String(employee.user_id);
            }
        }

        var fileEl = form.querySelector('[name="photo"]');
        if (fileEl) {
            fileEl.value = '';
        }

        updatePhotoCurrent(employee.photo || '');
        renderAllowances(employee.allowances || []);

        if (employee.department_id) {
            populatePositions(
                employee.department_id,
                employee.position_id,
                employee.position_title || '',
            );
        } else {
            resetPositionSelect();
        }
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

    function getEmployeeFromRow(id) {
        var row = tableBody.querySelector('tr[data-employee-id="' + id + '"]');
        if (!row || !row.dataset.employee) {
            return null;
        }

        try {
            return JSON.parse(row.dataset.employee);
        } catch (e) {
            return null;
        }
    }

    function applyClientValidationRules() {
        var codeEl = form.querySelector('[name="code"]');
        if (codeEl) {
            codeEl.setCustomValidity(codeEl.value.trim() === '' ? 'Employee code is required.' : '');
        }

        var departmentEl = form.querySelector('[name="department_id"]');
        if (departmentEl) {
            departmentEl.setCustomValidity(departmentEl.value === '' ? 'Department is required.' : '');
        }

        var positionEl = form.querySelector('[name="position_id"]');
        if (positionEl) {
            positionEl.setCustomValidity(positionEl.value === '' ? 'Position is required.' : '');
        }

        var firstNameEl = form.querySelector('[name="first_name"]');
        if (firstNameEl) {
            firstNameEl.setCustomValidity(firstNameEl.value.trim() === '' ? 'First name is required.' : '');
        }

        var lastNameEl = form.querySelector('[name="last_name"]');
        if (lastNameEl) {
            lastNameEl.setCustomValidity(lastNameEl.value.trim() === '' ? 'Last name is required.' : '');
        }

        var emailEl = form.querySelector('[name="email"]');
        if (emailEl) {
            emailEl.setCustomValidity(emailEl.value.trim() === '' ? 'Email is required.' : '');
        }

        var hireDateEl = form.querySelector('[name="hire_date"]');
        if (hireDateEl) {
            hireDateEl.setCustomValidity(hireDateEl.value === '' ? 'Hire date is required.' : '');
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

        formData.append('photo_current', form.querySelector('[name="photo_current"]').value || '');
        formData.append('allowances', JSON.stringify(collectAllowances()));

        var fileEl = form.querySelector('[name="photo"]');
        if (fileEl && fileEl.files && fileEl.files[0]) {
            formData.append('photo', fileEl.files[0]);
        }

        return formData;
    }

    function displayValue(value) {
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
            el.textContent = displayValue(value);
        }
    }

    function formatMoneyView(amount, colorClass) {
        if (amount == null || amount === '') {
            return '—';
        }

        var num = parseFloat(amount);

        if (isNaN(num)) {
            return '—';
        }

        return (
            '<span class="text-' +
            colorClass +
            ' fw-medium">$ ' +
            num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
            '</span>'
        );
    }

    function fillViewDetails(employee) {
        setViewField('view-code', employee.code);
        setViewField('view-full-name', employee.full_name);
        setViewField('view-email', employee.email);
        setViewField('view-phone', employee.phone);
        setViewField('view-system-user', employee.system_user_label);
        setViewField('view-department', employee.department_name);
        setViewField('view-position', employee.position_display || employee.position_title);
        setViewField('view-hire-date', employee.hire_date_display || employee.hire_date);
        setViewField(
            'view-status',
            statusBadge(employee.employment_status, employee.employment_status_label),
            true,
        );
        setViewField('view-employment-type', employee.employment_type_label || employee.employment_type);
        setViewField('view-birth-date', employee.birth_date_display || employee.birth_date);
        setViewField('view-address', employee.address);
        setViewField('view-city', employee.city);
        setViewField('view-province', employee.province);
        setViewField('view-country', employee.country);
        setViewField('view-zip-code', employee.zip_code);
        setViewField('view-basic-salary', formatMoneyView(employee.basic_salary, 'success'), true);
        setViewField('view-total-allowances', formatMoneyView(employee.total_allowances, 'info'), true);
        setViewField(
            'view-annual-leave',
            '<span class="text-info fw-medium">' + escapeHtml(employee.annual_leave_display || '') + '</span>',
            true,
        );
        setViewField('view-remaining-leave', employee.remaining_leave_display);
        setViewField('view-notes', employee.notes);
    }

    function showViewModal() {
        if (viewModal) {
            viewModal.show();
        }
    }

    function loadEmployeeDetails(id, onSuccess) {
        post('mf_staffcore_employee_get', { id: id })
            .then(function (result) {
                if (!result.success || !result.data || !result.data.employee) {
                    showToast(
                        result.data && result.data.message
                            ? result.data.message
                            : 'Could not load employee.',
                        'error',
                    );
                    return;
                }

                onSuccess(result.data.employee, result.data.user_accounts || []);
            })
            .catch(function () {
                showToast('Network error. Please try again.', 'error');
            });
    }

    function openViewModal(id) {
        loadEmployeeDetails(id, function (employee) {
            fillViewDetails(employee);
            showViewModal();
        });
    }

    function openCreateModal() {
        clearFormError();
        fillEmployeeForm(emptyEmployee(), initialUserAccounts);
        setFormModalTitle('Add employee');
        showFormModal();
    }

    function openEditModal(id) {
        clearFormError();
        setFormModalTitle('Edit employee');

        loadEmployeeDetails(id, function (employee, userAccounts) {
            fillEmployeeForm(employee, userAccounts);
            showFormModal();
        });
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

        postForm('mf_staffcore_employee_save', formData)
            .then(function (result) {
                setSubmitLoading(false);

                if (!result.success) {
                    showFormError(
                        result.data && typeof result.data === 'object'
                            ? result.data
                            : { message: 'Save failed.' },
                    );
                    return;
                }

                if (formModal) {
                    formModal.hide();
                }

                if (isCreate) {
                    window.location.reload();
                    return;
                }

                upsertRow(result.data.employee);
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

        post('mf_staffcore_employee_delete', { id: pendingDeleteId })
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

        if (action === 'open-records') {
            openViewModal(target.getAttribute('data-id'));
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
            return;
        }

        if (action === 'add-allowance') {
            addAllowanceRow(null);
            return;
        }

        if (action === 'remove-allowance') {
            var row = target.closest('[data-role="allowance-row"]');
            if (row) {
                row.remove();
                updateAllowancesEmptyState();
            }
        }
    });

    if (departmentSelect) {
        departmentSelect.addEventListener('change', function () {
            var departmentId = departmentSelect.value;
            if (!departmentId) {
                resetPositionSelect();
                return;
            }
            populatePositions(departmentId, '', '');
        });
    }

    if (photoCurrentLink) {
        photoCurrentLink.addEventListener('click', function (event) {
            event.preventDefault();
            var url = photoCurrentLink.dataset.imageUrl || photoCurrentLink.href;
            if (!url || url === '#') {
                return;
            }
            openPhotoPreview(url, true);
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitForm();
        });
    }
})();
