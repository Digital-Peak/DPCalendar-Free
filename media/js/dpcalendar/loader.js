/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	window.DP_LOADER_PROMISES = {};
	window.loadDPAssets = (paths, callBack) => {
		const promises = [];
		paths.forEach((path) => {
			if (window.DP_LOADER_PROMISES[path] === true) {
				return;
			}
			if (window.DP_LOADER_PROMISES[path] instanceof Promise) {
				promises.push(window.DP_LOADER_PROMISES[path]);
				return;
			}
			let fullPath = path;
			if (fullPath.indexOf('https://') === -1) {
				fullPath = Joomla.getOptions('system.paths').root + '/media' + path;
				let src = document.querySelector('script[src*="loader"]');
				src = src ? src.getAttribute('src') : null;
				if (!src || src.indexOf('.min.js') > -1 || fullPath.indexOf('/dpcalendar/') === -1) {
					fullPath = fullPath.replace('.js', '.min.js').replace('.css', '.min.css');
				}
				if (src && src.indexOf('?') > 0) {
					fullPath += src.substr(src.indexOf('?'));
				}
			}
			let promise = null;
			if (path.indexOf('.css') > 0) {
				promise = new Promise((resolve) => {
					const link = document.createElement('link');
					link.type = 'text/css';
					link.rel = 'stylesheet';
					link.href = fullPath;
					document.head.appendChild(link);
					resolve();
				});
			} else {
				promise = new Promise((resolve) => {
					const script = document.createElement('script');
					script.src = fullPath;
					script.addEventListener('load', resolve);
					document.head.appendChild(script);
				});
			}
			if (promise) {
				promises.push(promise);
				window.DP_LOADER_PROMISES[path] = promise;
			}
		});
		Promise.all(promises).then(() => {
			paths.forEach((path) => window.DP_LOADER_PROMISES[path] = true);
			if (callBack) {
				callBack();
			}
		});
	};
})();
