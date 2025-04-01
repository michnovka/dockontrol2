document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select.choices-select').forEach(function (el) {
        if (el !== null) {
            let isReadOnly = el.getAttribute('readonly') === 'readonly';

            let choicesOptions = {
                searchEnabled: (el.getAttribute('data-choices-search-enabled') !== null && el.getAttribute('data-choices-search-enabled').toLowerCase() === "true"),
                searchChoices: (el.getAttribute('data-choices-search-choices') !== null && el.getAttribute('data-choices-search-choices').toLowerCase() === "true"),
                removeItems: (el.getAttribute('data-choices-remove-items') !== null && el.getAttribute('data-choices-remove-items').toLowerCase() === "true"),
                placeholder: el.getAttribute('data-choices-placeholder') !== null,
                placeholderValue: el.getAttribute('data-choices-placeholder'),
                removeItemButton: (el.getAttribute('data-choices-remove-item-button') !== null && el.getAttribute('data-choices-remove-item-button').toLowerCase() === "true"),
                allowHTML: true,
            }

            if (!isReadOnly) {
                el.choicesInstance = new Choices(el, choicesOptions);
            } else {
                el.classList.add('readonly');
            }
        }
    });
});
