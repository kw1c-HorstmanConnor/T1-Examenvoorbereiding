// Find the parts of the page we need.
const searchForm = document.querySelector('#cottage-search');
const cottageList = document.querySelector('.accommodations-list');
const cottages = document.querySelectorAll('.accommodation-card--listing');
const accommodationCards = Array.from(document.querySelectorAll('[data-accommodation-card], .accommodation-card--listing'));
const tabs = document.querySelectorAll('.cottage-tabs__item');
const noResults = document.querySelector('#cottage-no-results');
const otherOptionsDivider = document.querySelector('#cottage-options-divider');

let chosenType = 'all';

// Show only cottages that match the chosen guests, dates and category.
function showMatchingCottages() {
    if (!cottageList || !noResults || !otherOptionsDivider) {
        return;
    }

    const guestsField = document.querySelector('#guests');
    const arrivalField = document.querySelector('#arrival');
    const departureField = document.querySelector('#departure');
    const guests = guestsField ? Number(guestsField.value) : 0;
    const arrival = arrivalField ? arrivalField.value : '';
    const departure = departureField ? departureField.value : '';
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

    matchingCottages.forEach(function (cottage) {
        cottageList.appendChild(cottage);
    });

    otherOptionsDivider.hidden = otherCottages.length === 0;
    if (otherCottages.length > 0) {
        cottageList.appendChild(otherOptionsDivider);
    }

    otherCottages.forEach(function (cottage) {
        cottageList.appendChild(cottage);
    });

    noResults.hidden = matchingCottages.length !== 0;
}

// Date availability is confirmed by the server; this form intentionally submits normally.

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
const modalPhoto = document.querySelector('#accommodation-modal-photo');
const modalTitle = document.querySelector('#accommodation-modal-title');
const modalLocation = document.querySelector('#accommodation-modal-location');
const modalPrice = document.querySelector('#accommodation-modal-price');
const modalMax = document.querySelector('#accommodation-modal-max');
const modalFacilities = document.querySelector('#accommodation-modal-facilities');
const modalDescription = document.querySelector('#accommodation-modal-description');
const bookNowButton = document.querySelector('#book-now-button');
const bookingModal = document.querySelector('#booking-modal');
const bookingDialog = document.querySelector('.booking-modal__dialog');
const bookingHuisId = document.querySelector('#booking-huis-id');
const bookingAccommodationName = document.querySelector('#booking-accommodation-name');
const bookingStartDate = document.querySelector('#booking-start-date');
const bookingEndDate = document.querySelector('#booking-end-date');
const bookingPeople = document.querySelector('#booking-people');
let previouslyFocusedElement = null;
let activeAccommodation = null;
let activeAccommodationCard = null;

function translateAccommodationText(value) {
    if (window.MapleLanguage && typeof window.MapleLanguage.translate === 'function') {
        return window.MapleLanguage.translate(value);
    }

    return value;
}

function updateAccommodationModal(card) {
    if (!card || !modalDescription) {
        return;
    }

    modalDescription.textContent = translateAccommodationText(card.dataset.description || '');
}

document.querySelectorAll('.accommodation-card__photo').forEach(function (image) {
    image.addEventListener('error', function () {
        if (image.dataset.fallbackSrc && image.dataset.fallbackApplied !== '1') {
            image.dataset.fallbackApplied = '1';
            image.src = image.dataset.fallbackSrc;
        }
    });
});

if (modalPhoto) {
    modalPhoto.addEventListener('error', function () {
        if (modalPhoto.dataset.fallbackSrc && modalPhoto.dataset.fallbackApplied !== '1') {
            modalPhoto.dataset.fallbackApplied = '1';
            modalPhoto.src = modalPhoto.dataset.fallbackSrc;
        }
    });
}

