/**
 * DPCalendar polyfill code for old browsers, mainly IE and some Mobile ones.
 * The following functions are handled:
 * - Object.assign   [<IE11]
 * - Element.matches [<IE9]
 * - Element.closest [IE]
 * - Element.dataset [<IE11]
 * - String.endsWith [<IE11]
 * - CustomEvent     [IE]
 */
if (!Element.prototype.matches) {
  Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest) {
  Element.prototype.closest = function (selector) {
    var el = this;

    if (!document.documentElement.contains(el)) {
      return null;
    }

    do {
      if (el.matches(selector)) {
        return el;
      }

      el = el.parentElement;
    } while (el !== null);

    return null;
  };
}

if (!String.prototype.endsWith) {
  String.prototype.endsWith = function (search, this_len) {
    if (this_len === undefined || this_len > this.length) {
      this_len = this.length;
    }

    return this.substring(this_len - search.length, this_len) === search;
  };
}

if (typeof Object.assign != 'function') {
  // Must be writable: true, enumerable: false, configurable: true
  Object.defineProperty(Object, "assign", {
    value: function assign(target, varArgs) {
      // .length of function is 2
      'use strict';

      if (target == null) {
        // TypeError if undefined or null
        throw new TypeError('Cannot convert undefined or null to object');
      }

      var to = Object(target);

      for (var index = 1; index < arguments.length; index++) {
        var nextSource = arguments[index];

        if (nextSource != null) {
          // Skip over if undefined or null
          for (var nextKey in nextSource) {
            // Avoid bugs when hasOwnProperty is shadowed
            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
              to[nextKey] = nextSource[nextKey];
            }
          }
        }
      }

      return to;
    },
    writable: true,
    configurable: true
  });
}
/**
 * Add dataset support to elements
 * No globals, no overriding prototype with non-standard methods,
 *   handles CamelCase properly, attempts to use standard
 *   Object.defineProperty() (and Function bind()) methods,
 *   falls back to native implementation when existing
 * Inspired by http://code.eligrey.com/html5/dataset/
 *   (via https://github.com/adalgiso/html5-dataset/blob/master/html5-dataset.js )
 * Depends on Function.bind and Object.defineProperty/Object.getOwnPropertyDescriptor (polyfills below)
 * All code below is Licensed under the X11/MIT License
 */
// Inspired by https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Function/bind#Compatibility


if (!Function.prototype.bind) {
  Function.prototype.bind = function (oThis) {
    'use strict';

    if (typeof this !== "function") {
      // closest thing possible to the ECMAScript 5 internal IsCallable function
      throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
    }

    var aArgs = Array.prototype.slice.call(arguments, 1),
        fToBind = this,
        FNOP = function FNOP() {},
        fBound = function fBound() {
      return fToBind.apply(this instanceof FNOP && oThis ? this : oThis, aArgs.concat(Array.prototype.slice.call(arguments)));
    };

    FNOP.prototype = this.prototype;
    fBound.prototype = new FNOP();
    return fBound;
  };
}
/*
 * Xccessors Standard: Cross-browser ECMAScript 5 accessors
 * http://purl.eligrey.com/github/Xccessors
 *
 * 2010-06-21
 *
 * By Eli Grey, http://eligrey.com
 *
 * A shim that partially implements Object.defineProperty,
 * Object.getOwnPropertyDescriptor, and Object.defineProperties in browsers that have
 * legacy __(define|lookup)[GS]etter__ support.
 *
 * Licensed under the X11/MIT License
 *   See LICENSE.md
*/
// Removed a few JSLint options as Notepad++ JSLint validator complaining and
//   made comply with JSLint; also moved 'use strict' inside function

/*jslint white: true, undef: true, plusplus: true,
  bitwise: true, regexp: true, newcap: true, maxlen: 90 */

/*! @source http://purl.eligrey.com/github/Xccessors/blob/master/xccessors-standard.js*/


