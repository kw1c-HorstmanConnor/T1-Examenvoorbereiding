(() => {
    const picker = document.querySelector('[data-admin-facility-picker]');

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
    const resetButton = picker.querySelector('[data-admin-preset-reset]');
    const presetButtons = Array.from(picker.querySelectorAll('[data-admin-preset]'));
    const selectCategoryButtons = Array.from(picker.querySelectorAll('[data-admin-select-category]'));
    let activeCategory = 'all';
    let activePreset = presetButtons.find((button) => button.classList.contains('is-selected'))?.dataset.adminPreset || 'custom';
    let lastPreset = activePreset !== 'custom' ? activePreset : '';

    function checkedInputs() {
        return tags
            .map((tag) => tag.querySelector('input[type="checkbox"]'))
            .filter((input) => input && input.checked);
    }

    function selectedValues() {
        return checkedInputs()
            .map((input) => input.value)
            .sort();
    }

    function presetValues(button) {
        return String(button.dataset.adminPresetFacilities || '')
            .split(',')
            .map((value) => value.trim())
            .filter((value) => value !== '')
            .sort();
    }

    function sameValues(left, right) {
        return left.length === right.length && left.every((value, index) => value === right[index]);
    }

    function detectPreset() {
        const selected = selectedValues();
        const matchingPreset = presetButtons.find((button) => {
            const preset = button.dataset.adminPreset || 'custom';
            return preset !== 'custom' && sameValues(selected, presetValues(button));
        });

        return matchingPreset ? matchingPreset.dataset.adminPreset : 'custom';
    }

    function tagName(tag) {
        return `${tag.dataset.name || ''} ${tag.textContent || ''}`.toLowerCase();
    }

    function matchesSearch(tag) {
        if (!searchInput) {
            return true;
        }

        const search = searchInput.value.trim().toLowerCase();
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

    function updatePresetState(nextPreset = detectPreset()) {
        activePreset = nextPreset || 'custom';

        if (activePreset !== 'custom') {
            lastPreset = activePreset;
        }

        presetButtons.forEach((button) => {
            const isSelected = button.dataset.adminPreset === activePreset;
            const status = button.querySelector('em');
            button.classList.toggle('is-selected', isSelected);
            button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');

            if (status) {
                status.textContent = isSelected ? 'Selected' : '';
            }
        });

        if (resetButton) {
            resetButton.disabled = !lastPreset;
        }
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

    function updateAll(options = {}) {
        tags.forEach(updateTagState);
        updateSelectedPreview();
        applyFilters();
        updatePresetState(options.preset || detectPreset(), options.rememberPreset !== false);
    }

    function applyPreset(button) {
        const preset = button.dataset.adminPreset || 'custom';

        if (preset !== 'custom') {
            const values = new Set(presetValues(button));
            tags.forEach((tag) => {
                const input = tag.querySelector('input[type="checkbox"]');
                if (input) {
                    input.checked = values.has(input.value);
                }
            });
            lastPreset = preset;
        }

        updateAll({
            preset,
            rememberPreset: preset !== 'custom',
        });
    }

    function resetToPreset() {
        if (!lastPreset) {
            return;
        }

        const button = presetButtons.find((presetButton) => presetButton.dataset.adminPreset === lastPreset);
        if (button) {
            applyPreset(button);
        }
    }

    tags.forEach((tag) => {
        const input = tag.querySelector('input[type="checkbox"]');

        if (!input) {
            return;
        }

        input.addEventListener('change', () => updateAll({ rememberPreset: false }));
    });

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
            updateAll({ preset: 'custom', rememberPreset: false });
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', resetToPreset);
    }

    presetButtons.forEach((button) => {
        button.addEventListener('click', () => applyPreset(button));
    });

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
            updateAll({ rememberPreset: false });
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
                updateAll({ rememberPreset: false });
            }
        });
    }

    updateAll();
})();

(() => {
    const uploader = document.querySelector('[data-admin-image-uploader]');

    if (!uploader) {
        return;
    }

    const input = uploader.querySelector('[data-admin-image-input]');
    const removeInput = uploader.querySelector('[data-admin-image-remove]');
    const preview = uploader.querySelector('[data-admin-image-preview]');
    const previewWrap = uploader.querySelector('[data-admin-image-preview-wrap]');
    const placeholder = uploader.querySelector('[data-admin-image-placeholder]');
    const currentSrc = uploader.dataset.currentSrc || '';
    const fallbackSrc = uploader.dataset.fallbackSrc || currentSrc;
    let objectUrl = '';

    function setPlaceholder(text, src = fallbackSrc) {
        if (preview) {
            preview.src = src;
            preview.alt = text;
        }

        if (placeholder) {
            placeholder.textContent = text;
        }

        if (previewWrap) {
            previewWrap.classList.add('is-placeholder');
        }
    }

    function setPreview(src, altText) {
        if (preview) {
            preview.src = src;
            preview.alt = altText;
        }

        if (previewWrap) {
            previewWrap.classList.remove('is-placeholder');
        }
    }

    function revokeObjectUrl() {
        if (objectUrl !== '') {
            URL.revokeObjectURL(objectUrl);
            objectUrl = '';
        }
    }

    if (input) {
        input.addEventListener('change', () => {
            revokeObjectUrl();
            const file = input.files && input.files.length > 0 ? input.files[0] : null;

            if (!file) {
                if (removeInput && removeInput.checked) {
                    setPlaceholder('Image will be removed');
                } else {
                    setPreview(currentSrc || fallbackSrc, 'Current accommodation image');
                }
                return;
            }

            objectUrl = URL.createObjectURL(file);
            setPreview(objectUrl, file.name || 'Selected accommodation image');

            if (removeInput) {
                removeInput.checked = false;
            }
        });
    }

    if (removeInput) {
        removeInput.addEventListener('change', () => {
            revokeObjectUrl();

            if (removeInput.checked) {
                if (input) {
                    input.value = '';
                }
                setPlaceholder('Image will be removed');
                return;
            }

            setPreview(currentSrc || fallbackSrc, 'Current accommodation image');
        });
    }

    window.addEventListener('beforeunload', revokeObjectUrl);
})();
