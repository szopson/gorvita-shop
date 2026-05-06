(function () {
	'use strict';

	// B2BKing tags every registration field wrapper with one of:
	//   .b2bking_custom_registration_allroles        → always visible
	//   .b2bking_custom_registration_role_<roleId>   → visible only for that role
	// The role <select> emits values formatted "role_<roleId>" (e.g. "role_1062").
	var SELECT_SELECTOR = 'select[name="b2bking_registration_roles_dropdown"], ' +
		'select[name="b2bking_role"], ' +
		'select[name="registration_role_id"]';
	var WRAPPER_SELECTOR = '.b2bking_custom_registration_container';
	var ALLROLES_CLASS = 'b2bking_custom_registration_allroles';
	var ROLE_CLASS_PREFIX = 'b2bking_custom_registration_role_';

	function currentRoleId(select) {
		var match = String(select.value || '').match(/role_(\d+)/);
		return match ? match[1] : '';
	}

	function setHidden(wrapper, hidden) {
		wrapper.style.display = hidden ? 'none' : '';
		wrapper.querySelectorAll('input, select, textarea').forEach(function (el) {
			if (hidden) {
				if (el.required) {
					el.dataset.gorvitaWasRequired = '1';
					el.required = false;
				}
				// Leave hidden helper inputs (B2BKing internal markers) untouched.
				if (el.type === 'hidden') {
					return;
				}
				if (el.type === 'checkbox' || el.type === 'radio') {
					el.checked = false;
				} else if (el.tagName === 'SELECT') {
					if (el.options.length) {
						el.selectedIndex = 0;
					}
				} else {
					el.value = '';
				}
			} else if (el.dataset.gorvitaWasRequired === '1') {
				el.required = true;
				delete el.dataset.gorvitaWasRequired;
			}
		});
	}

	function isVisibleForRole(wrapper, roleId) {
		if (wrapper.classList.contains(ALLROLES_CLASS)) {
			return true;
		}
		if (!roleId) {
			return false;
		}
		return wrapper.classList.contains(ROLE_CLASS_PREFIX + roleId);
	}

	function applyState() {
		var selects = document.querySelectorAll(SELECT_SELECTOR);
		if (!selects.length) {
			return;
		}
		selects.forEach(function (select) {
			var roleId = currentRoleId(select);
			var scope = select.form || select.closest('form') || document;
			scope.querySelectorAll(WRAPPER_SELECTOR).forEach(function (wrapper) {
				setHidden(wrapper, !isVisibleForRole(wrapper, roleId));
			});
		});
	}

	function init() {
		// Delegated change handler — works whether the form exists at load time
		// (Blocksy modal pre-rendered) or is injected later.
		document.addEventListener('change', function (e) {
			var t = e.target;
			if (t && t.matches && t.matches(SELECT_SELECTOR)) {
				applyState();
			}
		}, false);

		applyState();

		// Re-apply when Blocksy injects/swaps the modal contents or B2BKing
		// re-renders fields. Debounced via rAF; we only mutate inline
		// style/value/required which do not trigger childList mutations.
		if ('MutationObserver' in window) {
			var pending = false;
			var mo = new MutationObserver(function () {
				if (pending) {
					return;
				}
				pending = true;
				window.requestAnimationFrame(function () {
					pending = false;
					applyState();
				});
			});
			mo.observe(document.body, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
