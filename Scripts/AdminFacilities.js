(() => {
    const picker = document.querySelector('[data-admin-facility-picker]');
    const typeSelect = document.querySelector('[data-admin-cottage-type]');

    if (!picker) {
        return;
    }

    const searchInput = picker.querySelector('[data-admin-facility-search]');
    const categoryButtons = Array.from(picker.querySelectorAll('[data-admin-facility-category]'));
    const tags = Array.from(picker.querySelectorAll('[data-admin-facility-tag]'));
    const groups = Array.from(picker.querySelectorAll('[data-admin-facility-group]'));
    const selectedPreview = picker.querySelector('[data-admin-selected-preview]');
    const selectedCount = picker.querySelector('[data-admin-selected-count]');
    const clearButton = picker.querySelector('[data-admin-facility-clear]');
    const resetButton = picker.querySelector('[data-admin-type-preset-reset]');
    const selectCategoryButtons = Array.from(picker.querySelectorAll('[data-admin-select-category]'));
    let activeCategory = 'all';

    function checkedInputs() {
        return tags
            .map((tag) => tag.querySelector('input[type="checkbox"]'))
            .filter((input) => input && input.checked);
    }

    function selectedTypeOption() {
        if (!typeSelect || typeSelect.selectedIndex < 0) {
            return null;
        }

        const option = typeSelect.options[typeSelect.selectedIndex];
        return option && option.value ? option : null;
    }

    function selectedTypeFacilities() {
        const option = selectedTypeOption();

        if (!option) {
            return [];
        }

        return String(option.dataset.presetFacilities || '')
            .split(',')
            .map((value) => value.trim())
            .filter((value) => value !== '');
    }

    function applyTypePreset() {
        const values = new Set(selectedTypeFacilities());

        tags.forEach((tag) => {
            const input = tag.querySelector('input[type="checkbox"]');
            if (input) {
                input.checked = values.has(input.value);
            }
        });

        updateAll();
    }

    function tagName(tag) {
        return `${tag.dataset.name || ''} ${tag.textContent || ''}`.toLowerCase();
    }

    function matchesSearch(tag) {
        const search = searchInput ? searchInput.value.trim().toLowerCase() : '';
        return search === '' || tagName(tag).includes(search);
    }

    function matchesCategory(tag) {
        return activeCategory === 'all' || tag.dataset.category === activeCategory;
    }

    function updateTagState(tag) {
        const input = tag.querySelector('input[type="checkbox"]');
        tag.classList.toggle('is-selected', Boolean(input && input.checked));
    }

    function updateSelectedPreview() {
        const selected = checkedInputs();

        if (selectedCount) {
            selectedCount.textContent = `${selected.length} ${selected.length === 1 ? 'facility' : 'facilities'} selected`;
        }

        if (!selectedPreview) {
            return;
        }

        selectedPreview.replaceChildren();

        if (selected.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'admin-selected-empty';
            empty.textContent = 'No facilities selected';
            selectedPreview.appendChild(empty);
            return;
        }

        selected.forEach((input) => {
            const tag = input.closest('[data-admin-facility-tag]');
            const label = tag ? tag.querySelector('span span') : null;
            const chip = document.createElement('button');
            const text = document.createElement('span');
            const removeIcon = document.createElement('strong');

            chip.type = 'button';
            chip.className = 'admin-selected-chip';
            chip.dataset.removeFacilityId = input.value;
            text.textContent = label ? label.textContent : input.value;
            removeIcon.setAttribute('aria-hidden', 'true');
            removeIcon.textContent = 'x';
            chip.append(text, removeIcon);
            chip.setAttribute('aria-label', `Remove ${label ? label.textContent : 'facility'}`);
            selectedPreview.appendChild(chip);
        });
    }

    function applyFilters() {
        groups.forEach((group) => {
            let visibleInGroup = 0;

            tags
                .filter((tag) => tag.closest('[data-admin-facility-group]') === group)
                .forEach((tag) => {
                    const visible = matchesCategory(tag) && matchesSearch(tag);
                    tag.hidden = !visible;
                    if (visible) {
                        visibleInGroup += 1;
                    }
                });

            group.hidden = visibleInGroup === 0;
        });
    }

    function updateResetState() {
        if (resetButton) {
            resetButton.disabled = selectedTypeOption() === null;
        }
    }

    function updateAll() {
        tags.forEach(updateTagState);
        updateSelectedPreview();
        applyFilters();
        updateResetState();
    }

    tags.forEach((tag) => {
        const input = tag.querySelector('input[type="checkbox"]');
        if (input) {
            input.addEventListener('change', updateAll);
        }
    });

    if (typeSelect) {
        typeSelect.addEventListener('change', applyTypePreset);
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.adminFacilityCategory || 'all';
            categoryButtons.forEach((item) => item.classList.toggle('is-active', item === button));
            applyFilters();
        });
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            checkedInputs().forEach((input) => {
                input.checked = false;
            });
            updateAll();
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', applyTypePreset);
    }

    selectCategoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const category = button.dataset.adminSelectCategory || '';

            tags
                .filter((tag) => tag.dataset.category === category)
                .forEach((tag) => {
                    const input = tag.querySelector('input[type="checkbox"]');
                    if (input) {
                        input.checked = true;
                    }
                });

            updateAll();
        });
    });

    if (selectedPreview) {
        selectedPreview.addEventListener('click', (event) => {
            const chip = event.target.closest('[data-remove-facility-id]');

            if (!chip) {
                return;
            }

            const input = Array.from(picker.querySelectorAll('input[type="checkbox"]'))
                .find((checkbox) => checkbox.value === chip.dataset.removeFacilityId);

            if (input) {
                input.checked = false;
                updateAll();
            }
        });
    }

    updateAll();
})();

(() => {
    const preview = document.querySelector('[data-admin-gallery-preview]');
    const typeSelect = document.querySelector('[data-admin-cottage-type]');

    if (!preview || !typeSelect) {
        return;
    }

    const grid = preview.querySelector('[data-admin-gallery-grid]');
    const source = preview.querySelector('[data-admin-gallery-source]');
    let galleries = {};

    try {
        galleries = JSON.parse(preview.dataset.galleryMap || '{}');
    } catch (error) {
        galleries = {};
    }

    function selectedOption() {
        if (typeSelect.selectedIndex < 0) {
            return null;
        }

        const option = typeSelect.options[typeSelect.selectedIndex];
        return option && option.value ? option : null;
    }

    function renderGallery() {
        const option = selectedOption();

        if (grid) {
            grid.replaceChildren();
        }

        if (!option) {
            if (source) {
                source.textContent = 'Select a cottage type';
            }
            return;
        }

        const imageSourceId = option.dataset.imageSourceId || '';
        const slides = Array.isArray(galleries[option.value]) ? galleries[option.value] : [];

        if (source) {
            source.textContent = imageSourceId ? `Images/Accommodations/${imageSourceId}/` : '';
        }

        if (!grid) {
            return;
        }

        slides.forEach((slide) => {
            const figure = document.createElement('figure');
            const image = document.createElement('img');
            const caption = document.createElement('figcaption');

            figure.className = 'admin-gallery-preview__item';
            if (slide.placeholder) {
                figure.classList.add('is-placeholder');
            }

            image.src = slide.src;
            image.alt = slide.label || option.value;
            if (slide.type === 'floorplan') {
                image.classList.add('is-floorplan');
            }

            caption.textContent = slide.placeholder ? 'No images found' : (slide.label || 'Image');
            figure.append(image, caption);
            grid.appendChild(figure);
        });
    }

    typeSelect.addEventListener('change', renderGallery);
    renderGallery();
})();
