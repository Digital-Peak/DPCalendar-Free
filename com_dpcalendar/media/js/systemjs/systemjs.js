function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

/*
* SJS 6.2.6
* Minimal SystemJS Build
*/
(function () {
  var hasSelf = typeof self !== 'undefined';
  var hasDocument = typeof document !== 'undefined';
  var envGlobal = hasSelf ? self : global;
  var baseUrl;

  if (hasDocument) {
    var baseEl = document.querySelector('base[href]');
    if (baseEl) baseUrl = baseEl.href;
  }

  if (!baseUrl && typeof location !== 'undefined') {
    baseUrl = location.href.split('#')[0].split('?')[0];
    var lastSepIndex = baseUrl.lastIndexOf('/');
    if (lastSepIndex !== -1) baseUrl = baseUrl.slice(0, lastSepIndex + 1);
  }

  var backslashRegEx = /\\/g;

  function resolveIfNotPlainOrUrl(relUrl, parentUrl) {
    if (relUrl.indexOf('\\') !== -1) relUrl = relUrl.replace(backslashRegEx, '/'); // protocol-relative

    if (relUrl[0] === '/' && relUrl[1] === '/') {
      return parentUrl.slice(0, parentUrl.indexOf(':') + 1) + relUrl;
    } // relative-url
    else if (relUrl[0] === '.' && (relUrl[1] === '/' || relUrl[1] === '.' && (relUrl[2] === '/' || relUrl.length === 2 && (relUrl += '/')) || relUrl.length === 1 && (relUrl += '/')) || relUrl[0] === '/') {
        var parentProtocol = parentUrl.slice(0, parentUrl.indexOf(':') + 1); // Disabled, but these cases will give inconsistent results for deep backtracking
        //if (parentUrl[parentProtocol.length] !== '/')
        //  throw Error('Cannot resolve');
        // read pathname from parent URL
        // pathname taken to be part after leading "/"

        var pathname;

        if (parentUrl[parentProtocol.length + 1] === '/') {
          // resolving to a :// so we need to read out the auth and host
          if (parentProtocol !== 'file:') {
            pathname = parentUrl.slice(parentProtocol.length + 2);
            pathname = pathname.slice(pathname.indexOf('/') + 1);
          } else {
            pathname = parentUrl.slice(8);
          }
        } else {
          // resolving to :/ so pathname is the /... part
          pathname = parentUrl.slice(parentProtocol.length + (parentUrl[parentProtocol.length] === '/'));
        }

        if (relUrl[0] === '/') return parentUrl.slice(0, parentUrl.length - pathname.length - 1) + relUrl; // join together and split for removal of .. and . segments
        // looping the string instead of anything fancy for perf reasons
        // '../../../../../z' resolved to 'x/y' is just 'z'

        var segmented = pathname.slice(0, pathname.lastIndexOf('/') + 1) + relUrl;
        var output = [];
        var segmentIndex = -1;

        for (var i = 0; i < segmented.length; i++) {
          // busy reading a segment - only terminate on '/'
          if (segmentIndex !== -1) {
            if (segmented[i] === '/') {
              output.push(segmented.slice(segmentIndex, i + 1));
              segmentIndex = -1;
            }
          } // new segment - check if it is relative
          else if (segmented[i] === '.') {
              // ../ segment
              if (segmented[i + 1] === '.' && (segmented[i + 2] === '/' || i + 2 === segmented.length)) {
                output.pop();
                i += 2;
              } // ./ segment
              else if (segmented[i + 1] === '/' || i + 1 === segmented.length) {
                  i += 1;
                } else {
                  // the start of a new segment as below
                  segmentIndex = i;
                }
            } // it is the start of a new segment
            else {
                segmentIndex = i;
              }
        } // finish reading out the last segment


        if (segmentIndex !== -1) output.push(segmented.slice(segmentIndex));
        return parentUrl.slice(0, parentUrl.length - pathname.length) + output.join('');
      }
  }
  /*
   * Import maps implementation
   *
   * To make lookups fast we pre-resolve the entire import map
   * and then match based on backtracked hash lookups
   *
   */


  function resolveUrl(relUrl, parentUrl) {
    return resolveIfNotPlainOrUrl(relUrl, parentUrl) || (relUrl.indexOf(':') !== -1 ? relUrl : resolveIfNotPlainOrUrl('./' + relUrl, parentUrl));
  }
  /*
   * SystemJS Core
   * 
   * Provides
   * - System.import
   * - System.register support for
   *     live bindings, function hoisting through circular references,
   *     reexports, dynamic import, import.meta.url, top-level await
   * - System.getRegister to get the registration
   * - Symbol.toStringTag support in Module objects
   * - Hookable System.createContext to customize import.meta
   * - System.onload(err, id, deps) handler for tracing / hot-reloading
   * 
   * Core comes with no System.prototype.resolve or
   * System.prototype.instantiate implementations
   */


  var hasSymbol = typeof Symbol !== 'undefined';
  var toStringTag = hasSymbol && Symbol.toStringTag;
  var REGISTRY = hasSymbol ? Symbol() : '@';

  function SystemJS() {
    this[REGISTRY] = {};
  }

  var systemJSPrototype = SystemJS.prototype;

  systemJSPrototype.prepareImport = function () {};

  systemJSPrototype.import = function (id, parentUrl) {
    var loader = this;
    return Promise.resolve(loader.prepareImport()).then(function () {
      return loader.resolve(id, parentUrl);
    }).then(function (id) {
      var load = getOrCreateLoad(loader, id);
      return load.C || topLevelLoad(loader, load);
    });
  }; // Hookable createContext function -> allowing eg custom import meta


  systemJSPrototype.createContext = function (parentId) {
    return {
      url: parentId
    };
  };

  var lastRegister;

  systemJSPrototype.register = function (deps, declare) {
    lastRegister = [deps, declare];
  };
  /*
   * getRegister provides the last anonymous System.register call
   */


  systemJSPrototype.getRegister = function () {
    var _lastRegister = lastRegister;
    lastRegister = undefined;
    return _lastRegister;
  };

  function getOrCreateLoad(loader, id, firstParentUrl) {
    var load = loader[REGISTRY][id];
    if (load) return load;
    var importerSetters = [];
    var ns = Object.create(null);
    if (toStringTag) Object.defineProperty(ns, toStringTag, {
      value: 'Module'
    });
    var instantiatePromise = Promise.resolve().then(function () {
      return loader.instantiate(id, firstParentUrl);
    }).then(function (registration) {
      if (!registration) throw Error('Module ' + id + ' did not instantiate');

      function _export(name, value) {
        // note if we have hoisted exports (including reexports)
        load.h = true;
        var changed = false;

        if (_typeof(name) !== 'object') {
          if (!(name in ns) || ns[name] !== value) {
            ns[name] = value;
            changed = true;
          }
        } else {
          for (var p in name) {
            var _value = name[p];

            if (!(p in ns) || ns[p] !== _value) {
              ns[p] = _value;
              changed = true;
            }
          }

          if (name.__esModule) {
            ns.__esModule = name.__esModule;
          }
        }

        if (changed) for (var i = 0; i < importerSetters.length; i++) {
          importerSetters[i](ns);
        }
        return value;
      }

      var declared = registration[1](_export, registration[1].length === 2 ? {
        import: function _import(importId) {
          return loader.import(importId, id);
        },
        meta: loader.createContext(id)
      } : undefined);

      load.e = declared.execute || function () {};

      return [registration[0], declared.setters || []];
    });
    var linkPromise = instantiatePromise.then(function (instantiation) {
      return Promise.all(instantiation[0].map(function (dep, i) {
        var setter = instantiation[1][i];
        return Promise.resolve(loader.resolve(dep, id)).then(function (depId) {
          var depLoad = getOrCreateLoad(loader, depId, id); // depLoad.I may be undefined for already-evaluated

          return Promise.resolve(depLoad.I).then(function () {
            if (setter) {
              depLoad.i.push(setter); // only run early setters when there are hoisted exports of that module
              // the timing works here as pending hoisted export calls will trigger through importerSetters

              if (depLoad.h || !depLoad.I) setter(depLoad.n);
            }

            return depLoad;
          });
        });
      })).then(function (depLoads) {
        load.d = depLoads;
      });
    });
    linkPromise.catch(function (err) {
      load.e = null;
      load.er = err;
    }); // Capital letter = a promise function

    return load = loader[REGISTRY][id] = {
      id: id,
      // importerSetters, the setters functions registered to this dependency
      // we retain this to add more later
      i: importerSetters,
      // module namespace object
      n: ns,
      // instantiate
      I: instantiatePromise,
      // link
      L: linkPromise,
      // whether it has hoisted exports
      h: false,
      // On instantiate completion we have populated:
      // dependency load records
      d: undefined,
      // execution function
      // set to NULL immediately after execution (or on any failure) to indicate execution has happened
      // in such a case, C should be used, and E, I, L will be emptied
      e: undefined,
      // On execution we have populated:
      // the execution error if any
      er: undefined,
      // in the case of TLA, the execution promise
      E: undefined,
      // On execution, L, I, E cleared
      // Promise for top-level completion
      C: undefined
    };
  }

  function instantiateAll(loader, load, loaded) {
    if (!loaded[load.id]) {
      loaded[load.id] = true; // load.L may be undefined for already-instantiated

      return Promise.resolve(load.L).then(function () {
        return Promise.all(load.d.map(function (dep) {
          return instantiateAll(loader, dep, loaded);
        }));
      });
    }
  }

  function topLevelLoad(loader, load) {
    return load.C = instantiateAll(loader, load, {}).then(function () {
      return postOrderExec(loader, load, {});
    }).then(function () {
      return load.n;
    });
  } // the closest we can get to call(undefined)


  var nullContext = Object.freeze(Object.create(null)); // returns a promise if and only if a top-level await subgraph
  // throws on sync errors

  function postOrderExec(loader, load, seen) {
    if (seen[load.id]) return;
    seen[load.id] = true;

    if (!load.e) {
      if (load.er) throw load.er;
      if (load.E) return load.E;
      return;
    } // deps execute first, unless circular


    var depLoadPromises;
    load.d.forEach(function (depLoad) {
      {
        var depLoadPromise = postOrderExec(loader, depLoad, seen);
        if (depLoadPromise) (depLoadPromises = depLoadPromises || []).push(depLoadPromise);
      }
    });
    if (depLoadPromises) return Promise.all(depLoadPromises).then(doExec);
    return doExec();

    function doExec() {
      try {
        var execPromise = load.e.call(nullContext);

        if (execPromise) {
          execPromise = execPromise.then(function () {
            load.C = load.n;
            load.E = null;
          });
          return load.E = load.E || execPromise;
        } // (should be a promise, but a minify optimization to leave out Promise.resolve)


        load.C = load.n;
      } catch (err) {
        load.er = err;
        throw err;
      } finally {
        load.L = load.I = undefined;
        load.e = null;
      }
    }
  }

  envGlobal.System = new SystemJS();
  /*
   * Supports loading System.register via script tag injection
   */

  var systemRegister = systemJSPrototype.register;

  systemJSPrototype.register = function (deps, declare) {
    systemRegister.call(this, deps, declare);
  };

  systemJSPrototype.createScript = function (url) {
    var script = document.createElement('script');
    script.charset = 'utf-8';
    script.async = true;
    script.src = url;
    return script;
  };

  var lastWindowErrorUrl, lastWindowError;
  if (hasDocument) window.addEventListener('error', function (evt) {
    lastWindowErrorUrl = evt.filename;
    lastWindowError = evt.error;
  });

  systemJSPrototype.instantiate = function (url, firstParentUrl) {
    var loader = this;
    return new Promise(function (resolve, reject) {
      var script = systemJSPrototype.createScript(url);
      script.addEventListener('error', function () {
        reject(Error('Error loading ' + url + (firstParentUrl ? ' from ' + firstParentUrl : '')));
      });
      script.addEventListener('load', function () {
        document.head.removeChild(script); // Note that if an error occurs that isn't caught by this if statement,
        // that getRegister will return null and a "did not instantiate" error will be thrown.

        if (lastWindowErrorUrl === url) {
          reject(lastWindowError);
        } else {
          resolve(loader.getRegister());
        }
      });
      document.head.appendChild(script);
    });
  };

  if (hasDocument) {
    window.addEventListener('DOMContentLoaded', loadScriptModules);
    loadScriptModules();
  }

  function loadScriptModules() {
    Array.prototype.forEach.call(document.querySelectorAll('script[type=systemjs-module]'), function (script) {
      if (script.src) {
        System.import(script.src.slice(0, 7) === 'import:' ? script.src.slice(7) : resolveUrl(script.src, baseUrl));
      }
    });
  }
  /*
   * Supports loading System.register in workers
   */


  if (hasSelf && typeof importScripts === 'function') systemJSPrototype.instantiate = function (url) {
    var loader = this;
    return new Promise(function (resolve, reject) {
      try {
        importScripts(url);
      } catch (e) {
        reject(e);
      }

      resolve(loader.getRegister());
    });
  };

  systemJSPrototype.resolve = function (id, parentUrl) {
    var resolved = resolveIfNotPlainOrUrl(id, parentUrl || baseUrl);

    if (!resolved) {
      if (id.indexOf(':') !== -1) return Promise.resolve(id);
      throw Error('Cannot resolve "' + id + (parentUrl ? '" from ' + parentUrl : '"'));
    }

    return Promise.resolve(resolved);
  };
})();
/*
 * SystemJS global script loading support
 * Extra for the s.js build only
 * (Included by default in system.js build)
 */


