(function () {
    function initItalianDateInputs() {
        var dateInputs = document.querySelectorAll('input[data-date-it]');

        function isValidItalianDate(value) {
            var match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);
            if (!match) {
                return false;
            }

            var day = parseInt(match[1], 10);
            var month = parseInt(match[2], 10);
            var year = parseInt(match[3], 10);
            var date = new Date(year, month - 1, day);

            return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
        }

        function prettifyDateInput(rawValue) {
            var digits = String(rawValue || '').replace(/\D/g, '').slice(0, 8);
            if (digits.length <= 2) {
                return digits;
            }
            if (digits.length <= 4) {
                return digits.slice(0, 2) + '/' + digits.slice(2);
            }
            return digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
        }

        function supportsNativeDateInput() {
            var testInput = document.createElement('input');
            testInput.setAttribute('type', 'date');
            return testInput.type === 'date';
        }

        function toIsoDate(value) {
            var match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);
            if (!match) {
                return value;
            }
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        function fromIsoDate(value) {
            var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
            if (!match) {
                return value;
            }
            return match[3] + '/' + match[2] + '/' + match[1];
        }

        function attachNativeDatePicker(inputs) {
            inputs.forEach(function (input) {
                if (!input || input.dataset.pickerEnhanced === '1' || !input.parentNode) {
                    return;
                }

                input.dataset.pickerEnhanced = '1';

                if (supportsNativeDateInput()) {
                    var originalValue = input.value;
                    input.type = 'date';
                    input.lang = 'it';
                    input.removeAttribute('maxlength');
                    input.removeAttribute('inputmode');

                    if (isValidItalianDate(originalValue)) {
                        input.value = toIsoDate(originalValue);
                    }

                    input.addEventListener('change', function () {
                        input.setCustomValidity('');
                    });
                    return;
                }

                input.addEventListener('input', function () {
                    input.value = prettifyDateInput(input.value);
                    input.setCustomValidity('');
                });

                input.addEventListener('blur', function () {
                    if (!input.value) {
                        input.setCustomValidity('');
                        return;
                    }

                    if (!isValidItalianDate(input.value)) {
                        input.setCustomValidity('Inserisci la data nel formato gg/mm/aaaa.');
                        input.reportValidity();
                        return;
                    }

                    input.setCustomValidity('');
                });
            });
        }

        attachNativeDatePicker(dateInputs);
    }

    function initSignaturePreviewToggle() {
        var buttons = document.querySelectorAll('.toggle-signature-preview');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-signature-target');
                var previewBox = document.getElementById(targetId);

                if (!previewBox) {
                    return;
                }

                var isVisible = previewBox.classList.toggle('is-visible');
                button.textContent = isVisible ? 'Nascondi Firma' : 'Visualizza Firma';
            });
        });
    }

    function initDeleteConfirm() {
        var deleteLinks = document.querySelectorAll('.iscritti-delete-action[data-confirm-message]');

        deleteLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var message = link.getAttribute('data-confirm-message') || 'Confermi questa operazione?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initItalianDateInputs();
        initSignaturePreviewToggle();
        initDeleteConfirm();
    });
})();