(function () {
  'use strict';

  var ObjectProto = Object.prototype,
      defineGetter = ObjectProto.__defineGetter__,
      defineSetter = ObjectProto.__defineSetter__,
      lookupGetter = ObjectProto.__lookupGetter__,
      lookupSetter = ObjectProto.__lookupSetter__,
      hasOwnProp = ObjectProto.hasOwnProperty;

  if (defineGetter && defineSetter && lookupGetter && lookupSetter) {
    if (!Object.defineProperty) {
      Object.defineProperty = function (obj, prop, descriptor) {
        if (arguments.length < 3) {
          // all arguments required
          throw new TypeError("Arguments not optional");
        }

        prop += ""; // convert prop to string

        if (hasOwnProp.call(descriptor, "value")) {
          if (!lookupGetter.call(obj, prop) && !lookupSetter.call(obj, prop)) {
            // data property defined and no pre-existing accessors
            obj[prop] = descriptor.value;
          }

          if (hasOwnProp.call(descriptor, "get") || hasOwnProp.call(descriptor, "set")) {
            // descriptor has a value prop but accessor already exists
            throw new TypeError("Cannot specify an accessor and a value");
          }
        } // can't switch off these features in ECMAScript 3
        // so throw a TypeError if any are false


        if (!(descriptor.writable && descriptor.enumerable && descriptor.configurable)) {
          throw new TypeError("This implementation of Object.defineProperty does not support" + " false for configurable, enumerable, or writable.");
        }

        if (descriptor.get) {
          defineGetter.call(obj, prop, descriptor.get);
        }

        if (descriptor.set) {
          defineSetter.call(obj, prop, descriptor.set);
        }

        return obj;
      };
    }

    if (!Object.getOwnPropertyDescriptor) {
      Object.getOwnPropertyDescriptor = function (obj, prop) {
        if (arguments.length < 2) {
          // all arguments required
          throw new TypeError("Arguments not optional.");
        }

        prop += ""; // convert prop to string

        var descriptor = {
          configurable: true,
          enumerable: true,
          writable: true
        };
        var getter = lookupGetter.call(obj, prop);
        var setter = lookupSetter.call(obj, prop);

        if (!hasOwnProp.call(obj, prop)) {
          // property doesn't exist or is inherited
          return descriptor;
        }

        if (!getter && !setter) {
          // not an accessor so return prop
          descriptor.value = obj[prop];
          return descriptor;
        } // there is an accessor, remove descriptor.writable;
        // populate descriptor.get and descriptor.set (IE's behavior)


        delete descriptor.writable;
        descriptor.get = descriptor.set = undefined;

        if (getter) {
          descriptor.get = getter;
        }

        if (setter) {
          descriptor.set = setter;
        }

        return descriptor;
      };
    }

    if (!Object.defineProperties) {
      Object.defineProperties = function (obj, props) {
        var prop;

        for (prop in props) {
          if (hasOwnProp.call(props, prop)) {
            Object.defineProperty(obj, prop, props[prop]);
          }
        }
      };
    }
  }
})(); // Begin dataset code


if (!document.documentElement.dataset && ( // FF is empty while IE gives empty object
!Object.getOwnPropertyDescriptor(Element.prototype, 'dataset') || !Object.getOwnPropertyDescriptor(Element.prototype, 'dataset').get)) {
  var propDescriptor = {
    enumerable: true,
    get: function get() {
      'use strict';

      var i,
          that = this,
          HTML5_DOMStringMap,
          attrVal,
          attrName,
          propName,
          attribute,
          attributes = this.attributes,
          attsLength = attributes.length,
          toUpperCase = function toUpperCase(n0) {
        return n0.charAt(1).toUpperCase();
      },
          getter = function getter() {
        return this;
      },
          setter = function setter(attrName, value) {
        return typeof value !== 'undefined' ? this.setAttribute(attrName, value) : this.removeAttribute(attrName);
      };

      try {
        // Simulate DOMStringMap w/accessor support
        // Test setting accessor on normal object
        ({}).__defineGetter__('test', function () {});

        HTML5_DOMStringMap = {};
      } catch (e1) {
        // Use a DOM object for IE8
        HTML5_DOMStringMap = document.createElement('div');
      }

      for (i = 0; i < attsLength; i++) {
        attribute = attributes[i]; // Fix: This test really should allow any XML Name without
        //         colons (and non-uppercase for XHTML)

        if (attribute && attribute.name && /^data-\w[\w\-]*$/.test(attribute.name)) {
          attrVal = attribute.value;
          attrName = attribute.name; // Change to CamelCase

          propName = attrName.substr(5).replace(/-./g, toUpperCase);

          try {
            Object.defineProperty(HTML5_DOMStringMap, propName, {
              enumerable: this.enumerable,
              get: getter.bind(attrVal || ''),
              set: setter.bind(that, attrName)
            });
          } catch (e2) {
            // if accessors are not working
            HTML5_DOMStringMap[propName] = attrVal;
          }
        }
      }

      return HTML5_DOMStringMap;
    }
  };

  try {
    // FF enumerates over element's dataset, but not
    //   Element.prototype.dataset; IE9 iterates over both
    Object.defineProperty(Element.prototype, 'dataset', propDescriptor);
  } catch (e) {
    propDescriptor.enumerable = false; // IE8 does not allow setting to true

    Object.defineProperty(Element.prototype, 'dataset', propDescriptor);
  }
} // Polyfill for creating CustomEvents on IE9/10/11
// code pulled from:
// https://github.com/d4tocchini/customevent-polyfill
// https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent#Polyfill


try {
  var ce = new window.CustomEvent('test');
  ce.preventDefault();

  if (ce.defaultPrevented !== true) {
    // IE has problems with .preventDefault() on custom events
    // http://stackoverflow.com/questions/23349191
    throw new Error('Could not prevent default');
  }
} catch (e) {
  var CustomEvent = function CustomEvent(event, params) {
    var evt, origPrevent;
    params = params || {
      bubbles: false,
      cancelable: false,
      detail: undefined
    };
    evt = document.createEvent("CustomEvent");
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    origPrevent = evt.preventDefault;

    evt.preventDefault = function () {
      origPrevent.call(this);

      try {
        Object.defineProperty(this, 'defaultPrevented', {
          get: function get() {
            return true;
          }
        });
      } catch (e) {
        this.defaultPrevented = true;
      }
    };

    return evt;
  };

  CustomEvent.prototype = window.Event.prototype;
  window.CustomEvent = CustomEvent; // expose definition to window
}