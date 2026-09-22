(function () {
    var modal = document.getElementById('signature-modal');
    var openBtn = document.getElementById('open-signature');
    var closeBtn = document.getElementById('close-modal');
    var saveBtn = document.getElementById('save-signature');
    var clearBtn = document.getElementById('clear-signature');
    var canvas = document.getElementById('signature-pad');
    var hiddenInput = document.getElementById('firma_base64');
    var previewImg = document.getElementById('signature-preview');
    var placeholderText = document.getElementById('placeholder-text');
    var privacyLink = document.getElementById('privacy-link');
    var privacyCheckbox = document.getElementById('privacy-checkbox');
    var form = document.querySelector('form');
    var italianDateInputs = document.querySelectorAll('input[data-date-it]');

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

    function attachNativeDatePicker(dateInputs) {
        dateInputs.forEach(function (input) {
            if (!input || input.dataset.pickerEnhanced === '1' || !input.parentNode) {
                return;
            }

            input.dataset.pickerEnhanced = '1';

            if (supportsNativeDateInput()) {
                input.type = 'date';
                input.lang = 'it';
                input.removeAttribute('maxlength');
                input.removeAttribute('inputmode');

                if (isValidItalianDate(input.value)) {
                    input.value = toIsoDate(input.value);
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
                    var dateMsgBlur = (window.mokiI18n && window.mokiI18n.t) ?
                        window.mokiI18n.t('date_format_alert') :
                        'Inserisci la data nel formato gg/mm/aaaa.';
                    input.setCustomValidity(dateMsgBlur);
                    input.reportValidity();
                    return;
                }

                input.setCustomValidity('');
            });
        });
    }

    attachNativeDatePicker(italianDateInputs);

    if (!modal || !openBtn || !closeBtn || !saveBtn || !clearBtn || !canvas || !hiddenInput || !previewImg || !placeholderText) {
        return;
    }

    var ctx = canvas.getContext('2d');
    var drawing = false;

    function resizeCanvas() {
        if (!modal.classList.contains('active')) {
            return;
        }

        var rect = canvas.getBoundingClientRect();
        var ratio = window.devicePixelRatio || 1;

        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;

        ctx.scale(ratio, ratio);
        ctx.strokeStyle = '#024b86';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }

    function getPos(e) {
        var rect = canvas.getBoundingClientRect();
        var clientX;
        var clientY;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function startDraw(e) {
        drawing = true;
        var pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        if (e.cancelable) {
            e.preventDefault();
        }
    }

    function draw(e) {
        if (!drawing) {
            return;
        }
        var pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        if (e.cancelable) {
            e.preventDefault();
        }
    }

    function stopDraw() {
        if (drawing) {
            ctx.closePath();
            drawing = false;
        }
    }

    openBtn.addEventListener('click', function () {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(resizeCanvas, 400);
    });

    closeBtn.addEventListener('click', function () {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    });

    window.addEventListener('resize', resizeCanvas);
    window.addEventListener('orientationchange', function () {
        setTimeout(resizeCanvas, 500);
    });

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    window.addEventListener('mouseup', stopDraw);

    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDraw);

    clearBtn.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    saveBtn.addEventListener('click', function () {
        var tempCanvas = document.createElement('canvas');
        var tempCtx = tempCanvas.getContext('2d');

        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;

        tempCtx.fillStyle = '#ffffff';
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        tempCtx.drawImage(canvas, 0, 0);

        var dataURL = tempCanvas.toDataURL('image/png');
        hiddenInput.value = dataURL;
        previewImg.src = dataURL;
        previewImg.style.display = 'block';
        placeholderText.style.display = 'none';

        modal.classList.remove('active');
        document.body.style.overflow = '';
    });

    if (privacyLink && privacyCheckbox && form) {
        privacyLink.addEventListener('click', function (event) {
            event.preventDefault();
            window.open(privacyLink.href, '_blank');
            privacyCheckbox.disabled = false;
        });

        form.addEventListener('submit', function (event) {
            if (!privacyCheckbox.checked) {
                event.preventDefault();
                var privacyMsg = (window.mokiI18n && window.mokiI18n.t) ?
                    window.mokiI18n.t('privacy_alert') :
                    'Devi accettare le condizioni della privacy policy per inviare il modulo.';
                window.alert(privacyMsg);
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            for (var i = 0; i < italianDateInputs.length; i++) {
                var dateInput = italianDateInputs[i];
                if (!dateInput.value) {
                    continue;
                }

                if (supportsNativeDateInput()) {
                    continue;
                }

                if (!isValidItalianDate(dateInput.value)) {
                    event.preventDefault();
                    var dateMsgSubmit = (window.mokiI18n && window.mokiI18n.t) ?
                        window.mokiI18n.t('date_format_alert') :
                        'Inserisci la data nel formato gg/mm/aaaa.';
                    dateInput.setCustomValidity(dateMsgSubmit);
                    dateInput.reportValidity();
                    return;
                }

                dateInput.setCustomValidity('');
                dateInput.value = toIsoDate(dateInput.value);
            }
        });
    }
})();
