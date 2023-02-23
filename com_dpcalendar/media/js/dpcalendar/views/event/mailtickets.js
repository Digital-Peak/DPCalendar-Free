/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-event-mailtickets__actions .dp-button-action')).forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();
				Joomla.submitbutton('event.' + button.getAttribute('data-task'));
				return false;
			});
		});
		Joomla.submitbutton = (task) => {
			const form = document.getElementsByName('adminForm')[0];
			if (!form || (task.indexOf('cancel') === -1 && !document.formvalidator.isValid(form))) {
				return;
			}
			if (task === 'event.mailticketsuser') {
				form.elements['return'].value = btoa(location.href);
			}
			Joomla.submitform(task, form);
		};
	});
})();
