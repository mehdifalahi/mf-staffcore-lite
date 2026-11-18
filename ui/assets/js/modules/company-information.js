(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="company-information"]');
    if (!root) {
        return;
    }

    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';
    var canEdit = root.getAttribute('data-can-edit') === '1';
    var form = root.querySelector('[data-role="company-information-form"]');
    var formError = root.querySelector('[data-role="form-error"]');
    var logoPreview = root.querySelector('[data-role="logo-preview"]');
    var logoInput = form ? form.querySelector('[name="logo"]') : null;
    var logoCurrentInput = form ? form.querySelector('[name="logo_current"]') : null;
    var toastContainer = root.querySelector('[data-role="toast-container"]');
    var ui = window.mfStaffCoreUi || null;

    var FIELD_NAMES = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'country',
        'website',
        'currency',
        'timezone',
    ];

    function post(action, formData) {
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

    function updateLogoPreview(url) {
        if (!logoPreview) {
            return;
        }

        if (!url) {
            logoPreview.classList.add('d-none');
            logoPreview.innerHTML = '';
            return;
        }

        logoPreview.classList.remove('d-none');
        var avatar = document.createElement('span');
        avatar.className = 'avatar avatar-xl rounded';
        avatar.setAttribute('role', 'img');
        avatar.setAttribute('aria-label', 'Company logo');
        avatar.style.backgroundImage = 'url("' + String(url).replace(/"/g, '\\"') + '")';
        logoPreview.innerHTML = '';
        logoPreview.appendChild(avatar);
    }

    function applyClientValidationRules() {
        var nameEl = form.querySelector('[name="name"]');
        if (nameEl) {
            nameEl.setCustomValidity(nameEl.value.trim() === '' ? 'Company name is required.' : '');
        }

        var emailEl = form.querySelector('[name="email"]');
        if (emailEl && emailEl.value.trim() !== '') {
            emailEl.setCustomValidity(emailEl.validity.typeMismatch ? 'Enter a valid email address.' : '');
        } else if (emailEl) {
            emailEl.setCustomValidity('');
        }

        var websiteEl = form.querySelector('[name="website"]');
        if (websiteEl && websiteEl.value.trim() !== '') {
            websiteEl.setCustomValidity(websiteEl.validity.typeMismatch ? 'Enter a valid website URL.' : '');
        } else if (websiteEl) {
            websiteEl.setCustomValidity('');
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

        if (logoCurrentInput) {
            formData.append('logo_current', logoCurrentInput.value);
        }

        if (logoInput && logoInput.files && logoInput.files[0]) {
            formData.append('logo', logoInput.files[0]);
        }

        return formData;
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

        post('mf_staffcore_company_information_save', collectFormData())
            .then(function (result) {
                setSubmitLoading(false);

                if (!result.success) {
                    showFormError(result.data && result.data.message ? result.data.message : 'Save failed.');
                    return;
                }

                var profile = result.data.profile || {};
                if (logoCurrentInput) {
                    logoCurrentInput.value = profile.logo || '';
                }
                updateLogoPreview(profile.logo || '');
                if (logoInput) {
                    logoInput.value = '';
                }

                root.setAttribute('data-profile', JSON.stringify(profile));
                showToast(result.data.message || 'Saved.', 'success');
            })
            .catch(function () {
                setSubmitLoading(false);
                showFormError('Network error. Please try again.');
            });
    }

    function removeLogo() {
        clearFormError();

        post('mf_staffcore_company_information_remove_logo', new FormData())
            .then(function (result) {
                if (!result.success) {
                    showFormError(result.data && result.data.message ? result.data.message : 'Remove failed.');
                    return;
                }

                var profile = result.data.profile || {};
                if (logoCurrentInput) {
                    logoCurrentInput.value = '';
                }
                updateLogoPreview('');
                if (logoInput) {
                    logoInput.value = '';
                }

                var removeBtn = root.querySelector('[data-action="remove-logo"]');
                if (removeBtn) {
                    removeBtn.remove();
                }

                showToast(result.data.message || 'Logo removed.', 'success');
            })
            .catch(function () {
                showFormError('Network error. Please try again.');
            });
    }

    if (logoInput && canEdit) {
        logoInput.addEventListener('change', function () {
            if (!logoInput.files || !logoInput.files[0]) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                updateLogoPreview(event.target.result);
            };
            reader.readAsDataURL(logoInput.files[0]);
        });
    }

    if (canEdit) {
        root.addEventListener('click', function (event) {
            var target = event.target.closest('[data-action]');
            if (!target) {
                return;
            }

            var action = target.getAttribute('data-action');

            if (action === 'submit-form') {
                submitForm();
                return;
            }

            if (action === 'remove-logo') {
                removeLogo();
            }
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitForm();
            });
        }
    }
})();
