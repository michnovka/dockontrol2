function initPhoneInput(container = null) {
    if (container === null) {
        container = document;
    }
    container.querySelectorAll(".phone-with-prefix").forEach(wrapper => {
        const phonePrefix = wrapper.querySelector(".country-prefix");
        const phoneNumber = wrapper.querySelector(".phone-number");

        if (phonePrefix && phoneNumber) {
            phonePrefix.addEventListener("input", function () {
                this.value = this.value.replace(/[^0-9+]/g, '').slice(0, 4);

                if (this.value.length === 4) {
                    phoneNumber.focus();
                }
            });

            phoneNumber.addEventListener("input", function () {
                this.value = this.value.replace(/\D/g, '');
            });
        }
    });
}