(function (global) {
  var systemJSPrototype = global.System.constructor.prototype;
  var isIE = navigator.userAgent.indexOf('Trident') !== -1; // safari unpredictably lists some new globals first or second in object order

  var firstGlobalProp, secondGlobalProp, lastGlobalProp;

  function getGlobalProp() {
    var cnt = 0;
    var lastProp;

    for (var p in global) {
      // do not check frames cause it could be removed during import
      if (!global.hasOwnProperty(p) || !isNaN(p) && p < global.length || isIE && global[p] && global[p].parent === window) continue;
      if (cnt === 0 && p !== firstGlobalProp || cnt === 1 && p !== secondGlobalProp) return p;
      cnt++;
      lastProp = p;
    }

    if (lastProp !== lastGlobalProp) return lastProp;
  }

  function noteGlobalProps() {
    // alternatively Object.keys(global).pop()
    // but this may be faster (pending benchmarks)
    firstGlobalProp = secondGlobalProp = undefined;

    for (var p in global) {
      // do not check frames cause it could be removed during import
      if (!global.hasOwnProperty(p) || !isNaN(p) && p < global.length || isIE && global[p] && global[p].parent === window) continue;
      if (!firstGlobalProp) firstGlobalProp = p;else if (!secondGlobalProp) secondGlobalProp = p;
      lastGlobalProp = p;
    }

    return lastGlobalProp;
  }

  var impt = systemJSPrototype.import;

  systemJSPrototype.import = function (id, parentUrl) {
    noteGlobalProps();
    return impt.call(this, id, parentUrl);
  };

  var emptyInstantiation = [[], function () {
    return {};
  }];
  var getRegister = systemJSPrototype.getRegister;

  systemJSPrototype.getRegister = function () {
    var lastRegister = getRegister.call(this);
    if (lastRegister) return lastRegister; // no registration -> attempt a global detection as difference from snapshot
    // when multiple globals, we take the global value to be the last defined new global object property
    // for performance, this will not support multi-version / global collisions as previous SystemJS versions did
    // note in Edge, deleting and re-adding a global does not change its ordering

    var globalProp = getGlobalProp();
    if (!globalProp) return emptyInstantiation;
    var globalExport;

    try {
      globalExport = global[globalProp];
    } catch (e) {
      return emptyInstantiation;
    }

    return [[], function (_export) {
      return {
        execute: function execute() {
          _export({
            default: globalExport,
            __useDefault: true
          });
        }
      };
    }];
  };
})(typeof self !== 'undefined' ? self : global);
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */


function loadDPAssets(paths, callBack) {
  var promises = [];
  paths.forEach(function (path) {
    var fullPath = path;

    if (fullPath.indexOf('https://') === -1) {
      fullPath = Joomla.getOptions('system.paths').root + '/media' + path; // Load the script minified when systemjs is minified or doesn't exist

      var src = document.querySelector('script[src*="systemjs"]');
      src = src ? src.getAttribute('src') : null;

      if (!src || src.indexOf('.min.js') > -1) {
        fullPath = fullPath.replace('.js', '.min.js').replace('.css', '.min.css');
      } // Add media param


      if (src && src.indexOf('?') > 0) {
        fullPath += src.substr(src.indexOf('?'));
      }
    }

    if (path.indexOf('.css') > 0) {
      promises.push(new Promise(function (resolve) {
        var link = document.createElement('link');
        link.type = 'text/css';
        link.rel = 'stylesheet';
        link.href = fullPath;
        document.head.appendChild(link);
        resolve();
      }));
      return;
    }

    promises.push(System.import(fullPath));
  });
  Promise.all(promises).then(callBack ? callBack : function () {});
}