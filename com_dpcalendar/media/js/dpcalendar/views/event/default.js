(function (document, window, location) {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var mailButton = document.querySelector('.com-dpcalendar-event__actions .dp-button-mail');

    if (mailButton) {
      mailButton.addEventListener('click', function (event) {
        window.open(event.target.getAttribute('data-mailtohref'), 'win2', 'width=400,height=350,menubar=yes,resizable=yes');
        return false;
      });
    }

    [].slice.call(document.querySelectorAll('.com-dpcalendar-event__actions [data-href]')).forEach(function (el) {
      el.addEventListener('click', function (event) {
        if (event.target.getAttribute('data-open') == 'window') {
          window.open(event.target.getAttribute('data-href'));
        } else {
          location.href = event.target.getAttribute('data-href');
        }

        return false;
      });
    });
  });
})(document, window, location);