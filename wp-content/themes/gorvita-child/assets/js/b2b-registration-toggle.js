(function () {
	'use strict';

	var ROLE_B2B = '1062';
	var WRAPPER_SELECTOR = '.form-row, .b2bking-form-field, [class*="b2bking"][class*="field"]';

	function findRoleSelect() {
		return document.querySelector(
			'select[name="b2bking_role"], select[name="registration_role_id"]'
		);
	}

	function findB2BFields(form) {
		var matches = [];
		var labels = form.querySelectorAll('label');
		labels.forEach(function (lbl) {
			var text = (lbl.textContent || '').trim().toLowerCase();
			if (text.indexOf('nazwa firmy') === 0 || text === 'nip' || text.indexOf('nip ') === 0) {
				var wrapper = lbl.closest(WRAPPER_SELECTOR) || lbl.parentElement;
				if (wrapper && matches.indexOf(wrapper) === -1) {
					matches.push(wrapper);
				}
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
		var isB2B = String(select.value) === ROLE_B2B;
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
