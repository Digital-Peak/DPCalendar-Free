(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 * Object.assign [<IE11]
	 */

	if (typeof Object.assign != 'function') {
		// Must be writable: true, enumerable: false, configurable: true
		Object.defineProperty(Object, "assign", {
			value: function assign(target, varArgs) {
				if (target == null) { // TypeError if undefined or null
					throw new TypeError('Cannot convert undefined or null to object');
				}

				var to = Object(target);

				for (var index = 1; index < arguments.length; index++) {
					var nextSource = arguments[index];

					if (nextSource != null) { // Skip over if undefined or null
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
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * Element.closest [IE]
	 */

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

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * SVGElement.prototype.contains [IE]
	 */

	if (!SVGElement.prototype.contains) {
		SVGElement.prototype.contains = HTMLDivElement.prototype.contains;
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * CustomEvent [IE]
	 */

	// Polyfill for creating CustomEvents on IE9/10/11
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
		var CustomEvent = function (event, params) {
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
						get: function () {
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

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * Element.dataset [<IE11]
	 */

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
			if (typeof this !== "function") {
				// closest thing possible to the ECMAScript 5 internal IsCallable function
				throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
			}

			var aArgs = Array.prototype.slice.call(arguments, 1),
				fToBind = this,
				FNOP = function () {
				},
				fBound = function () {
					return fToBind.apply(
						this instanceof FNOP && oThis ? this : oThis,
						aArgs.concat(Array.prototype.slice.call(arguments))
					);
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
		var ObjectProto = Object.prototype,
			defineGetter = ObjectProto.__defineGetter__,
			defineSetter = ObjectProto.__defineSetter__,
			lookupGetter = ObjectProto.__lookupGetter__,
			lookupSetter = ObjectProto.__lookupSetter__,
			hasOwnProp = ObjectProto.hasOwnProperty;

		if (defineGetter && defineSetter && lookupGetter && lookupSetter) {
			if (!Object.defineProperty) {
				Object.defineProperty = function (obj, prop, descriptor) {
					if (arguments.length < 3) { // all arguments required
						throw new TypeError("Arguments not optional");
					}

					prop += ""; // convert prop to string

					if (hasOwnProp.call(descriptor, "value")) {
						if (!lookupGetter.call(obj, prop) && !lookupSetter.call(obj, prop)) {
							// data property defined and no pre-existing accessors
							obj[prop] = descriptor.value;
						}

						if ((hasOwnProp.call(descriptor, "get") ||
							hasOwnProp.call(descriptor, "set"))) {
							// descriptor has a value prop but accessor already exists
							throw new TypeError("Cannot specify an accessor and a value");
						}
					}

					// can't switch off these features in ECMAScript 3
					// so throw a TypeError if any are false
					if (!(descriptor.writable && descriptor.enumerable &&
						descriptor.configurable)) {
						throw new TypeError(
							"This implementation of Object.defineProperty does not support" +
							" false for configurable, enumerable, or writable."
						);
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
					if (arguments.length < 2) { // all arguments required
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
					if (!getter && !setter) { // not an accessor so return prop
						descriptor.value = obj[prop];
						return descriptor;
					}

					// there is an accessor, remove descriptor.writable;
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
	}());

	// Begin dataset code
	if (!document.documentElement.dataset &&
		// FF is empty while IE gives empty object
		(!Object.getOwnPropertyDescriptor(Element.prototype, 'dataset') ||
			!Object.getOwnPropertyDescriptor(Element.prototype, 'dataset').get)
	) {
		var propDescriptor = {
			enumerable: true,
			get: function () {
				var i,
					that = this,
					HTML5_DOMStringMap,
					attrVal, attrName, propName,
					attribute,
					attributes = this.attributes,
					attsLength = attributes.length,
					toUpperCase = function (n0) {
						return n0.charAt(1).toUpperCase();
					},
					getter = function () {
						return this;
					},
					setter = function (attrName, value) {
						return (typeof value !== 'undefined') ?
							this.setAttribute(attrName, value) :
							this.removeAttribute(attrName);
					};
				try { // Simulate DOMStringMap w/accessor support
					// Test setting accessor on normal object
					({}).__defineGetter__('test', function () {
					});
					HTML5_DOMStringMap = {};
				} catch (e1) { // Use a DOM object for IE8
					HTML5_DOMStringMap = document.createElement('div');
				}
				for (i = 0; i < attsLength; i++) {
					attribute = attributes[i];
					// Fix: This test really should allow any XML Name without
					//         colons (and non-uppercase for XHTML)
					if (attribute && attribute.name &&
						(/^data-\w[\w\-]*$/).test(attribute.name)) {
						attrVal = attribute.value;
						attrName = attribute.name;
						// Change to CamelCase
						propName = attrName.substr(5).replace(/-./g, toUpperCase);
						try {
							Object.defineProperty(HTML5_DOMStringMap, propName, {
								enumerable: this.enumerable,
								get: getter.bind(attrVal || ''),
								set: setter.bind(that, attrName)
							});
						} catch (e2) { // if accessors are not working
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
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * String.endsWith [<IE11]
	 */

	if (!String.prototype.endsWith) {
		String.prototype.endsWith = function (search, this_len) {
			if (this_len === undefined || this_len > this.length) {
				this_len = this.length;
			}
			return this.substring(this_len - search.length, this_len) === search;
		};
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * Array.fill [IE]
	 */

	if (!Array.prototype.fill) {
		Object.defineProperty(Array.prototype, 'fill', {
			value: function (value) {

				// Steps 1-2.
				if (this == null) {
					throw new TypeError('this is null or not defined');
				}

				var O = Object(this);

				// Steps 3-5.
				var len = O.length >>> 0;

				// Steps 6-7.
				var start = arguments[1];
				var relativeStart = start >> 0;

				// Step 8.
				var k = relativeStart < 0 ?
					Math.max(len + relativeStart, 0) :
					Math.min(relativeStart, len);

				// Steps 9-10.
				var end = arguments[2];
				var relativeEnd = end === undefined ?
					len : end >> 0;

				// Step 11.
				var final = relativeEnd < 0 ?
					Math.max(len + relativeEnd, 0) :
					Math.min(relativeEnd, len);

				// Step 12.
				while (k < final) {
					O[k] = value;
					k++;
				}

				// Step 13.
				return O;
			}
		});
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * Array.prototype.find [IE]
	 */

	// https://tc39.github.io/ecma262/#sec-array.prototype.find
	if (!Array.prototype.find) {
		Object.defineProperty(Array.prototype, 'find', {
			value: function (predicate) {
				// 1. Let O be ? ToObject(this value).
				if (this == null) {
					throw TypeError('"this" is null or not defined');
				}

				var o = Object(this);

				// 2. Let len be ? ToLength(? Get(O, "length")).
				var len = o.length >>> 0;

				// 3. If IsCallable(predicate) is false, throw a TypeError exception.
				if (typeof predicate !== 'function') {
					throw TypeError('predicate must be a function');
				}

				// 4. If thisArg was supplied, let T be thisArg; else let T be undefined.
				var thisArg = arguments[1];

				// 5. Let k be 0.
				var k = 0;

				// 6. Repeat, while k < len
				while (k < len) {
					// a. Let Pk be ! ToString(k).
					// b. Let kValue be ? Get(O, Pk).
					// c. Let testResult be ToBoolean(? Call(predicate, T, « kValue, k, O »)).
					// d. If testResult is true, return kValue.
					var kValue = o[k];
					if (predicate.call(thisArg, kValue, k, o)) {
						return kValue;
					}
					// e. Increase k by 1.
					k++;
				}

				// 7. Return undefined.
				return undefined;
			},
			configurable: true,
			writable: true
		});
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * Array.from [IE]
	 */

	if (!Array.from) {
		Array.from = (function () {
			var toStr = Object.prototype.toString;
			var isCallable = function (fn) {
				return typeof fn === 'function' || toStr.call(fn) === '[object Function]';
			};
			var toInteger = function (value) {
				var number = Number(value);
				if (isNaN(number)) { return 0; }
				if (number === 0 || !isFinite(number)) { return number; }
				return (number > 0 ? 1 : -1) * Math.floor(Math.abs(number));
			};
			var maxSafeInteger = Math.pow(2, 53) - 1;
			var toLength = function (value) {
				var len = toInteger(value);
				return Math.min(Math.max(len, 0), maxSafeInteger);
			};

			// The length property of the from method is 1.
			return function from(arrayLike/*, mapFn, thisArg */) {
				// 1. Let C be the this value.
				var C = this;

				// 2. Let items be ToObject(arrayLike).
				var items = Object(arrayLike);

				// 3. ReturnIfAbrupt(items).
				if (arrayLike == null) {
					throw new TypeError('Array.from requires an array-like object - not null or undefined');
				}

				// 4. If mapfn is undefined, then let mapping be false.
				var mapFn = arguments.length > 1 ? arguments[1] : void undefined;
				var T;
				if (typeof mapFn !== 'undefined') {
					// 5. else
					// 5. a If IsCallable(mapfn) is false, throw a TypeError exception.
					if (!isCallable(mapFn)) {
						throw new TypeError('Array.from: when provided, the second argument must be a function');
					}

					// 5. b. If thisArg was supplied, let T be thisArg; else let T be undefined.
					if (arguments.length > 2) {
						T = arguments[2];
					}
				}

				// 10. Let lenValue be Get(items, "length").
				// 11. Let len be ToLength(lenValue).
				var len = toLength(items.length);

				// 13. If IsConstructor(C) is true, then
				// 13. a. Let A be the result of calling the [[Construct]] internal method
				// of C with an argument list containing the single item len.
				// 14. a. Else, Let A be ArrayCreate(len).
				var A = isCallable(C) ? Object(new C(len)) : new Array(len);

				// 16. Let k be 0.
				var k = 0;
				// 17. Repeat, while k < len… (also steps a - h)
				var kValue;
				while (k < len) {
					kValue = items[k];
					if (mapFn) {
						A[k] = typeof T === 'undefined' ? mapFn(kValue, k) : mapFn.call(T, kValue, k);
					} else {
						A[k] = kValue;
					}
					k += 1;
				}
				// 18. Let putStatus be Put(A, "length", len, true).
				A.length = len;
				// 20. Return A.
				return A;
			};
		}());
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 *
	 * Element.matches [<IE9]
	 */

	if (!Element.prototype.matches) {
		Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
	}

}());
//# sourceMappingURL=polyfill.js.map
