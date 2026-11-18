(function () {
    'use strict';

    var root = document.querySelector('[data-staffcore-module="my-profile"]');
    var ui = window.mfStaffCoreUi || null;

    if (!root) {
        return;
    }

    var form = root.querySelector('#staffcore-my-profile-password-form');
    var errorEl = root.querySelector('[data-role="password-error"]');
    var submitBtn = root.querySelector('[data-action="submit-password"]');
    var cancelBtn = root.querySelector('[data-action="cancel-password"]');
    var spinner = root.querySelector('[data-role="password-spinner"]');
    var toastContainer = root.querySelector('[data-role="toast-container"]');
    var ajaxUrl = root.getAttribute('data-ajax-url') || '';
    var nonce = root.getAttribute('data-nonce') || '';
    var passwordMin = parseInt(root.getAttribute('data-password-min') || '8', 10);
    var employeeName = root.getAttribute('data-employee-name') || '';

    var photoModalEl = document.getElementById('staffcore-my-profile-photo-modal');
    var photoTrigger = root.querySelector('[data-action="open-photo-modal"]');
    var photoAvatarFrame = root.querySelector('[data-role="profile-avatar"]');
    var photoFileInput = root.querySelector('[data-role="photo-file-input"]');
    var photoCropStage = root.querySelector('[data-role="photo-crop-stage"]');
    var photoPickStage = root.querySelector('[data-role="photo-pick-stage"]');
    var photoCropWrap = root.querySelector('[data-role="photo-crop-wrap"]');
    var photoCropImage = root.querySelector('[data-role="photo-crop-image"]');
    var photoErrorEl = root.querySelector('[data-role="photo-error"]');
    var photoSaveBtn = root.querySelector('[data-action="save-photo"]');
    var photoSpinner = root.querySelector('[data-role="photo-spinner"]');
    var cropper = null;
    var photoModal = null;
    var cropState = {
        scale: 1,
        rotation: 0,
        offsetX: 0,
        offsetY: 0,
        dragging: false,
        lastX: 0,
        lastY: 0,
        naturalWidth: 0,
        naturalHeight: 0,
    };

    var MAX_PHOTO_BYTES = 5 * 1024 * 1024;
    var ALLOWED_PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function post(action, data, file) {
        var body = new FormData();
        body.append('action', action);
        body.append('nonce', nonce);

        Object.keys(data || {}).forEach(function (key) {
            if (data[key] !== undefined && data[key] !== null) {
                body.append(key, data[key]);
            }
        });

        if (file) {
            body.append('photo', file, file.name || 'profile-photo.jpg');
        }

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

    function initialsFromName(name) {
        var parts = (name || '').trim().split(/\s+/).filter(Boolean);
        var letters = '';

        for (var i = 0; i < parts.length; i++) {
            letters += parts[i].charAt(0).toUpperCase();
            if (letters.length >= 2) {
                break;
            }
        }

        return letters || '?';
    }

    function updateAvatar(photoUrl) {
        if (!photoAvatarFrame) {
            return;
        }

        var avatar = photoAvatarFrame.querySelector('.avatar');
        if (!avatar) {
            return;
        }

        root.setAttribute('data-photo-url', photoUrl || '');

        if (photoUrl) {
            avatar.className = 'avatar avatar-xl';
            avatar.style.backgroundImage = 'url(' + photoUrl + ')';
            avatar.textContent = '';
            avatar.setAttribute('role', 'img');
            avatar.setAttribute('aria-label', employeeName || 'User');
            return;
        }

        avatar.className = 'avatar avatar-xl';
        avatar.style.backgroundImage = '';
        avatar.removeAttribute('role');
        avatar.removeAttribute('aria-label');
        avatar.textContent = initialsFromName(employeeName);
    }

    function destroyCropper() {
        cropState.scale = 1;
        cropState.rotation = 0;
        cropState.offsetX = 0;
        cropState.offsetY = 0;
        cropState.dragging = false;
        cropState.naturalWidth = 0;
        cropState.naturalHeight = 0;

        if (photoCropImage) {
            photoCropImage.style.transform = '';
            photoCropImage.onpointerdown = null;
        }

        if (photoCropWrap) {
            photoCropWrap.onpointermove = null;
            photoCropWrap.onpointerup = null;
            photoCropWrap.onpointerleave = null;
            photoCropWrap.onpointercancel = null;
        }
    }

    function applyImageTransform() {
        if (!photoCropImage) {
            return;
        }

        photoCropImage.style.transform =
            'translate(' +
            cropState.offsetX +
            'px, ' +
            cropState.offsetY +
            'px) rotate(' +
            cropState.rotation +
            'deg) scale(' +
            cropState.scale +
            ')';
    }

    function fitImageToCropBox() {
        if (!photoCropImage || !photoCropWrap || !cropState.naturalWidth || !cropState.naturalHeight) {
            return;
        }

        var box = Math.min(photoCropWrap.clientWidth || 280, photoCropWrap.clientHeight || 280);
        var fit = Math.max(box / cropState.naturalWidth, box / cropState.naturalHeight);
        cropState.scale = fit;
        cropState.offsetX = 0;
        cropState.offsetY = 0;
        applyImageTransform();
    }

    function hidePhotoError() {
        if (!photoErrorEl) {
            return;
        }

        photoErrorEl.classList.add('d-none');
        photoErrorEl.textContent = '';
    }

    function showPhotoError(message) {
        if (!photoErrorEl) {
            return;
        }

        photoErrorEl.textContent = message || 'Upload failed.';
        photoErrorEl.classList.remove('d-none');
    }

    function resetPhotoModal() {
        hidePhotoError();
        destroyCropper();

        if (photoFileInput) {
            photoFileInput.value = '';
        }

        if (photoCropImage) {
            photoCropImage.src = '';
        }

        if (photoCropWrap) {
            photoCropWrap.style.height = '';
            photoCropWrap.style.maxHeight = '';
        }

        if (photoCropStage) {
            photoCropStage.classList.add('d-none');
        }

        if (photoPickStage) {
            photoPickStage.classList.remove('d-none');
        }

        if (photoSaveBtn) {
            photoSaveBtn.classList.add('d-none');
            photoSaveBtn.disabled = false;
        }

        if (photoSpinner) {
            photoSpinner.classList.add('d-none');
        }
    }

    function getCropContainerHeight() {
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 640;
        var adminBar = document.getElementById('wpadminbar');
        var adminBarHeight = adminBar && adminBar.offsetHeight ? adminBar.offsetHeight : 0;
        var reserved = adminBarHeight + 240;
        var preferred = Math.min(Math.floor(viewportHeight * 0.5), 360);
        var available = viewportHeight - reserved;

        if (viewportHeight <= 576) {
            preferred = Math.min(Math.floor(viewportHeight * 0.4), 280);
        }

        return Math.max(180, Math.min(preferred, available));
    }

    function applyCropContainerSize() {
        if (!photoCropWrap) {
            return;
        }

        var height = getCropContainerHeight();
        photoCropWrap.style.height = height + 'px';
        photoCropWrap.style.maxHeight = height + 'px';
    }

    function scheduleCropperInit() {
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                applyCropContainerSize();
                initCropper();
            });
        });
    }

    function refreshCropperLayout() {
        if (!photoCropStage || photoCropStage.classList.contains('d-none')) {
            return;
        }

        applyCropContainerSize();
        fitImageToCropBox();
    }

    function showCropStage() {
        if (photoPickStage) {
            photoPickStage.classList.add('d-none');
        }

        if (photoCropStage) {
            photoCropStage.classList.remove('d-none');
        }

        if (photoSaveBtn) {
            photoSaveBtn.classList.remove('d-none');
        }
    }

    function initCropper() {
        if (!photoCropImage || !photoCropWrap) {
            showPhotoError('Image editor is not available.');
            return;
        }

        destroyCropper();
        applyCropContainerSize();

        cropState.naturalWidth = photoCropImage.naturalWidth || 0;
        cropState.naturalHeight = photoCropImage.naturalHeight || 0;
        fitImageToCropBox();

        photoCropImage.style.cursor = 'grab';
        photoCropImage.style.transformOrigin = 'center center';
        photoCropImage.style.touchAction = 'none';
        photoCropImage.style.userSelect = 'none';
        photoCropImage.style.maxWidth = 'none';
        photoCropImage.style.maxHeight = 'none';
        photoCropImage.style.width = cropState.naturalWidth + 'px';
        photoCropImage.style.height = cropState.naturalHeight + 'px';

        photoCropImage.onpointerdown = function (event) {
            cropState.dragging = true;
            cropState.lastX = event.clientX;
            cropState.lastY = event.clientY;
            photoCropImage.style.cursor = 'grabbing';
            photoCropImage.setPointerCapture(event.pointerId);
            event.preventDefault();
        };

        photoCropWrap.onpointermove = function (event) {
            if (!cropState.dragging) {
                return;
            }

            cropState.offsetX += event.clientX - cropState.lastX;
            cropState.offsetY += event.clientY - cropState.lastY;
            cropState.lastX = event.clientX;
            cropState.lastY = event.clientY;
            applyImageTransform();
        };

        function endDrag(event) {
            if (!cropState.dragging) {
                return;
            }

            cropState.dragging = false;
            photoCropImage.style.cursor = 'grab';

            if (event && event.pointerId !== undefined) {
                try {
                    photoCropImage.releasePointerCapture(event.pointerId);
                } catch (ignore) {
                    // ignore release errors when capture was already cleared
                }
            }
        }

        photoCropWrap.onpointerup = endDrag;
        photoCropWrap.onpointerleave = endDrag;
        photoCropWrap.onpointercancel = endDrag;
    }

    function handlePhotoFile(file) {
        hidePhotoError();

        if (!file) {
            return;
        }

        if (ALLOWED_PHOTO_TYPES.indexOf(file.type) === -1) {
            showPhotoError('Only JPEG, PNG, and WebP images are allowed.');
            return;
        }

        if (file.size > MAX_PHOTO_BYTES) {
            showPhotoError('Image must be smaller than 5 MB.');
            return;
        }

        var reader = new FileReader();

        reader.onload = function (event) {
            if (!photoCropImage) {
                return;
            }

            photoCropImage.onload = function () {
                showCropStage();
                scheduleCropperInit();
            };

            photoCropImage.src = event.target.result;
        };

        reader.onerror = function () {
            showPhotoError('Could not read the selected image.');
        };

        reader.readAsDataURL(file);
    }

    function setPhotoSubmitting(isSubmitting) {
        if (photoSaveBtn) {
            photoSaveBtn.disabled = isSubmitting;
        }

        if (photoSpinner) {
            photoSpinner.classList.toggle('d-none', !isSubmitting);
        }
    }

    function savePhoto() {
        if (!photoCropImage || !photoCropWrap || !cropState.naturalWidth) {
            showPhotoError('Choose an image to crop first.');
            return;
        }

        hidePhotoError();
        setPhotoSubmitting(true);

        var outputSize = 400;
        var box = Math.min(photoCropWrap.clientWidth || outputSize, photoCropWrap.clientHeight || outputSize);
        var canvas = document.createElement('canvas');
        canvas.width = outputSize;
        canvas.height = outputSize;
        var ctx = canvas.getContext('2d');

        if (!ctx) {
            setPhotoSubmitting(false);
            showPhotoError('Could not crop the image.');
            return;
        }

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, outputSize, outputSize);
        ctx.translate(outputSize / 2, outputSize / 2);

        var drawScale = (outputSize / box) * cropState.scale;
        ctx.rotate((cropState.rotation * Math.PI) / 180);
        ctx.translate(
            cropState.offsetX * (outputSize / box),
            cropState.offsetY * (outputSize / box),
        );
        ctx.scale(drawScale, drawScale);
        ctx.drawImage(
            photoCropImage,
            -cropState.naturalWidth / 2,
            -cropState.naturalHeight / 2,
            cropState.naturalWidth,
            cropState.naturalHeight,
        );

        canvas.toBlob(
            function (blob) {
                if (!blob) {
                    setPhotoSubmitting(false);
                    showPhotoError('Could not prepare the image for upload.');
                    return;
                }

                var file = new File([blob], 'profile-photo.jpg', { type: 'image/jpeg' });

                post('mf_staffcore_my_profile_upload_photo', {}, file)
                    .then(function (result) {
                        setPhotoSubmitting(false);

                        if (!result.success) {
                            showPhotoError(
                                result.data && result.data.message ? result.data.message : 'Upload failed.',
                            );
                            return;
                        }

                        updateAvatar(result.data.photo || '');
                        showToast(result.data.message || 'Profile photo updated.', 'success');

                        if (photoModal) {
                            photoModal.hide();
                        }
                    })
                    .catch(function () {
                        setPhotoSubmitting(false);
                        showPhotoError('Network error. Please try again.');
                    });
            },
            'image/jpeg',
            0.92,
        );
    }

    function bindPhotoUpload() {
        if (!photoModalEl || !photoTrigger) {
            return;
        }

        if (ui && typeof ui.modal === 'function') {
            photoModal = ui.modal(photoModalEl, { scroll: true });
        } else if (window.tabler && window.tabler.Modal) {
            photoModal = new window.tabler.Modal(photoModalEl, { scroll: true });
        }

        photoTrigger.addEventListener('click', function () {
            resetPhotoModal();

            if (photoModal) {
                photoModal.show();
            }
        });

        photoModalEl.addEventListener('hidden.bs.modal', resetPhotoModal);

        photoModalEl.addEventListener('shown.bs.modal', function () {
            refreshCropperLayout();
        });

        window.addEventListener('resize', function () {
            if (!photoModalEl || !photoModalEl.classList.contains('show')) {
                return;
            }

            refreshCropperLayout();
        });

        if (photoFileInput) {
            photoFileInput.addEventListener('change', function () {
                var file = photoFileInput.files && photoFileInput.files[0];
                handlePhotoFile(file);
            });
        }

        root.querySelectorAll('[data-action="photo-zoom-in"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                cropState.scale = Math.min(cropState.scale * 1.1, 8);
                applyImageTransform();
            });
        });

        root.querySelectorAll('[data-action="photo-zoom-out"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                cropState.scale = Math.max(cropState.scale / 1.1, 0.2);
                applyImageTransform();
            });
        });

        root.querySelectorAll('[data-action="photo-rotate-left"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                cropState.rotation = (cropState.rotation - 90) % 360;
                applyImageTransform();
            });
        });

        if (photoSaveBtn) {
            photoSaveBtn.addEventListener('click', savePhoto);
        }
    }

    bindPhotoUpload();

    if (!form) {
        return;
    }

    function showError(message) {
        if (!errorEl) {
            return;
        }

        errorEl.textContent = message || 'Update failed.';
        errorEl.classList.remove('d-none');
    }

    function hideError() {
        if (errorEl) {
            errorEl.classList.add('d-none');
            errorEl.textContent = '';
        }
    }

    function applyPasswordValidation() {
        var passEl = form.querySelector('[name="new_password"]');
        var confirmEl = form.querySelector('[name="new_password_confirm"]');

        if (passEl && passEl.value !== '' && passEl.value.length < passwordMin) {
            passEl.setCustomValidity('Password must be at least ' + passwordMin + ' characters.');
        } else if (passEl) {
            passEl.setCustomValidity('');
        }

        if (confirmEl && passEl) {
            confirmEl.setCustomValidity(
                confirmEl.value !== '' && confirmEl.value !== passEl.value
                    ? 'Password confirmation does not match.'
                    : '',
            );
        }
    }

    function resetForm() {
        form.reset();
        hideError();

        if (ui && typeof ui.resetFormValidation === 'function') {
            ui.resetFormValidation(form);
        }
    }

    function setSubmitting(isSubmitting) {
        if (submitBtn) {
            submitBtn.disabled = isSubmitting;
        }

        if (spinner) {
            spinner.classList.toggle('d-none', !isSubmitting);
        }
    }

    function submitPassword(event) {
        if (event) {
            event.preventDefault();
        }

        hideError();

        if (ui && !ui.validateForm(form, { custom: [applyPasswordValidation] })) {
            return;
        }

        setSubmitting(true);

        post('mf_staffcore_my_profile_change_password', {
            current_password: form.querySelector('[name="current_password"]').value,
            new_password: form.querySelector('[name="new_password"]').value,
            new_password_confirm: form.querySelector('[name="new_password_confirm"]').value,
        })
            .then(function (result) {
                setSubmitting(false);

                if (!result.success) {
                    showError(result.data && result.data.message ? result.data.message : 'Update failed.');
                    return;
                }

                resetForm();
                showToast(result.data.message || 'Password updated.', 'success');
            })
            .catch(function () {
                setSubmitting(false);
                showError('Network error. Please try again.');
            });
    }

    form.addEventListener('submit', submitPassword);

    if (cancelBtn) {
        cancelBtn.addEventListener('click', resetForm);
    }

    form.querySelectorAll('[name="new_password"], [name="new_password_confirm"]').forEach(function (field) {
        field.addEventListener('input', applyPasswordValidation);
    });
})();
