let pinModal = null;
let pinInput = null;
let pinDisplay = null;
let submitPinBtn = null;
let pinButtons = null;
let pinDigitButtons = null;
let clearPinBtn = null;
let PINMaskTimeout = null;
let pinModalIsSubmitted = false;
let pinModalSelector = document.getElementById('pin-modal');
let submitCallback;
let modalCloseCallback;

if (pinModalSelector !== null) {
    pinModal = new bootstrap.Modal(pinModalSelector);
    pinInput = document.getElementById("pin-input");
    pinDisplay = document.getElementById("pin-display");
    submitPinBtn = document.getElementById('submit-pin');
    pinButtons = document.querySelectorAll('.pin-btn');
    pinDigitButtons = document.querySelectorAll('.pin-digit-btn');
    clearPinBtn = document.getElementById('clear-pin');
}

if (pinModal !== null) {
    function showPinModal(callbackSubmit, callbackModalClosed)
    {
        pinModalIsSubmitted = false;
        pinInput.value = null;
        pinDisplay.textContent = null;
        submitCallback = callbackSubmit;
        modalCloseCallback = callbackModalClosed;
        updateSubmitPinButtonState();
        pinModal.show();
    }
}
if (submitPinBtn !== null) {
    submitPinBtn.addEventListener('click', (e) => {
        e.preventDefault();
        submitCallback();
    });
}

if (pinModalSelector !== null) {
    pinModalSelector.addEventListener('show.bs.modal', (e) => {
        pinButtons.forEach(button => button.disabled = true);

        setTimeout(() => {
            pinButtons.forEach(button => button.disabled = false);
        }, 100);
    });

    pinModalSelector.addEventListener('shown.bs.modal', () => {
        pinModalSelector.removeAttribute("aria-hidden");
    });

    pinModalSelector.addEventListener('hidden.bs.modal', () => {
        modalCloseCallback();
    });
}

if (pinDigitButtons !== null && clearPinBtn !== null) {
    pinDigitButtons.forEach((el) => {
        el.addEventListener('pointerup', handlePinClick);
    });

    function handlePinClick(event)
    {
        clearTimeout(PINMaskTimeout);
        PINMaskTimeout = null;

        let digit = event.target.dataset.pinValue;
        let currentText = pinDisplay.textContent;

        if (currentText.length < 8) {
            pinDisplay.textContent = "*".repeat(currentText.length) + digit;
            pinInput.value += digit;
        }

        PINMaskTimeout = setTimeout(() => {
            pinDisplay.textContent = "*".repeat(pinDisplay.textContent.length);
        }, 1000);

        console.log('clicked : ' + digit);
        updateSubmitPinButtonState();
    }

    clearPinBtn.addEventListener('click', (e) => {
        e.preventDefault();
        clearTimeout(PINMaskTimeout);
        let currentValue = pinInput.value;
        pinInput.value = currentValue.slice(0, -1);
        pinDisplay.textContent = "*".repeat(pinInput.value.length);

        updateSubmitPinButtonState();
    });

    function updateSubmitPinButtonState()
    {
        if (pinInput.value.length < 4) {
            submitPinBtn.classList.add('disabled');
        } else {
            submitPinBtn.classList.remove('disabled');
        }
    }
}

function showFloatingMessageAlert(message)
{
    let floatingMessageAlert = new bootstrap.Toast(document.querySelector('.floating-message-alert'), {
        animation: true,
        autohide: false,
    });
    document.querySelector('.show-error-message').innerHTML = message;
    floatingMessageAlert.show();

    setTimeout(() => {
        floatingMessageAlert.hide();
    }, 10000);
}