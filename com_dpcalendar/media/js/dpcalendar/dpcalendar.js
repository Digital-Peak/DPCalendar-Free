function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
  'use strict'; // Make sure the options are loaded correctly

  Joomla.loadOptions(); // Counter for currently active XHR requests

  DPCalendar.requestCounter = 0;

  DPCalendar.modal = function (url, width, height, closeFunction) {
    var modal = new tingle.modal({
      footer: false,
      stickyFooter: false,
      closeMethods: ['overlay', 'button', 'escape'],
      cssClass: ['dpcalendar-modal'],
      closeLabel: Joomla.JText._('COM_DPCALENDAR_CLOSE', 'Close'),
      onClose: function onClose() {
        if (closeFunction) {
          closeFunction(modal.modalBox.children[0].querySelector('iframe'));
        }
      }
    }); // Overwrite the width of the modal

    if (width && document.body.clientWidth > width) {
      if (!isNaN(width)) {
        width = width + 'px';
      }

      document.querySelector('.tingle-modal-box').style.width = width;
    }

    if (!isNaN(height)) {
      height = height + 'px';
    }

    modal.setContent('<iframe width="100%" height="' + height + '" src="' + url.toString() + '" frameborder="0" allowfullscreen></iframe>');
    modal.open();
  };

  DPCalendar.print = function (selector) {
    document.body.outerHTML = document.querySelector(selector).outerHTML;
    window.addEventListener('afterprint', function (event) {
      // Page needs to be reloaded, otherwise all listeners are lost
      // A timeout is needed till the system dialog closes on FF
      setTimeout(function () {
        return window.location.reload(true);
      }, 2000);
    });
    window.print();
  };

  DPCalendar.slideToggle = function (el, fn) {
    if (!el) {
      return;
    }

    if (!el.getAttribute('data-max-height')) {
      // Backup the styles
      var style = window.getComputedStyle(el),
          display = style.display,
          position = style.position,
          visibility = style.visibility; // Some defaults

      var elHeight = el.offsetHeight; // If its not hidden we just use normal height

      if (display === 'none') {
        // The element is hidden:
        // Making the el block so we can measure its height but still be hidden
        // el.style.position = 'absolute';
        el.style.visibility = 'hidden';
        el.style.display = 'block';
        elHeight = el.offsetHeight;
        var styles = window.getComputedStyle(el);
        elHeight += parseFloat(styles.getPropertyValue('margin-top')) + parseFloat(styles['marginBottom']); // Reverting to the original values

        el.style.display = display;
        el.style.position = position;
        el.style.visibility = visibility;
      }

      if (el.style.maxHeight && el.style.maxHeight < elHeight) {
        elHeight = el.style.maxHeight;
        el.style.overflowY = 'auto';
      } // Setting the required styles


      el.style['transition'] = 'max-height 0.5s ease-in-out';
      el.style.overflowX = 'hidden';
      el.style.overflowY = 'hidden';
      el.style.maxHeight = display === 'none' ? '0px' : elHeight + 'px';
      el.style.display = 'block'; // Backup the element height attribute

      el.setAttribute('data-max-height', elHeight + 'px');
    } // Flag if we fade in


    var fadeIn = el.style.maxHeight.replace('px', '').replace('%', '') === '0'; // If a callback exists add a listener

    if (fn) {
      el.addEventListener('transitionend', function () {
        fn(fadeIn);
      }, {
        once: true
      });
    } // We use setTimeout to modify maxHeight later than display to have a transition effect


    setTimeout(function () {
      el.style.maxHeight = fadeIn ? el.getAttribute('data-max-height') : '0';
    }, 1);
  };

  DPCalendar.encode = function (str) {
    return str.replace(/&amp;/g, '&');
  };

  DPCalendar.pad = function (num, size) {
    var s = num + '';

    while (s.length < size) {
      s = '0' + s;
    }

    return s;
  };

  DPCalendar.isLocalStorageSupported = function () {
    var testKey = 'test';

    try {
      localStorage.setItem(testKey, '1');
      localStorage.removeItem(testKey);
      return true;
    } catch (error) {
      return false;
    }
  };

  DPCalendar.formToQueryString = function (form, selector) {
    var elements = selector ? form.querySelectorAll(selector) : form.elements;
    var field,
        s = [];

    for (var i = 0; i < elements.length; i++) {
      field = elements[i];

      if (!field.name || field.disabled || field.type == 'file' || field.type == 'reset' || field.type == 'submit' || field.type == 'button') {
        continue;
      }

      if (field.type == 'select-multiple') {
        for (var j = elements[i].options.length - 1; j >= 0; j--) {
          if (field.options[j].selected) {
            s[s.length] = encodeURIComponent(field.name) + '=' + encodeURIComponent(field.options[j].value);
          }
        }
      } else if (field.type != 'checkbox' && field.type != 'radio' || field.checked) {
        s[s.length] = encodeURIComponent(field.name) + '=' + encodeURIComponent(field.value);
      }
    }

    return s.join('&').replace(/%20/g, '+');
  };

  DPCalendar.arrayToQueryString = function (array, prefix) {
    var str = [];
    var p;

    for (p in array) {
      if (array.hasOwnProperty(p)) {
        var k = prefix ? prefix + '[' + p + ']' : p,
            v = array[p];
        str.push(v !== null && _typeof(v) === 'object' ? DPCalendar.arrayToQueryString(v, k) : encodeURIComponent(k) + '=' + encodeURIComponent(v));
      }
    }

    return str.join('&').replace(/%20/g, '+');
  };

  DPCalendar.currentLocation = function (callback) {
    if (!navigator.geolocation) {
      return false;
    }

    navigator.geolocation.getCurrentPosition(function (pos) {
      var task = 'location.loc';

      if (window.location.href.indexOf('administrator') == -1) {
        task = 'locationform.loc';
      }

      DPCalendar.request('task=' + task + '&loc=' + encodeURIComponent(pos.coords.latitude + ',' + pos.coords.longitude), function (json) {
        callback(json.data.formated);
      });
    }, function (error) {
      Joomla.renderMessages({
        error: [error.message]
      });
    });
    return true;
  };

  DPCalendar.request = function (url, callback, data, updateLoader) {
    var loader = updateLoader !== false ? document.querySelector('.dp-loader') : null;

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
      onSuccess: function onSuccess(res) {
        DPCalendar.requestCounter--;

        if (loader) {
          loader.classList.add('dp-loader_hidden');
        }

        try {
          var json = JSON.parse(res);

          if (json.messages != null && json.messages.length !== 0 && document.getElementById('system-message-container')) {
            Joomla.renderMessages(json.messages);
          }

          if (callback) {
            callback(json);
          }
        } catch (e) {
          if (document.getElementById('system-message-container')) {
            Joomla.renderMessages({
              error: [url + '<br>' + e.message]
            });
          }
        }
      },
      onError: function onError(response) {
        DPCalendar.requestCounter--;

        if (loader) {
          loader.classList.add('dp-loader_hidden');
        }

        try {
          var json = JSON.parse(response);

          if (json.messages != null && json.messages.length !== 0 && document.getElementById('system-message-container')) {
            Joomla.renderMessages(json.messages);
          }
        } catch (e) {
          if (document.getElementById('system-message-container')) {
            Joomla.renderMessages({
              error: [url + '<br>' + e.message]
            });
          }
        }
      }
    });
  };

  DPCalendar.debounce = function (func, wait, immediate) {
    if (wait == null) {
      wait = 500;
    }

    if (immediate == null) {
      immediate = false;
    }

    var timeout;
    return function () {
      var context = this,
          args = arguments;

      var later = function later() {
        timeout = null;

        if (!immediate) {
          func.apply(context, args);
        }
      };

      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);

      if (callNow) {
        func.apply(context, args);
      }
    };
  };

  document.addEventListener('DOMContentLoaded', function () {
    // Print button
    [].slice.call(document.querySelectorAll('.dp-button-print')).forEach(function (button) {
      button.addEventListener('click', function () {
        DPCalendar.print(button.getAttribute('data-selector'));
      });
    }); // Buttons as links

    [].slice.call(document.querySelectorAll('.dp-button-action[data-href]')).forEach(function (el) {
      el.addEventListener('click', function () {
        if (this.getAttribute('data-target') == 'new') {
          window.open(this.getAttribute('data-href'));
          return false;
        }

        location.href = this.getAttribute('data-href');
        return false;
      });
    }); // Timezone switcher

    var tzSwitcher = document.querySelector('.dp-timezone .dp-select');

    if (tzSwitcher) {
      if (typeof Choices != 'undefined') {
        tzSwitcher._choicejs = new Choices(tzSwitcher, {
          itemSelectText: '',
          noChoicesText: '',
          shouldSortItems: false,
          shouldSort: false,
          searchResultLimit: 30
        });
      }

      tzSwitcher.addEventListener('change', function () {
        this.form.submit();
      });
    } // Tabs save state


    if (DPCalendar.isLocalStorageSupported()) {
      [].slice.call(document.querySelectorAll('.dp-tabs__input')).forEach(function (tab) {
        tab.addEventListener('click', function () {
          localStorage.setItem('dp-tabs-' + this.name, this.id);
        });

        if (localStorage.getItem('dp-tabs-' + tab.name) == tab.id) {
          tab.checked = true;
        }
      });
    }
  });
})(document, Joomla, DPCalendar);