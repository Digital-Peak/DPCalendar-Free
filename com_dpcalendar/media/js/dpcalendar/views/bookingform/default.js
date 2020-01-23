DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    calculatePrice();
    [].slice.call(document.querySelectorAll('.dp-booking-series__input input')).forEach(function (input) {
      input.addEventListener('click', function () {
        var showSeries = document.getElementById('jform_series0').checked;
        [].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__events .dp-event_instance')).forEach(function (row) {
          row.style.display = showSeries ? 'inherit' : 'none';
          row.querySelector('select').value = showSeries ? '1' : '0';
        });
        calculatePrice();
      });
    });
    [].slice.call(document.querySelectorAll('.dp-ticket__amount .dp-select, .dp-option__amount .dp-select, .dp-field-country .dp-select')).forEach(function (select) {
      select.addEventListener('change', function () {
        calculatePrice();
      });
    });
    var terms = [].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__fields .dp-input-terms'));
    terms.forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        var accepted = true;
        terms.forEach(function (term) {
          if (!term.checked) {
            accepted = false;
          }
        });
        document.querySelector('.com-dpcalendar-bookingform .dp-button-save').disabled = !accepted;
      });
    });

    if (terms.length) {
      document.querySelector('.com-dpcalendar-bookingform .dp-button-save').disabled = true;
    }

    [].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__actions .dp-button')).forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        var options = document.querySelectorAll('.dp-payment-option'); // If there is one option check it

        if (button.getAttribute('data-task') == 'save' && options.length == 1) {
          options[0].querySelector('.dp-payment-option__input').checked = true;
        } // If there is no price check the first option


        var total = document.querySelector('.dp-price-total__content');

        if (button.getAttribute('data-task') == 'save' && options.length > 0 && total && total.getAttribute('data-raw') == 0) {
          options[0].querySelector('.dp-payment-option__input').checked = true;
        }

        if (button.getAttribute('data-task') == 'save' && options.length > 1 && !document.querySelectorAll('.dp-payment-option__input:checked').length) {
          var form = document.getElementsByName('adminForm')[0];

          if (form && !document.formvalidator.isValid(form)) {
            return false;
          }

          DPCalendar.slideToggle(document.querySelector('.com-dpcalendar-bookingform__payment-options'));
          [].slice.call(document.querySelectorAll('.dp-payment-option')).forEach(function (option) {
            option.addEventListener('click', function () {
              option.querySelector('.dp-payment-option__input').checked = true;
              Joomla.submitbutton('bookingform.save');
            });
          });
          return false;
        }

        Joomla.submitbutton('bookingform.' + button.getAttribute('data-task'));
        return false;
      });
    });

    Joomla.submitbutton = function (task) {
      var form = document.getElementsByName('adminForm')[0];

      if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
        Joomla.submitform(task, form);
      }
    };

    [].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform .dp-field-country .dp-select')).forEach(function (select) {
      select._choicejs = new Choices(select, {
        itemSelectText: '',
        noChoicesText: '',
        shouldSortItems: false,
        shouldSort: false,
        searchResultLimit: 30
      });
    });
  });

  function calculatePrice() {
    var saveButton = document.querySelector('.com-dpcalendar-bookingform .dp-button-save');

    if (saveButton) {
      saveButton.disabled = false;
    }

    if (!Joomla.getOptions('DPCalendar.price.url') || !document.querySelector('.dp-price-total__content')) {
      return;
    }

    [].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform .dp-event')).forEach(function (event) {
      var selected = 0;
      var events = [].slice.call(event.querySelectorAll('.dp-ticket__amount .dp-select'));
      events.forEach(function (select) {
        selected += parseInt(select.options[select.selectedIndex].value);
      });
      events.forEach(function (select) {
        if (selected > event.getAttribute('data-ticket-count') && parseInt(select.options[select.selectedIndex].value) > 0) {
          select.classList.add('dp-select_error');
        } else {
          select.classList.remove('dp-select_error');
        }
      });
    });
    var taxElement = document.querySelector('.com-dpcalendar-bookingform .dp-tax');

    if (document.querySelectorAll('.com-dpcalendar-bookingform .dp-ticket__amount .dp-select_error').length > 0) {
      document.querySelector('.com-dpcalendar-bookingform .dp-price-total__content').innerHTML = '';
      Joomla.renderMessages({
        error: [Joomla.JText._('COM_DPCALENDAR_VIEW_BOOKINGFORM_TICKETS_OVERBOOKED_MESSAGE')]
      });
      saveButton.disabled = true;

      if (taxElement) {
        taxElement.style.display = 'none';
      }

      return;
    }

    var form = document.querySelector('.com-dpcalendar-bookingform__form');
    var data = DPCalendar.formToQueryString(form, 'input:not([name="task"]), select');
    DPCalendar.request(Joomla.getOptions('DPCalendar.price.url'), function (json) {
      var textTax = Joomla.JText._('COM_DPCALENDAR_VIEW_BOOKINGFORM_TAX_' + (json.data.taxinclusive == 1 ? 'IN' : 'EX') + 'CLUSIVE_TEXT');

      var textDiscount = Joomla.JText._('COM_DPCALENDAR_VIEW_BOOKINGFORM_DISCOUNT');

      Object.keys(json.data.events).map(function (eventId) {
        var root = form.querySelector('[data-event-id="' + eventId + '"]');
        Object.keys(json.data.events[eventId]).map(function (type) {
          Object.keys(json.data.events[eventId][type]).map(function (id) {
            var price = json.data.events[eventId][type][id]; // In markup it is singular

            var selector = type.substring(0, type.length - 1);
            var row = root.querySelector('[data-' + selector + '-price="' + id + '"]');

            if (!row) {
              return;
            }

            var liveCell = row.querySelector('.dp-price__live');

            if (!liveCell) {
              return;
            }

            var info = row.querySelector('.dp-price__info');
            var infoText = '';
            liveCell.innerHTML = price.discount;

            if (price.discount != price.original) {
              row.querySelector('.dp-price__live').classList.remove('dp-price_hidden');
              row.querySelector('.dp-price__original').classList.remove('dp-price_hidden');
              row.querySelector('.dp-price__original').innerHTML = price.original;
              infoText = textDiscount;
            } else {
              row.querySelector('.dp-price__original').classList.add('dp-price_hidden');

              if (info) {
                info.classList.add('dp-price_hidden');
              }
            }

            if (json.data.tax && price.raw != '0.00') {
              infoText += (infoText ? '<br>' : '') + textTax;
            }

            if (info && infoText) {
              info.classList.remove('dp-price_hidden');
              tippy(info, {
                interactive: true,
                delay: 100,
                arrow: true,
                content: infoText,
                ignoreAttributes: true,
                popperOptions: {
                  modifiers: {
                    preventOverflow: {
                      enabled: false
                    },
                    hide: {
                      enabled: false
                    }
                  }
                }
              });
            }
          });
        });
      });
      document.querySelector('.com-dpcalendar-bookingform .dp-price-total__content').innerHTML = json.data.total;
      document.querySelector('.com-dpcalendar-bookingform .dp-price-total__content').setAttribute('data-raw', json.data.totalraw);
      var taxElement = document.querySelector('.com-dpcalendar-bookingform .dp-tax');

      if (json.data.tax) {
        taxElement.style.display = 'inline-block';
        taxElement.querySelector('.dp-tax__content').innerHTML = json.data.tax;
        taxElement.querySelector('.dp-tax__title').innerHTML = '(' + json.data.taxtitle + ')';
        tippy(taxElement.querySelector('.dp-tax__icon'), {
          interactive: true,
          delay: 100,
          arrow: true,
          content: textTax,
          ignoreAttributes: true,
          popperOptions: {
            modifiers: {
              preventOverflow: {
                enabled: false
              },
              hide: {
                enabled: false
              }
            }
          }
        });
      } else {
        taxElement.style.display = 'none';
      }
    }, data);
  }

  ;

  DPCalendar.updateBookingMail = function (input) {
    DPCalendar.request('task=booking.mail', function (json) {
      if (json.success) {
        document.getElementById('jform_name').value = json.data.name;
        document.getElementById('jform_email').value = json.data.email;
      }
    }, 'ajax=1&id=' + document.getElementById('jform_user_id_id').value);
  };
})(document, Joomla, DPCalendar);