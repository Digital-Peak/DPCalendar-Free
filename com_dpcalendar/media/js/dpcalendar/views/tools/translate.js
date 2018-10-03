(function (document, Joomla) {
	'use strict';

	var callFetch = function (resource, next, queue) {
		DPCalendar.request(
			'task=translate.fetch',
			function (json) {
				for (var i in json.languages) {
					var language = json.languages[i];
					var el = resource.querySelector('.dp-resource__language[data-language="' + language.tag + '"] .dp-resource__percentage');

					if (!el) {
						continue;
					}

					el.innerHTML = language.percent + '%';
					var label = 'success';
					if (language.percent < 30) label = 'important';
					else if (language.percent < 50) label = 'warning';
					else if (language.percent < 100) label = 'info';
					el.parentElement.classList.add('dp-resource_' + label);

					if (next >= queue.length) {
						return;
					}

					callFetch(queue[next], next + 1, queue);
				}
			},
			'resource=' + resource.getAttribute('data-slug')
		);
	};

	var callUpdate = function (resource, next, queue) {
		DPCalendar.request(
			'task=translate.update',
			function (json) {
				resource.querySelector('.dp-resource__icon i').setAttribute('class', 'icon-checkmark-circle');

				if (next >= queue.length) {
					return;
				}

				callUpdate(queue[next], next + 1, queue);
			},
			'resource=' + resource.getAttribute('data-slug')
		);
	};

	document.addEventListener('DOMContentLoaded', function () {
		var queue = [];
		[].slice.call(document.querySelectorAll('.com-dpcalendar-translate .dp-resource')).forEach(function (resource) {
			queue.push(resource);
		});

		callFetch(queue[0], 1, queue);
	});

	Joomla.submitbutton = function (task) {
		if (task == 'translate.update') {
			var queue = [];
			[].slice.call(document.querySelectorAll('.com-dpcalendar-translate .dp-resource:nth-child(-n+6)')).forEach(function (resource) {
				resource.querySelector('.dp-resource__icon i').setAttribute('class', 'icon-loop');
				queue.push(resource);
			});
			callUpdate(queue[0], 1, queue);
		}
		return true;
	}
})(document, Joomla);
