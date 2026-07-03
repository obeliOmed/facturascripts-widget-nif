/**
 * NIF/NIE/CIF client-side validator for FacturaScripts WidgetNif plugin.
 * Mirrors the PHP NifValidator logic for immediate blur feedback.
 * No external dependencies — vanilla JS, ES5 compatible.
 *
 * obeliOmed team — https://obeliomed.com — GPL-3.0
 */
(function () {
    'use strict';

    var NIF_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';
    var CIF_CONTROL_LETTERS = 'JABCDEFGHI';
    var CIF_ALWAYS_LETTER = ['P', 'Q', 'K', 'L', 'M', 'S'];
    var CIF_ALWAYS_DIGIT = ['A', 'B', 'E', 'H'];

    function normalize(value) {
        return value.trim().toUpperCase().replace(/[\s\-.]/g, '');
    }

    function detectType(v) {
        if (/^[0-9]{8}[A-Z]$/.test(v)) return 'nif';
        if (/^[XYZ][0-9]{7}[A-Z]$/.test(v)) return 'nie';
        if (/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/.test(v)) return 'cif';
        if (/^[A-Z0-9]{6,12}$/.test(v)) return 'passport';
        return 'unknown';
    }

    function validateNif(v) {
        var m = v.match(/^([0-9]{8})([A-Z])$/);
        if (!m) return false;
        return m[2] === NIF_LETTERS[parseInt(m[1], 10) % 23];
    }

    function validateNie(v) {
        var m = v.match(/^([XYZ])([0-9]{7})([A-Z])$/);
        if (!m) return false;
        var prefixMap = { X: '0', Y: '1', Z: '2' };
        var number = prefixMap[m[1]] + m[2];
        return m[3] === NIF_LETTERS[parseInt(number, 10) % 23];
    }

    function cifControlNumber(digits) {
        var sum = 0;
        for (var i = 0; i < 7; i++) {
            var d = parseInt(digits[i], 10);
            if (i % 2 === 0) {
                var doubled = d * 2;
                sum += doubled >= 10 ? (doubled - 9) : doubled;
            } else {
                sum += d;
            }
        }
        return (10 - (sum % 10)) % 10;
    }

    function validateCif(v) {
        var m = v.match(/^([ABCDEFGHJKLMNPQRSUVW])([0-9]{7})([0-9A-J])$/);
        if (!m) return false;
        var entity = m[1], digits = m[2], ctrl = m[3];
        var ctrlNum = cifControlNumber(digits);
        var ctrlLetter = CIF_CONTROL_LETTERS[ctrlNum];
        var ctrlDigit = String(ctrlNum);
        if (CIF_ALWAYS_LETTER.indexOf(entity) >= 0) return ctrl === ctrlLetter;
        if (CIF_ALWAYS_DIGIT.indexOf(entity) >= 0) return ctrl === ctrlDigit;
        return ctrl === ctrlLetter || ctrl === ctrlDigit;
    }

    function validate(value, allowPassport) {
        var v = normalize(value);
        var type = detectType(v);
        if (type === 'nif') return { valid: validateNif(v), label: 'NIF' };
        if (type === 'nie') return { valid: validateNie(v), label: 'NIE' };
        if (type === 'cif') return { valid: validateCif(v), label: 'CIF' };
        if (type === 'passport') return { valid: allowPassport !== false, label: 'PAS' };
        return { valid: false, label: null };
    }

    function clearFeedback(input) {
        var badge = input.parentNode.querySelector('.nif-feedback-badge');
        if (badge) badge.parentNode.removeChild(badge);
    }

    function showFeedback(input, result) {
        clearFeedback(input);
        var badge = document.createElement('span');
        // Render as Bootstrap input-group-text for clean inline appearance.
        badge.className = 'nif-feedback-badge input-group-text '
            + (result.valid ? 'text-success' : 'text-danger');
        badge.setAttribute('aria-live', 'polite');
        badge.textContent = result.valid ? ('✓ ' + result.label) : '✗';
        input.insertAdjacentElement('afterend', badge);
    }

    function attachToInput(input) {
        input.addEventListener('blur', function () {
            var value = this.value;
            if (!value || !value.trim()) {
                clearFeedback(this);
                return;
            }
            var allowPassport = this.getAttribute('data-nif-allow-passport') !== 'false';
            showFeedback(this, validate(value, allowPassport));
        });

        // Clear badge on form reset.
        var form = input.form;
        if (form) {
            form.addEventListener('reset', function () {
                clearFeedback(input);
            });
        }
    }

    function init() {
        document.querySelectorAll('input[data-nif-validate]').forEach(attachToInput);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
