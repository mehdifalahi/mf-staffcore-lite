(function () {
    'use strict';

    var shellRoot = document.querySelector('[data-staffcore-notifications-shell]');
    var inboxRoot = document.querySelector('[data-staffcore-module="notifications-inbox"]');
    var ui = window.mfStaffCoreUi || null;

    function panelContext() {
        var root = shellRoot || inboxRoot;
        if (!root) {
            return 'admin';
        }

        return root.getAttribute('data-panel-context') === 'frontend' ? 'frontend' : 'admin';
    }

    function post(ajaxUrl, nonce, action, data) {
        var body = new FormData();
        body.append('action', action);
        body.append('nonce', nonce);
        body.append('panel_context', panelContext());

        Object.keys(data || {}).forEach(function (key) {
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

    function updateBadge(count) {
        var badges = document.querySelectorAll('[data-role="notification-unread-badge"]');
        badges.forEach(function (badge) {
            if (!badge) {
                return;
            }

            var numeric = Number(count || 0);
            if (numeric > 0) {
                badge.classList.remove('d-none');
                badge.textContent = numeric > 99 ? '99+' : String(numeric);
            } else {
                badge.classList.add('d-none');
                badge.textContent = '';
            }
        });
    }

    function refreshShellUnread() {
        if (!shellRoot) {
            return;
        }

        var ajaxUrl = shellRoot.getAttribute('data-ajax-url') || '';
        var nonce = shellRoot.getAttribute('data-nonce') || '';

        post(ajaxUrl, nonce, 'mf_staffcore_notifications_unread_count', {})
            .then(function (result) {
                if (!result.success) {
                    return;
                }
                updateBadge(result.data && result.data.unread ? result.data.unread : 0);
            })
            .catch(function () {});
    }

    function renderDropdownItems(items) {
        if (!shellRoot) {
            return;
        }

        var list = shellRoot.querySelector('[data-role="notification-dropdown-list"]');
        if (!list) {
            return;
        }

        list.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            list.innerHTML =
                '<div class="list-group-item text-secondary text-center py-3">No new notifications</div>';
            return;
        }

        items.forEach(function (item) {
            var actionUrl = item.action_url || '#';
            var title = escapeHtml(item.title || '');
            var body = escapeHtml(item.body || '');
            var time = escapeHtml(item.relative_time || '');
            var tone = item.tone || 'azure';

            var anchor = document.createElement('a');
            anchor.className = 'list-group-item list-group-item-action';
            anchor.href = actionUrl === '#' ? '#' : actionUrl;
            anchor.setAttribute('data-notification-id', String(item.id || ''));
            anchor.setAttribute('data-action', 'notification-open');

            anchor.innerHTML =
                '<div class="row align-items-center">' +
                '<div class="col-auto"><span class="badge bg-' + tone + '-lt"></span></div>' +
                '<div class="col text-truncate">' +
                '<div class="text-body fw-semibold">' + title + '</div>' +
                '<div class="d-block text-secondary text-truncate small">' + body + '</div>' +
                '</div>' +
                '<div class="col-auto text-secondary small">' + time + '</div>' +
                '</div>';

            list.appendChild(anchor);
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function refreshDropdown() {
        if (!shellRoot) {
            return;
        }

        var ajaxUrl = shellRoot.getAttribute('data-ajax-url') || '';
        var nonce = shellRoot.getAttribute('data-nonce') || '';

        post(ajaxUrl, nonce, 'mf_staffcore_notifications_dropdown', {})
            .then(function (result) {
                if (!result.success) {
                    renderDropdownItems([]);
                    return;
                }

                var items = result.data && Array.isArray(result.data.notifications)
                    ? result.data.notifications
                    : [];
                renderDropdownItems(items);
            })
            .catch(function () {
                renderDropdownItems([]);
            });
    }

    function markRead(notificationId, cb) {
        var root = shellRoot || inboxRoot;
        if (!root) {
            return;
        }

        var ajaxUrl = root.getAttribute('data-ajax-url') || '';
        var nonce = root.getAttribute('data-nonce') || '';

        post(ajaxUrl, nonce, 'mf_staffcore_notifications_mark_read', { id: notificationId })
            .then(function (result) {
                if (result.success) {
                    updateBadge(result.data && result.data.unread ? result.data.unread : 0);
                }
                if (typeof cb === 'function') {
                    cb(result);
                }
            })
            .catch(function () {
                if (typeof cb === 'function') {
                    cb({ success: false });
                }
            });
    }

    function markAllRead(cb) {
        var root = shellRoot || inboxRoot;
        if (!root) {
            return;
        }

        var ajaxUrl = root.getAttribute('data-ajax-url') || '';
        var nonce = root.getAttribute('data-nonce') || '';

        post(ajaxUrl, nonce, 'mf_staffcore_notifications_mark_all_read', {})
            .then(function (result) {
                if (result.success) {
                    updateBadge(result.data && result.data.unread ? result.data.unread : 0);
                }
                if (typeof cb === 'function') {
                    cb(result);
                }
            })
            .catch(function () {
                if (typeof cb === 'function') {
                    cb({ success: false });
                }
            });
    }

    function initShell() {
        if (!shellRoot) {
            return;
        }

        refreshShellUnread();
        refreshDropdown();

        shellRoot.addEventListener('click', function (event) {
            var target = event.target.closest('[data-action]');
            if (!target) {
                return;
            }

            var action = target.getAttribute('data-action');

            if (action === 'open-notifications-dropdown') {
                refreshDropdown();
                refreshShellUnread();
                return;
            }

            if (action === 'mark-all-read') {
                event.preventDefault();
                markAllRead(function () {
                    refreshDropdown();
                });
                return;
            }

            if (action === 'notification-open') {
                var id = target.getAttribute('data-notification-id') || '';
                var href = target.getAttribute('href') || '';

                event.preventDefault();
                if (!id) {
                    if (href && href !== '#') {
                        window.location.href = href;
                    }
                    return;
                }

                markRead(id, function () {
                    if (href && href !== '#') {
                        window.location.href = href;
                    } else {
                        refreshDropdown();
                    }
                });
            }
        });
    }

    function initInbox() {
        if (!inboxRoot) {
            return;
        }

        inboxRoot.addEventListener('click', function (event) {
            var target = event.target.closest('[data-action]');
            if (!target) {
                return;
            }

            var action = target.getAttribute('data-action');

            if (action === 'mark-all-read') {
                event.preventDefault();
                markAllRead(function () {
                    window.location.reload();
                });
                return;
            }

            if (action === 'mark-read') {
                event.preventDefault();
                var id = target.getAttribute('data-id') || '';
                if (!id) {
                    return;
                }

                markRead(id, function () {
                    window.location.reload();
                });
                return;
            }

            if (action === 'open-link') {
                event.preventDefault();
                var notificationId = target.getAttribute('data-id') || '';
                var href = target.getAttribute('href') || '';
                if (!notificationId) {
                    if (href) {
                        window.location.href = href;
                    }
                    return;
                }
                markRead(notificationId, function () {
                    if (href) {
                        window.location.href = href;
                    }
                });
            }
        });
    }

    if (ui) {
        initShell();
        initInbox();
    }
})();
