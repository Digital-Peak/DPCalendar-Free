DPCalendar = window.DPCalendar || {};

(function (document, DPCalendar) {
  'use strict';

  DPCalendar.autocomplete = {};

  DPCalendar.autocomplete.create = function (input) {
    input.addEventListener('keydown', function (event) {
      var c = event.keyCode;
      var auto = input.parentElement.querySelector('.dp-autocomplete');
      var selected = null;

      if (auto) {
        selected = auto.querySelector('.dp-autocomplete__result_selected');
      } // Enter


      if (c === 13 && selected) {
        selected.click();
      } // ESC or tab


      if ((c === 27 || c === 9) && auto) {
        DPCalendar.slideToggle(auto);
        auto.parentElement.removeChild(auto);
      } // Down


      if (c === 40 && selected && selected.nextElementSibling) {
        selected.classList.remove('dp-autocomplete__result_selected');
        selected.nextElementSibling.classList.add('dp-autocomplete__result_selected');
        auto.scrollTop = selected.offsetTop;
      } // Up


      if (c === 38 && selected && selected.previousElementSibling) {
        selected.classList.remove('dp-autocomplete__result_selected');
        selected.previousElementSibling.classList.add('dp-autocomplete__result_selected');
        auto.scrollTop = selected.offsetTop - 30;
      }
    });
    input.addEventListener('keyup', DPCalendar.debounce(function (event) {
      var maxLength = input.getAttribute('data-max-length') ? input.getAttribute('data-max-length') : 3;

      if ([13, 27, 38, 40].includes(event.keyCode) || input.value.trim().length < maxLength) {
        return;
      }

      input.dispatchEvent(new CustomEvent('dp-autocomplete-change'));
    }));
    input.addEventListener('blur', function (e) {
      if (e.relatedTarget && e.relatedTarget.classList.contains('dp-autocomplete__result')) {
        return true;
      }

      var dropdown = e.target.parentElement.querySelector('.dp-autocomplete');

      if (!dropdown) {
        return true;
      }

      DPCalendar.slideToggle(dropdown);
      dropdown.parentElement.removeChild(dropdown);
    });
  };

  DPCalendar.autocomplete.setItems = function (input, items) {
    var root = input.parentElement.querySelector('.dp-autocomplete');

    if (root && root.items == items) {
      return;
    }

    if (root) {
      root.parentElement.removeChild(root);
    }

    if (items.length == 0) {
      return;
    }

    root = document.createElement('div');
    root.items = items;
    root.classList.add('dp-autocomplete');
    input.parentElement.appendChild(root);
    items.forEach(function (item, index) {
      var e = document.createElement('a');
      e.href = '#';
      e.innerHTML = '<strong>' + item.title + '</strong> <span>' + item.details + '</span>';
      e.classList.add('dp-autocomplete__result');

      if (item.title == input.value) {
        e.classList.add('dp-autocomplete__result_selected');
      }

      e.addEventListener('click', function (ev) {
        ev.preventDefault();
        input.value = item.title;
        input.dispatchEvent(new CustomEvent('dp-autocomplete-select', {
          detail: item
        }));
        root.parentElement.removeChild(root);
        return false;
      });
      root.appendChild(e);
    });

    if (items && !root.querySelector('.dp-autocomplete__result_selected')) {
      root.querySelector('.dp-autocomplete__result').classList.add('dp-autocomplete__result_selected');
    }

    DPCalendar.slideToggle(root, function () {
      root.scrollTop = root.querySelector('.dp-autocomplete__result_selected').offsetTop;
    });
    new Popper(input, root, {
      placement: 'bottom-start',
      modifiers: {
        autoSizing: {
          enabled: true,
          fn: function fn(data) {
            data.styles.width = data.offsets.reference.width;
            return data;
          },
          order: 840
        }
      }
    });
  };
})(document, DPCalendar);