document.addEventListener('DOMContentLoaded', () => {
    const loadingContainer = document.getElementById('loading-element');
    const imgContainer = document.getElementById('img-container');
    const pausedContainer = document.getElementById('paused-container');
    const pauseOverlay = document.getElementById('pause-overlay');
    const cameraModalElement = document.getElementById('camera-modal');
    const cameraModal = new bootstrap.Modal(cameraModalElement, {});
    const fallbackImageUrl = 'assets/images/camera_not_found.jpg';

    let cameras = [];
    let cameraSessionId = null;
    let isPaused = false;
    let cameraTimeoutMilliseconds = 500;
    let ongoingRequests = [];
    let isCurrentWindowHidden;
    let cameraCache = {};
    let currentButtonId;
    let cameraLastFetchTime = [];
    let cameraTimers = {};
    let cameraPauseTimestamps;

    const hiddenPropertyName = document.hidden !== undefined ? "hidden" :
        document.msHidden !== undefined ? "msHidden" :
            document.webkitHidden !== undefined ? "webkitHidden" : null;

    const visibilityChangeEventName = hiddenPropertyName ? (hiddenPropertyName === "hidden" ? "visibilitychange" : (hiddenPropertyName === "msHidden" ? "msvisibilitychange" : "webkitvisibilitychange")) : null;

    function handleVisibilityChange()
    {
        isCurrentWindowHidden = document[hiddenPropertyName];
    }

    document.addEventListener(visibilityChangeEventName, handleVisibilityChange, false);

    async function fetchCameraUuid()
    {
        let formData = new FormData();
        formData.append('cameras', JSON.stringify(cameras));
        const controller = new AbortController();
        ongoingRequests.push(controller);

        try {
            const res = await fetch(fetchCameraUuidURL, {
                method: 'POST',
                body: formData,
                signal: controller.signal
            });

            if (!res.ok) {
                throw new Error(`Failed to fetch session ID: ${res.status}`);
            }

            const response = await res.json();

            if (response.success) {
                return response.camera_session_id;
            } else {
                throw new Error('Failed to get a valid camera session ID.');
            }
        } catch (err) {
            console.error('Error fetching camera session ID:', err);
            throw err;
        }
    }


    // Separate the fetching of the camera image
    async function fetchCameraImage(cameraId, buttonId)
    {
        if (cameraSessionId === null) {
            console.error('no camera session id');
            return;
        }

        handleVisibilityChange();
        if (isCurrentWindowHidden) {
            pauseStream();
        }

        try {
            const url = viewCameraURL
                .replace('__ID__', cameraId)
                .replace('__CAMERA_SESSION_ID__', cameraSessionId);

            const controller = new AbortController();
            ongoingRequests.push(controller);

            const res = await fetch(url, { signal: controller.signal });

            if (res.status !== 200) {
                if (res.status === 401) {
                    console.error('Unauthorized error, will need to get fresh camera session ID.');
                    cameraSessionId = null;
                    displayImage(fallbackImageUrl, cameraId);
                } else {
                    // no idea what happened, but lets pause stream anyways, and keep the old image shown
                    console.error(`Failed to load image with HTTP status ${res.status}`);
                }

                pauseStream();
            }

            const blob = await res.blob();
            const urlObject = URL.createObjectURL(blob);

            if (!cameraCache[buttonId]) {
                cameraCache[buttonId] = [];
            }

            cameraCache[buttonId][cameraId] = urlObject;
            displayImage(urlObject, cameraId, false);

            cameraLastFetchTime[cameraId] = Date.now();

            if (!isPaused) {
                scheduleCameraFetch(cameraId, buttonId);
            }
        } catch (err) {
            if (err.name === 'AbortError') {
                // for AbortError we do nothing, this is valid expected behavior
            } else {
                // throw it up to handle above, this is not an expected graceful error
                throw err;
            }
        }
    }

    function scheduleCameraFetch(cameraId, buttonId)
    {
        if (cameraTimers[cameraId]) {
            clearTimeout(cameraTimers[cameraId]);
        }

        const timeSinceLastFetch = Date.now() - (cameraLastFetchTime[cameraId] || 0);
        const delay = Math.max(0, cameraTimeoutMilliseconds - timeSinceLastFetch);

        cameraTimers[cameraId] = setTimeout(() => {
            fetchCameraImage(cameraId, buttonId).catch(err => {
                console.error('Error in fetchCameraImage:', err);
            });
        }, delay);
    }

    function displayImage(imageUrl, cameraId, fromCache)
    {
        let imgWrapper = imgContainer.querySelector(`#camera_wrapper_${cameraId}`);
        let img = imgContainer.querySelector(`#camera_${cameraId}`);
        let spinner = imgContainer.querySelector(`#spinner_${cameraId}`);

        if (!imgWrapper) {
            imgWrapper = document.createElement('div');
            imgWrapper.id = `camera_wrapper_${cameraId}`;
            imgWrapper.classList.add('position-relative', 'd-inline-block');
            imgContainer.appendChild(imgWrapper);
        }

        if (!img) {
            img = document.createElement('img');
            img.id = `camera_${cameraId}`;
            img.classList.add('img-fluid');
            imgWrapper.appendChild(img);
        }
        img.src = imageUrl;

        if (fromCache) {
            img.classList.add('from-cache');
        } else {
            img.classList.remove('from-cache');
        }

        if (fromCache) {
            if (!spinner) {
                spinner = document.createElement('div');
                spinner.id = `spinner_${cameraId}`;
                spinner.classList.add('position-absolute', 'top-50', 'start-50', 'translate-middle');
                spinner.innerHTML = `<div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>`;
                imgWrapper.appendChild(spinner);
            }
        } else if (spinner) {
            spinner.remove();
        }

        imgContainer.classList.remove('d-none');
        let imgLoadingContainer = document.getElementById('loading-container-' + cameraId);
        if (imgLoadingContainer) {
            imgLoadingContainer.classList.add('d-none');
        }
    }

    function stopStream()
    {
        ongoingRequests.forEach(controller => controller.abort());
        ongoingRequests = [];

        Object.keys(cameraTimers).forEach(cameraId => {
            clearTimeout(cameraTimers[cameraId]);
            delete cameraTimers[cameraId];
        });
        cameraTimers = {};
    }

    function pauseStream()
    {
        stopStream();
        isPaused = true;
        cameraPauseTimestamps = Date.now();
        pausedContainer.classList.remove('d-none');
    }

    async function resumeStream()
    {
        if (isPaused) {
            let currentTimestamp = Date.now();
            let timeSincePauseInSeconds = (currentTimestamp - cameraPauseTimestamps) / 1000;
            if (cameraSessionId === null || timeSincePauseInSeconds > 30) {
                await(async() => {
                    cameraSessionId = await fetchCameraUuid();
                    isPaused = false;
                    pausedContainer.classList.add('d-none');

                    cameras.forEach(cameraId => {
                        scheduleCameraFetch(cameraId, currentButtonId);
                    });
                })();
            } else {
                isPaused = false;
                pausedContainer.classList.add('d-none');
                cameras.forEach(cameraId => {
                    scheduleCameraFetch(cameraId, currentButtonId);
                });
            }
        }
    }

    function unpauseStream()
    {
        isPaused = false;
        pausedContainer.classList.add('d-none');
    }

    function createLoadingElement(camera, index)
    {
        let loadingElement = document.createElement('div');
        let loadingClasses = 'spinner-container';
        if (index > 0) {
            loadingClasses += ' border-top';
        }
        loadingElement.className = loadingClasses;
        loadingElement.id = 'loading-container-' + camera;

        let spinnerElement = document.createElement('div');
        spinnerElement.innerHTML = '<img src="assets/images/loading-bg.jpg" class="img-fluid" alt="loading...">' +
            ' <div class="spinner-wrapper">' +
            '<div class="spinner-border" role="status">' +
            '<span class="visually-hidden">Loading...</span>' +
            '</div></div>';
        loadingElement.appendChild(spinnerElement);
        loadingContainer.appendChild(loadingElement);
    }

    function setupCameraHooks(elements)
    {
        elements.forEach(el => {
            el.addEventListener('click', async e => {
                e.preventDefault();
                cameras = [el.dataset.camera1, el.dataset.camera2, el.dataset.camera3, el.dataset.camera4].filter(Boolean);
                let allow1min = Boolean(parseInt(el.dataset.allow1min));
                let buttonId = el.dataset.buttonId;
                currentButtonId = buttonId;
                let singleOpenBtnElement = cameraModalElement.querySelector('#single_open');
                singleOpenBtnElement.setAttribute('data-button-id', buttonId);
                let openFor1MinBtnElement = cameraModalElement.querySelector('#one_minute_open');
                openFor1MinBtnElement.setAttribute('data-button-id', buttonId);

                if (!allow1min) {
                    openFor1MinBtnElement.parentElement.classList.add('d-none');
                    singleOpenBtnElement.parentElement.parentElement.classList.add('col-md-12');
                } else {
                    singleOpenBtnElement.parentElement.parentElement.classList.contains('col-md-12') ? singleOpenBtnElement.parentElement.parentElement.classList.remove('col-md-12') : false;
                    openFor1MinBtnElement.parentElement.classList.contains('d-none') ? openFor1MinBtnElement.parentElement.classList.remove('d-none') : false;
                }

                cameraModalElement.querySelector('#camera-modal-title').textContent = el.dataset.text.trim();
                cameraModal.show();
                imgContainer.classList.add('d-none');
                loadingContainer.innerHTML = '';
                document.querySelectorAll('.custom-modal-buttons').forEach((el) => {
                   if (!el.classList.contains('d-none')) {
                       el.classList.add('d-none');
                   }
                });
                if (document.querySelector('.modal-buttons').classList.contains('d-none')) {
                    document.querySelector('.modal-buttons').classList.remove('d-none')
                }
                if (document.getElementById('modal_btn_' + buttonId) !== null && document.getElementById('modal_btn_' + buttonId).classList.contains('d-none')) {
                    if (!document.querySelector('.modal-buttons').classList.contains('d-none')) {
                        document.querySelector('.modal-buttons').classList.add('d-none')
                    }
                    document.getElementById('modal_btn_' + buttonId).classList.remove('d-none');
                }

                try {
                    if (cameraCache[buttonId]) {
                        let cacheData = Object.entries(cameraCache[buttonId]);
                        cacheData.forEach(([cameraId, imageUrl], index) => {
                            if (cameras.length !== cacheData.length) {
                                let nonExistCamera = cameras.filter((camId) => {
                                    return !Object.keys(cameraCache[buttonId]).includes(camId);
                                })[0];
                                createLoadingElement(nonExistCamera, 0);
                            }
                            displayImage(imageUrl, cameraId, true);
                        });
                    } else {
                        cameras.forEach((camera, index) => {
                            createLoadingElement(camera, index);
                        });
                    }
                    stopStream();
                    unpauseStream();
                    isPaused = false;
                    pausedContainer.classList.add('d-none');
                    cameraSessionId = await fetchCameraUuid();
                    cameras.forEach(cameraId => {
                        scheduleCameraFetch(cameraId, currentButtonId);
                    });
                } catch (error) {
                    console.error(error);
                }
            });
        });
    }

    cameraModal._element.addEventListener('hidden.bs.modal', () => {
        // Stop the stream and reset
        stopStream();
        unpauseStream();
        cameraSessionId = null;
        cameras = [];
        imgContainer.innerHTML = '';
        loadingContainer.classList.remove('d-none');
    });

    pauseOverlay.addEventListener('click', () => {
        isPaused ? resumeStream() : pauseStream();
    });

    setupCameraHooks(document.querySelectorAll('.camera-btn'));
});
