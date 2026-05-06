(function () {
	'use strict';

	// B2BKing emits option values as `role_<ID>`. Keep numeric forms as fallback
	// in case other deployments use bare IDs.
	var ROLE_B2B_VALUES = ['role_1062', '1062'];
	var WRAPPER_SELECTOR = 'p.form-row, .form-row, [class*="form-row"], [class*="b2bking"][class*="field"]:not(input):not(select):not(textarea)';

	function findRoleSelect() {
		return document.querySelector(
			'select[name="b2bking_registration_roles_dropdown"], ' +
			'select[name="b2bking_role"], ' +
			'select[name="registration_role_id"]'
		);
	}

	function wrapperFor(input) {
		if (!input) {
			return null;
		}
		return input.closest(WRAPPER_SELECTOR) || input.parentElement;
	}

	function isB2BLabelText(text) {
		text = (text || '').trim().toLowerCase();
		// Strip trailing required-marker " *".
		text = text.replace(/\s*\*\s*$/, '');
		return text.indexOf('nazwa firmy') === 0 ||
			text === 'nip' ||
			text.indexOf('nip ') === 0;
	}

	function findB2BFields(form) {
		var matches = [];

		form.querySelectorAll('label').forEach(function (lbl) {
			if (!isB2BLabelText(lbl.textContent)) {
				return;
			}
			var input = lbl.htmlFor ? document.getElementById(lbl.htmlFor) : null;
			if (!input) {
				input = lbl.parentElement && lbl.parentElement.querySelector('input, select, textarea');
			}
			var wrapper = wrapperFor(input) || lbl.closest(WRAPPER_SELECTOR) || lbl.parentElement;
			if (wrapper && matches.indexOf(wrapper) === -1) {
				matches.push(wrapper);
			}
		});

		return matches;
	}

	function setHidden(wrapper, hidden) {
		wrapper.style.display = hidden ? 'none' : '';
		var inputs = wrapper.querySelectorAll('input, select, textarea');
		inputs.forEach(function (el) {
			if (hidden) {
				if (el.required) {
					el.dataset.gorvitaWasRequired = '1';
					el.required = false;
				}
				if (el.type === 'checkbox' || el.type === 'radio') {
					el.checked = false;
				} else {
					el.value = '';
				}
			} else if (el.dataset.gorvitaWasRequired === '1') {
				el.required = true;
				delete el.dataset.gorvitaWasRequired;
			}
		});
	}

	function applyState(form, select) {
		var isB2B = ROLE_B2B_VALUES.indexOf(String(select.value)) !== -1;
		findB2BFields(form).forEach(function (wrapper) {
			setHidden(wrapper, !isB2B);
		});
	}

	function init() {
		var select = findRoleSelect();
		if (!select) {
			return;
		}
		var form = select.form || select.closest('form');
		if (!form) {
			return;
		}

		applyState(form, select);
		select.addEventListener('change', function () {
			applyState(form, select);
		});

		// B2BKing may re-render fields on role change; re-apply state on DOM mutations.
		if ('MutationObserver' in window) {
			var pending = false;
			var mo = new MutationObserver(function () {
				if (pending) {
					return;
				}
				pending = true;
				window.requestAnimationFrame(function () {
					pending = false;
					applyState(form, select);
				});
			});
			mo.observe(form, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
