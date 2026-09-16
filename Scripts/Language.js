(() => {
    const STORAGE_KEY = 'mapleCampLanguage';
    const DEFAULT_LANGUAGE = 'en';
    const SUPPORTED_LANGUAGES = ['en', 'es', 'fr', 'de'];
    const TRANSLATABLE_ATTRIBUTES = ['placeholder', 'aria-label', 'title'];
    const originalText = new WeakMap();
    const originalAttributes = new WeakMap();
    const originalTitle = document.title;
    let currentLanguage = DEFAULT_LANGUAGE;
    let isApplyingLanguage = false;
    let pendingLanguageApply = false;
    let mutationObserver = null;

    function getDictionary(language) {
        const languages = window.MapleLanguages || {};
        return languages[language] || languages[DEFAULT_LANGUAGE] || {};
    }

    function getStoredLanguage() {
        try {
            const storedLanguage = localStorage.getItem(STORAGE_KEY);
            return SUPPORTED_LANGUAGES.includes(storedLanguage) ? storedLanguage : DEFAULT_LANGUAGE;
        } catch {
            return DEFAULT_LANGUAGE;
        }
    }

    function storeLanguage(language) {
        try {
            localStorage.setItem(STORAGE_KEY, language);
        } catch {
        }
    }

    function replaceTrimmed(source, translated) {
        const start = source.search(/\S|$/);
        const trailingLength = source.length - source.trimEnd().length;
        const trailing = trailingLength > 0 ? source.slice(source.length - trailingLength) : '';
        return source.slice(0, start) + translated + trailing;
    }

    function translateDynamic(value, dictionary) {
        const trimmed = value.trim();
        const words = dictionary._words || {};

        const guestMatch = trimmed.match(/^(\d+)\s+(gast|gasten)$/i);
        if (guestMatch && words.guest && words.guests) {
            const amount = Number(guestMatch[1]);
            return `${amount} ${amount === 1 ? words.guest : words.guests}`;
        }

        const peopleMatch = trimmed.match(/^(\d+)\s+personen$/i);
        if (peopleMatch && words.people) {
            return `${peopleMatch[1]} ${words.people}`;
        }

        const bedroomMatch = trimmed.match(/^(\d+)\s+slaapkamers?$/i);
        if (bedroomMatch && words.bedrooms) {
            return `${bedroomMatch[1]} ${words.bedrooms}`;
        }

        const monthMatch = trimmed.match(/^(\d{1,2})\s+(januari|februari|maart|april|mei|juni|juli|augustus|september|oktober|november|december)\s+(\d{4})(.*)$/i);
        if (monthMatch && words.months) {
            const translatedMonth = words.months[monthMatch[2].toLowerCase()] || monthMatch[2];
            return `${monthMatch[1]} ${translatedMonth} ${monthMatch[3]}${monthMatch[4]}`;
        }

        const starsMatch = trimmed.match(/^(\d+)\s+van\s+(\d+)\s+sterren$/i);
        if (starsMatch && words.starsOf) {
            return words.starsOf
                .replace('{current}', starsMatch[1])
                .replace('{total}', starsMatch[2]);
        }

        return null;
    }

    function translateValue(value, dictionary) {
        const trimmed = value.trim();

        if (trimmed === '') {
            return value;
        }

        if (Object.prototype.hasOwnProperty.call(dictionary, trimmed)) {
            return replaceTrimmed(value, dictionary[trimmed]);
        }

        const dynamicTranslation = translateDynamic(value, dictionary);
        if (dynamicTranslation !== null) {
            return replaceTrimmed(value, dynamicTranslation);
        }

        return value;
    }

    function shouldSkipTextNode(node) {
        const parent = node.parentElement;

        if (!parent) {
            return true;
        }

        return shouldSkipElement(parent);
    }

    function shouldSkipElement(element) {
        return Boolean(element.closest('script, style, noscript, code, [data-no-translate]'));
    }

    function rememberElementAttributes(element, onlyAttribute = null) {
        if (!originalAttributes.has(element)) {
            originalAttributes.set(element, {});
        }

        const values = originalAttributes.get(element) || {};
        const attributes = onlyAttribute ? [onlyAttribute] : TRANSLATABLE_ATTRIBUTES;

        attributes.forEach((attribute) => {
            if (!TRANSLATABLE_ATTRIBUTES.includes(attribute)) {
                return;
            }

            if (element.hasAttribute(attribute)) {
                values[attribute] = element.getAttribute(attribute) || '';
            } else {
                delete values[attribute];
            }
        });

        originalAttributes.set(element, values);
    }

    function translateTextNodes(dictionary) {
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        let node = walker.nextNode();

        while (node) {
            if (!shouldSkipTextNode(node)) {
                if (!originalText.has(node)) {
                    originalText.set(node, node.nodeValue || '');
                }

                node.nodeValue = translateValue(originalText.get(node) || '', dictionary);
            }

            node = walker.nextNode();
        }
    }

    function translateAttributes(dictionary) {
        document.querySelectorAll('*').forEach((element) => {
            if (shouldSkipElement(element)) {
                return;
            }

            if (!originalAttributes.has(element)) {
                rememberElementAttributes(element);
            }

            const values = originalAttributes.get(element) || {};

            Object.entries(values).forEach(([attribute, source]) => {
                element.setAttribute(attribute, translateValue(source, dictionary));
            });
        });
    }

    function syncLanguageSelectors(language) {
        document.querySelectorAll('[data-language-select]').forEach((select) => {
            select.value = language;
            select.dataset.currentLanguage = language;
        });
    }

    function applyLanguage(language, persist = true) {
        const selectedLanguage = SUPPORTED_LANGUAGES.includes(language) ? language : DEFAULT_LANGUAGE;
        const dictionary = getDictionary(selectedLanguage);

        if (Object.keys(dictionary).length === 0) {
            return;
        }

        currentLanguage = selectedLanguage;
        isApplyingLanguage = true;
        const shouldResumeObserver = Boolean(mutationObserver && document.body);

        if (shouldResumeObserver) {
            mutationObserver.disconnect();
        }

        try {
            document.documentElement.lang = selectedLanguage;
            document.title = translateValue(originalTitle, dictionary);
            translateTextNodes(dictionary);
            translateAttributes(dictionary);
            syncLanguageSelectors(selectedLanguage);
        } finally {
            if (mutationObserver) {
                mutationObserver.takeRecords();
            }

            if (shouldResumeObserver) {
                observeLanguageMutations();
            }

            isApplyingLanguage = false;
        }

        if (persist) {
            storeLanguage(selectedLanguage);
        }
    }

    function scheduleLanguageApply() {
        if (pendingLanguageApply) {
            return;
        }

        pendingLanguageApply = true;

        window.requestAnimationFrame(() => {
            pendingLanguageApply = false;
            applyLanguage(currentLanguage, false);
        });
    }

    function watchLanguageChanges() {
        if (mutationObserver || !document.body) {
            return;
        }

        mutationObserver = new MutationObserver((mutations) => {
            if (isApplyingLanguage) {
                return;
            }

            let shouldApply = false;

            mutations.forEach((mutation) => {
                if (mutation.type === 'characterData' && !shouldSkipTextNode(mutation.target)) {
                    originalText.set(mutation.target, mutation.target.nodeValue || '');
                    shouldApply = true;
                    return;
                }

                if (mutation.type === 'attributes' && mutation.target instanceof Element && !shouldSkipElement(mutation.target)) {
                    rememberElementAttributes(mutation.target, mutation.attributeName);
                    shouldApply = true;
                    return;
                }

                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    shouldApply = true;
                }
            });

            if (shouldApply) {
                scheduleLanguageApply();
            }
        });

        observeLanguageMutations();
    }

    function observeLanguageMutations() {
        if (!mutationObserver || !document.body) {
            return;
        }

        mutationObserver.observe(document.body, {
            attributes: true,
            attributeFilter: TRANSLATABLE_ATTRIBUTES,
            characterData: true,
            childList: true,
            subtree: true,
        });
    }

    function bindLanguageSelectors() {
        document.querySelectorAll('[data-language-select]').forEach((select) => {
            select.addEventListener('change', () => {
                applyLanguage(select.value);
            });
        });
    }

    function startLanguageSystem() {
        currentLanguage = getStoredLanguage();
        bindLanguageSelectors();
        applyLanguage(currentLanguage);
        watchLanguageChanges();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startLanguageSystem, { once: true });
    } else {
        startLanguageSystem();
    }
})();
