(() => {
    function setupRatingPicker(form) {
        const starsWrap = form.querySelector('[data-rating-stars]');
        const output = form.querySelector('[data-rating-output]');
        const inputs = Array.from(form.querySelectorAll('.review-rating-input'));
        const labels = Array.from(form.querySelectorAll('[data-rating-star]'));

        if (!starsWrap || !output || inputs.length === 0 || labels.length === 0) {
            return;
        }

        function selectedValue() {
            const checked = inputs.find((input) => input.checked);
            return checked ? Number(checked.value) : 0;
        }

        function fillStars(value) {
            labels.forEach((label) => {
                const starValue = Number(label.dataset.ratingValue || 0);
                label.classList.toggle('is-filled', starValue <= value);
            });
        }

        function setOutput(value) {
            output.replaceChildren();

            if (value <= 0) {
                output.textContent = 'No rating selected';
                return;
            }

            output.append(document.createTextNode('Selected rating: '));

            const valueNode = document.createElement('span');
            valueNode.dataset.noTranslate = '';
            valueNode.textContent = `${value} / 5`;
            output.append(valueNode);
        }

        function renderSelectedState() {
            const value = selectedValue();
            fillStars(value);
            setOutput(value);
            starsWrap.removeAttribute('data-invalid');
        }

        labels.forEach((label) => {
            label.addEventListener('mouseenter', () => {
                fillStars(Number(label.dataset.ratingValue || 0));
            });
        });

        starsWrap.addEventListener('mouseleave', renderSelectedState);

        inputs.forEach((input) => {
            input.addEventListener('change', renderSelectedState);
            input.addEventListener('focus', () => {
                fillStars(Number(input.value));
            });
            input.addEventListener('blur', renderSelectedState);
        });

        form.addEventListener('submit', (event) => {
            if (selectedValue() > 0) {
                return;
            }

            event.preventDefault();
            starsWrap.dataset.invalid = 'true';
            output.textContent = 'Please choose a rating from 1 to 5 stars.';
            inputs[0].focus();
        });

        renderSelectedState();
    }

    function setupCharacterCounter(form) {
        const textarea = form.querySelector('[data-review-textarea]');
        const counter = form.querySelector('[data-character-count]');

        if (!textarea || !counter) {
            return;
        }

        const maximumLength = Number(textarea.getAttribute('maxlength')) || 1500;

        function updateCounter() {
            counter.textContent = String(Math.max(0, maximumLength - textarea.value.length));
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    }

    function setupReviews() {
        document.querySelectorAll('[data-review-form]').forEach((form) => {
            setupRatingPicker(form);
            setupCharacterCounter(form);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupReviews, { once: true });
    } else {
        setupReviews();
    }
})();
