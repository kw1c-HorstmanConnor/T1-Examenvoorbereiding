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
const modalStage = document.querySelector('#accommodation-modal-stage');
const modalPhoto = document.querySelector('#accommodation-modal-photo');
const modalPlaceholder = document.querySelector('#accommodation-modal-placeholder');
const modalPrevButton = document.querySelector('[data-gallery-prev]');
const modalNextButton = document.querySelector('[data-gallery-next]');
const modalCounter = document.querySelector('#accommodation-modal-counter');
const modalThumbnails = document.querySelector('#accommodation-modal-thumbnails');
const modalTitle = document.querySelector('#accommodation-modal-title');
const modalLocation = document.querySelector('#accommodation-modal-location');
const modalPrice = document.querySelector('#accommodation-modal-price');
const modalBookingPrice = document.querySelector('#accommodation-modal-booking-price');
const modalMax = document.querySelector('#accommodation-modal-max');
const modalFacilities = document.querySelector('#accommodation-modal-facilities');
const modalFacilitiesSection = document.querySelector('#accommodation-modal-facilities-section');
const modalFacilitiesToggle = document.querySelector('#accommodation-modal-facilities-toggle');
const modalDescription = document.querySelector('#accommodation-modal-description');
const modalDescriptionSection = document.querySelector('#accommodation-modal-description-section');
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
let activeGallery = [];
let activeGalleryIndex = 0;
let facilitiesExpanded = false;
let touchStartX = 0;
let touchStartY = 0;
const FACILITIES_COLLAPSED_LIMIT = 10;

function translateAccommodationText(value) {
    if (window.MapleLanguage && typeof window.MapleLanguage.translate === 'function') {
        return window.MapleLanguage.translate(value);
    }

    return value;
}

function parseAccommodationGallery(card) {
    if (!card) {
        return [];
    }

    try {
        const gallery = JSON.parse(card.dataset.gallery || '[]');

        if (Array.isArray(gallery)) {
            return gallery
                .filter(function (slide) {
                    return slide && typeof slide.src === 'string' && slide.src.trim() !== '';
                })
                .map(function (slide) {
                    return {
                        src: slide.src,
                        type: slide.type === 'floorplan' ? 'floorplan' : 'photo',
                        label: typeof slide.label === 'string' ? slide.label : '',
                        placeholder: slide.placeholder === true || slide.placeholder === '1'
                    };
                });
        }
    } catch (error) {
    }

    if (card.dataset.imageSrc) {
        return [{
            src: card.dataset.imageSrc,
            type: 'photo',
            label: 'Image',
            placeholder: card.dataset.imagePlaceholder === '1'
        }];
    }

    return [];
}

function gallerySlideAlt(slide, index) {
    if (slide && slide.placeholder) {
        return translateAccommodationText('No image available yet');
    }

    const name = activeAccommodationCard ? activeAccommodationCard.dataset.huisName || '' : '';
    const label = slide && slide.label ? translateAccommodationText(slide.label) : translateAccommodationText('Image');
    const prefix = name !== '' ? name + ' - ' : '';

    return prefix + label + ' ' + (index + 1);
}

function renderGalleryThumbnails() {
    if (!modalThumbnails) {
        return;
    }

    modalThumbnails.textContent = '';
    const hasMultipleSlides = activeGallery.length > 1;
    modalThumbnails.hidden = !hasMultipleSlides;

    if (!hasMultipleSlides) {
        return;
    }

    activeGallery.forEach(function (slide, index) {
        const button = document.createElement('button');
        const image = document.createElement('img');

        button.type = 'button';
        button.className = 'accommodation-modal__thumb';
        button.classList.toggle('is-active', index === activeGalleryIndex);
        button.setAttribute('aria-label', translateAccommodationText('Image') + ' ' + (index + 1) + ' ' + translateAccommodationText('of') + ' ' + activeGallery.length);
        button.setAttribute('aria-current', index === activeGalleryIndex ? 'true' : 'false');
        button.addEventListener('click', function () {
            goToAccommodationSlide(index);
        });

        image.src = slide.src;
        image.alt = '';
        image.loading = 'lazy';
        image.classList.toggle('is-floorplan', slide.type === 'floorplan');
        image.classList.toggle('is-placeholder', slide.placeholder);
        image.addEventListener('error', function () {
            if (modalPhoto && modalPhoto.dataset.fallbackSrc && image.src !== modalPhoto.dataset.fallbackSrc) {
                image.src = modalPhoto.dataset.fallbackSrc;
                image.classList.add('is-placeholder');
            }
        });

        button.appendChild(image);
        modalThumbnails.appendChild(button);
    });
}

