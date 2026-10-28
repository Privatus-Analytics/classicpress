/* Privatus Analytics opt-out toggle for the [privatus_optout] shortcode.
 * Uses the tracker API (privatus.optOut / optIn / isOptedOut) when loaded and falls back to
 * the same localStorage flag (privatus_optout = "1") the tracker reads. Nothing else is stored. */
(function () {
	'use strict';

	var KEY = 'privatus_optout';

	function api() {
		var p = window.privatus;
		return p && typeof p.isOptedOut === 'function' ? p : null;
	}

	function isOptedOut() {
		if (api()) {
			return api().isOptedOut();
		}
		try {
			return window.localStorage.getItem(KEY) === '1';
		} catch (e) {
			return false;
		}
	}

	function setOptedOut(value) {
		if (api()) {
			return value ? api().optOut() : api().optIn();
		}
		try {
			if (value) {
				window.localStorage.setItem(KEY, '1');
			} else {
				window.localStorage.removeItem(KEY);
			}
		} catch (e) {
			/* storage blocked: nothing we can do */
		}
	}

	function render(root) {
		var out = isOptedOut();
		var button = root.querySelector('.privatus-optout__button');
		var status = root.querySelector('.privatus-optout__status');
		button.textContent = out ? root.getAttribute('data-opt-in-label') : root.getAttribute('data-opt-out-label');
		button.setAttribute('aria-pressed', out ? 'true' : 'false');
		status.textContent = out ? root.getAttribute('data-opted-out') : root.getAttribute('data-opted-in');
	}

	function init() {
		var roots = document.querySelectorAll('.privatus-optout');
		Array.prototype.forEach.call(roots, function (root) {
			render(root);
			root.querySelector('.privatus-optout__button').addEventListener('click', function () {
				setOptedOut(!isOptedOut());
				Array.prototype.forEach.call(roots, render);
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
