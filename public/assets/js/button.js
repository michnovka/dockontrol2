document.addEventListener('DOMContentLoaded', () => {
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints;
    let timeOut, touchStartY = 0, touchMoved = false;
    let abortController;
    let currentElementForNuki;
    const requestTimeoutMs = 5000;

    function startHold(event)
    {
        touchMoved = false;

        if (event.type === 'touchstart') {
            touchStartY = event.touches[0].clientY;
        }

        let el = event.currentTarget;
        const timeOutTime = isTouchDevice ? 250 : 0;

        if (el.classList.contains('nuki-btn')) {
            let el = event.currentTarget;
            timeOut = setTimeout(() => {
                if (!touchMoved) {
                    disableButtonForTimeout(el);
                    handleNukiAccess(el);
                }
            }, timeOutTime);
        } else {
            let isCarEnterExitBtn = el.classList.contains('car-enter-exit-btn');

            timeOut = setTimeout(() => {
                if (!touchMoved) {
                    disableButtonForTimeout(el);
                    if (isCarEnterExitBtn) {
                        executeCarEnterExitAction(el);
                    } else {
                        let allow1min =  el.dataset.allow1min === '1';
                        doAction(el, allow1min);
                    }
                }
            }, timeOutTime);
        }
    }

    function handleTouchMove(event)
    {
        if (event.type === 'touchmove') {
            const touchY = event.touches[0].clientY;
            if (Math.abs(touchY - touchStartY) > 10) {
                touchMoved = true;
                clearTimeout(timeOut);
            }
        }
    }

    function cancelHold(event)
    {
        let el = event.currentTarget;
        if (el.tagName === 'DIV') {
            el = el.querySelector('button');
        }
        clearTimeout(timeOut);
    }

    function vibrateIfPossible()
    {
        const canVibrate = "vibrate" in navigator;

        if (canVibrate) {
            window.navigator.vibrate(100);
        }
    }

    function disableButtonForTimeout(element)
    {
        element.disabled = true;
        setTimeout(() => {
            element.disabled = false;
        }, requestTimeoutMs);
    }

    function doAction(element, allow1min)
    {
        let xCsrfToken = buttonCSRF;
        let buttonId = element.dataset.buttonId;
        let buttonActionURL = executeButtonURL.replace('__ID__', buttonId);
        let formData = new FormData();
        formData.append('allow1min', allow1min);
        vibrateIfPossible();
        showLoadingAnimation(element);

        abortController = new AbortController();
        const signal = abortController.signal;

        const fetchPromise = fetch(buttonActionURL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': xCsrfToken
            },
            body: formData,
            signal: signal
        });

        const timeout = setTimeout(() => {
            abortController.abort();
            showAnimation(element, {status: 'error', message: timeoutMessage});
        }, requestTimeoutMs);

        fetchPromise.then((response) => {
            clearTimeout(timeout);
            return response.json();
        }).then((res) => {
            showAnimation(element, res);
        });
    }

    function executeCarEnterExitAction(element)
    {
        let xCsrfToken = buttonCSRF;
        let buttonType = element.dataset.value;
        let buttonActionURL;
        if (buttonType === 'enter') {
            buttonActionURL = carEnterURL;
        }
        if (buttonType === 'exit') {
            buttonActionURL = carExitURL;
        }
        vibrateIfPossible();
        showLoadingAnimation(element);

        abortController = new AbortController();
        const signal = abortController.signal;

        const fetchPromise = fetch(buttonActionURL, {
            method: 'POST',
            signal: signal,
            headers: {
                'X-CSRF-TOKEN': xCsrfToken
            },
        });

        const timeout = setTimeout(() => {
            abortController.abort();
            showAnimation(element, {status: 'error', message: timeoutMessage});
        }, requestTimeoutMs);

        fetchPromise.then((response) => {
            clearTimeout(timeout);
            return response.json();
        }).then((res) => {
            showAnimation(element, res);
        });
    }

    function showAnimation(element, res, isNuki = false)
    {
        let status = res.status;
        let parentElement = element.parentElement;
        let needsRefresh = res.needsRefresh;
        element.classList.add('btn-progress');
        let nukiLockUnlockIcon = null;
        let cameraButton = null;
        if (isNuki) {
            nukiLockUnlockIcon = element.querySelector('.nuki-btn-icon');
        } else {
            cameraButton = parentElement.querySelector('.camera-btn');
        }
        let buttonText = element.querySelector('.btn-text');
        let originalButtonText = buttonText.innerHTML;

        const successSvg = `
        <svg id="successAnimation" class="animated" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 70 70">
            <path id="successAnimationResult" fill="#D8D8D8" d="M35,60 C21.1928813,60 10,48.8071187 10,35 C10,21.1928813 21.1928813,10 35,10 C48.8071187,10 60,21.1928813 60,35 C60,48.8071187 48.8071187,60 35,60 Z M23.6332378,33.2260427 L22.3667622,34.7739573 L34.1433655,44.40936 L47.776114,27.6305926 L46.223886,26.3694074 L33.8566345,41.59064 L23.6332378,33.2260427 Z"/>
            <circle id="successAnimationCircle" cx="35" cy="35" r="24" stroke="#979797" stroke-width="2" stroke-linecap="round" fill="transparent"/>
            <polyline id="successAnimationCheck" stroke="#979797" stroke-width="2" points="23 34 34 43 47 27" fill="transparent"/>
        </svg>`;

        const errorSvg = `
        <svg id="errorAnimation" class="animated" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 70 70">
            <path id="errorAnimationResult" fill="#D8D8D8" d="M35,60 C21.1928813,60 10,48.8071187 10,35 C10,21.1928813 21.1928813,10 35,10 C48.8071187,10 60,21.1928813 60,35 C60,48.8071187 48.8071187,60 35,60 Z M25.5,24.5 L24,26 L33,35 L24,44 L25.5,45.5 L34.5,36.5 L43.5,45.5 L45,44 L36,35 L45,26 L43.5,24.5 L34.5,33.5 L25.5,24.5 Z"/>
            <circle id="errorAnimationCircle" cx="35" cy="35" r="24" stroke="#979797" stroke-width="2" stroke-linecap="round" fill="transparent"/>
            <polyline id="errorAnimationCross1" stroke="#979797" stroke-width="2" points="25 25 45 45" fill="transparent"/>
            <polyline id="errorAnimationCross2" stroke="#979797" stroke-width="2" points="45 25 25 45" fill="transparent"/>
        </svg>`;

        hideLoadingAnimation(element);
        if (status === 'ok' || status === 'success') {
            element.classList.add('btn-success-background');
            buttonText.innerHTML = successSvg;
        } else {
            element.classList.add('btn-error-background');
            buttonText.innerHTML = errorSvg;
            showFloatingMessageAlert(res.message);
        }
        buttonText.classList.remove('d-none');

        setTimeout(() => {
            const svgElement = parentElement.querySelector('svg');
            if (svgElement) {
                svgElement.remove();
            }

            if (buttonText !== null) {
                buttonText.innerHTML = originalButtonText;
            }

            if (nukiLockUnlockIcon) {
                nukiLockUnlockIcon.classList.remove('d-none');
            }

            if (cameraButton) {
                cameraButton.classList.remove('d-none');
            }
            if (status === 'ok' || status === 'success') {
                element.classList.remove('btn-success-background');
            } else {
                element.classList.remove('btn-error-background');
            }

            element.classList.remove('btn-progress');
        }, 1800);

        if (needsRefresh !== null && needsRefresh) {
            setTimeout(() => {
                location.reload();
            }, 2700);
        }
    }

    function showLoadingAnimation(element, isNuki = false)
    {
        let parentElement = element.parentElement;
        if (isNuki) {
            let nukiLockUnlockIcon = element.querySelector('.nuki-btn-icon');
            if (nukiLockUnlockIcon) {
                nukiLockUnlockIcon.classList.add('d-none');
            }
        } else {
            let cameraButton = parentElement.querySelector('.camera-btn');
            if (cameraButton) {
                cameraButton.classList.add('d-none');
            }
        }
        element.classList.add('btn-progress');
        let buttonText = element.querySelector('.btn-text');
        let loadingElement = element.querySelector('.spinner-border');
        element.classList.add('btn-pending');
        buttonText.classList.add('d-none');
        loadingElement.classList.remove('d-none');
    }

    function hideLoadingAnimation(element)
    {
        let loadingElement = element.querySelector('.spinner-border');
        element.classList.remove('btn-pending');
        loadingElement.classList.add('d-none');
    }

    let submitPin = function () {
        let pin = pinInput.value;
        if (pin.length >= 4 && pin.length <= 8) {
            let nukiId = currentElementForNuki.dataset.nukiId;
            let isLock = Boolean(parseInt(currentElementForNuki.dataset.isLock));
            let password2 = localStorage.getItem('nuki_' + nukiId + '_password');
            pinModalIsSubmitted = true;
            pinModal.hide();
            processNukiButton(currentElementForNuki, nukiId, password2, pin, isLock);
        }
    }

    let showOperationCancelledMessage = function showOperationCancelledMessage()
    {
        let showOperationCancelledMessage = !pinModalIsSubmitted;
        if (showOperationCancelledMessage) {
            showAnimation(currentElementForNuki, {status: 'error', message: operationCancelledMessage}, true);
        }
    }

    function generateTOTP(password2) {
        return sha256(password2).then((sha256_digest_password2) => {
            const totp_nonce = Date.now();
            return sha256(totp_nonce).then((sha256_digest_totp_nonce) => {
                const secret = base32.encode(hexToString(sha256_digest_password2.slice(0, 20))) +
                    base32.encode(hexToString(sha256_digest_totp_nonce.slice(0, 10)));

                const totp = (new jsOTP.totp()).getOtp(secret);
                return { totp, totp_nonce };
            });
        });
    }

    function fetchWithTimeout(url, options, timeoutMs) {
        const abortController = new AbortController();
        const signal = abortController.signal;

        const timeout = setTimeout(() => {
            abortController.abort();
            showAnimation(currentElementForNuki, { status: 'error', message: timeoutMessage }, true);
        }, timeoutMs);

        return fetch(url, { ...options, signal: signal })
            .then((response) => {
                clearTimeout(timeout);
                return response.json();
            })
            .then((res) => {
                showAnimation(currentElementForNuki, res, true);
            })
            .catch((err) => {
                showAnimation(currentElementForNuki, { status: 'error', message: err.message }, true);
            });
    }

    function checkRegistration(nukiId, password2, isLock) {
        if (!window.fetch || !navigator.credentials || !navigator.credentials.get) {
            window.alert('Browser not supported.');
            return;
        }

        fetch(webauthnGetArgsURL, { method: 'POST', cache: 'no-cache' })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error('WebAuthn arguments retrieval failed');
                }
                return recursiveBase64StrToArrayBuffer(data.getArgs);
            })
            .then((getCredentialArgs) => navigator.credentials.get(getCredentialArgs))
            .then((cred) => {
                return {
                    id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
                    clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
                    authenticatorData: cred.response.authenticatorData ? arrayBufferToBase64(cred.response.authenticatorData) : null,
                    signature: cred.response.signature ? arrayBufferToBase64(cred.response.signature) : null,
                };
            })
            .then((authResponse) => {
                return generateTOTP(password2).then(({ totp, totp_nonce }) => {
                    authResponse.totp = totp;
                    authResponse.totp_nonce = totp_nonce;
                    if (isLock) authResponse.isLock = isLock;

                    vibrateIfPossible();
                    webauthnProcessGetURL = webauthnProcessGetURL.replace('__ID__', nukiId);
                    return fetchWithTimeout(webauthnProcessGetURL, {
                        method: 'POST',
                        body: JSON.stringify(authResponse),
                    }, requestTimeoutMs);
                });
            })
            .catch((err) => {
                showAnimation(currentElementForNuki, { status: 'error', message: err.message }, true);
            });
    }

    function processNukiButton(element, nukiID, password2, pin, lock = false) {
        generateTOTP(password2)
            .then(({ totp, totp_nonce }) => {
                const csrfToken = document.querySelector('#pin-modal #nuki_csrf_token').value;

                let formData = new FormData();
                formData.append('_csrf', csrfToken);
                formData.append('totp', totp);
                formData.append('totp_nonce', totp_nonce);
                if (pin) formData.append('pin', pin);
                if (lock) formData.append('isLock', lock);

                vibrateIfPossible();

                nukiEngageURL = nukiEngageURL.replace('__ID__', nukiID);
                return fetchWithTimeout(nukiEngageURL, { method: 'POST', body: formData }, requestTimeoutMs);
            })
            .catch((err) => {
                showAnimation(element, { status: 'error', message: err.message }, true);
            });
    }


    function handleNukiAccess(nukiButtonElement)
    {
        let nukiID = nukiButtonElement.dataset.nukiId;
        let isLock = Boolean(parseInt(nukiButtonElement.dataset.isLock));
        let pinEnabled = parseInt(nukiButtonElement.dataset.pinEnabled) === 1;
        let password2 = localStorage.getItem('nuki_' + nukiID + '_password');
        let password2ModalSelector = document.getElementById('password2-modal');
        let password2Modal = new bootstrap.Modal(password2ModalSelector);
        if (!password2) {
            document.getElementById('password2').value = null;
            document.getElementById('repeat_password2').value = null;
            password2Modal.show();
            let password2Form = document.getElementById('add_password2_form');
            password2Form.addEventListener('submit', (e) => {
                e.preventDefault();
                password2 = document.getElementById('password2').value;
                let confirmPassword2 = document.getElementById('repeat_password2').value;
                if (password2 !== confirmPassword2) {
                    password2ModalSelector.querySelector('.incorrect_password').classList.remove('d-none');
                    setTimeout(() => {
                        password2ModalSelector.querySelector('.incorrect_password').classList.add('d-none');
                    }, 2500);
                } else {
                    let nukiCsrfToken = document.getElementById('password2_nuki_csrf').value;
                    let formData = new FormData();
                    formData.append('_csrf', nukiCsrfToken);
                    formData.append('password2', password2);
                    let saveBtn = password2ModalSelector.querySelector('#savePassword');
                    saveBtn.setAttribute('disabled', 'disabled');
                    let btnText = saveBtn.textContent;
                    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                    fetch(validatePassword2URL, {
                        method: 'POST',
                        body: formData
                    }).then((response) => {
                        return response.json();
                    }).then((jsonRes) => {
                        if (jsonRes.is_password_valid) {
                            localStorage.setItem('nuki_' + nukiID + '_password', password2);
                            password2Modal.hide();
                            if (pinEnabled && password2) {
                                showLoadingAnimation(nukiButtonElement, true);
                                currentElementForNuki = nukiButtonElement;
                                let hasUserEnableBioMetrics = Boolean(parseInt(localStorage.getItem('nuki_' + nukiID + '_fingerprint')));
                                if (hasUserEnableBioMetrics) {
                                    checkRegistration(nukiID, password2, isLock);
                                } else {
                                    showPinModal(submitPin, showOperationCancelledMessage);
                                }
                            } else {
                                processNukiButton(nukiButtonElement, nukiID, password2, null, isLock);
                            }
                        } else {
                            password2ModalSelector.querySelector('.generic_error').textContent = jsonRes.message;
                            password2ModalSelector.querySelector('.generic_error').classList.remove('d-none');
                            setTimeout(() => {
                                password2ModalSelector.querySelector('.generic_error').classList.add('d-none');
                            }, 2500);
                        }
                    }).finally(() => {
                        saveBtn.innerHTML = btnText;
                        saveBtn.removeAttribute('disabled');
                    });
                }
            }, {once: true});
        } else {
            if (pinEnabled && password2) {
                showLoadingAnimation(nukiButtonElement, true);
                currentElementForNuki = nukiButtonElement;
                let hasUserEnableBioMetrics = Boolean(parseInt(localStorage.getItem('nuki_' + nukiID + '_fingerprint')));
                if (hasUserEnableBioMetrics) {
                    checkRegistration(nukiID, password2, isLock);
                } else {
                    showPinModal(submitPin, showOperationCancelledMessage);
                }
            } else {
                showLoadingAnimation(nukiButtonElement, true);
                processNukiButton(nukiButtonElement, nukiID, password2, null, isLock);
            }
        }
    }

    function setUpHooks(element, forNuki = false)
    {
        element.forEach((el) => {
            if (isTouchDevice && buttonPressType === 'hold') {
                el.addEventListener('touchstart', startHold);
                el.addEventListener('touchmove', handleTouchMove);
                el.addEventListener('touchend', cancelHold);
                el.addEventListener('touchcancel', cancelHold);

                el.addEventListener('mousedown', startHold);
                el.addEventListener('mouseup', cancelHold);
                el.addEventListener('mouseleave', cancelHold);

                el.addEventListener('click', (e) => {
                    if (!lastClickTime || lastClickTime < Date.now() - 60000) {
                        lastClickTime = Date.now();
                        clickCount = 1;
                    } else {
                        clickCount++;
                    }

                    if (clickCount >= 5) {
                        holdButtonInfoModal.show();
                    }
                });
            } else {
                el.addEventListener('click', (e) => {
                    console.log('clicked')
                    e.preventDefault();
                    if (forNuki) {
                        handleNukiAccess(el);
                    } else {
                        let isCarEnterExitBtn = el.classList.contains('car-enter-exit-btn');
                        if (isCarEnterExitBtn) {
                            executeCarEnterExitAction(el);
                        } else {
                            let allow1min =  el.dataset.allow1min === '1';
                            doAction(el, allow1min);
                        }
                    }
                });
            }
        });
    }

    setUpHooks(document.querySelectorAll('div.single-open'));
    setUpHooks(document.querySelectorAll('div.nuki-btn'), true);
});