function renderAccommodationGallery() {
    if (!modalPhoto || !modalImage) {
        return;
    }

    if (activeGallery.length === 0) {
        activeGallery = [{
            src: modalPhoto.dataset.fallbackSrc || modalPhoto.src,
            type: 'photo',
            label: 'Image',
            placeholder: true
        }];
        activeGalleryIndex = 0;
    }

    const slide = activeGallery[activeGalleryIndex] || activeGallery[0];
    const hasMultipleSlides = activeGallery.length > 1;
    const isPlaceholder = Boolean(slide.placeholder);
    const isFloorplan = slide.type === 'floorplan';
    const currentSource = modalPhoto.getAttribute('src') || '';

    modalImage.className = 'accommodation-modal__gallery';
    modalImage.classList.toggle('is-placeholder', isPlaceholder);
    modalImage.classList.toggle('is-floorplan', isFloorplan);
    modalImage.classList.toggle('has-multiple-images', hasMultipleSlides);

    modalPhoto.classList.toggle('is-floorplan', isFloorplan);
    modalPhoto.classList.toggle('is-placeholder', isPlaceholder);
    modalPhoto.alt = gallerySlideAlt(slide, activeGalleryIndex);
    modalPhoto.dataset.fallbackApplied = isPlaceholder ? '1' : '0';

    if (currentSource !== slide.src) {
        modalPhoto.classList.add('is-loading');
        modalPhoto.src = slide.src;
    } else {
        window.requestAnimationFrame(function () {
            modalPhoto.classList.remove('is-loading');
        });
    }

    if (modalPlaceholder) {
        modalPlaceholder.hidden = !isPlaceholder;
        modalPlaceholder.textContent = translateAccommodationText('No image available yet');
    }

    if (modalPrevButton) {
        modalPrevButton.hidden = !hasMultipleSlides;
    }
    if (modalNextButton) {
        modalNextButton.hidden = !hasMultipleSlides;
    }
    if (modalCounter) {
        modalCounter.hidden = !hasMultipleSlides;
        modalCounter.textContent = (activeGalleryIndex + 1) + ' / ' + activeGallery.length;
        modalCounter.setAttribute('aria-label', translateAccommodationText('Image') + ' ' + (activeGalleryIndex + 1) + ' ' + translateAccommodationText('of') + ' ' + activeGallery.length);
    }

    renderGalleryThumbnails();
}

function goToAccommodationSlide(index) {
    if (activeGallery.length < 1) {
        return;
    }

    activeGalleryIndex = (index + activeGallery.length) % activeGallery.length;
    renderAccommodationGallery();
}

function showPreviousAccommodationSlide() {
    if (activeGallery.length > 1) {
        goToAccommodationSlide(activeGalleryIndex - 1);
    }
}

function showNextAccommodationSlide() {
    if (activeGallery.length > 1) {
        goToAccommodationSlide(activeGalleryIndex + 1);
    }
}

function parseFacilityNames(value) {
    return String(value || '')
        .split(',')
        .map(function (facility) {
            return facility.trim();
        })
        .filter(function (facility) {
            return facility !== '';
        });
}

function renderAccommodationFacilities(card) {
    if (!modalFacilities || !modalFacilitiesSection) {
        return;
    }

    const facilities = parseFacilityNames(card ? card.dataset.facilities || '' : '');
    const visibleFacilities = facilitiesExpanded ? facilities : facilities.slice(0, FACILITIES_COLLAPSED_LIMIT);
    const remainingCount = Math.max(0, facilities.length - FACILITIES_COLLAPSED_LIMIT);

    modalFacilities.textContent = '';
    modalFacilitiesSection.hidden = false;

    if (facilities.length === 0 || (facilities.length === 1 && facilities[0] === 'No facilities listed')) {
        const emptyChip = document.createElement('span');
        emptyChip.className = 'accommodation-modal__chip accommodation-modal__chip--muted';
        emptyChip.textContent = translateAccommodationText('No facilities listed');
        modalFacilities.appendChild(emptyChip);
    } else {
        visibleFacilities.forEach(function (facility) {
            const chip = document.createElement('span');
            chip.className = 'accommodation-modal__chip';
            chip.textContent = translateAccommodationText(facility);
            modalFacilities.appendChild(chip);
        });
    }

    if (modalFacilitiesToggle) {
        modalFacilitiesToggle.hidden = remainingCount === 0;
        modalFacilitiesToggle.textContent = facilitiesExpanded
            ? translateAccommodationText('Show less')
            : '+' + remainingCount + ' ' + translateAccommodationText('more');
        modalFacilitiesToggle.setAttribute('aria-expanded', facilitiesExpanded ? 'true' : 'false');
        modalFacilitiesToggle.setAttribute('aria-label', facilitiesExpanded ? translateAccommodationText('Show less') : translateAccommodationText('Show more'));
    }
}

