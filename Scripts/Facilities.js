(() => {
    const dataElement = document.getElementById('facilities-data');
    const facilityCards = Array.from(document.querySelectorAll('[data-facility-card]'));
    const filterInputs = Array.from(document.querySelectorAll('[data-facility-filter]'));
    const cottageCards = Array.from(document.querySelectorAll('[data-cottage-card]'));
    const clearButton = document.querySelector('[data-clear-facility-filters]');
    const noResults = document.querySelector('[data-no-results]');
    const resultsCount = document.querySelector('[data-results-count]');
    const filterDisclosure = document.querySelector('[data-facility-filter-disclosure]');
    const filterSelectionCount = document.querySelector('[data-filter-selection-count]');
    const modal = document.querySelector('[data-facility-modal]');
    const modalDialog = document.querySelector('[data-facility-modal-dialog]');
    const modalIcon = document.querySelector('[data-modal-icon]');
    const modalCategory = document.querySelector('[data-modal-category]');
    const modalTitle = document.querySelector('[data-modal-title]');
    const modalDescription = document.querySelector('[data-modal-description]');
    const modalCottages = document.querySelector('[data-modal-cottages]');
    const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    document.querySelectorAll('.facility-cottage__image img').forEach((image) => {
        image.addEventListener('error', () => {
            if (image.dataset.fallbackSrc && image.dataset.fallbackApplied !== '1') {
                image.dataset.fallbackApplied = '1';
                image.src = image.dataset.fallbackSrc;
            }
        });
    });

    let facilities = [];
    let accommodations = [];
    let activeFacility = null;
    let previousFocus = null;

    if (dataElement) {
        try {
            const parsed = JSON.parse(dataElement.textContent || '{}');
            facilities = Array.isArray(parsed.facilities) ? parsed.facilities : [];
            accommodations = Array.isArray(parsed.accommodations) ? parsed.accommodations : [];
        } catch {
            facilities = [];
            accommodations = [];
        }
    }

    function translate(value) {
        if (window.MapleLanguage && typeof window.MapleLanguage.translate === 'function') {
            return window.MapleLanguage.translate(value);
        }

        return value;
    }

    function slug(value) {
        const normalized = String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        return normalized || 'facility';
    }

    function iconClass(value) {
        const icon = String(value || '').trim();

        if (/\bfa-(?:solid|regular|brands|light|thin|duotone)\b/.test(icon)) {
            return icon;
        }

        const icons = {
            wifi: 'fa-solid fa-wifi',
            terrace: 'fa-solid fa-umbrella-beach',
            lake: 'fa-solid fa-water',
            fireplace: 'fa-solid fa-fire-flame-curved',
            bbq: 'fa-solid fa-burger',
            parking: 'fa-solid fa-square-parking',
            dishwasher: 'fa-solid fa-kitchen-set',
            pet: 'fa-solid fa-paw',
            fridge: 'fa-solid fa-snowflake',
            stove: 'fa-solid fa-fire-burner',
            microwave: 'fa-solid fa-clock',
            coffee: 'fa-solid fa-mug-saucer',
            kettle: 'fa-solid fa-mug-hot',
            utensils: 'fa-solid fa-utensils',
            bathroom: 'fa-solid fa-bath',
            shower: 'fa-solid fa-shower',
            towel: 'fa-solid fa-soap',
            bed: 'fa-solid fa-bed',
            beds: 'fa-solid fa-bed',
            linen: 'fa-solid fa-layer-group',
            sofa: 'fa-solid fa-couch',
            dining: 'fa-solid fa-chair',
            heating: 'fa-solid fa-temperature-three-quarters',
            tv: 'fa-solid fa-tv',
            chair: 'fa-solid fa-chair',
            fire: 'fa-solid fa-fire',
            mountain: 'fa-solid fa-mountain-sun',
            'smart-tv': 'fa-solid fa-tv',
            power: 'fa-solid fa-plug',
            accessibility: 'fa-solid fa-wheelchair',
            canoe: 'fa-solid fa-sailboat',
            trail: 'fa-solid fa-person-hiking',
            fishing: 'fa-solid fa-fish',
            bike: 'fa-solid fa-bicycle',
        };

        return icons[slug(icon)] || 'fa-solid fa-circle-info';
    }

    function facilityByValue(facilityValue) {
        return facilities.find((facility) => String(facility.value) === String(facilityValue)) || null;
    }

    function accommodationById(accommodationId) {
        return accommodations.find((accommodation) => String(accommodation.id) === String(accommodationId)) || null;
    }

    function facilityTranslation(facility) {
        const description = String(facility.description || '');

        return {
            name: translate(facility.name || facility.value || ''),
            short: translate(facility.short || description),
            description: translate(description),
            category: translate(facility.category || ''),
        };
    }

    function selectedFacilityValues() {
        return filterInputs
            .filter((input) => input.checked)
            .map((input) => String(input.value));
    }

    function cottageFacilityValues(card) {
        return String(card.dataset.facilities || '')
            .split(',')
            .map((value) => value.trim())
            .filter((value) => value !== '');
    }

    function createChip(text) {
        const chip = document.createElement('span');
        chip.className = 'facility-chip';
        chip.textContent = text;

        return chip;
    }

    function updateCottageChips(card, selectedValues) {
        const chipWrap = card.querySelector('[data-cottage-matches]');
        const cottage = accommodationById(card.dataset.cottageId);

        if (!chipWrap || !cottage) {
            return;
        }

        const facilityValues = Array.isArray(cottage.facilityValues)
            ? cottage.facilityValues.map((facilityValue) => String(facilityValue))
            : cottageFacilityValues(card);
        const valuesToShow = selectedValues.length > 0
            ? selectedValues
            : facilityValues.filter((facilityValue) => {
                const facility = facilityByValue(facilityValue);
                return facility && (facility.popular || facility.filter);
            }).slice(0, 4);

        chipWrap.replaceChildren();

        valuesToShow.forEach((facilityValue) => {
            const facility = facilityByValue(facilityValue);
            if (!facility || !facilityValues.includes(String(facility.value))) {
                return;
            }

            chipWrap.appendChild(createChip(facilityTranslation(facility).name));
        });
    }

    function updateResultsCount(visibleCount, selectedCount) {
        if (!resultsCount) {
            return;
        }

        const noun = visibleCount === 1 ? translate('cottage') : translate('cottages');
        const suffix = selectedCount > 0 ? translate('match selected facilities') : translate('shown');
        resultsCount.textContent = `${visibleCount} ${noun} ${suffix}`;
    }

    function applyFilters() {
        const selectedValues = selectedFacilityValues();
        let visibleCount = 0;

        if (filterSelectionCount) {
            filterSelectionCount.textContent = selectedValues.length > 0
                ? `${selectedValues.length} ${translate('Selected facilities')}`
                : translate('No facilities selected');
        }

        cottageCards.forEach((card) => {
            const facilityValues = cottageFacilityValues(card);
            const matches = selectedValues.every((facilityValue) => facilityValues.includes(facilityValue));
            card.hidden = !matches;

            if (matches) {
                visibleCount += 1;
                updateCottageChips(card, selectedValues);
            }
        });

        if (noResults) {
            noResults.hidden = visibleCount > 0;
        }

        updateResultsCount(visibleCount, selectedValues.length);
    }

    function updateFacilityText() {
        facilityCards.forEach((card) => {
            const facility = facilityByValue(card.dataset.facilityValue);

            if (!facility) {
                return;
            }

            const translation = facilityTranslation(facility);
            const nameElement = card.querySelector('[data-facility-name]');
            const shortElement = card.querySelector('[data-facility-short]');

            if (nameElement) {
                nameElement.textContent = translation.name;
            }

            if (shortElement) {
                shortElement.textContent = translation.short || translation.description;
            }

            card.setAttribute('aria-label', `${translate('Open facility details')}: ${translation.name}`);
        });

        document.querySelectorAll('[data-filter-label]').forEach((label) => {
            const facility = facilityByValue(label.dataset.facilityValue);
            if (facility) {
                label.textContent = facilityTranslation(facility).name;
            }
        });

        document.querySelectorAll('[data-comparison-facility-name]').forEach((cell) => {
            const facility = facilityByValue(cell.dataset.facilityValue);
            if (facility) {
                cell.textContent = facilityTranslation(facility).name;
            }
        });

        applyFilters();

        if (activeFacility) {
            updateModal(activeFacility);
        }
    }

    function setModalIcon(facility) {
        if (!modalIcon) {
            return;
        }

        let iconElement = modalIcon.querySelector('i');

        if (!iconElement) {
            iconElement = document.createElement('i');
            modalIcon.appendChild(iconElement);
        }

        iconElement.className = iconClass(facility.icon || facility.slug);
    }

    function updateModal(facility) {
        const translation = facilityTranslation(facility);

        setModalIcon(facility);

        if (modalCategory) {
            modalCategory.textContent = translation.category;
        }

        if (modalTitle) {
            modalTitle.textContent = translation.name;
        }

        if (modalDescription) {
            modalDescription.textContent = translation.description;
        }

        if (modalCottages) {
            modalCottages.replaceChildren();
            const cottages = Array.isArray(facility.accommodations) ? facility.accommodations : [];

            if (cottages.length === 0) {
                const item = document.createElement('li');
                item.textContent = translate('No cottages listed yet.');
                modalCottages.appendChild(item);
            }

            cottages.forEach((cottage) => {
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.href = cottage.url || `Accomodatie.php#huis-${cottage.id}`;
                link.textContent = cottage.name;
                item.appendChild(link);
                modalCottages.appendChild(item);
            });
        }
    }

    function openModal(facility) {
        if (!modal || !facility) {
            return;
        }

        activeFacility = facility;
        previousFocus = document.activeElement;
        updateModal(facility);
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-facility-modal-open');

        if (modalDialog) {
            modalDialog.focus();
        }
    }

    function closeModal() {
        if (!modal || modal.hidden) {
            return;
        }

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-facility-modal-open');
        activeFacility = null;

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    function trapFocus(event) {
        if (!modal || modal.hidden || event.key !== 'Tab') {
            return;
        }

        const focusableElements = Array.from(modal.querySelectorAll(focusableSelector))
            .filter((element) => element.offsetParent !== null);

        if (focusableElements.length === 0) {
            event.preventDefault();
            return;
        }

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    }

    facilityCards.forEach((card) => {
        card.addEventListener('click', () => openModal(facilityByValue(card.dataset.facilityValue)));
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openModal(facilityByValue(card.dataset.facilityValue));
            }
        });
    });

    filterInputs.forEach((input) => {
        input.addEventListener('change', applyFilters);
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            filterInputs.forEach((input) => {
                input.checked = false;
            });
            applyFilters();
        });
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target.closest('[data-facility-modal-close]')) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }

        trapFocus(event);
    });

    window.addEventListener('maple:languagechange', updateFacilityText);

    if (filterDisclosure && window.matchMedia) {
        const compactFilterQuery = window.matchMedia('(max-width: 700px)');
        const setInitialFilterState = () => {
            filterDisclosure.open = !compactFilterQuery.matches;
        };

        setInitialFilterState();
        if (typeof compactFilterQuery.addEventListener === 'function') {
            compactFilterQuery.addEventListener('change', setInitialFilterState);
        } else if (typeof compactFilterQuery.addListener === 'function') {
            compactFilterQuery.addListener(setInitialFilterState);
        }
    }

    updateFacilityText();
})();