function openAccommodationModal(card) {
    if (!card || !accommodationModal || !modalDialog) {
        return;
    }

    previouslyFocusedElement = document.activeElement;

    if (modalTitle) {
        modalTitle.textContent = card.dataset.huisName || '';
    }
    if (modalLocation) {
        modalLocation.textContent = card.dataset.location || '';
    }
    if (modalPrice) {
        modalPrice.textContent = '\u20ac' + (card.dataset.price || '0');
    }
    if (modalMax) {
        modalMax.textContent = (card.dataset.max || '0') + ' guests';
    }
    if (modalFacilities) {
        modalFacilities.textContent = card.dataset.facilities || '';
    }

    updateAccommodationModal(card);
    activeAccommodationCard = card;
    activeAccommodation = {
        huisId: card.dataset.huisId,
        name: card.dataset.huisName,
        max: card.dataset.max,
        detailUrl: card.dataset.detailUrl
    };

    if (modalImage) {
        modalImage.className = 'accommodation-modal__image';
    }
    if (modalPhoto) {
        modalPhoto.dataset.fallbackApplied = '0';
        modalPhoto.src = card.dataset.imageSrc || modalPhoto.src;
        modalPhoto.alt = card.dataset.huisName || 'Accommodation image';
    }

    accommodationModal.hidden = false;
    accommodationModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('accommodation-modal-open');
    modalDialog.focus();
}

window.addEventListener('maple:languagechange', function () {
    if (activeAccommodationCard && accommodationModal && !accommodationModal.hidden) {
        updateAccommodationModal(activeAccommodationCard);
    }
});

function openBookingModal() {
    if (!activeAccommodation) {
        return;
    }

    if (!bookingModal || !bookingDialog || !bookingHuisId || !bookingAccommodationName || !bookingStartDate || !bookingEndDate || !bookingPeople) {
        if (activeAccommodation.detailUrl) {
            window.location.href = activeAccommodation.detailUrl;
        }
        return;
    }

    const arrivalField = document.querySelector('#arrival');
    const departureField = document.querySelector('#departure');
    const guestsField = document.querySelector('#guests');

    bookingHuisId.value = activeAccommodation.huisId;
    bookingAccommodationName.textContent = activeAccommodation.name;
    bookingStartDate.value = arrivalField ? arrivalField.value : '';
    bookingEndDate.value = departureField ? departureField.value : '';
    bookingPeople.value = guestsField ? guestsField.value : '';
    bookingPeople.max = activeAccommodation.max;
    bookingModal.hidden = false;
    bookingModal.setAttribute('aria-hidden', 'false');
    bookingDialog.focus();
}

function closeBookingModal() {
    if (!bookingModal || bookingModal.hidden) {
        return;
    }

    bookingModal.hidden = true;
    bookingModal.setAttribute('aria-hidden', 'true');
    if (bookNowButton) {
        bookNowButton.focus();
    }
}

function closeAccommodationModal() {
    if (!accommodationModal || accommodationModal.hidden) {
        return;
    }

    accommodationModal.hidden = true;
    accommodationModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('accommodation-modal-open');

    if (previouslyFocusedElement) {
        previouslyFocusedElement.focus();
    }
}

accommodationCards.forEach(function (card) {
    card.addEventListener('click', function (event) {
        if (event.target.closest('[data-accommodation-open]')) {
            event.preventDefault();
            openAccommodationModal(card);
            return;
        }

        if (event.target.closest('a, button, input, select, textarea')) {
            return;
        }

        if (card.classList.contains('accommodation-card--listing')) {
            openAccommodationModal(card);
        }
    });

    card.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openAccommodationModal(card);
        }
    });
});

function openAccommodationFromHash() {
    const hash = window.location.hash ? window.location.hash.slice(1) : '';

    if (hash === '') {
        return;
    }

    const card = document.getElementById(hash);
    if (card && accommodationCards.includes(card)) {
        window.setTimeout(function () {
            openAccommodationModal(card);
        }, 0);
    }
}

if (accommodationModal) {
    accommodationModal.addEventListener('click', function (event) {
        if (event.target.closest('[data-modal-close="true"]')) {
            closeAccommodationModal();
        }
    });
}

if (bookNowButton) {
    bookNowButton.addEventListener('click', openBookingModal);
}

if (bookingModal) {
    bookingModal.addEventListener('click', function (event) {
        if (event.target.closest('[data-booking-modal-close="true"]')) {
            closeBookingModal();
        }
    });
}

openAccommodationFromHash();
window.addEventListener('hashchange', openAccommodationFromHash);

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        if (bookingModal && !bookingModal.hidden) {
            closeBookingModal();
        } else if (accommodationModal && !accommodationModal.hidden) {
            closeAccommodationModal();
        }
    }
});
