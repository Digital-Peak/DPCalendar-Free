DPCalendar = window.DPCalendar || {};

// Polyfill for matches and closest
if (!Element.prototype.matches) {
	Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector
}

if (!Element.prototype.closest) {
	Element.prototype.closest = function (selector) {
		var el = this;
		if (!document.documentElement.contains(el)) return null;
		do {
			if (el.matches(s)) return el;
			el = el.parentElement;
		} while (el !== null);
		return null;
	};
}

(function (DPCalendar) {
	"use strict";

	DPCalendar.modal = function (url, height, closeFunction) {
		var modal = new tingle.modal({
			footer: false,
			stickyFooter: false,
			closeMethods: ['overlay', 'button', 'escape'],
			cssClass: ['dpcalendar-modal'],
			closeLabel: Joomla.JText._('JLIB_HTML_BEHAVIOR_CLOSE', true),
			onClose: function () {
				if (closeFunction) {
					closeFunction(modal.modalBox.children[0].querySelector('iframe'));
				}
			}
		});
		modal.setContent('<iframe width="100%" height="' + height + '" src="' + url.toString() + '" frameborder="0" allowfullscreen></iframe>');
		modal.open();
	},

		DPCalendar.hide = function (el) {
			el.originalHeight = el.clientHeight;
			el.style.height = '0px';
		},

		DPCalendar.fadeToggle = function (el, fn) {
			if (!el) {
				return;
			}
			if (el.originalHeight) {
				var h = 0;
				var intval = setInterval(function () {
						h++;
						el.style.height = h + 'px';
						if (h >= el.originalHeight) {
							window.clearInterval(intval);
							el.originalHeight = 0;
							if (fn) {
								fn(true);
							}
						}
					}, 1
				);
			}
			else {
				var h = el.clientHeight;
				el.originalHeight = h;

				var intval = setInterval(function () {
						h--;
						el.style.height = h + 'px';
						if (h <= 0) {
							window.clearInterval(intval);

							if (fn) {
								fn(false);
							}
						}
					}, 1
				);
			}
		},

		DPCalendar.encode = function (str) {
			return str.replace(/&amp;/g, '&');
		},

		DPCalendar.pad = function (num, size) {
			var s = num + "";
			while (s.length < size) s = "0" + s;
			return s;
		},

		DPCalendar.isLocalStorageSupported = function () {
			var testKey = 'test';
			try {
				localStorage.setItem(testKey, '1');
				localStorage.removeItem(testKey);
				return true;
			} catch (error) {
				return false;
			}
		},

		DPCalendar.formToString = function (form) {
			var field, s = [];
			for (var i = 0; i < form.elements.length; i++) {
				field = form.elements[i];
				if (!field.name || field.disabled || field.type == 'file' || field.type == 'reset' || field.type == 'submit' || field.type == 'button') {
					continue;
				}

				if (field.type == 'select-multiple') {
					for (var j = form.elements[i].options.length - 1; j >= 0; j--) {
						if (field.options[j].selected)
							s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[j].value);
					}
				} else if ((field.type != 'checkbox' && field.type != 'radio') || field.checked) {
					s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value);
				}
			}
			return s.join('&').replace(/%20/g, '+');
		}
}(DPCalendar));
