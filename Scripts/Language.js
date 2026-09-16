(() => {
    const STORAGE_KEY = 'mapleCampLanguage';
    const DEFAULT_LANGUAGE = 'en';
    const SUPPORTED_LANGUAGES = ['en', 'de', 'fr', 'es'];
    const TRANSLATABLE_ATTRIBUTES = ['placeholder', 'aria-label', 'title'];
    const originalText = new WeakMap();
    const originalAttributes = new WeakMap();
    const originalTitle = document.title;
    let currentLanguage = DEFAULT_LANGUAGE;

    function dictionaries() {
        return window.MapleLanguages || {};
    }

    function dictionary(language = currentLanguage) {
        const allDictionaries = dictionaries();
        return allDictionaries[language] || allDictionaries[DEFAULT_LANGUAGE] || {};
    }

    function storedLanguage() {
        try {
            const language = localStorage.getItem(STORAGE_KEY);
            return SUPPORTED_LANGUAGES.includes(language) ? language : DEFAULT_LANGUAGE;
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

    function translateValue(value, activeDictionary = dictionary()) {
        const trimmed = String(value || '').trim();

        if (trimmed === '') {
            return value;
        }

        return Object.prototype.hasOwnProperty.call(activeDictionary, trimmed)
            ? replaceTrimmed(String(value), activeDictionary[trimmed])
            : value;
    }

    function shouldSkipElement(element) {
        return Boolean(element.closest('script, style, noscript, code, [data-no-translate]'));
    }

    function shouldSkipTextNode(node) {
        const parent = node.parentElement;

        return !parent || shouldSkipElement(parent) || Boolean(parent.closest('[data-i18n]'));
    }

    function translateKeyedElements(activeDictionary) {
        document.querySelectorAll('[data-i18n]').forEach((element) => {
            if (shouldSkipElement(element)) {
                return;
            }

            const key = element.dataset.i18n || '';
            element.textContent = activeDictionary[key] || key;
        });

        document.querySelectorAll('[data-i18n-attr]').forEach((element) => {
            if (shouldSkipElement(element)) {
                return;
            }

            const pairs = (element.dataset.i18nAttr || '').split(';');

            pairs.forEach((pair) => {
                const parts = pair.split(':');
                const attribute = (parts[0] || '').trim();
                const key = parts.slice(1).join(':').trim();

                if (attribute && key) {
                    element.setAttribute(attribute, activeDictionary[key] || key);
                }
            });
        });
    }

    function translateTextNodes(activeDictionary) {
        if (!document.body) {
            return;
        }

        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        let node = walker.nextNode();

        while (node) {
            if (!shouldSkipTextNode(node)) {
                if (!originalText.has(node)) {
                    originalText.set(node, node.nodeValue || '');
                }

                node.nodeValue = translateValue(originalText.get(node), activeDictionary);
            }

            node = walker.nextNode();
        }
    }

    function translateAttributes(activeDictionary) {
        document.querySelectorAll('*').forEach((element) => {
            if (shouldSkipElement(element) || element.hasAttribute('data-i18n-attr')) {
                return;
            }

            if (!originalAttributes.has(element)) {
                const values = {};

                TRANSLATABLE_ATTRIBUTES.forEach((attribute) => {
                    if (element.hasAttribute(attribute)) {
                        values[attribute] = element.getAttribute(attribute) || '';
                    }
                });

                originalAttributes.set(element, values);
            }

            Object.entries(originalAttributes.get(element) || {}).forEach(([attribute, source]) => {
                element.setAttribute(attribute, translateValue(source, activeDictionary));
            });
        });
    }

    function syncSelectors(language) {
        document.querySelectorAll('[data-language-select]').forEach((select) => {
            select.value = language;
        });
    }

    function applyLanguage(language, persist = true) {
        const selectedLanguage = SUPPORTED_LANGUAGES.includes(language) ? language : DEFAULT_LANGUAGE;
        const activeDictionary = dictionary(selectedLanguage);

        currentLanguage = selectedLanguage;
        document.documentElement.lang = selectedLanguage;
        document.title = translateValue(originalTitle, activeDictionary);
        translateKeyedElements(activeDictionary);
        translateTextNodes(activeDictionary);
        translateAttributes(activeDictionary);
        syncSelectors(selectedLanguage);

        if (persist) {
            storeLanguage(selectedLanguage);
        }

        window.dispatchEvent(new CustomEvent('maple:languagechange', {
            detail: {
                language: selectedLanguage,
                dictionary: activeDictionary,
            },
        }));
    }

    function bindSelectors() {
        document.querySelectorAll('[data-language-select]').forEach((select) => {
            select.addEventListener('change', () => applyLanguage(select.value));
        });
    }

    function start() {
        bindSelectors();
        applyLanguage(storedLanguage(), false);
    }

    window.MapleLanguage = {
        applyLanguage,
        getLanguage: () => currentLanguage,
        translate: (key) => dictionary()[key] || key,
        translateValue: (value) => translateValue(value, dictionary()),
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
