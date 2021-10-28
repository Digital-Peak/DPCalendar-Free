/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			function asyncPool(array, iteratorFn)
			{
				let i = 0;
				const ret = [];
				const executing = [];
				const enqueue = () => {
					if (i === array.length) {
						return Promise.resolve();
					}
					const p = Promise.resolve().then(() => iteratorFn(array[i++], array));
					ret.push(p);
					const e = p.then(() => executing.splice(executing.indexOf(e), 1));
					executing.push(e);
					let r = Promise.resolve();
					if (executing.length >= 3) {
						r = Promise.race(executing);
					}
					return r.then(() => enqueue());
				};
				return enqueue().then(() => Promise.all(ret));
			}
			asyncPool(document.querySelectorAll('.com-dpcalendar-tools-translate .dp-resource'), (resource) => {
				return new Promise(resolve => {
					DPCalendar.request(
						'task=translate.fetch',
						(json) => {
							for (const i in json.languages) {
								const language = json.languages[i];
								const el = resource.querySelector('.dp-resource__language[data-language="' + language.tag + '"] .dp-resource__percentage');
								if (!el) {
									continue;
								}
								el.innerHTML = language.percent + '%';
								let label = 'success';
								if (language.percent < 30) {
									label = 'important';
								} else if (language.percent < 50) {
									label = 'warning';
								} else if (language.percent < 100) {
									label = 'info';
								}
								el.parentElement.classList.add('dp-resource_' + label);
							}
							resolve();
						},
						'resource=' + resource.getAttribute('data-slug')
					);
				});
			});
			Joomla.submitbutton = (task) => {
				if (task != 'translate.update') {
					return true;
				}
				asyncPool(document.querySelectorAll('.com-dpcalendar-tools-translate .dp-resource'), (resource) => {
					return new Promise(resolve => {
						DPCalendar.request(
							'task=translate.update',
							() => {
								resource.querySelector('.dp-resource__icon i').setAttribute('class', 'icon-checkmark-circle');
								resolve();
							},
							'resource=' + resource.getAttribute('data-slug')
						);
					});
				});
				return true;
			};
		});
	});
})();
