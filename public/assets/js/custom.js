const themeMode = localStorage.getItem('theme');

let sortingElementFirst = document.createElement('i');
sortingElementFirst.innerHTML = '&#x2191';

let sortingElementSecond = document.createElement('i');
sortingElementSecond.innerHTML = '&#x2193';

let descElement = document.querySelector('table thead tr th a.desc');
let ascElement = document.querySelector('table thead tr th a.asc');
let sortableElement = document.querySelectorAll('table thead tr th a.sortable');

if (sortableElement !== null) {
    let sortableElementFirst = document.createElement('i');
    sortableElementFirst.classList.add('text-muted');
    sortableElementFirst.innerHTML = '&#x2191&#x2193';
    sortableElement.forEach((a) => {
        a.appendChild(sortableElementFirst.cloneNode(true));
    });
}
applySortableElementsAsPerTheme(themeMode);
function applySortableElementsAsPerTheme(themeMode)
{
    let primaryTheme = 'light';
    let secondaryTheme = 'dark';

    if (themeMode === secondaryTheme) {
        if (descElement !== null) {
            resetClassesForSortingElements(sortingElementFirst, sortingElementSecond);
            sortingElementFirst.classList.add('text-muted');
            sortingElementSecond.classList.add('text-white');

            descElement.append(sortingElementFirst, sortingElementSecond);
        }
        if (ascElement !== null) {
            resetClassesForSortingElements(sortingElementFirst, sortingElementSecond);
            sortingElementFirst.classList.add('text-white');
            sortingElementSecond.classList.add('text-muted');

            ascElement.append(sortingElementFirst, sortingElementSecond);
        }
    } else if (themeMode === primaryTheme) {
        if (descElement !== null) {
            resetClassesForSortingElements(sortingElementFirst, sortingElementSecond);
            sortingElementFirst.classList.add('text-muted');
            sortingElementSecond.classList.add('text-dark');

            descElement.append(sortingElementFirst, sortingElementSecond);
        }
        if (ascElement !== null) {
            resetClassesForSortingElements(sortingElementFirst, sortingElementSecond);
            sortingElementFirst.classList.add('text-dark');
            sortingElementSecond.classList.add('text-muted');
            ascElement.append(sortingElementFirst, sortingElementSecond);
        }
    }
}

function resetClassesForSortingElements(sortingElementFirst, sortingElementSecond)
{
    sortingElementFirst.className = '';
    sortingElementSecond.className = '';
}

function applyTheme(theme, clickedButton) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem('theme', theme);

    document.querySelectorAll('.theme-option button').forEach(button => {
        if (button === clickedButton) {
            button.closest('.theme-option').style.display = 'none !important';
        } else {
            button.closest('.theme-option').style.display = 'block !important';
        }
    });
}

function toggleThemeMenu() {
    let theme = localStorage.getItem('theme') || 'light';
    applyTheme(theme, null); // Apply saved theme on load

    document.querySelectorAll('.theme-option button[data-bs-theme-value]').forEach(button => {
        button.addEventListener('click', () => {
            const selectedTheme = button.getAttribute('data-bs-theme-value');
            applyTheme(selectedTheme, button);
        });
    });
}

document.addEventListener('DOMContentLoaded', toggleThemeMenu);


toggleThemeMenu();

function clearFormField(id)
{
    let element = document.getElementById(id);
    if (element.classList.contains('select') || element.classList.contains('select-search')) {
        element.selectedIndex = -1;
        const event = new Event('change');
        element.dispatchEvent(event);
    } else {
        element.value = '';
    }
}


function checkSpaceForDateRangePickerSingle(element, direction)
{
    const rect = element.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    if (direction === 'down') {
        return viewportHeight - rect.top - rect.height > 250;
    } else {
        return rect.top > 250;
    }
}

function setupPublicZone()
{
    setupCameraIcons();
    if (publicZone) {
        // showChangeThemeOptions();
        toggleThemeOptions();
    }
}

function setupCameraIcons()
{
    let allCameraIcons = document.querySelectorAll('.camera-icon');
    let theme = localStorage.getItem('theme');
    if (theme === 'dark') {
        allCameraIcons.forEach((el) => {
            let currentCameraSrc = el.getAttribute('src');
            let newCameraSrc = currentCameraSrc.replace('security-camera', 'security-camera-white');
            el.setAttribute('src', newCameraSrc);
        });
    } else if (theme === 'light') {
        allCameraIcons.forEach((el) => {
            let currentCameraSrc = el.getAttribute('src');
            let newCameraSrc = currentCameraSrc.replace('security-camera-white', 'security-camera');
            el.setAttribute('src', newCameraSrc);
        });
    }
}

function toggleThemeOptions()
{
    let theme = localStorage.getItem('theme');

    if (document.getElementById('light_theme_option') !== null && document.getElementById('light_theme_option') !== null) {
        if (theme === 'dark') {
            document.getElementById('light_theme_option').classList.remove('d-none');
            if (!document.getElementById('dark_theme_option').classList.contains('d-none')) {
                document.getElementById('dark_theme_option').classList.add('d-none');
            }
        } else if (theme === 'light') {
            document.getElementById('dark_theme_option').classList.remove('d-none');
            if (!document.getElementById('light_theme_option').classList.contains('d-none')) {
                document.getElementById('light_theme_option').classList.add('d-none');
            }
        }
    }
}

if (publicZone) {
    setTimeout(() => {
        setupPublicZone();
    }, 100);
}
const toggleButton = document.getElementById('api-logs-toggle');
const dropdownMenu = document.querySelector('#api-logs-menu .dropdown-menu');
const rotateIcon = document.querySelector('#api-logs-toggle .rotate-icon');

if (toggleButton && dropdownMenu && rotateIcon) {
    toggleButton.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = dropdownMenu.classList.toggle('show');
        rotateIcon.classList.toggle('open', isOpen);

        if (!isOpen) {
            document.querySelectorAll('.dropdown-item').forEach(item => item.blur());
        }
    });

    rotateIcon.addEventListener('click', function (e) {
        dropdownMenu.classList.remove('show');
        rotateIcon.classList.remove('open');
        document.querySelectorAll('.dropdown-item').forEach(item => item.blur());
    });
}

document.addEventListener('DOMContentLoaded', (e) => {
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');

    for (const tooltip of tooltipElements) {
        new bootstrap.Tooltip(tooltip);
    }
});


