// Find the parts of the page we need.
const searchForm = document.querySelector('#cottage-search');
const cottageList = document.querySelector('.accommodations-list');
const cottages = document.querySelectorAll('.accommodation-card--listing');
const tabs = document.querySelectorAll('.cottage-tabs__item');
const noResults = document.querySelector('#cottage-no-results');
const otherOptionsDivider = document.querySelector('#cottage-options-divider');

let chosenType = 'all';

// Show only cottages that match the chosen guests, dates and category.
function showMatchingCottages() {
    const guests = Number(document.querySelector('#guests').value);
    const arrival = document.querySelector('#arrival').value;
    const departure = document.querySelector('#departure').value;
    const matchingCottages = [];
    const otherCottages = [];

    cottages.forEach(function (cottage) {
        const enoughBeds = !guests || Number(cottage.dataset.guests) >= guests;
        const correctType = chosenType === 'all' || cottage.dataset.type === chosenType;
        const arrivalIsFree = !arrival || cottage.dataset.availableFrom <= arrival;
        const departureIsFree = !departure || cottage.dataset.availableTo >= departure;
        const shouldShow = enoughBeds && correctType && arrivalIsFree && departureIsFree;

        if (shouldShow) {
            matchingCottages.push(cottage);
        } else {
            otherCottages.push(cottage);
        }
    });

    // Put the matching cottages first.
    matchingCottages.forEach(function (cottage) {
        cottageList.appendChild(cottage);
    });

    // Put a line before cottages that do not match.
    otherOptionsDivider.hidden = otherCottages.length === 0;
    if (otherCottages.length > 0) {
        cottageList.appendChild(otherOptionsDivider);
    }

    // Keep the other cottages visible below that line.
    otherCottages.forEach(function (cottage) {
        cottageList.appendChild(cottage);
    });

    noResults.hidden = matchingCottages.length !== 0;
}

// Do not reload the page when the search button is pressed.
searchForm.addEventListener('submit', function (event) {
    event.preventDefault();
    showMatchingCottages();
});

// A category button chooses a type and then filters straight away.
tabs.forEach(function (tab) {
    tab.addEventListener('click', function (event) {
        event.preventDefault();
        chosenType = tab.dataset.type;

        tabs.forEach(function (item) {
            item.classList.remove('cottage-tabs__item--active');
        });
        tab.classList.add('cottage-tabs__item--active');
        showMatchingCottages();
    });
});

const accommodationModal = document.querySelector('#accommodation-modal');
const modalDialog = document.querySelector('.accommodation-modal__dialog');
const modalImage = document.querySelector('#accommodation-modal-image');
const modalTitle = document.querySelector('#accommodation-modal-title');
const modalLocation = document.querySelector('#accommodation-modal-location');
const modalPrice = document.querySelector('#accommodation-modal-price');
const modalMax = document.querySelector('#accommodation-modal-max');
const modalFacilities = document.querySelector('#accommodation-modal-facilities');
const modalDescription = document.querySelector('#accommodation-modal-description');
let previouslyFocusedElement = null;

function openAccommodationModal(card) {
    const image = card.querySelector('.accommodation-card__image');

    previouslyFocusedElement = document.activeElement;
    modalTitle.textContent = card.dataset.huisName;
    modalLocation.textContent = card.dataset.location;
    modalPrice.textContent = '€' + card.dataset.price;
    modalMax.textContent = card.dataset.max + ' guests';
    modalFacilities.textContent = card.dataset.facilities;
    modalDescription.textContent = card.dataset.description;

    modalImage.className = 'accommodation-modal__image';
    if (image) {
        modalImage.className += ' ' + image.className;
    }

    accommodationModal.hidden = false;
    accommodationModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('accommodation-modal-open');
    modalDialog.focus();
}

function closeAccommodationModal() {
    if (accommodationModal.hidden) {
        return;
    }

    accommodationModal.hidden = true;
    accommodationModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('accommodation-modal-open');

    if (previouslyFocusedElement) {
        previouslyFocusedElement.focus();
    }
}

cottages.forEach(function (card) {
    card.addEventListener('click', function (event) {
        if (event.target.closest('a, button, input, select, textarea')) {
            return;
        }

        openAccommodationModal(card);
    });

    card.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openAccommodationModal(card);
        }
    });
});

accommodationModal.addEventListener('click', function (event) {
    if (event.target.closest('[data-modal-close="true"]')) {
        closeAccommodationModal();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !accommodationModal.hidden) {
        closeAccommodationModal();
    }
});