function updateAccommodationModal(card) {
    if (!card) {
        return;
    }

    if (modalDescription && modalDescriptionSection) {
        const description = translateAccommodationText(card.dataset.description || '').trim();
        modalDescription.textContent = description;
        modalDescriptionSection.hidden = description === '';
    }

    renderAccommodationFacilities(card);
    renderAccommodationGallery();
}

function markAccommodationImagePlaceholder(image) {
    if (!image) {
        return;
    }

    const imageContainer = image.closest('.accommodation-card__image, .accommodation-modal__gallery, .accommodation-modal__stage');
    const card = image.closest('[data-accommodation-card]');

    if (imageContainer) {
        imageContainer.classList.add('is-placeholder');
    }

    if (card) {
        card.dataset.imagePlaceholder = '1';
        if (image.dataset.fallbackSrc) {
            card.dataset.imageSrc = image.dataset.fallbackSrc;
        }
    }

    image.alt = translateAccommodationText('No image available yet');
}

document.querySelectorAll('.accommodation-card__photo').forEach(function (image) {
    image.addEventListener('error', function () {
        if (image.dataset.fallbackSrc && image.dataset.fallbackApplied !== '1') {
            image.dataset.fallbackApplied = '1';
            image.src = image.dataset.fallbackSrc;
        }
        markAccommodationImagePlaceholder(image);
    });
});

if (modalPhoto) {
    modalPhoto.addEventListener('load', function () {
        modalPhoto.classList.remove('is-loading');
    });

    modalPhoto.addEventListener('error', function () {
        if (modalPhoto.dataset.fallbackSrc && modalPhoto.dataset.fallbackApplied !== '1') {
            modalPhoto.dataset.fallbackApplied = '1';
            if (activeGallery[activeGalleryIndex]) {
                activeGallery[activeGalleryIndex].src = modalPhoto.dataset.fallbackSrc;
                activeGallery[activeGalleryIndex].placeholder = true;
                activeGallery[activeGalleryIndex].type = 'photo';
            }
            modalPhoto.src = modalPhoto.dataset.fallbackSrc;
        }
        modalPhoto.classList.remove('is-loading');
        markAccommodationImagePlaceholder(modalPhoto);
    });
}

function openAccommodationModal(card) {
    if (!card || !accommodationModal || !modalDialog) {
        return;
    }

    previouslyFocusedElement = document.activeElement;
    activeAccommodationCard = card;
    activeAccommodation = {
        huisId: card.dataset.huisId,
        name: card.dataset.huisName,
        max: card.dataset.max,
        detailUrl: card.dataset.detailUrl
    };
    activeGallery = parseAccommodationGallery(card);
    activeGalleryIndex = 0;
    facilitiesExpanded = false;

    const priceText = '\u20ac' + (card.dataset.price || '0');

    if (modalTitle) {
        modalTitle.textContent = card.dataset.huisName || '';
    }
    if (modalLocation) {
        modalLocation.textContent = card.dataset.location || '';
    }
    if (modalPrice) {
        modalPrice.textContent = priceText;
    }
    if (modalBookingPrice) {
        modalBookingPrice.textContent = priceText;
    }
    if (modalMax) {
        modalMax.textContent = (card.dataset.max || '0') + ' guests';
    }

    updateAccommodationModal(card);

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

if (modalPrevButton) {
    modalPrevButton.addEventListener('click', showPreviousAccommodationSlide);
}

if (modalNextButton) {
    modalNextButton.addEventListener('click', showNextAccommodationSlide);
}

if (modalFacilitiesToggle) {
    modalFacilitiesToggle.addEventListener('click', function () {
        facilitiesExpanded = !facilitiesExpanded;
        renderAccommodationFacilities(activeAccommodationCard);
    });
}

if (modalStage) {
    modalStage.addEventListener('touchstart', function (event) {
        if (activeGallery.length < 2 || event.changedTouches.length === 0) {
            return;
        }

        touchStartX = event.changedTouches[0].clientX;
        touchStartY = event.changedTouches[0].clientY;
    }, { passive: true });

    modalStage.addEventListener('touchend', function (event) {
        if (activeGallery.length < 2 || event.changedTouches.length === 0) {
            return;
        }

        const deltaX = event.changedTouches[0].clientX - touchStartX;
        const deltaY = event.changedTouches[0].clientY - touchStartY;

        if (Math.abs(deltaX) < 45 || Math.abs(deltaX) <= Math.abs(deltaY)) {
            return;
        }

        if (deltaX > 0) {
            showPreviousAccommodationSlide();
        } else {
            showNextAccommodationSlide();
        }
    }, { passive: true });
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
        return;
    }

    if (!accommodationModal || accommodationModal.hidden || (bookingModal && !bookingModal.hidden)) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        showPreviousAccommodationSlide();
    } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        showNextAccommodationSlide();
    }
});
