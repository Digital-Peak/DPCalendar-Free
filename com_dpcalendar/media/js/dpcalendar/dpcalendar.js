/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	const DPCalendar = window.DPCalendar || {};
	window.DPCalendar = DPCalendar;
	Joomla.loadOptions();
	DPCalendar.requestCounter = 0;
	DPCalendar.modal = (url, width, height, closeFunction) => {
		loadDPAssets(['/com_dpcalendar/js/domurl/url.js', '/com_dpcalendar/js/tingle/tingle.js', '/com_dpcalendar/css/tingle/tingle.css'], () => {
			const modal = new tingle.modal({
				footer: false,
				stickyFooter: false,
				closeMethods: ['overlay', 'button', 'escape'],
				cssClass: ['dpcalendar-modal'],
				closeLabel: Joomla.JText._('COM_DPCALENDAR_CLOSE'),
				onClose: () => {
					if (closeFunction) {
						closeFunction(modal.modalBox.children[0].querySelector('iframe'));
					}
				}
			});
			if (width && document.body.clientWidth > width) {
				if (!isNaN(width)) {
					width = width + 'px';
				}
				document.querySelector('.tingle-modal-box').style.width = width;
			}
			if (!height) {
				height = '80vh';
			}
			if (!isNaN(height)) {
				height = height + 'px';
			}
			const urlObject = new Url(url);
			urlObject.query.tmpl = 'component';
			modal.setContent('<iframe style="width:100%; height:' + height + '" src="' + urlObject.toString() + '" frameborder="0" allowfullscreen></iframe>');
			modal.open();
		});
	};
	DPCalendar.print = (selector) => {
		document.body.outerHTML = document.querySelector(selector).outerHTML;
		window.addEventListener('afterprint', (event) => {
			setTimeout(() => window.location.reload(true), 2000);
		});
		window.print();
	};
	DPCalendar.encode = (str) => {
		return str.replace(/&amp;/g, '&');
	};
	DPCalendar.pad = (num, size) => {
		let s = num + '';
		while (s.length < size) {
			s = '0' + s;
		}
		return s;
	};
	DPCalendar.formToQueryString = (form, selector) => {
		const elements = selector ? form.querySelectorAll(selector) : form.elements;
		let field, s = [];
		for (let i = 0; i < elements.length; i++) {
			field = elements[i];
			if (!field.name || field.disabled || field.type == 'file' || field.type == 'reset' || field.type == 'submit' || field.type == 'button') {
				continue;
			}
			if (field.type == 'select-multiple') {
				for (let j = elements[i].options.length - 1; j >= 0; j--) {
					if (field.options[j].selected) {
						s[s.length] = encodeURIComponent(field.name) + '=' + encodeURIComponent(field.options[j].value);
					}
				}
			} else if ((field.type != 'checkbox' && field.type != 'radio') || field.checked) {
				s[s.length] = encodeURIComponent(field.name) + '=' + encodeURIComponent(field.value);
			}
		}
		return s.join('&').replace(/%20/g, '+');
	};
	DPCalendar.arrayToQueryString = (array, prefix) => {
		const str = [];
		let p;
		for (p in array) {
			if (array.hasOwnProperty(p)) {
				const k = prefix ? prefix + '[' + p + ']' : p,
					v = array[p];
				str.push(
					(v !== null && typeof v === 'object') ?
						DPCalendar.arrayToQueryString(v, k) :
						encodeURIComponent(k) + '=' + encodeURIComponent(v)
				);
			}
		}
		return str.join('&').replace(/%20/g, '+');
	};
	DPCalendar.currentLocation = (callback) => {
		if (!navigator.geolocation) {
			return false;
		}
		navigator.geolocation.getCurrentPosition((pos) => {
			let task = 'location.loc';
			if (window.location.href.indexOf('administrator') == -1) {
				task = 'locationform.loc';
			}
			DPCalendar.request(
				'task=' + task + '&loc=' + encodeURIComponent(pos.coords.latitude + ',' + pos.coords.longitude),
				(json) => callback(json.data.formated)
			);
		}, (error) => {
			Joomla.renderMessages({error: [error.message]});
		});
		return true;
	};
	DPCalendar.request = (url, callback, data, updateLoader) => {
		const loader = updateLoader !== false ? document.querySelector('.dp-loader') : null;
		if (loader) {
			loader.classList.remove('dp-loader_hidden');
		}
		if (!data) {
			data = '';
		}
		data += (data ? '&' : '') + Joomla.getOptions('csrf.token') + '=1';
		DPCalendar.requestCounter++;
		Joomla.request({
			url: Joomla.getOptions('system.paths').base + '/index.php?option=com_dpcalendar&' + url,
			method: 'POST',
			data: data,
			onSuccess: (res) => {
				DPCalendar.requestCounter--;
				if (loader) {
					loader.classList.add('dp-loader_hidden');
				}
				try {
					const json = JSON.parse(res);
					if (json.messages != null && json.messages.length !== 0 && document.getElementById('system-message-container')) {
						Joomla.renderMessages(json.messages);
					}
					if (callback) {
						callback(json);
					}
				} catch (e) {
					if (document.getElementById('system-message-container')) {
						Joomla.renderMessages({error: [url + '<br>' + e.message]});
					}
					if (window.console) {
						console.log(e);
					}
				}
			},
			onError: (response) => {
				DPCalendar.requestCounter--;
				if (loader) {
					loader.classList.add('dp-loader_hidden');
				}
				try {
					const json = JSON.parse(response);
					if (json.messages != null && json.messages.length !== 0 && document.getElementById('system-message-container')) {
						Joomla.renderMessages(json.messages);
					}
				} catch (e) {
					if (document.getElementById('system-message-container')) {
						Joomla.renderMessages({error: [url + '<br>' + e.message]});
					}
				}
			}
		});
	};
	DPCalendar.debounce = (func, wait, immediate) => {
		if (wait == null) {
			wait = 500;
		}
		if (immediate == null) {
			immediate = false;
		}
		let timeout;
		return function () {
			const context = this, args = arguments;
			const later = () => {
				timeout = null;
				if (!immediate) {
					func.apply(context, args);
				}
			};
			const callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) {
				func.apply(context, args);
			}
		};
	};
	DPCalendar.slideToggle = (target, fn) => {
		if (!target) {
			return;
		}
		if (window.getComputedStyle(target).display === 'none') {
			return slideDown(target, fn);
		}
		return slideUp(target, fn);
	};
	[].slice.call(document.querySelectorAll('.dp-button-print')).forEach((button) => {
		button.addEventListener('click', () => DPCalendar.print(button.getAttribute('data-selector')));
	});
	[].slice.call(document.querySelectorAll('.dp-button-action[data-href]')).forEach((el) => {
		el.addEventListener('click', () => {
			let text = el.getAttribute('data-confirmtext');
			if (!text) {
				text = Joomla.JText._('COM_DPCALENDAR_CONFIRM_DELETE');
			}
			if (el.classList.contains('dp-action-delete') && !confirm(text)) {
				return false;
			}
			if (el.getAttribute('data-target') == 'new') {
				window.open(el.getAttribute('data-href'));
				return false;
			}
			location.href = el.getAttribute('data-href');
			return false;
		});
	});
	[].slice.call(document.querySelectorAll('.dp-tabs__input')).forEach((tab) => {
		tab.addEventListener('click', () => localStorage.setItem('dp-tabs-' + tab.name, tab.id));
		if (localStorage.getItem('dp-tabs-' + tab.name) == tab.id) {
			tab.checked = true;
		}
	});
	let slideUp = (target, fn) => {
		target.style.transitionProperty = 'height, margin, padding';
		target.style.transitionDuration = '700ms';
		target.style.boxSizing = 'border-box';
		target.style.height = target.offsetHeight + 'px';
		target.offsetHeight;
		target.style.overflow = 'hidden';
		target.style.height = 0;
		target.style.paddingTop = 0;
		target.style.paddingBottom = 0;
		target.style.marginTop = 0;
		target.style.marginBottom = 0;
		window.setTimeout(() => {
			target.style.display = 'none';
			target.style.removeProperty('height');
			target.style.removeProperty('padding-top');
			target.style.removeProperty('padding-bottom');
			target.style.removeProperty('margin-top');
			target.style.removeProperty('margin-bottom');
			target.style.removeProperty('overflow');
			target.style.removeProperty('transition-duration');
			target.style.removeProperty('transition-property');
			if (fn) {
				fn(false, target);
			}
		}, 710);
	};
	let slideDown = (target, fn) => {
		target.style.removeProperty('display');
		let display = window.getComputedStyle(target).display;
		if (display === 'none') {
			display = 'block';
		}
		target.style.display = display;
		let height = target.offsetHeight;
		target.style.overflow = 'hidden';
		target.style.height = 0;
		target.style.paddingTop = 0;
		target.style.paddingBottom = 0;
		target.style.marginTop = 0;
		target.style.marginBottom = 0;
		target.offsetHeight;
		target.style.boxSizing = 'border-box';
		target.style.transitionProperty = "height, margin, padding";
		target.style.transitionDuration = '700ms';
		target.style.height = height + 'px';
		target.style.removeProperty('padding-top');
		target.style.removeProperty('padding-bottom');
		target.style.removeProperty('margin-top');
		target.style.removeProperty('margin-bottom');
		window.setTimeout(() => {
			target.style.removeProperty('height');
			target.style.removeProperty('overflow');
			target.style.removeProperty('transition-duration');
			target.style.removeProperty('transition-property');
			if (fn) {
				fn(true, target);
			}
		}, 710);
	};
}());
