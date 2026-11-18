/**
 * Shared Tabler / Bootstrap helpers for MF StaffCore admin UI.
 * Tabler 1.x exposes components on window.tabler (not window.bootstrap).
 */
(function (global) {
    'use strict';

    function tabler() {
        return global.tabler || null;
    }

    function ModalClass() {
        var t = tabler();
        if (t && t.Modal) {
            return t.Modal;
        }
        if (global.bootstrap && global.bootstrap.Modal) {
            return global.bootstrap.Modal;
        }
        return null;
    }

    function ToastClass() {
        var t = tabler();
        if (t && t.Toast) {
            return t.Toast;
        }
        if (global.bootstrap && global.bootstrap.Toast) {
            return global.bootstrap.Toast;
        }
        return null;
    }

    /**
     * Keep page scrollbar visible (no body overflow:hidden / padding-right shift in WP admin).
     * @see https://getbootstrap.com/docs/5.3/components/modal/#options — scroll
     */
    var MODAL_DEFAULTS = { scroll: true };

    function patchStaffCoreBodyScrollLock() {
        if (!document.body.classList.contains('mf-staffcore-admin')) {
            return;
        }

        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    /**
     * WordPress admin notices render in #wpbody-content before .wrap, so they sit
     * under the fixed Tabler sidebar. Move them into the main content column.
     */
    function isWordPressAdminNoticeNode(el) {
        if (!el || el.nodeType !== 1) {
            return false;
        }

        if (
            el.id === 'screen-meta'
            || el.id === 'screen-meta-links'
            || el.classList.contains('screen-meta')
            || el.classList.contains('wrap')
        ) {
            return false;
        }

        if (
            el.classList.contains('notice')
            || el.classList.contains('update-nag')
            || el.classList.contains('updated')
            || el.classList.contains('error')
        ) {
            return true;
        }

        return Boolean(
            el.querySelector
            && el.querySelector('.notice, .update-nag, .updated, .error'),
        );
    }

    function OffcanvasClass() {
        var t = tabler();
        if (t && t.Offcanvas) {
            return t.Offcanvas;
        }
        if (global.bootstrap && global.bootstrap.Offcanvas) {
            return global.bootstrap.Offcanvas;
        }
        return null;
    }

    function initMobileNav() {
        var offcanvasEl = document.getElementById('staffcore-mobile-nav');
        if (!offcanvasEl) {
            return;
        }

        var Offcanvas = OffcanvasClass();
        if (!Offcanvas) {
            return;
        }

        offcanvasEl.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                var instance = Offcanvas.getInstance(offcanvasEl);
                if (instance) {
                    instance.hide();
                }
            });
        });
    }

    function relocateWordPressAdminNotices() {
        if (!document.body.classList.contains('mf-staffcore-admin')) {
            return;
        }

        var target = document.getElementById('staffcore-wp-admin-notices');
        var wpContent = document.getElementById('wpbody-content');
        var wrap = wpContent && wpContent.querySelector('.mf-staffcore-wrap');

        if (!target || !wpContent || !wrap) {
            return;
        }

        var node = wpContent.firstElementChild;

        while (node && node !== wrap) {
            var next = node.nextElementSibling;

            if (isWordPressAdminNoticeNode(node)) {
                target.appendChild(node);
            }

            node = next;
        }
    }

    function grantedPermissionKeys() {
        if (!global.mfStaffCorePermissions || !Array.isArray(global.mfStaffCorePermissions.granted)) {
            return null;
        }

        return global.mfStaffCorePermissions.granted;
    }

    function hasPermission(key) {
        var granted = grantedPermissionKeys();

        if (granted === null || !key) {
            return true;
        }

        return granted.indexOf(key) !== -1;
    }

    function applyPermissionElements(root) {
        var scope = root || document;

        if (!scope || !scope.querySelectorAll) {
            return;
        }

        scope.querySelectorAll('[data-staffcore-permission]').forEach(function (el) {
            var key = el.getAttribute('data-staffcore-permission');

            if (!hasPermission(key)) {
                el.classList.add('d-none');
                el.setAttribute('aria-hidden', 'true');
            }
        });
    }

    function parseSearchThreshold(el, fallback) {
        var raw = el ? el.getAttribute('data-staffcore-search-threshold') : null;
        var parsed = raw ? parseInt(raw, 10) : NaN;
        if (!Number.isNaN(parsed) && parsed > 0) {
            return parsed;
        }
        return fallback;
    }

    function resolveSelectPlaceholder(select) {
        if (!select) {
            return '';
        }

        var explicit = select.getAttribute('data-placeholder') || '';
        if (explicit.trim() !== '') {
            return explicit;
        }

        if (select.options && select.options.length > 0) {
            var first = select.options[0];
            if (first && String(first.value || '').trim() === '') {
                return String(first.textContent || '').trim();
            }
        }

        return '';
    }

    function createTomSelect(select) {
        if (!global.TomSelect || !select) {
            return null;
        }

        if (select.tomselect) {
            return select.tomselect;
        }

        var placeholder = resolveSelectPlaceholder(select);
        var instance = new global.TomSelect(select, {
            create: false,
            allowEmptyOption: true,
            maxOptions: 500,
            hidePlaceholder: false,
            placeholder: placeholder,
            copyClassesToDropdown: false,
        });

        if (instance.wrapper) {
            instance.wrapper.classList.remove('form-select');
            instance.wrapper.classList.add('w-100');
        }

        if (instance.dropdown) {
            instance.dropdown.classList.remove('form-select');
        }

        select.setAttribute('data-staffcore-search-initialized', '1');
        return instance;
    }

    function isInsideHiddenModal(el) {
        if (!el || !el.closest) {
            return false;
        }

        var modal = el.closest('.modal');
        return Boolean(modal && !modal.classList.contains('show'));
    }

    function selectShouldUseTomSelect(select) {
        if (!select || !global.TomSelect) {
            return false;
        }

        var mode = (select.getAttribute('data-staffcore-search') || 'auto').toLowerCase();
        if (mode === 'off') {
            return false;
        }

        if (mode === 'always') {
            return true;
        }

        var threshold = parseSearchThreshold(select, 20);
        var optionCount = select.options ? select.options.length : 0;
        return optionCount > threshold;
    }

    function ensureTomSelect(select) {
        if (!select || select.tomselect) {
            return select ? select.tomselect : null;
        }

        if (!selectShouldUseTomSelect(select)) {
            return null;
        }

        return createTomSelect(select);
    }

    function setSelectValue(select, value) {
        if (!select) {
            return;
        }

        var str = value == null ? '' : String(value);
        select.value = str;

        var ts = ensureTomSelect(select);
        if (!ts) {
            return;
        }

        if (str === '') {
            ts.clear(true);
            return;
        }

        ts.setValue(str, true);
    }

    /**
     * Sync native select values to Tom Select UI (e.g. after modal open).
     *
     * @param {Document|HTMLElement} root
     */
    function syncFormSelects(root) {
        if (!root || !root.querySelectorAll) {
            return;
        }

        root.querySelectorAll('select.form-select:not([multiple])').forEach(function (select) {
            ensureTomSelect(select);

            if (!select.tomselect) {
                return;
            }

            var str = select.value == null ? '' : String(select.value);
            if (str === '') {
                select.tomselect.clear(true);
                return;
            }

            select.tomselect.setValue(str, true);
        });
    }

    /**
     * @param {HTMLFormElement} form
     * @param {string} name
     * @param {*} value
     */
    function setFormFieldValue(form, name, value) {
        if (!form || !name) {
            return;
        }

        var el = form.querySelector('[name="' + name + '"]');
        if (!el) {
            return;
        }

        if (el.tagName === 'SELECT') {
            setSelectValue(el, value);
            return;
        }

        el.value = value == null ? '' : String(value);
    }

    /**
     * @param {HTMLFormElement} form
     * @param {Record<string, *>} data
     * @param {string[]} fieldNames
     */
    function fillFormFields(form, data, fieldNames) {
        if (!form || !data || !fieldNames) {
            return;
        }

        fieldNames.forEach(function (name) {
            if (!Object.prototype.hasOwnProperty.call(data, name)) {
                return;
            }

            setFormFieldValue(form, name, data[name]);
        });
    }

    function initSearchableSelects(root) {
        if (!global.TomSelect) {
            return;
        }

        var scope = root && root.querySelectorAll ? root : document;
        var defaultThreshold = parseSearchThreshold(scope.documentElement || null, 20);
        var selects = scope.querySelectorAll(
            'select.form-select:not([multiple]):not([data-staffcore-search="off"])',
        );

        selects.forEach(function (select) {
            if (select.getAttribute('data-staffcore-search-initialized') === '1' || select.tomselect) {
                return;
            }

            if (isInsideHiddenModal(select)) {
                return;
            }

            var mode = (select.getAttribute('data-staffcore-search') || 'auto').toLowerCase();
            var threshold = parseSearchThreshold(select, defaultThreshold);
            var optionCount = select.options ? select.options.length : 0;
            var shouldInit = mode === 'always' || (mode === 'auto' && optionCount > threshold);

            if (!shouldInit) {
                return;
            }

            createTomSelect(select);
        });
    }

    if (typeof document !== 'undefined') {
        function runStaffCoreAdminBoot() {
            relocateWordPressAdminNotices();
            initMobileNav();
            applyPermissionElements(document.getElementById('staffcore-app'));
            initSearchableSelects(document.getElementById('staffcore-app') || document);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runStaffCoreAdminBoot);
        } else {
            runStaffCoreAdminBoot();
        }

        document.addEventListener('show.bs.modal', function (event) {
            if (!event.target || !event.target.hasAttribute('data-staffcore-modal')) {
                return;
            }

            patchStaffCoreBodyScrollLock();
            requestAnimationFrame(patchStaffCoreBodyScrollLock);
        });

        document.addEventListener('shown.bs.modal', patchStaffCoreBodyScrollLock);

        document.addEventListener('shown.bs.modal', function (event) {
            if (!event.target || !event.target.hasAttribute('data-staffcore-modal')) {
                return;
            }
            initSearchableSelects(event.target);
            syncFormSelects(event.target);
        });

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-action="staffcore-copy-input"]');

            if (!button) {
                return;
            }

            event.preventDefault();
            copyReadonlyInput(button);
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function copyReadonlyInput(button) {
        var group = button.closest('.input-group');
        var input = group ? group.querySelector('input.form-control') : null;
        var text = input ? input.value : '';

        if (!text) {
            return;
        }

        function markCopied() {
            var original = button.textContent;
            button.textContent = button.getAttribute('data-copied-label') || 'Copied!';
            button.disabled = true;

            window.setTimeout(function () {
                button.textContent = original;
                button.disabled = false;
            }, 2000);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(markCopied);
            return;
        }

        if (input) {
            input.focus();
            input.select();
            document.execCommand('copy');
            markCopied();
        }
    }

    global.mfStaffCoreUi = {
        modal: function (element) {
            var Modal = ModalClass();
            if (!element || !Modal) {
                return null;
            }

            var existing = Modal.getInstance(element);
            if (existing) {
                existing.dispose();
            }

            return Modal.getOrCreateInstance(element, MODAL_DEFAULTS);
        },

        showModal: function (element) {
            var instance = this.modal(element);
            if (instance) {
                instance.show();
            }
            return instance;
        },

        hideModal: function (element) {
            var instance = this.modal(element);
            if (instance) {
                instance.hide();
            }
        },

        toast: function (element, options) {
            var Toast = ToastClass();
            if (!element || !Toast) {
                return null;
            }
            return Toast.getOrCreateInstance(element, options || {});
        },

        /**
         * Bootstrap 5 client validation (Tabler has no built-in validator).
         * Use on modal forms before AJAX; server validation remains mandatory.
         *
         * @param {HTMLFormElement} form
         * @param {{ custom?: Array<(HTMLFormElement) => void> }} [options]
         * @returns {boolean}
         */
        validateForm: function (form, options) {
            if (!form) {
                return false;
            }

            options = options || {};

            if (Array.isArray(options.custom)) {
                options.custom.forEach(function (fn) {
                    fn(form);
                });
            }

            var valid = form.checkValidity();
            form.classList.add('was-validated');

            if (!valid) {
                var firstInvalid = form.querySelector(':invalid');
                if (firstInvalid && typeof firstInvalid.focus === 'function') {
                    firstInvalid.focus();
                }
            }

            return valid;
        },

        /** @param {HTMLFormElement} form */
        resetFormValidation: function (form) {
            if (!form) {
                return;
            }

            form.classList.remove('was-validated');

            form.querySelectorAll('input.form-control, textarea.form-control, select.form-select').forEach(function (field) {
                if (typeof field.setCustomValidity === 'function') {
                    field.setCustomValidity('');
                }
            });
        },

        /**
         * Simple image lightbox (above modals in StaffCore admin).
         *
         * @param {{ url: string, deleteLabel?: string, onDelete?: (close: () => void) => void }} options
         */
        imageLightbox: function (options) {
            options = options || {};
            var url = options.url || '';

            if (!url) {
                return null;
            }

            var existing = document.querySelector('.staffcore-image-lightbox');
            if (existing) {
                existing.remove();
            }

            var overlay = document.createElement('div');
            overlay.className = 'staffcore-image-lightbox';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');

            var panel = document.createElement('div');
            panel.className = 'staffcore-image-lightbox-panel';

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn btn-close staffcore-image-lightbox-close';
            closeBtn.setAttribute('aria-label', 'Close');

            var img = document.createElement('img');
            img.className = 'staffcore-image-lightbox-img';
            img.src = url;
            img.alt = '';

            panel.appendChild(closeBtn);
            panel.appendChild(img);

            if (typeof options.onDelete === 'function') {
                var actions = document.createElement('div');
                actions.className = 'staffcore-image-lightbox-actions';

                var deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'btn btn-outline btn-danger btn-sm';
                deleteBtn.textContent = options.deleteLabel || 'Remove image';
                deleteBtn.addEventListener('click', function () {
                    options.onDelete(close);
                });

                actions.appendChild(deleteBtn);
                panel.appendChild(actions);
            }

            overlay.appendChild(panel);
            document.body.appendChild(overlay);

            function close() {
                document.removeEventListener('keydown', onKeydown);
                overlay.remove();
            }

            function onKeydown(event) {
                if (event.key === 'Escape') {
                    close();
                }
            }

            closeBtn.addEventListener('click', close);
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    close();
                }
            });

            document.addEventListener('keydown', onKeydown);

            return { close: close, element: overlay };
        },

        hasPermission: hasPermission,
        applyPermissionElements: applyPermissionElements,
        initSearchableSelects: initSearchableSelects,
        syncFormSelects: syncFormSelects,
        setSelectValue: setSelectValue,
        setFormFieldValue: setFormFieldValue,
        fillFormFields: fillFormFields,

        showPlanAwareAlert: function (alertEl, data, fallbackMessage) {
            if (!alertEl) {
                return;
            }

            alertEl.classList.remove('d-none');
            alertEl.textContent = '';

            var message =
                data && data.message
                    ? data.message
                    : fallbackMessage || 'Request failed.';

            alertEl.appendChild(document.createTextNode(message));

            if (data && data.upgrade_url) {
                alertEl.appendChild(document.createTextNode(' '));

                var link = document.createElement('a');
                link.href = data.upgrade_url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = data.upgrade_label || 'Upgrade to Pro';
                alertEl.appendChild(link);
            }
        },

        clearPlanAwareAlert: function (alertEl) {
            if (!alertEl) {
                return;
            }

            alertEl.classList.add('d-none');
            alertEl.textContent = '';
        },

        personInitials: function (name) {
            var parts = String(name || '')
                .trim()
                .split(/\s+/);
            var letters = '';

            for (var i = 0; i < parts.length; i++) {
                if (parts[i]) {
                    letters += parts[i][0].toUpperCase();
                }

                if (letters.length >= 2) {
                    break;
                }
            }

            return letters || '?';
        },

        personAvatarSpanHtml: function (name, photo, className) {
            className = className || 'avatar avatar-sm rounded';
            var label = name || 'User';

            if (photo) {
                return (
                    '<span class="' +
                    className +
                    '" style="background-image: url(\'' +
                    String(photo).replace(/'/g, "\\'") +
                    '\')" role="img" aria-label="' +
                    escapeHtml(label) +
                    '"></span>'
                );
            }

            return (
                '<span class="' +
                className +
                '">' +
                escapeHtml(global.mfStaffCoreUi.personInitials(label)) +
                '</span>'
            );
        },

        personAvatarCellHtml: function (name, photo, secondary) {
            var displayName = name || '—';
            var html = '<div class="d-flex align-items-center gap-2">';
            html += global.mfStaffCoreUi.personAvatarSpanHtml(displayName, photo);
            html += '<div><div class="fw-medium">' + escapeHtml(displayName) + '</div>';

            if (secondary) {
                html += '<div class="text-secondary small">' + escapeHtml(String(secondary)) + '</div>';
            }

            html += '</div></div>';

            return html;
        },

        /**
         * Wire bulk row selection + delete confirm for list tables.
         * Root must have data-bulk-delete="1" and data-bulk-delete-action.
         *
         * @param {HTMLElement} root
         * @returns {{ enabled: boolean, checkboxCellHtml: (id: string|number) => string, isSelected: (id: string|number) => boolean }|null}
         */
        initBulkDelete: function (root) {
            if (!root || root.getAttribute('data-bulk-delete') !== '1') {
                return null;
            }

            if (root._staffcoreBulkDelete) {
                return root._staffcoreBulkDelete;
            }

            var ajaxUrl = root.getAttribute('data-ajax-url') || '';
            var nonce = root.getAttribute('data-nonce') || '';
            var deleteAction = root.getAttribute('data-bulk-delete-action') || '';

            if (!deleteAction || !ajaxUrl) {
                return null;
            }

            var selectedIds = new Set();
            var bulkDeleteBtn = root.querySelector('[data-role="bulk-delete-btn"]');
            var bulkDeleteLabel = root.querySelector('[data-role="bulk-delete-label"]');
            var bulkDeleteCountEl = root.querySelector('[data-role="bulk-delete-count"]');
            var bulkDeleteError = root.querySelector('[data-role="bulk-delete-error"]');
            var selectPageCheckbox = root.querySelector('[data-action="select-page"]');
            var bulkDeleteModalEl = root.querySelector('.modal[id$="-bulk-delete-modal"]');
            var toastContainer = root.querySelector('[data-role="toast-container"]');
            var bulkDeleteModal = bulkDeleteModalEl ? global.mfStaffCoreUi.modal(bulkDeleteModalEl) : null;

            function updateBulkDeleteButton() {
                if (!bulkDeleteBtn || !bulkDeleteLabel) {
                    return;
                }

                var count = selectedIds.size;
                bulkDeleteLabel.textContent =
                    count === 1 ? 'Delete selected (1)' : 'Delete selected (' + count + ')';
                bulkDeleteBtn.disabled = count === 0;
            }

            function syncPageSelectCheckbox() {
                if (!selectPageCheckbox) {
                    return;
                }

                var checkboxes = root.querySelectorAll('[data-action="select-row"]');
                if (checkboxes.length === 0) {
                    selectPageCheckbox.checked = false;
                    selectPageCheckbox.indeterminate = false;
                    return;
                }

                var checkedCount = 0;
                checkboxes.forEach(function (checkbox) {
                    if (checkbox.checked) {
                        checkedCount += 1;
                    }
                });

                selectPageCheckbox.checked = checkedCount === checkboxes.length;
                selectPageCheckbox.indeterminate =
                    checkedCount > 0 && checkedCount < checkboxes.length;
            }

            function togglePageSelection(checked) {
                root.querySelectorAll('[data-action="select-row"]').forEach(function (checkbox) {
                    var id = checkbox.getAttribute('data-id') || '';
                    checkbox.checked = checked;

                    if (!id) {
                        return;
                    }

                    if (checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });

                updateBulkDeleteButton();
                syncPageSelectCheckbox();
            }

            function toggleRowSelection(id, checked) {
                if (!id) {
                    return;
                }

                if (checked) {
                    selectedIds.add(String(id));
                } else {
                    selectedIds.delete(String(id));
                }

                updateBulkDeleteButton();
                syncPageSelectCheckbox();
            }

            function showToast(message, type) {
                if (!toastContainer) {
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

                var toast = global.mfStaffCoreUi.toast(toastEl, { delay: 4000 });
                if (toast) {
                    toast.show();
                    toastEl.addEventListener('hidden.bs.toast', function () {
                        toastEl.remove();
                    });
                }
            }

            function openBulkDeleteModal() {
                if (selectedIds.size === 0) {
                    return;
                }

                if (bulkDeleteCountEl) {
                    bulkDeleteCountEl.textContent =
                        selectedIds.size === 1
                            ? '(1 item)'
                            : '(' + selectedIds.size + ' items)';
                }

                if (bulkDeleteError) {
                    bulkDeleteError.classList.add('d-none');
                    bulkDeleteError.textContent = '';
                }

                if (bulkDeleteModal) {
                    bulkDeleteModal.show();
                }
            }

            function setBulkDeleteLoading(loading) {
                var btn = root.querySelector('[data-action="confirm-bulk-delete"]');
                var spinner = root.querySelector('[data-role="bulk-delete-spinner"]');
                if (btn) {
                    btn.disabled = loading;
                }
                if (spinner) {
                    spinner.classList.toggle('d-none', !loading);
                }
            }

            function confirmBulkDelete() {
                if (selectedIds.size === 0) {
                    return;
                }

                setBulkDeleteLoading(true);

                var body = new FormData();
                body.append('action', deleteAction);
                body.append('nonce', nonce);
                selectedIds.forEach(function (id) {
                    body.append('ids[]', id);
                });

                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: body,
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (result) {
                        setBulkDeleteLoading(false);

                        if (!result.success) {
                            if (bulkDeleteError) {
                                bulkDeleteError.textContent =
                                    result.data && result.data.message
                                        ? result.data.message
                                        : 'Bulk delete failed.';
                                bulkDeleteError.classList.remove('d-none');
                            }
                            return;
                        }

                        if (bulkDeleteModal) {
                            bulkDeleteModal.hide();
                        }

                        showToast(result.data.message || 'Deleted.', 'success');
                        window.location.reload();
                    })
                    .catch(function () {
                        setBulkDeleteLoading(false);
                        if (bulkDeleteError) {
                            bulkDeleteError.textContent = 'Network error. Please try again.';
                            bulkDeleteError.classList.remove('d-none');
                        }
                    });
            }

            root.addEventListener('click', function (event) {
                var target = event.target.closest('[data-action]');
                if (!target || !root.contains(target)) {
                    return;
                }

                var action = target.getAttribute('data-action');

                if (action === 'open-bulk-delete') {
                    openBulkDeleteModal();
                } else if (action === 'confirm-bulk-delete') {
                    confirmBulkDelete();
                } else if (action === 'select-page') {
                    togglePageSelection(target.checked);
                }
            });

            root.addEventListener('change', function (event) {
                var target = event.target;

                if (!target || target.getAttribute('data-action') !== 'select-row') {
                    return;
                }

                toggleRowSelection(target.getAttribute('data-id'), target.checked);
            });

            var api = {
                enabled: true,
                checkboxCellHtml: function (id) {
                    var checked = selectedIds.has(String(id)) ? ' checked' : '';
                    return (
                        '<td><input type="checkbox" class="form-check-input" data-action="select-row" data-id="' +
                        String(id) +
                        '"' +
                        checked +
                        '></td>'
                    );
                },
                isSelected: function (id) {
                    return selectedIds.has(String(id));
                },
            };

            root._staffcoreBulkDelete = api;
            updateBulkDeleteButton();

            return api;
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bulk-delete="1"]').forEach(function (root) {
            global.mfStaffCoreUi.initBulkDelete(root);
        });
    });
})(typeof window !== 'undefined' ? window : this);
