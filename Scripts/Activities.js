(() => {
    const dataElement = document.getElementById('activities-data');
    const cards = Array.from(document.querySelectorAll('[data-activity-card]'));
    const modal = document.querySelector('[data-activity-modal]');
    const modalPanel = document.querySelector('[data-activity-modal-panel]');
    const modalOverlay = document.querySelector('[data-activity-modal-overlay]');
    const closeButton = document.querySelector('[data-activity-modal-close]');
    const modalImage = document.querySelector('[data-activity-modal-image]');
    const modalTitle = document.querySelector('[data-activity-modal-title]');
    const modalDescription = document.querySelector('[data-activity-modal-description]');
    const modalLocation = document.querySelector('[data-activity-modal-location]');
    const modalDuration = document.querySelector('[data-activity-modal-duration]');
    const modalPrice = document.querySelector('[data-activity-modal-price]');
    const modalExtraWrap = document.querySelector('[data-activity-modal-extra-wrap]');
    const modalExtra = document.querySelector('[data-activity-modal-extra]');
    const locationRow = document.querySelector('[data-activity-modal-location-row]');
    const durationRow = document.querySelector('[data-activity-modal-duration-row]');
    const priceRow = document.querySelector('[data-activity-modal-price-row]');
    const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    let activities = [];
    let currentActivity = null;
    let lastFocusedElement = null;
    let closeTimer = null;

    if (dataElement) {
        try {
            activities = JSON.parse(dataElement.textContent || '[]');
        } catch {
            activities = [];
        }
    }

    function getLanguage() {
        if (window.MapleLanguage && typeof window.MapleLanguage.getLanguage === 'function') {
            return window.MapleLanguage.getLanguage();
        }

        return 'en';
    }

    function translate(key) {
        if (window.MapleLanguage && typeof window.MapleLanguage.translate === 'function') {
            return window.MapleLanguage.translate(key);
        }

        return key;
    }

    function activityTranslation(activity) {
        const translations = activity.translations || {};
        const language = getLanguage();

        return translations[language] || translations.en || {
            title: '',
            summary: '',
            description: '',
            extra: '',
        };
    }

    function activityById(activityId) {
        return activities.find((activity) => String(activity.id) === String(activityId)) || null;
    }

    function imageUrl(activity) {
        const image = String(activity.image || 'Images/background.png').trim();

        if (/^(?:https?:)?\/\//.test(image) || image.startsWith('/')) {
            return image;
        }

        return `../${image.replace(/^\/+/, '')}`;
    }

    function formatPrice(price) {
        const amount = Number(price);

        if (!Number.isFinite(amount) || amount <= 0) {
            return translate('Free');
        }

        const localeMap = {
            en: 'en-US',
            de: 'de-DE',
            fr: 'fr-FR',
            es: 'es-ES',
        };

        return new Intl.NumberFormat(localeMap[getLanguage()] || 'en-US', {
            style: 'currency',
            currency: 'EUR',
        }).format(amount);
    }

    function setText(element, value) {
        if (element) {
            element.textContent = value || '';
        }
    }

    function setRow(row, value) {
        if (row) {
            row.hidden = !value;
        }
    }

    function updateCards() {
        cards.forEach((card) => {
            const activity = activityById(card.dataset.activityId);

            if (!activity) {
                return;
            }

            const translation = activityTranslation(activity);
            setText(card.querySelector('[data-activity-title]'), translation.title);
            setText(card.querySelector('[data-activity-summary]'), translation.summary);
            card.setAttribute('aria-label', `${translate('Open activity details')}: ${translation.title}`);
        });
    }

    function updateModal(activity) {
        if (!activity) {
            return;
        }

        const translation = activityTranslation(activity);
        const image = imageUrl(activity);

        if (modalImage) {
            modalImage.src = image;
            modalImage.alt = translation.title;
        }

        if (modal) {
            Array.from(modal.classList)
                .filter((className) => className.startsWith('activity-modal--'))
                .forEach((className) => modal.classList.remove(className));
            modal.classList.add(`activity-modal--${activity.imageClass || activity.slug || 'activity'}`);
        }

        setText(modalTitle, translation.title);
        setText(modalDescription, translation.description);
        setText(modalLocation, activity.location);
        setText(modalDuration, activity.duration);
        setText(modalPrice, formatPrice(activity.price));
        setText(modalExtra, translation.extra);
        setRow(locationRow, activity.location);
        setRow(durationRow, activity.duration);
        setRow(priceRow, true);

        if (modalExtraWrap) {
            modalExtraWrap.hidden = !translation.extra;
        }
    }

    function openModal(activity) {
        if (!modal || !activity) {
            return;
        }

        currentActivity = activity;
        lastFocusedElement = document.activeElement;
        updateModal(activity);
        window.clearTimeout(closeTimer);
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-modal-open');

        window.requestAnimationFrame(() => {
            modal.classList.add('is-open');
            modal.classList.remove('is-closing');
            if (modalPanel) {
                modalPanel.focus();
            }
        });
    }

    function closeModal() {
        if (!modal || modal.hidden) {
            return;
        }

        modal.classList.add('is-closing');
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-modal-open');

        closeTimer = window.setTimeout(() => {
            modal.hidden = true;
            modal.classList.remove('is-closing');
            currentActivity = null;

            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
        }, 210);
    }

    function openCard(card) {
        const activity = activityById(card.dataset.activityId);
        openModal(activity);
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

    cards.forEach((card) => {
        card.addEventListener('click', () => openCard(card));
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openCard(card);
            }
        });
    });

    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeModal);
    }

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }

        trapFocus(event);
    });

    window.addEventListener('maple:languagechange', () => {
        updateCards();

        if (currentActivity) {
            updateModal(currentActivity);
        }
    });

    updateCards();
})();
