(function () {
    'use strict';

    var modal = document.getElementById('block-accommodation-modal');
    if (!modal) {
        return;
    }

    var dialog = modal.querySelector('.block-modal__dialog');
    var houseSelect = modal.querySelector('#block-huis-id');
    var startDate = modal.querySelector('#block-start-date');
    var endDate = modal.querySelector('#block-end-date');
    var form = modal.querySelector('[data-block-form]');
    var submitButton = form ? form.querySelector('[type="submit"]') : null;
    var returnFocusTo = null;
    var underlyingModal = null;
    var underlyingModalState = null;

    function hideUnderlyingModalFromAssistiveTechnology() {
        var accommodationModal = document.getElementById('accommodation-modal');

        if (!accommodationModal || accommodationModal.hidden) {
            return;
        }

        underlyingModal = accommodationModal;
        underlyingModalState = {
            hadAriaHidden: accommodationModal.hasAttribute('aria-hidden'),
            ariaHidden: accommodationModal.getAttribute('aria-hidden'),
            inert: 'inert' in accommodationModal ? accommodationModal.inert : null
        };
        accommodationModal.setAttribute('aria-hidden', 'true');
        if ('inert' in accommodationModal) {
            accommodationModal.inert = true;
        }
    }

    function restoreUnderlyingModal() {
        if (!underlyingModal || !underlyingModalState) {
            return;
        }

        if (underlyingModalState.hadAriaHidden) {
            underlyingModal.setAttribute('aria-hidden', underlyingModalState.ariaHidden);
        } else {
            underlyingModal.removeAttribute('aria-hidden');
        }
        if (underlyingModalState.inert !== null) {
            underlyingModal.inert = underlyingModalState.inert;
        }

        underlyingModal = null;
        underlyingModalState = null;
    }

    function openModal(trigger) {
        var houseId = trigger.getAttribute('data-block-house-id') || '';
        var arrival = document.getElementById('arrival');
        var departure = document.getElementById('departure');

        returnFocusTo = trigger;
        form.reset();
        houseSelect.value = houseId;
        startDate.value = trigger.getAttribute('data-block-start-date') || (arrival ? arrival.value : '');
        endDate.value = trigger.getAttribute('data-block-end-date') || (departure ? departure.value : '');
        hideUnderlyingModalFromAssistiveTechnology();
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('block-modal-open');
        dialog.focus();
    }

    function closeModal() {
        if (modal.hidden) {
            return;
        }
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('block-modal-open');
        restoreUnderlyingModal();
        if (returnFocusTo) {
            returnFocusTo.focus();
        }
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-block-house]');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            openModal(trigger);
            return;
        }

        if (event.target.closest('[data-block-modal-close="true"]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            event.preventDefault();
            event.stopImmediatePropagation();
            closeModal();
            return;
        }

        if (event.key === 'Tab' && !modal.hidden) {
            var focusable = modal.querySelectorAll('button:not([disabled]):not([hidden]), input:not([disabled]):not([type="hidden"]):not([hidden]), select:not([disabled]):not([hidden]), textarea:not([disabled]):not([hidden])');
            var first = focusable[0];
            var last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }, true);

    startDate.addEventListener('change', function () {
        endDate.min = startDate.value || startDate.min;
        if (endDate.value !== '' && endDate.value <= startDate.value) {
            endDate.value = '';
        }
    });

    if (form) {
        form.addEventListener('submit', function () {
            if (submitButton) {
                submitButton.disabled = true;
            }
        });
    }
}());
