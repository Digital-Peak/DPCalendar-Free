/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/iframe-resizer/iframeresizer-contentwindow.js']);
		loadDPAssets(['/com_dpcalendar/js/choices/choices.js', '/com_dpcalendar/css/choices/choices.css'], () => {
			const select = document.querySelector('.com-dpcalendar-extcalendar select');
			select._choicejs = new Choices(
				select,
				{
					itemSelectText: '',
					noChoicesText: '',
					shouldSortItems: false,
					shouldSort: false,
					removeItemButton: true,
					searchResultLimit: 30
				}
			);
		});
		Joomla.submitbutton = (task) => {
			const form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});
})();
