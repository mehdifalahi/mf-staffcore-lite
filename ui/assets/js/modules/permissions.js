(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="permissions"]');

    if (!root || typeof mfStaffCoreUi === 'undefined') {
        return;
    }

    var ui = mfStaffCoreUi;
    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';
    var canEdit = root.getAttribute('data-can-edit') === '1';
    var saveBtn = root.querySelector('[data-action="save-permissions"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');
    var dirty = false;

    if (!canEdit || !saveBtn) {
        return;
    }

    function setDirty(value) {
        dirty = value;
        saveBtn.disabled = !dirty;
    }

    function activeRoleId() {
        var activePane = root.querySelector('.tab-pane.active[data-role]');

        return activePane ? activePane.getAttribute('data-role') : '';
    }

    function collectRolePermissions(roleId) {
        var pane = root.querySelector('.tab-pane[data-role="' + roleId + '"]');

        if (!pane) {
            return [];
        }

        var keys = [];
        pane.querySelectorAll('input[data-permission-key]:checked').forEach(function (input) {
            keys.push(input.getAttribute('data-permission-key'));
        });

        return keys;
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

    root.addEventListener('change', function (event) {
        if (event.target.matches('input[data-permission-key]')) {
            setDirty(true);
        }
    });

    saveBtn.addEventListener('click', function () {
        var roleId = activeRoleId();

        if (!roleId) {
            return;
        }

        saveBtn.disabled = true;

        var body = new URLSearchParams();
        body.set('action', 'mf_staffcore_permissions_save');
        body.set('nonce', nonce);
        body.set('role', roleId);
        body.set('permissions', JSON.stringify(collectRolePermissions(roleId)));

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.success) {
                    throw new Error(
                        (payload && payload.data && payload.data.message) ||
                            'Failed to save permissions.',
                    );
                }

                setDirty(false);
                showToast(payload.data.message || 'Saved.', 'success');
            })
            .catch(function (error) {
                showToast(error.message || 'Failed to save permissions.', 'error');
                setDirty(true);
            });
    });
})();
