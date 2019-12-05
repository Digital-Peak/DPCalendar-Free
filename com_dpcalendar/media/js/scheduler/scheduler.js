/*!
FullCalendar Resources Common Plugin v4.1.0
Docs & License: https://fullcalendar.io/scheduler
(c) 2019 Adam Shaw
*/
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('@fullcalendar/core')) :
    typeof define === 'function' && define.amd ? define(['exports', '@fullcalendar/core'], factory) :
    (global = global || self, factory(global.FullCalendarResourceCommon = {}, global.FullCalendar));
}(this, function (exports, core) { 'use strict';

    /*! *****************************************************************************
    Copyright (c) Microsoft Corporation. All rights reserved.
    Licensed under the Apache License, Version 2.0 (the "License"); you may not use
    this file except in compliance with the License. You may obtain a copy of the
    License at http://www.apache.org/licenses/LICENSE-2.0

    THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
    KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
    WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
    MERCHANTABLITY OR NON-INFRINGEMENT.

    See the Apache Version 2.0 License for specific language governing permissions
    and limitations under the License.
    ***************************************************************************** */
    /* global Reflect, Promise */

    var extendStatics = function(d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };

    function __extends(d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    }

    var __assign = function() {
        __assign = Object.assign || function __assign(t) {
            for (var s, i = 1, n = arguments.length; i < n; i++) {
                s = arguments[i];
                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
            }
            return t;
        };
        return __assign.apply(this, arguments);
    };

    var ResourceDataAdder = /** @class */ (function () {
        function ResourceDataAdder() {
            this.filterResources = core.memoize(filterResources);
        }
        ResourceDataAdder.prototype.transform = function (viewProps, viewSpec, calendarProps, view) {
            if (viewSpec.class.needsResourceData) {
                return {
                    resourceStore: this.filterResources(calendarProps.resourceStore, view.opt('filterResourcesWithEvents'), calendarProps.eventStore, calendarProps.dateProfile.activeRange),
                    resourceEntityExpansions: calendarProps.resourceEntityExpansions
                };
            }
        };
        return ResourceDataAdder;
    }());
    function filterResources(resourceStore, doFilterResourcesWithEvents, eventStore, activeRange) {
        if (doFilterResourcesWithEvents) {
            var instancesInRange = filterEventInstancesInRange(eventStore.instances, activeRange);
            var hasEvents_1 = computeHasEvents(instancesInRange, eventStore.defs);
            __assign(hasEvents_1, computeAncestorHasEvents(hasEvents_1, resourceStore));
            return core.filterHash(resourceStore, function (resource, resourceId) {
                return hasEvents_1[resourceId];
            });
        }
        else {
            return resourceStore;
        }
    }
    function filterEventInstancesInRange(eventInstances, activeRange) {
        return core.filterHash(eventInstances, function (eventInstance) {
            return core.rangesIntersect(eventInstance.range, activeRange);
        });
    }
    function computeHasEvents(eventInstances, eventDefs) {
        var hasEvents = {};
        for (var instanceId in eventInstances) {
            var instance = eventInstances[instanceId];
            for (var _i = 0, _a = eventDefs[instance.defId].resourceIds; _i < _a.length; _i++) {
                var resourceId = _a[_i];
                hasEvents[resourceId] = true;
            }
        }
        return hasEvents;
    }
    /*
    mark resources as having events if any of their ancestors have them
    NOTE: resourceStore might not have all the resources that hasEvents{} has keyed
    */
    function computeAncestorHasEvents(hasEvents, resourceStore) {
        var res = {};
        for (var resourceId in hasEvents) {
            var resource = void 0;
            while ((resource = resourceStore[resourceId])) {
                resourceId = resource.parentId; // now functioning as the parentId
                if (resourceId) {
                    res[resourceId] = true;
                }
                else {
                    break;
                }
            }
        }
        return res;
    }
    // for when non-resource view should be given EventUi info (for event coloring/constraints based off of resource data)
    var ResourceEventConfigAdder = /** @class */ (function () {
        function ResourceEventConfigAdder() {
            this.buildResourceEventUis = core.memoizeOutput(buildResourceEventUis, core.isObjectsSimilar);
            this.injectResourceEventUis = core.memoize(injectResourceEventUis);
        }
        ResourceEventConfigAdder.prototype.transform = function (viewProps, viewSpec, calendarProps) {
            if (!viewSpec.class.needsResourceData) { // is a non-resource view?
                return {
                    eventUiBases: this.injectResourceEventUis(viewProps.eventUiBases, viewProps.eventStore.defs, this.buildResourceEventUis(calendarProps.resourceStore))
                };
            }
        };
        return ResourceEventConfigAdder;
    }());
    function buildResourceEventUis(resourceStore) {
        return core.mapHash(resourceStore, function (resource) {
            return resource.ui;
        });
    }
    function injectResourceEventUis(eventUiBases, eventDefs, resourceEventUis) {
        return core.mapHash(eventUiBases, function (eventUi, defId) {
            if (defId) { // not the '' key
                return injectResourceEventUi(eventUi, eventDefs[defId], resourceEventUis);
            }
            else {
                return eventUi;
            }
        });
    }
    function injectResourceEventUi(origEventUi, eventDef, resourceEventUis) {
        var parts = [];
        // first resource takes precedence, which fights with the ordering of combineEventUis, thus the unshifts
        for (var _i = 0, _a = eventDef.resourceIds; _i < _a.length; _i++) {
            var resourceId = _a[_i];
            if (resourceEventUis[resourceId]) {
                parts.unshift(resourceEventUis[resourceId]);
            }
        }
        parts.unshift(origEventUi);
        return core.combineEventUis(parts);
    }

    var RESOURCE_SOURCE_PROPS = {
        id: String
    };
    var defs = [];
    var uid = 0;
    function registerResourceSourceDef(def) {
        defs.push(def);
    }
    function getResourceSourceDef(id) {
        return defs[id];
    }
    function doesSourceIgnoreRange(source) {
        return Boolean(defs[source.sourceDefId].ignoreRange);
    }
    function parseResourceSource(input) {
        for (var i = defs.length - 1; i >= 0; i--) { // later-added plugins take precedence
            var def = defs[i];
            var meta = def.parseMeta(input);
            if (meta) {
                var res = parseResourceSourceProps((typeof input === 'object' && input) ? input : {}, meta, i);
                res._raw = core.freezeRaw(input);
                return res;
            }
        }
        return null;
    }
    function parseResourceSourceProps(input, meta, sourceDefId) {
        var props = core.refineProps(input, RESOURCE_SOURCE_PROPS);
        props.sourceId = String(uid++);
        props.sourceDefId = sourceDefId;
        props.meta = meta;
        props.publicId = props.id;
        props.isFetching = false;
        props.latestFetchId = '';
        props.fetchRange = null;
        delete props.id;
        return props;
    }

    function reduceResourceSource (source, action, dateProfile, calendar) {
        switch (action.type) {
            case 'INIT':
                return createSource(calendar.opt('resources'), calendar);
            case 'RESET_RESOURCE_SOURCE':
                return createSource(action.resourceSourceInput, calendar, true);
            case 'PREV': // TODO: how do we track all actions that affect dateProfile :(
            case 'NEXT':
            case 'SET_DATE':
            case 'SET_VIEW_TYPE':
                return handleRange(source, dateProfile.activeRange, calendar);
            case 'RECEIVE_RESOURCES':
            case 'RECEIVE_RESOURCE_ERROR':
                return receiveResponse(source, action.fetchId, action.fetchRange);
            case 'REFETCH_RESOURCES':
                return fetchSource(source, dateProfile.activeRange, calendar);
            default:
                return source;
        }
    }
    var uid$1 = 0;
    function createSource(input, calendar, forceFetch) {
        if (input) {
            var source = parseResourceSource(input);
            if (forceFetch || !calendar.opt('refetchResourcesOnNavigate')) { // because assumes handleRange will do it later
                source = fetchSource(source, null, calendar);
            }
            return source;
        }
        return null;
    }
    function handleRange(source, activeRange, calendar) {
        if (calendar.opt('refetchResourcesOnNavigate') &&
            !doesSourceIgnoreRange(source) &&
            (!source.fetchRange || !core.rangesEqual(source.fetchRange, activeRange))) {
            return fetchSource(source, activeRange, calendar);
        }
        else {
            return source;
        }
    }
    function fetchSource(source, fetchRange, calendar) {
        var sourceDef = getResourceSourceDef(source.sourceDefId);
        var fetchId = String(uid$1++);
        sourceDef.fetch({
            resourceSource: source,
            calendar: calendar,
            range: fetchRange
        }, function (res) {
            // HACK
            // do before calling dispatch in case dispatch renders synchronously
            calendar.afterSizingTriggers._resourcesRendered = [null]; // fire once
            calendar.dispatch({
                type: 'RECEIVE_RESOURCES',
                fetchId: fetchId,
                fetchRange: fetchRange,
                rawResources: res.rawResources
            });
        }, function (error) {
            calendar.dispatch({
                type: 'RECEIVE_RESOURCE_ERROR',
                fetchId: fetchId,
                fetchRange: fetchRange,
                error: error
            });
        });
        return __assign({}, source, { isFetching: true, latestFetchId: fetchId });
    }
    function receiveResponse(source, fetchId, fetchRange) {
        if (fetchId === source.latestFetchId) {
            return __assign({}, source, { isFetching: false, fetchRange: fetchRange });
        }
        return source;
    }

    var RESOURCE_PROPS = {
        id: String,
        title: String,
        parentId: String,
        businessHours: null,
        children: null,
        extendedProps: null
    };
    var PRIVATE_ID_PREFIX = '_fc:';
    var uid$2 = 0;
    /*
    needs a full store so that it can populate children too
    */
    function parseResource(input, parentId, store, calendar) {
        if (parentId === void 0) { parentId = ''; }
        var leftovers0 = {};
        var props = core.refineProps(input, RESOURCE_PROPS, {}, leftovers0);
        var leftovers1 = {};
        var ui = core.processScopedUiProps('event', leftovers0, calendar, leftovers1);
        if (!props.id) {
            props.id = PRIVATE_ID_PREFIX + (uid$2++);
        }
        if (!props.parentId) { // give precedence to the parentId property
            props.parentId = parentId;
        }
        props.businessHours = props.businessHours ? core.parseBusinessHours(props.businessHours, calendar) : null;
        props.ui = ui;
        props.extendedProps = __assign({}, leftovers1, props.extendedProps);
        // help out ResourceApi from having user modify props
        Object.freeze(ui.classNames);
        Object.freeze(props.extendedProps);
        if (store[props.id]) ;
        else {
            store[props.id] = props;
            if (props.children) {
                for (var _i = 0, _a = props.children; _i < _a.length; _i++) {
                    var childInput = _a[_i];
                    parseResource(childInput, props.id, store, calendar);
                }
                delete props.children;
            }
        }
        return props;
    }
    /*
    TODO: use this in more places
    */
    function getPublicId(id) {
        if (id.indexOf(PRIVATE_ID_PREFIX) === 0) {
            return '';
        }
        return id;
    }

    function reduceResourceStore (store, action, source, calendar) {
        switch (action.type) {
            case 'INIT':
                return {};
            case 'RECEIVE_RESOURCES':
                return receiveRawResources(store, action.rawResources, action.fetchId, source, calendar);
            case 'ADD_RESOURCE':
                return addResource(store, action.resourceHash);
            case 'REMOVE_RESOURCE':
                return removeResource(store, action.resourceId);
            case 'SET_RESOURCE_PROP':
                return setResourceProp(store, action.resourceId, action.propName, action.propValue);
            case 'RESET_RESOURCES':
                // must make the calendar think each resource is a new object :/
                return core.mapHash(store, function (resource) {
                    return __assign({}, resource);
                });
            default:
                return store;
        }
    }
    function receiveRawResources(existingStore, inputs, fetchId, source, calendar) {
        if (source.latestFetchId === fetchId) {
            var nextStore = {};
            for (var _i = 0, inputs_1 = inputs; _i < inputs_1.length; _i++) {
                var input = inputs_1[_i];
                parseResource(input, '', nextStore, calendar);
            }
            return nextStore;
        }
        else {
            return existingStore;
        }
    }
    function addResource(existingStore, additions) {
        // TODO: warn about duplicate IDs
        return __assign({}, existingStore, additions);
    }
    function removeResource(existingStore, resourceId) {
        var newStore = __assign({}, existingStore);
        delete newStore[resourceId];
        // promote children
        for (var childResourceId in newStore) { // a child, *maybe* but probably not
            if (newStore[childResourceId].parentId === resourceId) {
                newStore[childResourceId] = __assign({}, newStore[childResourceId], { parentId: '' });
            }
        }
        return newStore;
    }
    function setResourceProp(existingStore, resourceId, name, value) {
        var _a, _b;
        var existingResource = existingStore[resourceId];
        // TODO: sanitization
        if (existingResource) {
            return __assign({}, existingStore, (_a = {}, _a[resourceId] = __assign({}, existingResource, (_b = {}, _b[name] = value, _b)), _a));
        }
        else {
            return existingStore;
        }
    }

    function reduceResourceEntityExpansions(expansions, action) {
        var _a;
        switch (action.type) {
            case 'INIT':
                return {};
            case 'SET_RESOURCE_ENTITY_EXPANDED':
                return __assign({}, expansions, (_a = {}, _a[action.id] = action.isExpanded, _a));
            default:
                return expansions;
        }
    }

    function resourcesReducers (state, action, calendar) {
        var resourceSource = reduceResourceSource(state.resourceSource, action, state.dateProfile, calendar);
        var resourceStore = reduceResourceStore(state.resourceStore, action, resourceSource, calendar);
        var resourceEntityExpansions = reduceResourceEntityExpansions(state.resourceEntityExpansions, action);
        return __assign({}, state, { resourceSource: resourceSource,
            resourceStore: resourceStore,
            resourceEntityExpansions: resourceEntityExpansions });
    }

    var RESOURCE_RELATED_PROPS = {
        resourceId: String,
        resourceIds: function (items) {
            return (items || []).map(function (item) {
                return String(item);
            });
        },
        resourceEditable: Boolean
    };
    function parseEventDef(def, props, leftovers) {
        var resourceRelatedProps = core.refineProps(props, RESOURCE_RELATED_PROPS, {}, leftovers);
        var resourceIds = resourceRelatedProps.resourceIds;
        if (resourceRelatedProps.resourceId) {
            resourceIds.push(resourceRelatedProps.resourceId);
        }
        def.resourceIds = resourceIds;
        def.resourceEditable = resourceRelatedProps.resourceEditable;
    }

    function massageEventDragMutation(eventMutation, hit0, hit1) {
        var resource0 = hit0.dateSpan.resourceId;
        var resource1 = hit1.dateSpan.resourceId;
        if (resource0 && resource1 &&
            resource0 !== resource1) {
            eventMutation.resourceMutation = {
                matchResourceId: resource0,
                setResourceId: resource1
            };
        }
    }
    /*
    TODO: all this would be much easier if we were using a hash!
    */
    function applyEventDefMutation(eventDef, mutation, calendar) {
        var resourceMutation = mutation.resourceMutation;
        if (resourceMutation && computeResourceEditable(eventDef, calendar)) {
            var index = eventDef.resourceIds.indexOf(resourceMutation.matchResourceId);
            if (index !== -1) {
                var resourceIds = eventDef.resourceIds.slice(); // copy
                resourceIds.splice(index, 1); // remove
                if (resourceIds.indexOf(resourceMutation.setResourceId) === -1) { // not already in there
                    resourceIds.push(resourceMutation.setResourceId); // add
                }
                eventDef.resourceIds = resourceIds;
            }
        }
    }
    /*
    HACK
    TODO: use EventUi system instead of this
    */
    function computeResourceEditable(eventDef, calendar) {
        var resourceEditable = eventDef.resourceEditable;
        if (resourceEditable == null) {
            var source = eventDef.sourceId && calendar.state.eventSources[eventDef.sourceId];
            if (source) {
                resourceEditable = source.extendedProps.resourceEditable; // used the Source::extendedProps hack
            }
            if (resourceEditable == null) {
                resourceEditable = calendar.opt('eventResourceEditable');
                if (resourceEditable == null) {
                    resourceEditable = calendar.opt('editable'); // TODO: use defaults system instead
                }
            }
        }
        return resourceEditable;
    }
    function transformEventDrop(mutation, calendar) {
        var resourceMutation = mutation.resourceMutation;
        if (resourceMutation) {
            return {
                oldResource: calendar.getResourceById(resourceMutation.matchResourceId),
                newResource: calendar.getResourceById(resourceMutation.setResourceId)
            };
        }
        else {
            return {
                oldResource: null,
                newResource: null
            };
        }
    }

    function transformDateSelectionJoin(hit0, hit1) {
        var resourceId0 = hit0.dateSpan.resourceId;
        var resourceId1 = hit1.dateSpan.resourceId;
        if (resourceId0 && resourceId1) {
            if (hit0.component.allowAcrossResources === false &&
                resourceId0 !== resourceId1) {
                return false;
            }
            else {
                return { resourceId: resourceId0 };
            }
        }
    }

    var ResourceApi = /** @class */ (function () {
        function ResourceApi(calendar, rawResource) {
            this._calendar = calendar;
            this._resource = rawResource;
        }
        ResourceApi.prototype.setProp = function (name, value) {
            this._calendar.dispatch({
                type: 'SET_RESOURCE_PROP',
                resourceId: this._resource.id,
                propName: name,
                propValue: value
            });
        };
        ResourceApi.prototype.remove = function () {
            this._calendar.dispatch({
                type: 'REMOVE_RESOURCE',
                resourceId: this._resource.id
            });
        };
        ResourceApi.prototype.getParent = function () {
            var calendar = this._calendar;
            var parentId = this._resource.parentId;
            if (parentId) {
                return new ResourceApi(calendar, calendar.state.resourceSource[parentId]);
            }
            else {
                return null;
            }
        };
        ResourceApi.prototype.getChildren = function () {
            var thisResourceId = this._resource.id;
            var calendar = this._calendar;
            var resourceStore = calendar.state.resourceStore;
            var childApis = [];
            for (var resourceId in resourceStore) {
                if (resourceStore[resourceId].parentId === thisResourceId) {
                    childApis.push(new ResourceApi(calendar, resourceStore[resourceId]));
                }
            }
            return childApis;
        };
        /*
        this is really inefficient!
        TODO: make EventApi::resourceIds a hash or keep an index in the Calendar's state
        */
        ResourceApi.prototype.getEvents = function () {
            var thisResourceId = this._resource.id;
            var calendar = this._calendar;
            var _a = calendar.state.eventStore, defs = _a.defs, instances = _a.instances;
            var eventApis = [];
            for (var instanceId in instances) {
                var instance = instances[instanceId];
                var def = defs[instance.defId];
                if (def.resourceIds.indexOf(thisResourceId) !== -1) { // inefficient!!!
                    eventApis.push(new core.EventApi(calendar, def, instance));
                }
            }
            return eventApis;
        };
        Object.defineProperty(ResourceApi.prototype, "id", {
            get: function () { return this._resource.id; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "title", {
            get: function () { return this._resource.title; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventConstraint", {
            get: function () { return this._resource.ui.constraints[0] || null; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventOverlap", {
            get: function () { return this._resource.ui.overlap; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventAllow", {
            get: function () { return this._resource.ui.allows[0] || null; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventBackgroundColor", {
            get: function () { return this._resource.ui.backgroundColor; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventBorderColor", {
            get: function () { return this._resource.ui.borderColor; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventTextColor", {
            get: function () { return this._resource.ui.textColor; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventClassNames", {
            // NOTE: user can't modify these because Object.freeze was called in event-def parsing
            get: function () { return this._resource.ui.classNames; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "extendedProps", {
            get: function () { return this._resource.extendedProps; },
            enumerable: true,
            configurable: true
        });
        return ResourceApi;
    }());

    core.Calendar.prototype.addResource = function (input, scrollTo) {
        var _a;
        if (scrollTo === void 0) { scrollTo = true; }
        var resourceHash;
        var resource;
        if (input instanceof ResourceApi) {
            resource = input._resource;
            resourceHash = (_a = {}, _a[resource.id] = resource, _a);
        }
        else {
            resourceHash = {};
            resource = parseResource(input, '', resourceHash, this);
        }
        // HACK
        if (scrollTo) {
            this.component.view.addScroll({ forcedRowId: resource.id });
        }
        this.dispatch({
            type: 'ADD_RESOURCE',
            resourceHash: resourceHash
        });
        return new ResourceApi(this, resource);
    };
    core.Calendar.prototype.getResourceById = function (id) {
        id = String(id);
        if (this.state.resourceStore) { // guard against calendar with no resource functionality
            var rawResource = this.state.resourceStore[id];
            if (rawResource) {
                return new ResourceApi(this, rawResource);
            }
        }
        return null;
    };
    core.Calendar.prototype.getResources = function () {
        var resourceStore = this.state.resourceStore;
        var resourceApis = [];
        if (resourceStore) { // guard against calendar with no resource functionality
            for (var resourceId in resourceStore) {
                resourceApis.push(new ResourceApi(this, resourceStore[resourceId]));
            }
        }
        return resourceApis;
    };
    core.Calendar.prototype.getTopLevelResources = function () {
        var resourceStore = this.state.resourceStore;
        var resourceApis = [];
        if (resourceStore) { // guard against calendar with no resource functionality
            for (var resourceId in resourceStore) {
                if (!resourceStore[resourceId].parentId) {
                    resourceApis.push(new ResourceApi(this, resourceStore[resourceId]));
                }
            }
        }
        return resourceApis;
    };
    core.Calendar.prototype.rerenderResources = function () {
        this.dispatch({
            type: 'RESET_RESOURCES'
        });
    };
    core.Calendar.prototype.refetchResources = function () {
        this.dispatch({
            type: 'REFETCH_RESOURCES'
        });
    };
    function transformDatePoint(dateSpan, calendar) {
        return dateSpan.resourceId ?
            { resource: calendar.getResourceById(dateSpan.resourceId) } :
            {};
    }
    function transformDateSpan(dateSpan, calendar) {
        return dateSpan.resourceId ?
            { resource: calendar.getResourceById(dateSpan.resourceId) } :
            {};
    }

    /*
    splits things BASED OFF OF which resources they are associated with.
    creates a '' entry which is when something has NO resource.
    */
    var ResourceSplitter = /** @class */ (function (_super) {
        __extends(ResourceSplitter, _super);
        function ResourceSplitter() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        ResourceSplitter.prototype.getKeyInfo = function (props) {
            return __assign({ '': {} }, props.resourceStore // already has `ui` and `businessHours` keys!
            );
        };
        ResourceSplitter.prototype.getKeysForDateSpan = function (dateSpan) {
            return [dateSpan.resourceId || ''];
        };
        ResourceSplitter.prototype.getKeysForEventDef = function (eventDef) {
            var resourceIds = eventDef.resourceIds;
            if (!resourceIds.length) {
                return [''];
            }
            return resourceIds;
        };
        return ResourceSplitter;
    }(core.Splitter));

    function isPropsValidWithResources(props, calendar) {
        var splitter = new ResourceSplitter();
        var sets = splitter.splitProps(__assign({}, props, { resourceStore: calendar.state.resourceStore }));
        for (var resourceId in sets) {
            var props_1 = sets[resourceId];
            // merge in event data from the non-resource segment
            if (resourceId && sets['']) { // current segment is not the non-resource one, and there IS a non-resource one
                props_1 = __assign({}, props_1, { eventStore: core.mergeEventStores(sets[''].eventStore, props_1.eventStore), eventUiBases: __assign({}, sets[''].eventUiBases, props_1.eventUiBases) });
            }
            if (!core.isPropsValid(props_1, calendar, { resourceId: resourceId }, filterConfig.bind(null, resourceId))) {
                return false;
            }
        }
        return true;
    }
    function filterConfig(resourceId, config) {
        return __assign({}, config, { constraints: filterConstraints(resourceId, config.constraints) });
    }
    function filterConstraints(resourceId, constraints) {
        return constraints.map(function (constraint) {
            var defs = constraint.defs;
            if (defs) { // we are dealing with an EventStore
                // if any of the events define constraints to resources that are NOT this resource,
                // then this resource is unconditionally prohibited, which is what a `false` value does.
                for (var defId in defs) {
                    var resourceIds = defs[defId].resourceIds;
                    if (resourceIds.length && resourceIds.indexOf(resourceId) === -1) { // TODO: use a hash?!!! (for other reasons too)
                        return false;
                    }
                }
            }
            return constraint;
        });
    }

    function transformExternalDef(dateSpan) {
        return dateSpan.resourceId ?
            { resourceId: dateSpan.resourceId } :
            {};
    }

    function transformEventResizeJoin(hit0, hit1) {
        var component = hit0.component;
        if (component.allowAcrossResources === false &&
            hit0.dateSpan.resourceId !== hit1.dateSpan.resourceId) {
            return false;
        }
    }

    core.EventApi.prototype.getResources = function () {
        var calendar = this._calendar;
        return this._def.resourceIds.map(function (resourceId) {
            return calendar.getResourceById(resourceId);
        });
    };
    core.EventApi.prototype.setResources = function (resources) {
        var resourceIds = [];
        // massage resources -> resourceIds
        for (var _i = 0, resources_1 = resources; _i < resources_1.length; _i++) {
            var resource = resources_1[_i];
            var resourceId = null;
            if (typeof resource === 'string') {
                resourceId = resource;
            }
            else if (typeof resource === 'number') {
                resourceId = String(resource);
            }
            else if (resource instanceof ResourceApi) {
                resourceId = resource.id; // guaranteed to always have an ID. hmmm
            }
            else {
                console.warn('unknown resource type: ' + resource);
            }
            if (resourceId) {
                resourceIds.push(resourceId);
            }
        }
        this.mutate({
            standardProps: {
                resourceIds: resourceIds
            }
        });
    };

    var RELEASE_DATE = '2019-04-24'; // for Scheduler
    var UPGRADE_WINDOW = 365 + 7; // days. 1 week leeway, for tz shift reasons too
    var LICENSE_INFO_URL = 'http://fullcalendar.io/scheduler/license/';
    var PRESET_LICENSE_KEYS = [
        'GPL-My-Project-Is-Open-Source',
        'CC-Attribution-NonCommercial-NoDerivatives'
    ];
    var CSS = {
        position: 'absolute',
        'z-index': 99999,
        bottom: '1px',
        left: '1px',
        background: '#eee',
        'border-color': '#ddd',
        'border-style': 'solid',
        'border-width': '1px 1px 0 0',
        padding: '2px 4px',
        'font-size': '12px',
        'border-top-right-radius': '3px'
    };
    function injectLicenseWarning(containerEl, calendar) {
        var key = calendar.opt('schedulerLicenseKey');
        if (!isImmuneUrl(window.location.href) && !isValidKey(key)) {
            core.appendToElement(containerEl, '<div class="fc-license-message" style="' + core.htmlEscape(core.cssToStr(CSS)) + '">' +
                'Please use a valid license key. <a href="' + LICENSE_INFO_URL + '">More Info</a>' +
                '</div>');
        }
    }
    /*
    This decryption is not meant to be bulletproof. Just a way to remind about an upgrade.
    */
    function isValidKey(key) {
        if (PRESET_LICENSE_KEYS.indexOf(key) !== -1) {
            return true;
        }
        var parts = (key || '').match(/^(\d+)\-fcs\-(\d+)$/);
        if (parts && (parts[1].length === 10)) {
            var purchaseDate = new Date(parseInt(parts[2], 10) * 1000);
            var releaseDate = new Date(core.config.mockSchedulerReleaseDate || RELEASE_DATE);
            if (core.isValidDate(releaseDate)) { // token won't be replaced in dev mode
                var minPurchaseDate = core.addDays(releaseDate, -UPGRADE_WINDOW);
                if (minPurchaseDate < purchaseDate) {
                    return true;
                }
            }
        }
        return false;
    }
    function isImmuneUrl(url) {
        return /\w+\:\/\/fullcalendar\.io\/|\/demos\/[\w-]+\.html$/.test(url);
    }

    var optionChangeHandlers = {
        resources: handleResources
    };
    function handleResources(newSourceInput, calendar) {
        var oldSourceInput = calendar.state.resourceSource._raw;
        if (!core.isValuesSimilar(oldSourceInput, newSourceInput, 2)) {
            calendar.dispatch({
                type: 'RESET_RESOURCE_SOURCE',
                resourceSourceInput: newSourceInput
            });
        }
    }

    registerResourceSourceDef({
        ignoreRange: true,
        parseMeta: function (raw) {
            if (Array.isArray(raw)) {
                return raw;
            }
            else if (Array.isArray(raw.resources)) {
                return raw.resources;
            }
            return null;
        },
        fetch: function (arg, successCallback) {
            successCallback({
                rawResources: arg.resourceSource.meta
            });
        }
    });

    registerResourceSourceDef({
        parseMeta: function (raw) {
            if (typeof raw === 'function') {
                return raw;
            }
            else if (typeof raw.resources === 'function') {
                return raw.resources;
            }
            return null;
        },
        fetch: function (arg, success, failure) {
            var dateEnv = arg.calendar.dateEnv;
            var func = arg.resourceSource.meta;
            var publicArg = {};
            if (arg.range) {
                publicArg = {
                    start: dateEnv.toDate(arg.range.start),
                    end: dateEnv.toDate(arg.range.end),
                    startStr: dateEnv.formatIso(arg.range.start),
                    endStr: dateEnv.formatIso(arg.range.end),
                    timeZone: dateEnv.timeZone
                };
            }
            // TODO: make more dry with EventSourceFunc
            // TODO: accept a response?
            core.unpromisify(func.bind(null, publicArg), function (rawResources) {
                success({ rawResources: rawResources }); // needs an object response
            }, failure // send errorObj directly to failure callback
            );
        }
    });

    registerResourceSourceDef({
        parseMeta: function (raw) {
            if (typeof raw === 'string') {
                raw = { url: raw };
            }
            else if (!raw || typeof raw !== 'object' || !raw.url) {
                return null;
            }
            return {
                url: raw.url,
                method: (raw.method || 'GET').toUpperCase(),
                extraParams: raw.extraParams
            };
        },
        fetch: function (arg, successCallback, failureCallback) {
            var meta = arg.resourceSource.meta;
            var requestParams = buildRequestParams(meta, arg.range, arg.calendar);
            core.requestJson(meta.method, meta.url, requestParams, function (rawResources, xhr) {
                successCallback({ rawResources: rawResources, xhr: xhr });
            }, function (message, xhr) {
                failureCallback({ message: message, xhr: xhr });
            });
        }
    });
    // TODO: somehow consolidate with event json feed
    function buildRequestParams(meta, range, calendar) {
        var dateEnv = calendar.dateEnv;
        var startParam;
        var endParam;
        var timeZoneParam;
        var customRequestParams;
        var params = {};
        if (range) {
            // startParam = meta.startParam
            // if (startParam == null) {
            startParam = calendar.opt('startParam');
            // }
            // endParam = meta.endParam
            // if (endParam == null) {
            endParam = calendar.opt('endParam');
            // }
            // timeZoneParam = meta.timeZoneParam
            // if (timeZoneParam == null) {
            timeZoneParam = calendar.opt('timeZoneParam');
            // }
            params[startParam] = dateEnv.formatIso(range.start);
            params[endParam] = dateEnv.formatIso(range.end);
            if (dateEnv.timeZone !== 'local') {
                params[timeZoneParam] = dateEnv.timeZone;
            }
        }
        // retrieve any outbound GET/POST data from the options
        if (typeof meta.extraParams === 'function') {
            // supplied as a function that returns a key/value object
            customRequestParams = meta.extraParams();
        }
        else {
            // probably supplied as a straight key/value object
            customRequestParams = meta.extraParams || {};
        }
        __assign(params, customRequestParams);
        return params;
    }

    function buildResourceTextFunc(resourceTextSetting, calendar) {
        if (typeof resourceTextSetting === 'function') {
            return function (resource) {
                return resourceTextSetting(new ResourceApi(calendar, resource));
            };
        }
        else {
            return function (resource) {
                return resource.title || getPublicId(resource.id);
            };
        }
    }

    var ResourceDayHeader = /** @class */ (function (_super) {
        __extends(ResourceDayHeader, _super);
        function ResourceDayHeader(context, parentEl) {
            var _this = _super.call(this, context) || this;
            _this.datesAboveResources = _this.opt('datesAboveResources');
            _this.resourceTextFunc = buildResourceTextFunc(_this.opt('resourceText'), _this.calendar);
            parentEl.innerHTML = ''; // because might be nbsp
            parentEl.appendChild(_this.el = core.htmlToElement('<div class="fc-row ' + _this.theme.getClass('headerRow') + '">' +
                '<table class="' + _this.theme.getClass('tableGrid') + '">' +
                '<thead></thead>' +
                '</table>' +
                '</div>'));
            _this.thead = _this.el.querySelector('thead');
            return _this;
        }
        ResourceDayHeader.prototype.destroy = function () {
            core.removeElement(this.el);
        };
        ResourceDayHeader.prototype.render = function (props) {
            var html;
            this.dateFormat = core.createFormatter(this.opt('columnHeaderFormat') ||
                core.computeFallbackHeaderFormat(props.datesRepDistinctDays, props.dates.length));
            if (props.dates.length === 1) {
                html = this.renderResourceRow(props.resources);
            }
            else {
                if (this.datesAboveResources) {
                    html = this.renderDayAndResourceRows(props.dates, props.resources);
                }
                else {
                    html = this.renderResourceAndDayRows(props.resources, props.dates);
                }
            }
            this.thead.innerHTML = html;
            this.processResourceEls(props.resources);
        };
        ResourceDayHeader.prototype.renderResourceRow = function (resources) {
            var _this = this;
            var cellHtmls = resources.map(function (resource) {
                return _this.renderResourceCell(resource, 1);
            });
            return this.buildTr(cellHtmls);
        };
        ResourceDayHeader.prototype.renderDayAndResourceRows = function (dates, resources) {
            var dateHtmls = [];
            var resourceHtmls = [];
            for (var _i = 0, dates_1 = dates; _i < dates_1.length; _i++) {
                var date = dates_1[_i];
                dateHtmls.push(this.renderDateCell(date, resources.length));
                for (var _a = 0, resources_1 = resources; _a < resources_1.length; _a++) {
                    var resource = resources_1[_a];
                    resourceHtmls.push(this.renderResourceCell(resource, 1, date));
                }
            }
            return this.buildTr(dateHtmls) +
                this.buildTr(resourceHtmls);
        };
        ResourceDayHeader.prototype.renderResourceAndDayRows = function (resources, dates) {
            var resourceHtmls = [];
            var dateHtmls = [];
            for (var _i = 0, resources_2 = resources; _i < resources_2.length; _i++) {
                var resource = resources_2[_i];
                resourceHtmls.push(this.renderResourceCell(resource, dates.length));
                for (var _a = 0, dates_2 = dates; _a < dates_2.length; _a++) {
                    var date = dates_2[_a];
                    dateHtmls.push(this.renderDateCell(date, 1, resource));
                }
            }
            return this.buildTr(resourceHtmls) +
                this.buildTr(dateHtmls);
        };
        // Cell Rendering Utils
        // ----------------------------------------------------------------------------------------------
        // a cell with the resource name. might be associated with a specific day
        ResourceDayHeader.prototype.renderResourceCell = function (resource, colspan, date) {
            var dateEnv = this.dateEnv;
            return '<th class="fc-resource-cell"' +
                ' data-resource-id="' + resource.id + '"' +
                (date ?
                    ' data-date="' + dateEnv.formatIso(date, { omitTime: true }) + '"' :
                    '') +
                (colspan > 1 ?
                    ' colspan="' + colspan + '"' :
                    '') +
                '>' +
                core.htmlEscape(this.resourceTextFunc(resource)) +
                '</th>';
        };
        // a cell with date text. might have a resource associated with it
        ResourceDayHeader.prototype.renderDateCell = function (date, colspan, resource) {
            var props = this.props;
            return core.renderDateCell(date, props.dateProfile, props.datesRepDistinctDays, props.dates.length * props.resources.length, this.dateFormat, this.context, colspan, resource ? 'data-resource-id="' + resource.id + '"' : '');
        };
        ResourceDayHeader.prototype.buildTr = function (cellHtmls) {
            if (!cellHtmls.length) {
                cellHtmls = ['<td>&nbsp;</td>'];
            }
            if (this.props.renderIntroHtml) {
                cellHtmls = [this.props.renderIntroHtml()].concat(cellHtmls);
            }
            if (this.isRtl) {
                cellHtmls.reverse();
            }
            return '<tr>' +
                cellHtmls.join('') +
                '</tr>';
        };
        // Post-rendering
        // ----------------------------------------------------------------------------------------------
        // given a container with already rendered resource cells
        ResourceDayHeader.prototype.processResourceEls = function (resources) {
            var _this = this;
            var view = this.view;
            core.findElements(this.thead, '.fc-resource-cell').forEach(function (node, col) {
                col = col % resources.length;
                if (_this.isRtl) {
                    col = resources.length - 1 - col;
                }
                var resource = resources[col];
                view.publiclyTrigger('resourceRender', [
                    {
                        resource: new ResourceApi(_this.calendar, resource),
                        el: node,
                        view: view
                    }
                ]);
            });
        };
        return ResourceDayHeader;
    }(core.Component));

    var AbstractResourceDayTable = /** @class */ (function () {
        function AbstractResourceDayTable(dayTable, resources) {
            this.dayTable = dayTable;
            this.resources = resources;
            this.resourceIndex = new ResourceIndex(resources);
            this.rowCnt = dayTable.rowCnt;
            this.colCnt = dayTable.colCnt * resources.length;
            this.cells = this.buildCells();
        }
        AbstractResourceDayTable.prototype.buildCells = function () {
            var _a = this, rowCnt = _a.rowCnt, dayTable = _a.dayTable, resources = _a.resources;
            var rows = [];
            for (var row = 0; row < rowCnt; row++) {
                var rowCells = [];
                for (var dateCol = 0; dateCol < dayTable.colCnt; dateCol++) {
                    for (var resourceCol = 0; resourceCol < resources.length; resourceCol++) {
                        var resource = resources[resourceCol];
                        var htmlAttrs = 'data-resource-id="' + resource.id + '"';
                        rowCells[this.computeCol(dateCol, resourceCol)] = {
                            date: dayTable.cells[row][dateCol].date,
                            resource: resource,
                            htmlAttrs: htmlAttrs
                        };
                    }
                }
                rows.push(rowCells);
            }
            return rows;
        };
        return AbstractResourceDayTable;
    }());
    /*
    resources over dates
    */
    var ResourceDayTable = /** @class */ (function (_super) {
        __extends(ResourceDayTable, _super);
        function ResourceDayTable() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        ResourceDayTable.prototype.computeCol = function (dateI, resourceI) {
            return resourceI * this.dayTable.colCnt + dateI;
        };
        /*
        all date ranges are intact
        */
        ResourceDayTable.prototype.computeColRanges = function (dateStartI, dateEndI, resourceI) {
            return [
                {
                    firstCol: this.computeCol(dateStartI, resourceI),
                    lastCol: this.computeCol(dateEndI, resourceI),
                    isStart: true,
                    isEnd: true
                }
            ];
        };
        return ResourceDayTable;
    }(AbstractResourceDayTable));
    /*
    dates over resources
    */
    var DayResourceTable = /** @class */ (function (_super) {
        __extends(DayResourceTable, _super);
        function DayResourceTable() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        DayResourceTable.prototype.computeCol = function (dateI, resourceI) {
            return dateI * this.resources.length + resourceI;
        };
        /*
        every single day is broken up
        */
        DayResourceTable.prototype.computeColRanges = function (dateStartI, dateEndI, resourceI) {
            var segs = [];
            for (var i = dateStartI; i <= dateEndI; i++) {
                var col = this.computeCol(i, resourceI);
                segs.push({
                    firstCol: col,
                    lastCol: col,
                    isStart: i === dateStartI,
                    isEnd: i === dateEndI
                });
            }
            return segs;
        };
        return DayResourceTable;
    }(AbstractResourceDayTable));
    var ResourceIndex = /** @class */ (function () {
        function ResourceIndex(resources) {
            var indicesById = {};
            var ids = [];
            for (var i = 0; i < resources.length; i++) {
                var id = resources[i].id;
                ids.push(id);
                indicesById[id] = i;
            }
            this.ids = ids;
            this.indicesById = indicesById;
            this.length = resources.length;
        }
        return ResourceIndex;
    }());
    /*
    TODO: just use ResourceHash somehow? could then use the generic ResourceSplitter
    */
    var VResourceSplitter = /** @class */ (function (_super) {
        __extends(VResourceSplitter, _super);
        function VResourceSplitter() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        VResourceSplitter.prototype.getKeyInfo = function (props) {
            var resourceDayTable = props.resourceDayTable;
            var hash = core.mapHash(resourceDayTable.resourceIndex.indicesById, function (i) {
                return resourceDayTable.resources[i]; // has `ui` AND `businessHours` keys!
            }); // :(
            hash[''] = {};
            return hash;
        };
        VResourceSplitter.prototype.getKeysForDateSpan = function (dateSpan) {
            return [dateSpan.resourceId || ''];
        };
        VResourceSplitter.prototype.getKeysForEventDef = function (eventDef) {
            var resourceIds = eventDef.resourceIds;
            if (!resourceIds.length) {
                return [''];
            }
            return resourceIds;
        };
        return VResourceSplitter;
    }(core.Splitter));
    // joiner
    var NO_SEGS = []; // for memoizing
    var VResourceJoiner = /** @class */ (function () {
        function VResourceJoiner() {
            this.joinDateSelection = core.memoize(this.joinSegs);
            this.joinBusinessHours = core.memoize(this.joinSegs);
            this.joinFgEvents = core.memoize(this.joinSegs);
            this.joinBgEvents = core.memoize(this.joinSegs);
            this.joinEventDrags = core.memoize(this.joinInteractions);
            this.joinEventResizes = core.memoize(this.joinInteractions);
        }
        /*
        propSets also has a '' key for things with no resource
        */
        VResourceJoiner.prototype.joinProps = function (propSets, resourceDayTable) {
            var dateSelectionSets = [];
            var businessHoursSets = [];
            var fgEventSets = [];
            var bgEventSets = [];
            var eventDrags = [];
            var eventResizes = [];
            var eventSelection = '';
            var keys = resourceDayTable.resourceIndex.ids.concat(['']); // add in the all-resource key
            for (var _i = 0, keys_1 = keys; _i < keys_1.length; _i++) {
                var key = keys_1[_i];
                var props = propSets[key];
                dateSelectionSets.push(props.dateSelectionSegs);
                businessHoursSets.push(key ? props.businessHourSegs : NO_SEGS); // don't include redundant all-resource businesshours
                fgEventSets.push(key ? props.fgEventSegs : NO_SEGS); // don't include fg all-resource segs
                bgEventSets.push(props.bgEventSegs);
                eventDrags.push(props.eventDrag);
                eventResizes.push(props.eventResize);
                eventSelection = eventSelection || props.eventSelection;
            }
            return {
                dateSelectionSegs: this.joinDateSelection.apply(this, [resourceDayTable].concat(dateSelectionSets)),
                businessHourSegs: this.joinBusinessHours.apply(this, [resourceDayTable].concat(businessHoursSets)),
                fgEventSegs: this.joinFgEvents.apply(this, [resourceDayTable].concat(fgEventSets)),
                bgEventSegs: this.joinBgEvents.apply(this, [resourceDayTable].concat(bgEventSets)),
                eventDrag: this.joinEventDrags.apply(this, [resourceDayTable].concat(eventDrags)),
                eventResize: this.joinEventResizes.apply(this, [resourceDayTable].concat(eventResizes)),
                eventSelection: eventSelection
            };
        };
        VResourceJoiner.prototype.joinSegs = function (resourceDayTable) {
            var segGroups = [];
            for (var _i = 1; _i < arguments.length; _i++) {
                segGroups[_i - 1] = arguments[_i];
            }
            var resourceCnt = resourceDayTable.resources.length;
            var transformedSegs = [];
            for (var i = 0; i < resourceCnt; i++) {
                for (var _a = 0, _b = segGroups[i]; _a < _b.length; _a++) {
                    var seg = _b[_a];
                    transformedSegs.push.apply(transformedSegs, this.transformSeg(seg, resourceDayTable, i));
                }
                for (var _c = 0, _d = segGroups[resourceCnt]; _c < _d.length; _c++) { // one beyond. the all-resource
                    var seg = _d[_c];
                    transformedSegs.push.apply(// one beyond. the all-resource
                    transformedSegs, this.transformSeg(seg, resourceDayTable, i));
                }
            }
            return transformedSegs;
        };
        /*
        for expanding non-resource segs to all resources.
        only for public use.
        no memoizing.
        */
        VResourceJoiner.prototype.expandSegs = function (resourceDayTable, segs) {
            var resourceCnt = resourceDayTable.resources.length;
            var transformedSegs = [];
            for (var i = 0; i < resourceCnt; i++) {
                for (var _i = 0, segs_1 = segs; _i < segs_1.length; _i++) {
                    var seg = segs_1[_i];
                    transformedSegs.push.apply(transformedSegs, this.transformSeg(seg, resourceDayTable, i));
                }
            }
            return transformedSegs;
        };
        VResourceJoiner.prototype.joinInteractions = function (resourceDayTable) {
            var interactions = [];
            for (var _i = 1; _i < arguments.length; _i++) {
                interactions[_i - 1] = arguments[_i];
            }
            var resourceCnt = resourceDayTable.resources.length;
            var affectedInstances = {};
            var transformedSegs = [];
            var isEvent = false;
            var sourceSeg = null;
            for (var i = 0; i < resourceCnt; i++) {
                var interaction = interactions[i];
                if (interaction) {
                    for (var _a = 0, _b = interaction.segs; _a < _b.length; _a++) {
                        var seg = _b[_a];
                        transformedSegs.push.apply(transformedSegs, this.transformSeg(seg, resourceDayTable, i) // TODO: templateify Interaction::segs
                        );
                    }
                    __assign(affectedInstances, interaction.affectedInstances);
                    isEvent = isEvent || interaction.isEvent;
                    sourceSeg = sourceSeg || interaction.sourceSeg;
                }
                if (interactions[resourceCnt]) { // one beyond. the all-resource
                    for (var _c = 0, _d = interactions[resourceCnt].segs; _c < _d.length; _c++) {
                        var seg = _d[_c];
                        transformedSegs.push.apply(transformedSegs, this.transformSeg(seg, resourceDayTable, i) // TODO: templateify Interaction::segs
                        );
                    }
                }
            }
            return {
                affectedInstances: affectedInstances,
                segs: transformedSegs,
                isEvent: isEvent,
                sourceSeg: sourceSeg
            };
        };
        return VResourceJoiner;
    }());

    /*
    doesn't accept grouping
    */
    function flattenResources(resourceStore, orderSpecs) {
        return buildRowNodes(resourceStore, [], orderSpecs, false, {}, true)
            .map(function (node) {
            return node.resource;
        });
    }
    function buildRowNodes(resourceStore, groupSpecs, orderSpecs, isVGrouping, expansions, expansionDefault) {
        var complexNodes = buildHierarchy(resourceStore, isVGrouping ? -1 : 1, groupSpecs, orderSpecs);
        var flatNodes = [];
        flattenNodes(complexNodes, flatNodes, isVGrouping, [], 0, expansions, expansionDefault);
        return flatNodes;
    }
    function flattenNodes(complexNodes, res, isVGrouping, rowSpans, depth, expansions, expansionDefault) {
        for (var i = 0; i < complexNodes.length; i++) {
            var complexNode = complexNodes[i];
            var group = complexNode.group;
            if (group) {
                if (isVGrouping) {
                    var firstRowIndex = res.length;
                    var rowSpanIndex = rowSpans.length;
                    flattenNodes(complexNode.children, res, isVGrouping, rowSpans.concat(0), depth, expansions, expansionDefault);
                    if (firstRowIndex < res.length) {
                        var firstRow = res[firstRowIndex];
                        var firstRowSpans = firstRow.rowSpans = firstRow.rowSpans.slice();
                        firstRowSpans[rowSpanIndex] = res.length - firstRowIndex;
                    }
                }
                else {
                    var id = group.spec.field + ':' + group.value;
                    var isExpanded = expansions[id] != null ? expansions[id] : expansionDefault;
                    res.push({ id: id, group: group, isExpanded: isExpanded });
                    if (isExpanded) {
                        flattenNodes(complexNode.children, res, isVGrouping, rowSpans, depth + 1, expansions, expansionDefault);
                    }
                }
            }
            else if (complexNode.resource) {
                var id = complexNode.resource.id;
                var isExpanded = expansions[id] != null ? expansions[id] : expansionDefault;
                res.push({
                    id: id,
                    rowSpans: rowSpans,
                    depth: depth,
                    isExpanded: isExpanded,
                    hasChildren: Boolean(complexNode.children.length),
                    resource: complexNode.resource,
                    resourceFields: complexNode.resourceFields
                });
                if (isExpanded) {
                    flattenNodes(complexNode.children, res, isVGrouping, rowSpans, depth + 1, expansions, expansionDefault);
                }
            }
        }
    }
    function buildHierarchy(resourceStore, maxDepth, groupSpecs, orderSpecs) {
        var resourceNodes = buildResourceNodes(resourceStore, orderSpecs);
        var builtNodes = [];
        for (var resourceId in resourceNodes) {
            var resourceNode = resourceNodes[resourceId];
            if (!resourceNode.resource.parentId) {
                insertResourceNode(resourceNode, builtNodes, groupSpecs, 0, maxDepth, orderSpecs);
            }
        }
        return builtNodes;
    }
    function buildResourceNodes(resourceStore, orderSpecs) {
        var nodeHash = {};
        for (var resourceId in resourceStore) {
            var resource = resourceStore[resourceId];
            nodeHash[resourceId] = {
                resource: resource,
                resourceFields: buildResourceFields(resource),
                children: []
            };
        }
        for (var resourceId in resourceStore) {
            var resource = resourceStore[resourceId];
            if (resource.parentId) {
                var parentNode = nodeHash[resource.parentId];
                if (parentNode) {
                    insertResourceNodeInSiblings(nodeHash[resourceId], parentNode.children, orderSpecs);
                }
            }
        }
        return nodeHash;
    }
    function insertResourceNode(resourceNode, nodes, groupSpecs, depth, maxDepth, orderSpecs) {
        if (groupSpecs.length && (maxDepth === -1 || depth <= maxDepth)) {
            var groupNode = ensureGroupNodes(resourceNode, nodes, groupSpecs[0]);
            insertResourceNode(resourceNode, groupNode.children, groupSpecs.slice(1), depth + 1, maxDepth, orderSpecs);
        }
        else {
            insertResourceNodeInSiblings(resourceNode, nodes, orderSpecs);
        }
    }
    function ensureGroupNodes(resourceNode, nodes, groupSpec) {
        var groupValue = resourceNode.resourceFields[groupSpec.field];
        var groupNode;
        var newGroupIndex;
        // find an existing group that matches, or determine the position for a new group
        if (groupSpec.order) {
            for (newGroupIndex = 0; newGroupIndex < nodes.length; newGroupIndex++) {
                var node = nodes[newGroupIndex];
                if (node.group) {
                    var cmp = core.flexibleCompare(groupValue, node.group.value) * groupSpec.order;
                    if (cmp === 0) {
                        groupNode = node;
                        break;
                    }
                    else if (cmp < 0) {
                        break;
                    }
                }
            }
        }
        else { // the groups are unordered
            for (newGroupIndex = 0; newGroupIndex < nodes.length; newGroupIndex++) {
                var node = nodes[newGroupIndex];
                if (node.group && groupValue === node.group.value) {
                    groupNode = node;
                    break;
                }
            }
        }
        if (!groupNode) {
            groupNode = {
                group: {
                    value: groupValue,
                    spec: groupSpec
                },
                children: []
            };
            nodes.splice(newGroupIndex, 0, groupNode);
        }
        return groupNode;
    }
    function insertResourceNodeInSiblings(resourceNode, siblings, orderSpecs) {
        var i;
        for (i = 0; i < siblings.length; i++) {
            var cmp = core.compareByFieldSpecs(siblings[i].resourceFields, resourceNode.resourceFields, orderSpecs);
            if (cmp > 0) { // went 1 past. insert at i
                break;
            }
        }
        siblings.splice(i, 0, resourceNode);
    }
    function buildResourceFields(resource) {
        var obj = __assign({}, resource.extendedProps, resource.ui, resource);
        delete obj.ui;
        delete obj.extendedProps;
        return obj;
    }
    function isGroupsEqual(group0, group1) {
        return group0.spec === group1.spec && group0.value === group1.value;
    }

    var main = core.createPlugin({
        reducers: [resourcesReducers],
        eventDefParsers: [parseEventDef],
        eventDragMutationMassagers: [massageEventDragMutation],
        eventDefMutationAppliers: [applyEventDefMutation],
        dateSelectionTransformers: [transformDateSelectionJoin],
        datePointTransforms: [transformDatePoint],
        dateSpanTransforms: [transformDateSpan],
        viewPropsTransformers: [ResourceDataAdder, ResourceEventConfigAdder],
        isPropsValid: isPropsValidWithResources,
        externalDefTransforms: [transformExternalDef],
        eventResizeJoinTransforms: [transformEventResizeJoin],
        viewContainerModifiers: [injectLicenseWarning],
        eventDropTransformers: [transformEventDrop],
        optionChangeHandlers: optionChangeHandlers
    });

    exports.AbstractResourceDayTable = AbstractResourceDayTable;
    exports.DayResourceTable = DayResourceTable;
    exports.ResourceApi = ResourceApi;
    exports.ResourceDayHeader = ResourceDayHeader;
    exports.ResourceDayTable = ResourceDayTable;
    exports.ResourceSplitter = ResourceSplitter;
    exports.VResourceJoiner = VResourceJoiner;
    exports.VResourceSplitter = VResourceSplitter;
    exports.buildResourceFields = buildResourceFields;
    exports.buildResourceTextFunc = buildResourceTextFunc;
    exports.buildRowNodes = buildRowNodes;
    exports.computeResourceEditable = computeResourceEditable;
    exports.default = main;
    exports.flattenResources = flattenResources;
    exports.isGroupsEqual = isGroupsEqual;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
/*!
FullCalendar Resources Common Plugin v4.1.0
Docs & License: https://fullcalendar.io/scheduler
(c) 2019 Adam Shaw
*/
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('@fullcalendar/core')) :
    typeof define === 'function' && define.amd ? define(['exports', '@fullcalendar/core'], factory) :
    (global = global || self, factory(global.FullCalendarResourceCommon = {}, global.FullCalendar));
}(this, function (exports, core) { 'use strict';

    /*! *****************************************************************************
    Copyright (c) Microsoft Corporation. All rights reserved.
    Licensed under the Apache License, Version 2.0 (the "License"); you may not use
    this file except in compliance with the License. You may obtain a copy of the
    License at http://www.apache.org/licenses/LICENSE-2.0

    THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
    KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
    WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
    MERCHANTABLITY OR NON-INFRINGEMENT.

    See the Apache Version 2.0 License for specific language governing permissions
    and limitations under the License.
    ***************************************************************************** */
    /* global Reflect, Promise */

    var extendStatics = function(d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };

    function __extends(d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    }

    var __assign = function() {
        __assign = Object.assign || function __assign(t) {
            for (var s, i = 1, n = arguments.length; i < n; i++) {
                s = arguments[i];
                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
            }
            return t;
        };
        return __assign.apply(this, arguments);
    };

    var ResourceDataAdder = /** @class */ (function () {
        function ResourceDataAdder() {
            this.filterResources = core.memoize(filterResources);
        }
        ResourceDataAdder.prototype.transform = function (viewProps, viewSpec, calendarProps, view) {
            if (viewSpec.class.needsResourceData) {
                return {
                    resourceStore: this.filterResources(calendarProps.resourceStore, view.opt('filterResourcesWithEvents'), calendarProps.eventStore, calendarProps.dateProfile.activeRange),
                    resourceEntityExpansions: calendarProps.resourceEntityExpansions
                };
            }
        };
        return ResourceDataAdder;
    }());
    function filterResources(resourceStore, doFilterResourcesWithEvents, eventStore, activeRange) {
        if (doFilterResourcesWithEvents) {
            var instancesInRange = filterEventInstancesInRange(eventStore.instances, activeRange);
            var hasEvents_1 = computeHasEvents(instancesInRange, eventStore.defs);
            __assign(hasEvents_1, computeAncestorHasEvents(hasEvents_1, resourceStore));
            return core.filterHash(resourceStore, function (resource, resourceId) {
                return hasEvents_1[resourceId];
            });
        }
        else {
            return resourceStore;
        }
    }
    function filterEventInstancesInRange(eventInstances, activeRange) {
        return core.filterHash(eventInstances, function (eventInstance) {
            return core.rangesIntersect(eventInstance.range, activeRange);
        });
    }
    function computeHasEvents(eventInstances, eventDefs) {
        var hasEvents = {};
        for (var instanceId in eventInstances) {
            var instance = eventInstances[instanceId];
            for (var _i = 0, _a = eventDefs[instance.defId].resourceIds; _i < _a.length; _i++) {
                var resourceId = _a[_i];
                hasEvents[resourceId] = true;
            }
        }
        return hasEvents;
    }
    /*
    mark resources as having events if any of their ancestors have them
    NOTE: resourceStore might not have all the resources that hasEvents{} has keyed
    */
    function computeAncestorHasEvents(hasEvents, resourceStore) {
        var res = {};
        for (var resourceId in hasEvents) {
            var resource = void 0;
            while ((resource = resourceStore[resourceId])) {
                resourceId = resource.parentId; // now functioning as the parentId
                if (resourceId) {
                    res[resourceId] = true;
                }
                else {
                    break;
                }
            }
        }
        return res;
    }
    // for when non-resource view should be given EventUi info (for event coloring/constraints based off of resource data)
    var ResourceEventConfigAdder = /** @class */ (function () {
        function ResourceEventConfigAdder() {
            this.buildResourceEventUis = core.memoizeOutput(buildResourceEventUis, core.isObjectsSimilar);
            this.injectResourceEventUis = core.memoize(injectResourceEventUis);
        }
        ResourceEventConfigAdder.prototype.transform = function (viewProps, viewSpec, calendarProps) {
            if (!viewSpec.class.needsResourceData) { // is a non-resource view?
                return {
                    eventUiBases: this.injectResourceEventUis(viewProps.eventUiBases, viewProps.eventStore.defs, this.buildResourceEventUis(calendarProps.resourceStore))
                };
            }
        };
        return ResourceEventConfigAdder;
    }());
    function buildResourceEventUis(resourceStore) {
        return core.mapHash(resourceStore, function (resource) {
            return resource.ui;
        });
    }
    function injectResourceEventUis(eventUiBases, eventDefs, resourceEventUis) {
        return core.mapHash(eventUiBases, function (eventUi, defId) {
            if (defId) { // not the '' key
                return injectResourceEventUi(eventUi, eventDefs[defId], resourceEventUis);
            }
            else {
                return eventUi;
            }
        });
    }
    function injectResourceEventUi(origEventUi, eventDef, resourceEventUis) {
        var parts = [];
        // first resource takes precedence, which fights with the ordering of combineEventUis, thus the unshifts
        for (var _i = 0, _a = eventDef.resourceIds; _i < _a.length; _i++) {
            var resourceId = _a[_i];
            if (resourceEventUis[resourceId]) {
                parts.unshift(resourceEventUis[resourceId]);
            }
        }
        parts.unshift(origEventUi);
        return core.combineEventUis(parts);
    }

    var RESOURCE_SOURCE_PROPS = {
        id: String
    };
    var defs = [];
    var uid = 0;
    function registerResourceSourceDef(def) {
        defs.push(def);
    }
    function getResourceSourceDef(id) {
        return defs[id];
    }
    function doesSourceIgnoreRange(source) {
        return Boolean(defs[source.sourceDefId].ignoreRange);
    }
    function parseResourceSource(input) {
        for (var i = defs.length - 1; i >= 0; i--) { // later-added plugins take precedence
            var def = defs[i];
            var meta = def.parseMeta(input);
            if (meta) {
                var res = parseResourceSourceProps((typeof input === 'object' && input) ? input : {}, meta, i);
                res._raw = core.freezeRaw(input);
                return res;
            }
        }
        return null;
    }
    function parseResourceSourceProps(input, meta, sourceDefId) {
        var props = core.refineProps(input, RESOURCE_SOURCE_PROPS);
        props.sourceId = String(uid++);
        props.sourceDefId = sourceDefId;
        props.meta = meta;
        props.publicId = props.id;
        props.isFetching = false;
        props.latestFetchId = '';
        props.fetchRange = null;
        delete props.id;
        return props;
    }

    function reduceResourceSource (source, action, dateProfile, calendar) {
        switch (action.type) {
            case 'INIT':
                return createSource(calendar.opt('resources'), calendar);
            case 'RESET_RESOURCE_SOURCE':
                return createSource(action.resourceSourceInput, calendar, true);
            case 'PREV': // TODO: how do we track all actions that affect dateProfile :(
            case 'NEXT':
            case 'SET_DATE':
            case 'SET_VIEW_TYPE':
                return handleRange(source, dateProfile.activeRange, calendar);
            case 'RECEIVE_RESOURCES':
            case 'RECEIVE_RESOURCE_ERROR':
                return receiveResponse(source, action.fetchId, action.fetchRange);
            case 'REFETCH_RESOURCES':
                return fetchSource(source, dateProfile.activeRange, calendar);
            default:
                return source;
        }
    }
    var uid$1 = 0;
    function createSource(input, calendar, forceFetch) {
        if (input) {
            var source = parseResourceSource(input);
            if (forceFetch || !calendar.opt('refetchResourcesOnNavigate')) { // because assumes handleRange will do it later
                source = fetchSource(source, null, calendar);
            }
            return source;
        }
        return null;
    }
    function handleRange(source, activeRange, calendar) {
        if (calendar.opt('refetchResourcesOnNavigate') &&
            !doesSourceIgnoreRange(source) &&
            (!source.fetchRange || !core.rangesEqual(source.fetchRange, activeRange))) {
            return fetchSource(source, activeRange, calendar);
        }
        else {
            return source;
        }
    }
    function fetchSource(source, fetchRange, calendar) {
        var sourceDef = getResourceSourceDef(source.sourceDefId);
        var fetchId = String(uid$1++);
        sourceDef.fetch({
            resourceSource: source,
            calendar: calendar,
            range: fetchRange
        }, function (res) {
            // HACK
            // do before calling dispatch in case dispatch renders synchronously
            calendar.afterSizingTriggers._resourcesRendered = [null]; // fire once
            calendar.dispatch({
                type: 'RECEIVE_RESOURCES',
                fetchId: fetchId,
                fetchRange: fetchRange,
                rawResources: res.rawResources
            });
        }, function (error) {
            calendar.dispatch({
                type: 'RECEIVE_RESOURCE_ERROR',
                fetchId: fetchId,
                fetchRange: fetchRange,
                error: error
            });
        });
        return __assign({}, source, { isFetching: true, latestFetchId: fetchId });
    }
    function receiveResponse(source, fetchId, fetchRange) {
        if (fetchId === source.latestFetchId) {
            return __assign({}, source, { isFetching: false, fetchRange: fetchRange });
        }
        return source;
    }

    var RESOURCE_PROPS = {
        id: String,
        title: String,
        parentId: String,
        businessHours: null,
        children: null,
        extendedProps: null
    };
    var PRIVATE_ID_PREFIX = '_fc:';
    var uid$2 = 0;
    /*
    needs a full store so that it can populate children too
    */
    function parseResource(input, parentId, store, calendar) {
        if (parentId === void 0) { parentId = ''; }
        var leftovers0 = {};
        var props = core.refineProps(input, RESOURCE_PROPS, {}, leftovers0);
        var leftovers1 = {};
        var ui = core.processScopedUiProps('event', leftovers0, calendar, leftovers1);
        if (!props.id) {
            props.id = PRIVATE_ID_PREFIX + (uid$2++);
        }
        if (!props.parentId) { // give precedence to the parentId property
            props.parentId = parentId;
        }
        props.businessHours = props.businessHours ? core.parseBusinessHours(props.businessHours, calendar) : null;
        props.ui = ui;
        props.extendedProps = __assign({}, leftovers1, props.extendedProps);
        // help out ResourceApi from having user modify props
        Object.freeze(ui.classNames);
        Object.freeze(props.extendedProps);
        if (store[props.id]) ;
        else {
            store[props.id] = props;
            if (props.children) {
                for (var _i = 0, _a = props.children; _i < _a.length; _i++) {
                    var childInput = _a[_i];
                    parseResource(childInput, props.id, store, calendar);
                }
                delete props.children;
            }
        }
        return props;
    }
    /*
    TODO: use this in more places
    */
    function getPublicId(id) {
        if (id.indexOf(PRIVATE_ID_PREFIX) === 0) {
            return '';
        }
        return id;
    }

    function reduceResourceStore (store, action, source, calendar) {
        switch (action.type) {
            case 'INIT':
                return {};
            case 'RECEIVE_RESOURCES':
                return receiveRawResources(store, action.rawResources, action.fetchId, source, calendar);
            case 'ADD_RESOURCE':
                return addResource(store, action.resourceHash);
            case 'REMOVE_RESOURCE':
                return removeResource(store, action.resourceId);
            case 'SET_RESOURCE_PROP':
                return setResourceProp(store, action.resourceId, action.propName, action.propValue);
            case 'RESET_RESOURCES':
                // must make the calendar think each resource is a new object :/
                return core.mapHash(store, function (resource) {
                    return __assign({}, resource);
                });
            default:
                return store;
        }
    }
    function receiveRawResources(existingStore, inputs, fetchId, source, calendar) {
        if (source.latestFetchId === fetchId) {
            var nextStore = {};
            for (var _i = 0, inputs_1 = inputs; _i < inputs_1.length; _i++) {
                var input = inputs_1[_i];
                parseResource(input, '', nextStore, calendar);
            }
            return nextStore;
        }
        else {
            return existingStore;
        }
    }
    function addResource(existingStore, additions) {
        // TODO: warn about duplicate IDs
        return __assign({}, existingStore, additions);
    }
    function removeResource(existingStore, resourceId) {
        var newStore = __assign({}, existingStore);
        delete newStore[resourceId];
        // promote children
        for (var childResourceId in newStore) { // a child, *maybe* but probably not
            if (newStore[childResourceId].parentId === resourceId) {
                newStore[childResourceId] = __assign({}, newStore[childResourceId], { parentId: '' });
            }
        }
        return newStore;
    }
    function setResourceProp(existingStore, resourceId, name, value) {
        var _a, _b;
        var existingResource = existingStore[resourceId];
        // TODO: sanitization
        if (existingResource) {
            return __assign({}, existingStore, (_a = {}, _a[resourceId] = __assign({}, existingResource, (_b = {}, _b[name] = value, _b)), _a));
        }
        else {
            return existingStore;
        }
    }

    function reduceResourceEntityExpansions(expansions, action) {
        var _a;
        switch (action.type) {
            case 'INIT':
                return {};
            case 'SET_RESOURCE_ENTITY_EXPANDED':
                return __assign({}, expansions, (_a = {}, _a[action.id] = action.isExpanded, _a));
            default:
                return expansions;
        }
    }

    function resourcesReducers (state, action, calendar) {
        var resourceSource = reduceResourceSource(state.resourceSource, action, state.dateProfile, calendar);
        var resourceStore = reduceResourceStore(state.resourceStore, action, resourceSource, calendar);
        var resourceEntityExpansions = reduceResourceEntityExpansions(state.resourceEntityExpansions, action);
        return __assign({}, state, { resourceSource: resourceSource,
            resourceStore: resourceStore,
            resourceEntityExpansions: resourceEntityExpansions });
    }

    var RESOURCE_RELATED_PROPS = {
        resourceId: String,
        resourceIds: function (items) {
            return (items || []).map(function (item) {
                return String(item);
            });
        },
        resourceEditable: Boolean
    };
    function parseEventDef(def, props, leftovers) {
        var resourceRelatedProps = core.refineProps(props, RESOURCE_RELATED_PROPS, {}, leftovers);
        var resourceIds = resourceRelatedProps.resourceIds;
        if (resourceRelatedProps.resourceId) {
            resourceIds.push(resourceRelatedProps.resourceId);
        }
        def.resourceIds = resourceIds;
        def.resourceEditable = resourceRelatedProps.resourceEditable;
    }

    function massageEventDragMutation(eventMutation, hit0, hit1) {
        var resource0 = hit0.dateSpan.resourceId;
        var resource1 = hit1.dateSpan.resourceId;
        if (resource0 && resource1 &&
            resource0 !== resource1) {
            eventMutation.resourceMutation = {
                matchResourceId: resource0,
                setResourceId: resource1
            };
        }
    }
    /*
    TODO: all this would be much easier if we were using a hash!
    */
    function applyEventDefMutation(eventDef, mutation, calendar) {
        var resourceMutation = mutation.resourceMutation;
        if (resourceMutation && computeResourceEditable(eventDef, calendar)) {
            var index = eventDef.resourceIds.indexOf(resourceMutation.matchResourceId);
            if (index !== -1) {
                var resourceIds = eventDef.resourceIds.slice(); // copy
                resourceIds.splice(index, 1); // remove
                if (resourceIds.indexOf(resourceMutation.setResourceId) === -1) { // not already in there
                    resourceIds.push(resourceMutation.setResourceId); // add
                }
                eventDef.resourceIds = resourceIds;
            }
        }
    }
    /*
    HACK
    TODO: use EventUi system instead of this
    */
    function computeResourceEditable(eventDef, calendar) {
        var resourceEditable = eventDef.resourceEditable;
        if (resourceEditable == null) {
            var source = eventDef.sourceId && calendar.state.eventSources[eventDef.sourceId];
            if (source) {
                resourceEditable = source.extendedProps.resourceEditable; // used the Source::extendedProps hack
            }
            if (resourceEditable == null) {
                resourceEditable = calendar.opt('eventResourceEditable');
                if (resourceEditable == null) {
                    resourceEditable = calendar.opt('editable'); // TODO: use defaults system instead
                }
            }
        }
        return resourceEditable;
    }
    function transformEventDrop(mutation, calendar) {
        var resourceMutation = mutation.resourceMutation;
        if (resourceMutation) {
            return {
                oldResource: calendar.getResourceById(resourceMutation.matchResourceId),
                newResource: calendar.getResourceById(resourceMutation.setResourceId)
            };
        }
        else {
            return {
                oldResource: null,
                newResource: null
            };
        }
    }

    function transformDateSelectionJoin(hit0, hit1) {
        var resourceId0 = hit0.dateSpan.resourceId;
        var resourceId1 = hit1.dateSpan.resourceId;
        if (resourceId0 && resourceId1) {
            if (hit0.component.allowAcrossResources === false &&
                resourceId0 !== resourceId1) {
                return false;
            }
            else {
                return { resourceId: resourceId0 };
            }
        }
    }

    var ResourceApi = /** @class */ (function () {
        function ResourceApi(calendar, rawResource) {
            this._calendar = calendar;
            this._resource = rawResource;
        }
        ResourceApi.prototype.setProp = function (name, value) {
            this._calendar.dispatch({
                type: 'SET_RESOURCE_PROP',
                resourceId: this._resource.id,
                propName: name,
                propValue: value
            });
        };
        ResourceApi.prototype.remove = function () {
            this._calendar.dispatch({
                type: 'REMOVE_RESOURCE',
                resourceId: this._resource.id
            });
        };
        ResourceApi.prototype.getParent = function () {
            var calendar = this._calendar;
            var parentId = this._resource.parentId;
            if (parentId) {
                return new ResourceApi(calendar, calendar.state.resourceSource[parentId]);
            }
            else {
                return null;
            }
        };
        ResourceApi.prototype.getChildren = function () {
            var thisResourceId = this._resource.id;
            var calendar = this._calendar;
            var resourceStore = calendar.state.resourceStore;
            var childApis = [];
            for (var resourceId in resourceStore) {
                if (resourceStore[resourceId].parentId === thisResourceId) {
                    childApis.push(new ResourceApi(calendar, resourceStore[resourceId]));
                }
            }
            return childApis;
        };
        /*
        this is really inefficient!
        TODO: make EventApi::resourceIds a hash or keep an index in the Calendar's state
        */
        ResourceApi.prototype.getEvents = function () {
            var thisResourceId = this._resource.id;
            var calendar = this._calendar;
            var _a = calendar.state.eventStore, defs = _a.defs, instances = _a.instances;
            var eventApis = [];
            for (var instanceId in instances) {
                var instance = instances[instanceId];
                var def = defs[instance.defId];
                if (def.resourceIds.indexOf(thisResourceId) !== -1) { // inefficient!!!
                    eventApis.push(new core.EventApi(calendar, def, instance));
                }
            }
            return eventApis;
        };
        Object.defineProperty(ResourceApi.prototype, "id", {
            get: function () { return this._resource.id; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "title", {
            get: function () { return this._resource.title; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventConstraint", {
            get: function () { return this._resource.ui.constraints[0] || null; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventOverlap", {
            get: function () { return this._resource.ui.overlap; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventAllow", {
            get: function () { return this._resource.ui.allows[0] || null; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventBackgroundColor", {
            get: function () { return this._resource.ui.backgroundColor; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventBorderColor", {
            get: function () { return this._resource.ui.borderColor; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventTextColor", {
            get: function () { return this._resource.ui.textColor; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "eventClassNames", {
            // NOTE: user can't modify these because Object.freeze was called in event-def parsing
            get: function () { return this._resource.ui.classNames; },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(ResourceApi.prototype, "extendedProps", {
            get: function () { return this._resource.extendedProps; },
            enumerable: true,
            configurable: true
        });
        return ResourceApi;
    }());

    core.Calendar.prototype.addResource = function (input, scrollTo) {
        var _a;
        if (scrollTo === void 0) { scrollTo = true; }
        var resourceHash;
        var resource;
        if (input instanceof ResourceApi) {
            resource = input._resource;
            resourceHash = (_a = {}, _a[resource.id] = resource, _a);
        }
        else {
            resourceHash = {};
            resource = parseResource(input, '', resourceHash, this);
        }
        // HACK
        if (scrollTo) {
            this.component.view.addScroll({ forcedRowId: resource.id });
        }
        this.dispatch({
            type: 'ADD_RESOURCE',
            resourceHash: resourceHash
        });
        return new ResourceApi(this, resource);
    };
    core.Calendar.prototype.getResourceById = function (id) {
        id = String(id);
        if (this.state.resourceStore) { // guard against calendar with no resource functionality
            var rawResource = this.state.resourceStore[id];
            if (rawResource) {
                return new ResourceApi(this, rawResource);
            }
        }
        return null;
    };
    core.Calendar.prototype.getResources = function () {
        var resourceStore = this.state.resourceStore;
        var resourceApis = [];
        if (resourceStore) { // guard against calendar with no resource functionality
            for (var resourceId in resourceStore) {
                resourceApis.push(new ResourceApi(this, resourceStore[resourceId]));
            }
        }
        return resourceApis;
    };
    core.Calendar.prototype.getTopLevelResources = function () {
        var resourceStore = this.state.resourceStore;
        var resourceApis = [];
        if (resourceStore) { // guard against calendar with no resource functionality
            for (var resourceId in resourceStore) {
                if (!resourceStore[resourceId].parentId) {
                    resourceApis.push(new ResourceApi(this, resourceStore[resourceId]));
                }
            }
        }
        return resourceApis;
    };
    core.Calendar.prototype.rerenderResources = function () {
        this.dispatch({
            type: 'RESET_RESOURCES'
        });
    };
    core.Calendar.prototype.refetchResources = function () {
        this.dispatch({
            type: 'REFETCH_RESOURCES'
        });
    };
    function transformDatePoint(dateSpan, calendar) {
        return dateSpan.resourceId ?
            { resource: calendar.getResourceById(dateSpan.resourceId) } :
            {};
    }
    function transformDateSpan(dateSpan, calendar) {
        return dateSpan.resourceId ?
            { resource: calendar.getResourceById(dateSpan.resourceId) } :
            {};
    }

    /*
    splits things BASED OFF OF which resources they are associated with.
    creates a '' entry which is when something has NO resource.
    */
    var ResourceSplitter = /** @class */ (function (_super) {
        __extends(ResourceSplitter, _super);
        function ResourceSplitter() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        ResourceSplitter.prototype.getKeyInfo = function (props) {
            return __assign({ '': {} }, props.resourceStore // already has `ui` and `businessHours` keys!
            );
        };
        ResourceSplitter.prototype.getKeysForDateSpan = function (dateSpan) {
            return [dateSpan.resourceId || ''];
        };
        ResourceSplitter.prototype.getKeysForEventDef = function (eventDef) {
            var resourceIds = eventDef.resourceIds;
            if (!resourceIds.length) {
                return [''];
            }
            return resourceIds;
        };
        return ResourceSplitter;
    }(core.Splitter));

    function isPropsValidWithResources(props, calendar) {
        var splitter = new ResourceSplitter();
        var sets = splitter.splitProps(__assign({}, props, { resourceStore: calendar.state.resourceStore }));
        for (var resourceId in sets) {
            var props_1 = sets[resourceId];
            // merge in event data from the non-resource segment
            if (resourceId && sets['']) { // current segment is not the non-resource one, and there IS a non-resource one
                props_1 = __assign({}, props_1, { eventStore: core.mergeEventStores(sets[''].eventStore, props_1.eventStore), eventUiBases: __assign({}, sets[''].eventUiBases, props_1.eventUiBases) });
            }
            if (!core.isPropsValid(props_1, calendar, { resourceId: resourceId }, filterConfig.bind(null, resourceId))) {
                return false;
            }
        }
        return true;
    }
    function filterConfig(resourceId, config) {
        return __assign({}, config, { constraints: filterConstraints(resourceId, config.constraints) });
    }
    function filterConstraints(resourceId, constraints) {
        return constraints.map(function (constraint) {
            var defs = constraint.defs;
            if (defs) { // we are dealing with an EventStore
                // if any of the events define constraints to resources that are NOT this resource,
                // then this resource is unconditionally prohibited, which is what a `false` value does.
                for (var defId in defs) {
                    var resourceIds = defs[defId].resourceIds;
                    if (resourceIds.length && resourceIds.indexOf(resourceId) === -1) { // TODO: use a hash?!!! (for other reasons too)
                        return false;
                    }
                }
            }
            return constraint;
        });
    }

    function transformExternalDef(dateSpan) {
        return dateSpan.resourceId ?
            { resourceId: dateSpan.resourceId } :
            {};
    }

    function transformEventResizeJoin(hit0, hit1) {
        var component = hit0.component;
        if (component.allowAcrossResources === false &&
            hit0.dateSpan.resourceId !== hit1.dateSpan.resourceId) {
            return false;
        }
    }

    core.EventApi.prototype.getResources = function () {
        var calendar = this._calendar;
        return this._def.resourceIds.map(function (resourceId) {
            return calendar.getResourceById(resourceId);
        });
    };
    core.EventApi.prototype.setResources = function (resources) {
        var resourceIds = [];
        // massage resources -> resourceIds
        for (var _i = 0, resources_1 = resources; _i < resources_1.length; _i++) {
            var resource = resources_1[_i];
            var resourceId = null;
            if (typeof resource === 'string') {
                resourceId = resource;
            }
            else if (typeof resource === 'number') {
                resourceId = String(resource);
            }
            else if (resource instanceof ResourceApi) {
                resourceId = resource.id; // guaranteed to always have an ID. hmmm
            }
            else {
                console.warn('unknown resource type: ' + resource);
            }
            if (resourceId) {
                resourceIds.push(resourceId);
            }
        }
        this.mutate({
            standardProps: {
                resourceIds: resourceIds
            }
        });
    };

    var RELEASE_DATE = '2019-04-24'; // for Scheduler
    var UPGRADE_WINDOW = 365 + 7; // days. 1 week leeway, for tz shift reasons too
    var LICENSE_INFO_URL = 'http://fullcalendar.io/scheduler/license/';
    var PRESET_LICENSE_KEYS = [
        'GPL-My-Project-Is-Open-Source',
        'CC-Attribution-NonCommercial-NoDerivatives'
    ];
    var CSS = {
        position: 'absolute',
        'z-index': 99999,
        bottom: '1px',
        left: '1px',
        background: '#eee',
        'border-color': '#ddd',
        'border-style': 'solid',
        'border-width': '1px 1px 0 0',
        padding: '2px 4px',
        'font-size': '12px',
        'border-top-right-radius': '3px'
    };
    function injectLicenseWarning(containerEl, calendar) {
        var key = calendar.opt('schedulerLicenseKey');
        if (!isImmuneUrl(window.location.href) && !isValidKey(key)) {
            core.appendToElement(containerEl, '<div class="fc-license-message" style="' + core.htmlEscape(core.cssToStr(CSS)) + '">' +
                'Please use a valid license key. <a href="' + LICENSE_INFO_URL + '">More Info</a>' +
                '</div>');
        }
    }
    /*
    This decryption is not meant to be bulletproof. Just a way to remind about an upgrade.
    */
    function isValidKey(key) {
        if (PRESET_LICENSE_KEYS.indexOf(key) !== -1) {
            return true;
        }
        var parts = (key || '').match(/^(\d+)\-fcs\-(\d+)$/);
        if (parts && (parts[1].length === 10)) {
            var purchaseDate = new Date(parseInt(parts[2], 10) * 1000);
            var releaseDate = new Date(core.config.mockSchedulerReleaseDate || RELEASE_DATE);
            if (core.isValidDate(releaseDate)) { // token won't be replaced in dev mode
                var minPurchaseDate = core.addDays(releaseDate, -UPGRADE_WINDOW);
                if (minPurchaseDate < purchaseDate) {
                    return true;
                }
            }
        }
        return false;
    }
    function isImmuneUrl(url) {
        return /\w+\:\/\/fullcalendar\.io\/|\/demos\/[\w-]+\.html$/.test(url);
    }

    var optionChangeHandlers = {
        resources: handleResources
    };
    function handleResources(newSourceInput, calendar) {
        var oldSourceInput = calendar.state.resourceSource._raw;
        if (!core.isValuesSimilar(oldSourceInput, newSourceInput, 2)) {
            calendar.dispatch({
                type: 'RESET_RESOURCE_SOURCE',
                resourceSourceInput: newSourceInput
            });
        }
    }

    registerResourceSourceDef({
        ignoreRange: true,
        parseMeta: function (raw) {
            if (Array.isArray(raw)) {
                return raw;
            }
            else if (Array.isArray(raw.resources)) {
                return raw.resources;
            }
            return null;
        },
        fetch: function (arg, successCallback) {
            successCallback({
                rawResources: arg.resourceSource.meta
            });
        }
    });

    registerResourceSourceDef({
        parseMeta: function (raw) {
            if (typeof raw === 'function') {
                return raw;
            }
            else if (typeof raw.resources === 'function') {
                return raw.resources;
            }
            return null;
        },
        fetch: function (arg, success, failure) {
            var dateEnv = arg.calendar.dateEnv;
            var func = arg.resourceSource.meta;
            var publicArg = {};
            if (arg.range) {
                publicArg = {
                    start: dateEnv.toDate(arg.range.start),
                    end: dateEnv.toDate(arg.range.end),
                    startStr: dateEnv.formatIso(arg.range.start),
                    endStr: dateEnv.formatIso(arg.range.end),
                    timeZone: dateEnv.timeZone
                };
            }
            // TODO: make more dry with EventSourceFunc
            // TODO: accept a response?
            core.unpromisify(func.bind(null, publicArg), function (rawResources) {
                success({ rawResources: rawResources }); // needs an object response
            }, failure // send errorObj directly to failure callback
            );
        }
    });

    registerResourceSourceDef({
        parseMeta: function (raw) {
            if (typeof raw === 'string') {
                raw = { url: raw };
            }
            else if (!raw || typeof raw !== 'object' || !raw.url) {
                return null;
            }
            return {
                url: raw.url,
                method: (raw.method || 'GET').toUpperCase(),
                extraParams: raw.extraParams
            };
        },
        fetch: function (arg, successCallback, failureCallback) {
            var meta = arg.resourceSource.meta;
            var requestParams = buildRequestParams(meta, arg.range, arg.calendar);
            core.requestJson(meta.method, meta.url, requestParams, function (rawResources, xhr) {
                successCallback({ rawResources: rawResources, xhr: xhr });
            }, function (message, xhr) {
                failureCallback({ message: message, xhr: xhr });
            });
        }
    });
    // TODO: somehow consolidate with event json feed
    function buildRequestParams(meta, range, calendar) {
        var dateEnv = calendar.dateEnv;
        var startParam;
        var endParam;
        var timeZoneParam;
        var customRequestParams;
        var params = {};
        if (range) {
            // startParam = meta.startParam
            // if (startParam == null) {
            startParam = calendar.opt('startParam');
            // }
            // endParam = meta.endParam
            // if (endParam == null) {
            endParam = calendar.opt('endParam');
            // }
            // timeZoneParam = meta.timeZoneParam
            // if (timeZoneParam == null) {
            timeZoneParam = calendar.opt('timeZoneParam');
            // }
            params[startParam] = dateEnv.formatIso(range.start);
            params[endParam] = dateEnv.formatIso(range.end);
            if (dateEnv.timeZone !== 'local') {
                params[timeZoneParam] = dateEnv.timeZone;
            }
        }
        // retrieve any outbound GET/POST data from the options
        if (typeof meta.extraParams === 'function') {
            // supplied as a function that returns a key/value object
            customRequestParams = meta.extraParams();
        }
        else {
            // probably supplied as a straight key/value object
            customRequestParams = meta.extraParams || {};
        }
        __assign(params, customRequestParams);
        return params;
    }

    function buildResourceTextFunc(resourceTextSetting, calendar) {
        if (typeof resourceTextSetting === 'function') {
            return function (resource) {
                return resourceTextSetting(new ResourceApi(calendar, resource));
            };
        }
        else {
            return function (resource) {
                return resource.title || getPublicId(resource.id);
            };
        }
    }

    var ResourceDayHeader = /** @class */ (function (_super) {
        __extends(ResourceDayHeader, _super);
        function ResourceDayHeader(context, parentEl) {
            var _this = _super.call(this, context) || this;
            _this.datesAboveResources = _this.opt('datesAboveResources');
            _this.resourceTextFunc = buildResourceTextFunc(_this.opt('resourceText'), _this.calendar);
            parentEl.innerHTML = ''; // because might be nbsp
            parentEl.appendChild(_this.el = core.htmlToElement('<div class="fc-row ' + _this.theme.getClass('headerRow') + '">' +
                '<table class="' + _this.theme.getClass('tableGrid') + '">' +
                '<thead></thead>' +
                '</table>' +
                '</div>'));
            _this.thead = _this.el.querySelector('thead');
            return _this;
        }
        ResourceDayHeader.prototype.destroy = function () {
            core.removeElement(this.el);
        };
        ResourceDayHeader.prototype.render = function (props) {
            var html;
            this.dateFormat = core.createFormatter(this.opt('columnHeaderFormat') ||
                core.computeFallbackHeaderFormat(props.datesRepDistinctDays, props.dates.length));
            if (props.dates.length === 1) {
                html = this.renderResourceRow(props.resources);
            }
            else {
                if (this.datesAboveResources) {
                    html = this.renderDayAndResourceRows(props.dates, props.resources);
                }
                else {
                    html = this.renderResourceAndDayRows(props.resources, props.dates);
                }
            }
            this.thead.innerHTML = html;
            this.processResourceEls(props.resources);
        };
        ResourceDayHeader.prototype.renderResourceRow = function (resources) {
            var _this = this;
            var cellHtmls = resources.map(function (resource) {
                return _this.renderResourceCell(resource, 1);
            });
            return this.buildTr(cellHtmls);
        };
        ResourceDayHeader.prototype.renderDayAndResourceRows = function (dates, resources) {
            var dateHtmls = [];
            var resourceHtmls = [];
            for (var _i = 0, dates_1 = dates; _i < dates_1.length; _i++) {
                var date = dates_1[_i];
                dateHtmls.push(this.renderDateCell(date, resources.length));
                for (var _a = 0, resources_1 = resources; _a < resources_1.length; _a++) {
                    var resource = resources_1[_a];
                    resourceHtmls.push(this.renderResourceCell(resource, 1, date));
                }
            }
            return this.buildTr(dateHtmls) +
                this.buildTr(resourceHtmls);
        };
        ResourceDayHeader.prototype.renderResourceAndDayRows = function (resources, dates) {
            var resourceHtmls = [];
            var dateHtmls = [];
            for (var _i = 0, resources_2 = resources; _i < resources_2.length; _i++) {
                var resource = resources_2[_i];
                resourceHtmls.push(this.renderResourceCell(resource, dates.length));
                for (var _a = 0, dates_2 = dates; _a < dates_2.length; _a++) {
                    var date = dates_2[_a];
                    dateHtmls.push(this.renderDateCell(date, 1, resource));
                }
            }
            return this.buildTr(resourceHtmls) +
                this.buildTr(dateHtmls);
        };
        // Cell Rendering Utils
        // ----------------------------------------------------------------------------------------------
        // a cell with the resource name. might be associated with a specific day
        ResourceDayHeader.prototype.renderResourceCell = function (resource, colspan, date) {
            var dateEnv = this.dateEnv;
            return '<th class="fc-resource-cell"' +
                ' data-resource-id="' + resource.id + '"' +
                (date ?
                    ' data-date="' + dateEnv.formatIso(date, { omitTime: true }) + '"' :
                    '') +
                (colspan > 1 ?
                    ' colspan="' + colspan + '"' :
                    '') +
                '>' +
                core.htmlEscape(this.resourceTextFunc(resource)) +
                '</th>';
        };
        // a cell with date text. might have a resource associated with it
        ResourceDayHeader.prototype.renderDateCell = function (date, colspan, resource) {
            var props = this.props;
            return core.renderDateCell(date, props.dateProfile, props.datesRepDistinctDays, props.dates.length * props.resources.length, this.dateFormat, this.context, colspan, resource ? 'data-resource-id="' + resource.id + '"' : '');
        };
        ResourceDayHeader.prototype.buildTr = function (cellHtmls) {
            if (!cellHtmls.length) {
                cellHtmls = ['<td>&nbsp;</td>'];
            }
            if (this.props.renderIntroHtml) {
                cellHtmls = [this.props.renderIntroHtml()].concat(cellHtmls);
            }
            if (this.isRtl) {
                cellHtmls.reverse();
            }
            return '<tr>' +
                cellHtmls.join('') +
                '</tr>';
        };
        // Post-rendering
        // ----------------------------------------------------------------------------------------------
        // given a container with already rendered resource cells
        ResourceDayHeader.prototype.processResourceEls = function (resources) {
            var _this = this;
            var view = this.view;
            core.findElements(this.thead, '.fc-resource-cell').forEach(function (node, col) {
                col = col % resources.length;
                if (_this.isRtl) {
                    col = resources.length - 1 - col;
                }
                var resource = resources[col];
                view.publiclyTrigger('resourceRender', [
                    {
                        resource: new ResourceApi(_this.calendar, resource),
                        el: node,
                        view: view
                    }
                ]);
            });
        };
        return ResourceDayHeader;
    }(core.Component));

    var AbstractResourceDayTable = /** @class */ (function () {
        function AbstractResourceDayTable(dayTable, resources) {
            this.dayTable = dayTable;
            this.resources = resources;
            this.resourceIndex = new ResourceIndex(resources);
            this.rowCnt = dayTable.rowCnt;
            this.colCnt = dayTable.colCnt * resources.length;
            this.cells = this.buildCells();
        }
        AbstractResourceDayTable.prototype.buildCells = function () {
            var _a = this, rowCnt = _a.rowCnt, dayTable = _a.dayTable, resources = _a.resources;
            var rows = [];
            for (var row = 0; row < rowCnt; row++) {
                var rowCells = [];
                for (var dateCol = 0; dateCol < dayTable.colCnt; dateCol++) {
                    for (var resourceCol = 0; resourceCol < resources.length; resourceCol++) {
                        var resource = resources[resourceCol];
                        var htmlAttrs = 'data-resource-id="' + resource.id + '"';
                        rowCells[this.computeCol(dateCol, resourceCol)] = {
                            date: dayTable.cells[row][dateCol].date,
                            resource: resource,
                            htmlAttrs: htmlAttrs
                        };
                    }
                }
                rows.push(rowCells);
            }
            return rows;
        };
        return AbstractResourceDayTable;
    }());
    /*
    resources over dates
    */
    var ResourceDayTable = /** @class */ (function (_super) {
        __extends(ResourceDayTable, _super);
        function ResourceDayTable() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        ResourceDayTable.prototype.computeCol = function (dateI, resourceI) {
            return resourceI * this.dayTable.colCnt + dateI;
        };
        /*
        all date ranges are intact
        */
        ResourceDayTable.prototype.computeColRanges = function (dateStartI, dateEndI, resourceI) {
            return [
                {
                    firstCol: this.computeCol(dateStartI, resourceI),
                    lastCol: this.computeCol(dateEndI, resourceI),
                    isStart: true,
                    isEnd: true
                }
            ];
        };
        return ResourceDayTable;
    }(AbstractResourceDayTable));
    /*
    dates over resources
    */
    var DayResourceTable = /** @class */ (function (_super) {
        __extends(DayResourceTable, _super);
        function DayResourceTable() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        DayResourceTable.prototype.computeCol = function (dateI, resourceI) {
            return dateI * this.resources.length + resourceI;
        };
        /*
        every single day is broken up
        */
        DayResourceTable.prototype.computeColRanges = function (dateStartI, dateEndI, resourceI) {
            var segs = [];
            for (var i = dateStartI; i <= dateEndI; i++) {
                var col = this.computeCol(i, resourceI);
                segs.push({
                    firstCol: col,
                    lastCol: col,
                    isStart: i === dateStartI,
                    isEnd: i === dateEndI
                });
            }
            return segs;
        };
        return DayResourceTable;
    }(AbstractResourceDayTable));
    var ResourceIndex = /** @class */ (function () {
        function ResourceIndex(resources) {
            var indicesById = {};
            var ids = [];
            for (var i = 0; i < resources.length; i++) {
                var id = resources[i].id;
                ids.push(id);
                indicesById[id] = i;
            }
            this.ids = ids;
            this.indicesById = indicesById;
            this.length = resources.length;
        }
        return ResourceIndex;
    }());
    /*
    TODO: just use ResourceHash somehow? could then use the generic ResourceSplitter
    */
    var VResourceSplitter = /** @class */ (function (_super) {
        __extends(VResourceSplitter, _super);
        function VResourceSplitter() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        VResourceSplitter.prototype.getKeyInfo = function (props) {
            var resourceDayTable = props.resourceDayTable;
            var hash = core.mapHash(resourceDayTable.resourceIndex.indicesById, function (i) {
                return resourceDayTable.resources[i]; // has `ui` AND `businessHours` keys!
            }); // :(
            hash[''] = {};
            return hash;
        };
        VResourceSplitter.prototype.getKeysForDateSpan = function (dateSpan) {
            return [dateSpan.resourceId || ''];
        };
        VResourceSplitter.prototype.getKeysForEventDef = function (eventDef) {
            var resourceIds = eventDef.resourceIds;
            if (!resourceIds.length) {
                return [''];
            }
            return resourceIds;
        };
        return VResourceSplitter;
    }(core.Splitter));
    // joiner
    var NO_SEGS = []; // for memoizing
    var VResourceJoiner = /** @class */ (function () {
        function VResourceJoiner() {
            this.joinDateSelection = core.memoize(this.joinSegs);
            this.joinBusinessHours = core.memoize(this.joinSegs);
            this.joinFgEvents = core.memoize(this.joinSegs);
            this.joinBgEvents = core.memoize(this.joinSegs);
            this.joinEventDrags = core.memoize(this.joinInteractions);
            this.joinEventResizes = core.memoize(this.joinInteractions);
        }
        /*
        propSets also has a '' key for things with no resource
        */
        VResourceJoiner.prototype.joinProps = function (propSets, resourceDayTable) {
            var dateSelectionSets = [];
            var businessHoursSets = [];
            var fgEventSets = [];
            var bgEventSets = [];
            var eventDrags = [];
            var eventResizes = [];
            var eventSelection = '';
            var keys = resourceDayTable.resourceIndex.ids.concat(['']); // add in the all-resource key
            for (var _i = 0, keys_1 = keys; _i < keys_1.length; _i++) {
                var key = keys_1[_i];
                var props = propSets[key];
                dateSelectionSets.push(props.dateSelectionSegs);
                businessHoursSets.push(key ? props.businessHourSegs : NO_SEGS); // don't include redundant all-resource businesshours
                fgEventSets.push(key ? props.fgEventSegs : NO_SEGS); // don't include fg all-resource segs
                bgEventSets.push(props.bgEventSegs);
                eventDrags.push(props.eventDrag);
                eventResizes.push(props.eventResize);
                eventSelection = eventSelection || props.eventSelection;
            }
            return {
                dateSelectionSegs: this.joinDateSelection.apply(this, [resourceDayTable].concat(dateSelectionSets)),
                businessHourSegs: this.joinBusinessHours.apply(this, [resourceDayTable].concat(businessHoursSets)),
                fgEventSegs: this.joinFgEvents.apply(this, [resourceDayTable].concat(fgEventSets)),
                bgEventSegs: this.joinBgEvents.apply(this, [resourceDayTable].concat(bgEventSets)),
                eventDrag: this.joinEventDrags.apply(this, [resourceDayTable].concat(eventDrags)),
                eventResize: this.joinEventResizes.apply(this, [resourceDayTable].concat(eventResizes)),
                eventSelection: eventSelection
            };
        };
        VResourceJoiner.prototype.joinSegs = function (resourceDayTable) {
            var segGroups = [];
            for (var _i = 1; _i < arguments.length; _i++) {
                segGroups[_i - 1] = arguments[_i];
            }
            var resourceCnt = resourceDayTable.resources.length;
            var transformedSegs = [];
            for (var i = 0; i < resourceCnt; i++) {
                for (var _a = 0, _b = segGroups[i]; _a < _b.length; _a++) {
                    var seg = _b[_a];
                    transformedSegs.push.apply(transformedSegs, this.transformSeg(seg, resourceDayTable, i));
                }
                for (var _c = 0, _d = segGroups[resourceCnt]; _c < _d.length; _c++) { // one beyond. the all-resource
                    var seg = _d[_c];
                    transformedSegs.push.apply(// one beyond. the all-resource
                    transformedSegs, this.transformSeg(seg, resourceDayTable, i));
                }
            }
            return transformedSegs;
        };
        /*
        for expanding non-resource segs to all resources.
        only for public use.
        no memoizing.
        */
        VResourceJoiner.prototype.expandSegs = function (resourceDayTable, segs) {
            var resourceCnt = resourceDayTable.resources.length;
            var transformedSegs = [];
            for (var i = 0; i < resourceCnt; i++) {
                for (var _i = 0, segs_1 = segs; _i < segs_1.length; _i++) {
                    var seg = segs_1[_i];
                    transformedSegs.push.apply(transformedSegs, this.transformSeg(seg, resourceDayTable, i));
                }
            }
            return transformedSegs;
        };
        VResourceJoiner.prototype.joinInteractions = function (resourceDayTable) {
            var interactions = [];
            for (var _i = 1; _i < arguments.length; _i++) {
                interactions[_i - 1] = arguments[_i];
            }
            var resourceCnt = resourceDayTable.resources.length;
            var affectedInstances = {};
            var transformedSegs = [];
            var isEvent = false;
            var sourceSeg = null;
            for (var i = 0; i < resourceCnt; i++) {
                var interaction = interactions[i];
                if (interaction) {
                    for (var _a = 0, _b = interaction.segs; _a < _b.length; _a++) {
                        var seg = _b[_a];
                        transformedSegs.push.apply(transformedSegs, this.transformSeg(seg, resourceDayTable, i) // TODO: templateify Interaction::segs
                        );
                    }
                    __assign(affectedInstances, interaction.affectedInstances);
                    isEvent = isEvent || interaction.isEvent;
                    sourceSeg = sourceSeg || interaction.sourceSeg;
                }
                if (interactions[resourceCnt]) { // one beyond. the all-resource
                    for (var _c = 0, _d = interactions[resourceCnt].segs; _c < _d.length; _c++) {
                        var seg = _d[_c];
                        transformedSegs.push.apply(transformedSegs, this.transformSeg(seg, resourceDayTable, i) // TODO: templateify Interaction::segs
                        );
                    }
                }
            }
            return {
                affectedInstances: affectedInstances,
                segs: transformedSegs,
                isEvent: isEvent,
                sourceSeg: sourceSeg
            };
        };
        return VResourceJoiner;
    }());

    /*
    doesn't accept grouping
    */
    function flattenResources(resourceStore, orderSpecs) {
        return buildRowNodes(resourceStore, [], orderSpecs, false, {}, true)
            .map(function (node) {
            return node.resource;
        });
    }
    function buildRowNodes(resourceStore, groupSpecs, orderSpecs, isVGrouping, expansions, expansionDefault) {
        var complexNodes = buildHierarchy(resourceStore, isVGrouping ? -1 : 1, groupSpecs, orderSpecs);
        var flatNodes = [];
        flattenNodes(complexNodes, flatNodes, isVGrouping, [], 0, expansions, expansionDefault);
        return flatNodes;
    }
    function flattenNodes(complexNodes, res, isVGrouping, rowSpans, depth, expansions, expansionDefault) {
        for (var i = 0; i < complexNodes.length; i++) {
            var complexNode = complexNodes[i];
            var group = complexNode.group;
            if (group) {
                if (isVGrouping) {
                    var firstRowIndex = res.length;
                    var rowSpanIndex = rowSpans.length;
                    flattenNodes(complexNode.children, res, isVGrouping, rowSpans.concat(0), depth, expansions, expansionDefault);
                    if (firstRowIndex < res.length) {
                        var firstRow = res[firstRowIndex];
                        var firstRowSpans = firstRow.rowSpans = firstRow.rowSpans.slice();
                        firstRowSpans[rowSpanIndex] = res.length - firstRowIndex;
                    }
                }
                else {
                    var id = group.spec.field + ':' + group.value;
                    var isExpanded = expansions[id] != null ? expansions[id] : expansionDefault;
                    res.push({ id: id, group: group, isExpanded: isExpanded });
                    if (isExpanded) {
                        flattenNodes(complexNode.children, res, isVGrouping, rowSpans, depth + 1, expansions, expansionDefault);
                    }
                }
            }
            else if (complexNode.resource) {
                var id = complexNode.resource.id;
                var isExpanded = expansions[id] != null ? expansions[id] : expansionDefault;
                res.push({
                    id: id,
                    rowSpans: rowSpans,
                    depth: depth,
                    isExpanded: isExpanded,
                    hasChildren: Boolean(complexNode.children.length),
                    resource: complexNode.resource,
                    resourceFields: complexNode.resourceFields
                });
                if (isExpanded) {
                    flattenNodes(complexNode.children, res, isVGrouping, rowSpans, depth + 1, expansions, expansionDefault);
                }
            }
        }
    }
    function buildHierarchy(resourceStore, maxDepth, groupSpecs, orderSpecs) {
        var resourceNodes = buildResourceNodes(resourceStore, orderSpecs);
        var builtNodes = [];
        for (var resourceId in resourceNodes) {
            var resourceNode = resourceNodes[resourceId];
            if (!resourceNode.resource.parentId) {
                insertResourceNode(resourceNode, builtNodes, groupSpecs, 0, maxDepth, orderSpecs);
            }
        }
        return builtNodes;
    }
    function buildResourceNodes(resourceStore, orderSpecs) {
        var nodeHash = {};
        for (var resourceId in resourceStore) {
            var resource = resourceStore[resourceId];
            nodeHash[resourceId] = {
                resource: resource,
                resourceFields: buildResourceFields(resource),
                children: []
            };
        }
        for (var resourceId in resourceStore) {
            var resource = resourceStore[resourceId];
            if (resource.parentId) {
                var parentNode = nodeHash[resource.parentId];
                if (parentNode) {
                    insertResourceNodeInSiblings(nodeHash[resourceId], parentNode.children, orderSpecs);
                }
            }
        }
        return nodeHash;
    }
    function insertResourceNode(resourceNode, nodes, groupSpecs, depth, maxDepth, orderSpecs) {
        if (groupSpecs.length && (maxDepth === -1 || depth <= maxDepth)) {
            var groupNode = ensureGroupNodes(resourceNode, nodes, groupSpecs[0]);
            insertResourceNode(resourceNode, groupNode.children, groupSpecs.slice(1), depth + 1, maxDepth, orderSpecs);
        }
        else {
            insertResourceNodeInSiblings(resourceNode, nodes, orderSpecs);
        }
    }
    function ensureGroupNodes(resourceNode, nodes, groupSpec) {
        var groupValue = resourceNode.resourceFields[groupSpec.field];
        var groupNode;
        var newGroupIndex;
        // find an existing group that matches, or determine the position for a new group
        if (groupSpec.order) {
            for (newGroupIndex = 0; newGroupIndex < nodes.length; newGroupIndex++) {
                var node = nodes[newGroupIndex];
                if (node.group) {
                    var cmp = core.flexibleCompare(groupValue, node.group.value) * groupSpec.order;
                    if (cmp === 0) {
                        groupNode = node;
                        break;
                    }
                    else if (cmp < 0) {
                        break;
                    }
                }
            }
        }
        else { // the groups are unordered
            for (newGroupIndex = 0; newGroupIndex < nodes.length; newGroupIndex++) {
                var node = nodes[newGroupIndex];
                if (node.group && groupValue === node.group.value) {
                    groupNode = node;
                    break;
                }
            }
        }
        if (!groupNode) {
            groupNode = {
                group: {
                    value: groupValue,
                    spec: groupSpec
                },
                children: []
            };
            nodes.splice(newGroupIndex, 0, groupNode);
        }
        return groupNode;
    }
    function insertResourceNodeInSiblings(resourceNode, siblings, orderSpecs) {
        var i;
        for (i = 0; i < siblings.length; i++) {
            var cmp = core.compareByFieldSpecs(siblings[i].resourceFields, resourceNode.resourceFields, orderSpecs);
            if (cmp > 0) { // went 1 past. insert at i
                break;
            }
        }
        siblings.splice(i, 0, resourceNode);
    }
    function buildResourceFields(resource) {
        var obj = __assign({}, resource.extendedProps, resource.ui, resource);
        delete obj.ui;
        delete obj.extendedProps;
        return obj;
    }
    function isGroupsEqual(group0, group1) {
        return group0.spec === group1.spec && group0.value === group1.value;
    }

    var main = core.createPlugin({
        reducers: [resourcesReducers],
        eventDefParsers: [parseEventDef],
        eventDragMutationMassagers: [massageEventDragMutation],
        eventDefMutationAppliers: [applyEventDefMutation],
        dateSelectionTransformers: [transformDateSelectionJoin],
        datePointTransforms: [transformDatePoint],
        dateSpanTransforms: [transformDateSpan],
        viewPropsTransformers: [ResourceDataAdder, ResourceEventConfigAdder],
        isPropsValid: isPropsValidWithResources,
        externalDefTransforms: [transformExternalDef],
        eventResizeJoinTransforms: [transformEventResizeJoin],
        viewContainerModifiers: [injectLicenseWarning],
        eventDropTransformers: [transformEventDrop],
        optionChangeHandlers: optionChangeHandlers
    });

    exports.AbstractResourceDayTable = AbstractResourceDayTable;
    exports.DayResourceTable = DayResourceTable;
    exports.ResourceApi = ResourceApi;
    exports.ResourceDayHeader = ResourceDayHeader;
    exports.ResourceDayTable = ResourceDayTable;
    exports.ResourceSplitter = ResourceSplitter;
    exports.VResourceJoiner = VResourceJoiner;
    exports.VResourceSplitter = VResourceSplitter;
    exports.buildResourceFields = buildResourceFields;
    exports.buildResourceTextFunc = buildResourceTextFunc;
    exports.buildRowNodes = buildRowNodes;
    exports.computeResourceEditable = computeResourceEditable;
    exports.default = main;
    exports.flattenResources = flattenResources;
    exports.isGroupsEqual = isGroupsEqual;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
/*!
FullCalendar Resource Day Grid Plugin v4.1.0
Docs & License: https://fullcalendar.io/scheduler
(c) 2019 Adam Shaw
*/
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('@fullcalendar/core'), require('@fullcalendar/resource-common'), require('@fullcalendar/daygrid')) :
    typeof define === 'function' && define.amd ? define(['exports', '@fullcalendar/core', '@fullcalendar/resource-common', '@fullcalendar/daygrid'], factory) :
    (global = global || self, factory(global.FullCalendarResourceDayGrid = {}, global.FullCalendar, global.FullCalendarResourceCommon, global.FullCalendarDayGrid));
}(this, function (exports, core, ResourceCommonPlugin, DayGridPlugin) { 'use strict';

    var ResourceCommonPlugin__default = 'default' in ResourceCommonPlugin ? ResourceCommonPlugin['default'] : ResourceCommonPlugin;
    var DayGridPlugin__default = 'default' in DayGridPlugin ? DayGridPlugin['default'] : DayGridPlugin;

    /*! *****************************************************************************
    Copyright (c) Microsoft Corporation. All rights reserved.
    Licensed under the Apache License, Version 2.0 (the "License"); you may not use
    this file except in compliance with the License. You may obtain a copy of the
    License at http://www.apache.org/licenses/LICENSE-2.0

    THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
    KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
    WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
    MERCHANTABLITY OR NON-INFRINGEMENT.

    See the Apache Version 2.0 License for specific language governing permissions
    and limitations under the License.
    ***************************************************************************** */
    /* global Reflect, Promise */

    var extendStatics = function(d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };

    function __extends(d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    }

    var __assign = function() {
        __assign = Object.assign || function __assign(t) {
            for (var s, i = 1, n = arguments.length; i < n; i++) {
                s = arguments[i];
                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
            }
            return t;
        };
        return __assign.apply(this, arguments);
    };

    var ResourceDayGrid = /** @class */ (function (_super) {
        __extends(ResourceDayGrid, _super);
        function ResourceDayGrid(context, dayGrid) {
            var _this = _super.call(this, context, dayGrid.el) || this;
            _this.splitter = new ResourceCommonPlugin.VResourceSplitter();
            _this.slicers = {};
            _this.joiner = new ResourceDayGridJoiner();
            _this.dayGrid = dayGrid;
            context.calendar.registerInteractiveComponent(_this, {
                el: _this.dayGrid.el
            });
            return _this;
        }
        ResourceDayGrid.prototype.destroy = function () {
            _super.prototype.destroy.call(this);
            this.calendar.unregisterInteractiveComponent(this);
        };
        ResourceDayGrid.prototype.render = function (props) {
            var _this = this;
            var dayGrid = this.dayGrid;
            var dateProfile = props.dateProfile, resourceDayTable = props.resourceDayTable, nextDayThreshold = props.nextDayThreshold;
            var splitProps = this.splitter.splitProps(props);
            this.slicers = core.mapHash(splitProps, function (split, resourceId) {
                return _this.slicers[resourceId] || new DayGridPlugin.DayGridSlicer();
            });
            var slicedProps = core.mapHash(this.slicers, function (slicer, resourceId) {
                return slicer.sliceProps(splitProps[resourceId], dateProfile, nextDayThreshold, dayGrid, resourceDayTable.dayTable);
            });
            dayGrid.allowAcrossResources = resourceDayTable.dayTable.colCnt === 1;
            dayGrid.receiveProps(__assign({}, this.joiner.joinProps(slicedProps, resourceDayTable), { dateProfile: dateProfile, cells: resourceDayTable.cells, isRigid: props.isRigid }));
        };
        ResourceDayGrid.prototype.buildPositionCaches = function () {
            this.dayGrid.buildPositionCaches();
        };
        ResourceDayGrid.prototype.queryHit = function (positionLeft, positionTop) {
            var rawHit = this.dayGrid.positionToHit(positionLeft, positionTop);
            if (rawHit) {
                return {
                    component: this.dayGrid,
                    dateSpan: {
                        range: rawHit.dateSpan.range,
                        allDay: rawHit.dateSpan.allDay,
                        resourceId: this.props.resourceDayTable.cells[rawHit.row][rawHit.col].resource.id
                    },
                    dayEl: rawHit.dayEl,
                    rect: {
                        left: rawHit.relativeRect.left,
                        right: rawHit.relativeRect.right,
                        top: rawHit.relativeRect.top,
                        bottom: rawHit.relativeRect.bottom
                    },
                    layer: 0
                };
            }
        };
        return ResourceDayGrid;
    }(core.DateComponent));
    var ResourceDayGridJoiner = /** @class */ (function (_super) {
        __extends(ResourceDayGridJoiner, _super);
        function ResourceDayGridJoiner() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        ResourceDayGridJoiner.prototype.transformSeg = function (seg, resourceDayTable, resourceI) {
            var colRanges = resourceDayTable.computeColRanges(seg.firstCol, seg.lastCol, resourceI);
            return colRanges.map(function (colRange) {
                return __assign({}, seg, colRange, { isStart: seg.isStart && colRange.isStart, isEnd: seg.isEnd && colRange.isEnd });
            });
        };
        return ResourceDayGridJoiner;
    }(ResourceCommonPlugin.VResourceJoiner));

    var ResourceDayGridView = /** @class */ (function (_super) {
        __extends(ResourceDayGridView, _super);
        function ResourceDayGridView(context, viewSpec, dateProfileGenerator, parentEl) {
            var _this = _super.call(this, context, viewSpec, dateProfileGenerator, parentEl) || this;
            _this.flattenResources = core.memoize(ResourceCommonPlugin.flattenResources);
            _this.buildResourceDayTable = core.memoize(buildResourceDayTable);
            _this.resourceOrderSpecs = core.parseFieldSpecs(_this.opt('resourceOrder'));
            if (_this.opt('columnHeader')) {
                _this.header = new ResourceCommonPlugin.ResourceDayHeader(_this.context, _this.el.querySelector('.fc-head-container'));
            }
            _this.resourceDayGrid = new ResourceDayGrid(context, _this.dayGrid);
            return _this;
        }
        ResourceDayGridView.prototype.destroy = function () {
            _super.prototype.destroy.call(this);
            if (this.header) {
                this.header.destroy();
            }
            this.resourceDayGrid.destroy();
        };
        ResourceDayGridView.prototype.render = function (props) {
            _super.prototype.render.call(this, props); // for flags for updateSize
            var resources = this.flattenResources(props.resourceStore, this.resourceOrderSpecs);
            var resourceDayTable = this.buildResourceDayTable(this.props.dateProfile, this.dateProfileGenerator, resources, this.opt('datesAboveResources'));
            if (this.header) {
                this.header.receiveProps({
                    resources: resources,
                    dates: resourceDayTable.dayTable.headerDates,
                    dateProfile: props.dateProfile,
                    datesRepDistinctDays: true,
                    renderIntroHtml: this.renderHeadIntroHtml
                });
            }
            this.resourceDayGrid.receiveProps({
                dateProfile: props.dateProfile,
                resourceDayTable: resourceDayTable,
                businessHours: props.businessHours,
                eventStore: props.eventStore,
                eventUiBases: props.eventUiBases,
                dateSelection: props.dateSelection,
                eventSelection: props.eventSelection,
                eventDrag: props.eventDrag,
                eventResize: props.eventResize,
                isRigid: this.hasRigidRows(),
                nextDayThreshold: this.nextDayThreshold
            });
        };
        ResourceDayGridView.needsResourceData = true; // for ResourceViewProps
        return ResourceDayGridView;
    }(DayGridPlugin.AbstractDayGridView));
    function buildResourceDayTable(dateProfile, dateProfileGenerator, resources, datesAboveResources) {
        var dayTable = DayGridPlugin.buildBasicDayTable(dateProfile, dateProfileGenerator);
        return datesAboveResources ?
            new ResourceCommonPlugin.DayResourceTable(dayTable, resources) :
            new ResourceCommonPlugin.ResourceDayTable(dayTable, resources);
    }

    var main = core.createPlugin({
        deps: [ResourceCommonPlugin__default, DayGridPlugin__default],
        defaultView: 'resourceDayGridDay',
        views: {
            resourceDayGrid: ResourceDayGridView,
            resourceDayGridDay: {
                type: 'resourceDayGrid',
                duration: { days: 1 }
            },
            resourceDayGridWeek: {
                type: 'resourceDayGrid',
                duration: { weeks: 1 }
            },
            resourceDayGridMonth: {
                type: 'resourceDayGrid',
                duration: { months: 1 },
                // TODO: wish we didn't have to C&P from dayGrid's file
                monthMode: true,
                fixedWeekCount: true
            }
        }
    });

    exports.ResourceDayGrid = ResourceDayGrid;
    exports.ResourceDayGridView = ResourceDayGridView;
    exports.default = main;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
/*!
FullCalendar Resource Time Grid Plugin v4.1.0
Docs & License: https://fullcalendar.io/scheduler
(c) 2019 Adam Shaw
*/
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('@fullcalendar/core'), require('@fullcalendar/resource-common'), require('@fullcalendar/timegrid'), require('@fullcalendar/resource-daygrid')) :
    typeof define === 'function' && define.amd ? define(['exports', '@fullcalendar/core', '@fullcalendar/resource-common', '@fullcalendar/timegrid', '@fullcalendar/resource-daygrid'], factory) :
    (global = global || self, factory(global.FullCalendarResourceTimeGrid = {}, global.FullCalendar, global.FullCalendarResourceCommon, global.FullCalendarTimeGrid, global.FullCalendarResourceDayGrid));
}(this, function (exports, core, ResourceCommonPlugin, TimeGridPlugin, resourceDaygrid) { 'use strict';

    var ResourceCommonPlugin__default = 'default' in ResourceCommonPlugin ? ResourceCommonPlugin['default'] : ResourceCommonPlugin;
    var TimeGridPlugin__default = 'default' in TimeGridPlugin ? TimeGridPlugin['default'] : TimeGridPlugin;

    /*! *****************************************************************************
    Copyright (c) Microsoft Corporation. All rights reserved.
    Licensed under the Apache License, Version 2.0 (the "License"); you may not use
    this file except in compliance with the License. You may obtain a copy of the
    License at http://www.apache.org/licenses/LICENSE-2.0

    THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
    KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
    WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
    MERCHANTABLITY OR NON-INFRINGEMENT.

    See the Apache Version 2.0 License for specific language governing permissions
    and limitations under the License.
    ***************************************************************************** */
    /* global Reflect, Promise */

    var extendStatics = function(d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };

    function __extends(d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    }

    var __assign = function() {
        __assign = Object.assign || function __assign(t) {
            for (var s, i = 1, n = arguments.length; i < n; i++) {
                s = arguments[i];
                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
            }
            return t;
        };
        return __assign.apply(this, arguments);
    };

    var ResourceTimeGrid = /** @class */ (function (_super) {
        __extends(ResourceTimeGrid, _super);
        function ResourceTimeGrid(context, timeGrid) {
            var _this = _super.call(this, context, timeGrid.el) || this;
            _this.buildDayRanges = core.memoize(TimeGridPlugin.buildDayRanges);
            _this.splitter = new ResourceCommonPlugin.VResourceSplitter();
            _this.slicers = {};
            _this.joiner = new ResourceTimeGridJoiner();
            _this.timeGrid = timeGrid;
            context.calendar.registerInteractiveComponent(_this, {
                el: _this.timeGrid.el
            });
            return _this;
        }
        ResourceTimeGrid.prototype.destroy = function () {
            this.calendar.unregisterInteractiveComponent(this);
        };
        ResourceTimeGrid.prototype.render = function (props) {
            var _this = this;
            var timeGrid = this.timeGrid;
            var dateProfile = props.dateProfile, resourceDayTable = props.resourceDayTable;
            var dayRanges = this.dayRanges = this.buildDayRanges(resourceDayTable.dayTable, dateProfile, this.dateEnv);
            var splitProps = this.splitter.splitProps(props);
            this.slicers = core.mapHash(splitProps, function (split, resourceId) {
                return _this.slicers[resourceId] || new TimeGridPlugin.TimeGridSlicer();
            });
            var slicedProps = core.mapHash(this.slicers, function (slicer, resourceId) {
                return slicer.sliceProps(splitProps[resourceId], dateProfile, null, timeGrid, dayRanges);
            });
            timeGrid.allowAcrossResources = dayRanges.length === 1;
            timeGrid.receiveProps(__assign({}, this.joiner.joinProps(slicedProps, resourceDayTable), { dateProfile: dateProfile, cells: resourceDayTable.cells[0] }));
        };
        ResourceTimeGrid.prototype.renderNowIndicator = function (date) {
            var timeGrid = this.timeGrid;
            var resourceDayTable = this.props.resourceDayTable;
            var nonResourceSegs = this.slicers[''].sliceNowDate(date, timeGrid, this.dayRanges);
            var segs = this.joiner.expandSegs(resourceDayTable, nonResourceSegs);
            timeGrid.renderNowIndicator(segs, date);
        };
        ResourceTimeGrid.prototype.buildPositionCaches = function () {
            this.timeGrid.buildPositionCaches();
        };
        ResourceTimeGrid.prototype.queryHit = function (positionLeft, positionTop) {
            var rawHit = this.timeGrid.positionToHit(positionLeft, positionTop);
            if (rawHit) {
                return {
                    component: this.timeGrid,
                    dateSpan: {
                        range: rawHit.dateSpan.range,
                        allDay: rawHit.dateSpan.allDay,
                        resourceId: this.props.resourceDayTable.cells[0][rawHit.col].resource.id
                    },
                    dayEl: rawHit.dayEl,
                    rect: {
                        left: rawHit.relativeRect.left,
                        right: rawHit.relativeRect.right,
                        top: rawHit.relativeRect.top,
                        bottom: rawHit.relativeRect.bottom
                    },
                    layer: 0
                };
            }
        };
        return ResourceTimeGrid;
    }(core.DateComponent));
    var ResourceTimeGridJoiner = /** @class */ (function (_super) {
        __extends(ResourceTimeGridJoiner, _super);
        function ResourceTimeGridJoiner() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        ResourceTimeGridJoiner.prototype.transformSeg = function (seg, resourceDayTable, resourceI) {
            return [
                __assign({}, seg, { col: resourceDayTable.computeCol(seg.col, resourceI) })
            ];
        };
        return ResourceTimeGridJoiner;
    }(ResourceCommonPlugin.VResourceJoiner));

    var ResourceTimeGridView = /** @class */ (function (_super) {
        __extends(ResourceTimeGridView, _super);
        function ResourceTimeGridView(context, viewSpec, dateProfileGenerator, parentEl) {
            var _this = _super.call(this, context, viewSpec, dateProfileGenerator, parentEl) || this;
            _this.flattenResources = core.memoize(ResourceCommonPlugin.flattenResources);
            _this.buildResourceDayTable = core.memoize(buildResourceDayTable);
            _this.resourceOrderSpecs = core.parseFieldSpecs(_this.opt('resourceOrder'));
            if (_this.opt('columnHeader')) {
                _this.header = new ResourceCommonPlugin.ResourceDayHeader(_this.context, _this.el.querySelector('.fc-head-container'));
            }
            _this.resourceTimeGrid = new ResourceTimeGrid(context, _this.timeGrid);
            if (_this.dayGrid) {
                _this.resourceDayGrid = new resourceDaygrid.ResourceDayGrid(context, _this.dayGrid);
            }
            return _this;
        }
        ResourceTimeGridView.prototype.destroy = function () {
            _super.prototype.destroy.call(this);
            if (this.header) {
                this.header.destroy();
            }
            this.resourceTimeGrid.destroy();
            if (this.resourceDayGrid) {
                this.resourceDayGrid.destroy();
            }
        };
        ResourceTimeGridView.prototype.render = function (props) {
            _super.prototype.render.call(this, props); // for flags for updateSize
            var splitProps = this.splitter.splitProps(props);
            var resources = this.flattenResources(props.resourceStore, this.resourceOrderSpecs);
            var resourceDayTable = this.buildResourceDayTable(this.props.dateProfile, this.dateProfileGenerator, resources, this.opt('datesAboveResources'));
            if (this.header) {
                this.header.receiveProps({
                    resources: resources,
                    dates: resourceDayTable.dayTable.headerDates,
                    dateProfile: props.dateProfile,
                    datesRepDistinctDays: true,
                    renderIntroHtml: this.renderHeadIntroHtml
                });
            }
            this.resourceTimeGrid.receiveProps(__assign({}, splitProps['timed'], { dateProfile: props.dateProfile, resourceDayTable: resourceDayTable }));
            if (this.resourceDayGrid) {
                this.resourceDayGrid.receiveProps(__assign({}, splitProps['allDay'], { dateProfile: props.dateProfile, resourceDayTable: resourceDayTable, isRigid: false, nextDayThreshold: this.nextDayThreshold }));
            }
        };
        ResourceTimeGridView.prototype.renderNowIndicator = function (date) {
            this.resourceTimeGrid.renderNowIndicator(date);
        };
        ResourceTimeGridView.needsResourceData = true; // for ResourceViewProps
        return ResourceTimeGridView;
    }(TimeGridPlugin.AbstractTimeGridView));
    function buildResourceDayTable(dateProfile, dateProfileGenerator, resources, datesAboveResources) {
        var dayTable = TimeGridPlugin.buildDayTable(dateProfile, dateProfileGenerator);
        return datesAboveResources ?
            new ResourceCommonPlugin.DayResourceTable(dayTable, resources) :
            new ResourceCommonPlugin.ResourceDayTable(dayTable, resources);
    }

    var main = core.createPlugin({
        deps: [ResourceCommonPlugin__default, TimeGridPlugin__default],
        defaultView: 'resourceTimeGridDay',
        views: {
            resourceTimeGrid: {
                class: ResourceTimeGridView,
                // TODO: wish we didn't have to C&P from timeGrid's file
                allDaySlot: true,
                slotDuration: '00:30:00',
                slotEventOverlap: true // a bad name. confused with overlap/constraint system
            },
            resourceTimeGridDay: {
                type: 'resourceTimeGrid',
                duration: { days: 1 }
            },
            resourceTimeGridWeek: {
                type: 'resourceTimeGrid',
                duration: { weeks: 1 }
            }
        }
    });

    exports.ResourceTimeGrid = ResourceTimeGrid;
    exports.ResourceTimeGridView = ResourceTimeGridView;
    exports.default = main;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
/*!
FullCalendar Timeline Plugin v4.1.0
Docs & License: https://fullcalendar.io/scheduler
(c) 2019 Adam Shaw
*/
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('@fullcalendar/core')) :
    typeof define === 'function' && define.amd ? define(['exports', '@fullcalendar/core'], factory) :
    (global = global || self, factory(global.FullCalendarTimeline = {}, global.FullCalendar));
}(this, function (exports, core) { 'use strict';

    /*! *****************************************************************************
    Copyright (c) Microsoft Corporation. All rights reserved.
    Licensed under the Apache License, Version 2.0 (the "License"); you may not use
    this file except in compliance with the License. You may obtain a copy of the
    License at http://www.apache.org/licenses/LICENSE-2.0

    THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
    KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
    WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
    MERCHANTABLITY OR NON-INFRINGEMENT.

    See the Apache Version 2.0 License for specific language governing permissions
    and limitations under the License.
    ***************************************************************************** */
    /* global Reflect, Promise */

    var extendStatics = function(d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };

    function __extends(d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    }

    var __assign = function() {
        __assign = Object.assign || function __assign(t) {
            for (var s, i = 1, n = arguments.length; i < n; i++) {
                s = arguments[i];
                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
            }
            return t;
        };
        return __assign.apply(this, arguments);
    };

    /*
    A rectangular area of content that lives within a Scroller.
    Can have "gutters", areas of dead spacing around the perimeter.
    Also very useful for forcing a width, which a Scroller cannot do alone.
    Has a content area that lives above a background area.
    */
    var ScrollerCanvas = /** @class */ (function () {
        function ScrollerCanvas() {
            this.gutters = {};
            this.el = core.htmlToElement("<div class=\"fc-scroller-canvas\"> <div class=\"fc-content\"></div> <div class=\"fc-bg\"></div> </div>");
            this.contentEl = this.el.querySelector('.fc-content');
            this.bgEl = this.el.querySelector('.fc-bg');
        }
        /*
        If falsy, resets all the gutters to 0
        */
        ScrollerCanvas.prototype.setGutters = function (gutters) {
            if (!gutters) {
                this.gutters = {};
            }
            else {
                __assign(this.gutters, gutters);
            }
            this.updateSize();
        };
        ScrollerCanvas.prototype.setWidth = function (width) {
            this.width = width;
            this.updateSize();
        };
        ScrollerCanvas.prototype.setMinWidth = function (minWidth) {
            this.minWidth = minWidth;
            this.updateSize();
        };
        ScrollerCanvas.prototype.clearWidth = function () {
            this.width = null;
            this.minWidth = null;
            this.updateSize();
        };
        ScrollerCanvas.prototype.updateSize = function () {
            var _a = this, gutters = _a.gutters, el = _a.el;
            // is border-box (width includes padding)
            core.forceClassName(el, 'fc-gutter-left', gutters.left);
            core.forceClassName(el, 'fc-gutter-right', gutters.right);
            core.forceClassName(el, 'fc-gutter-top', gutters.top);
            core.forceClassName(el, 'fc-gutter-bottom', gutters.bottom);
            core.applyStyle(el, {
                paddingLeft: gutters.left || '',
                paddingRight: gutters.right || '',
                paddingTop: gutters.top || '',
                paddingBottom: gutters.bottom || '',
                width: (this.width != null) ?
                    this.width + (gutters.left || 0) + (gutters.right || 0) :
                    '',
                minWidth: (this.minWidth != null) ?
                    this.minWidth + (gutters.left || 0) + (gutters.right || 0) :
                    ''
            });
            core.applyStyle(this.bgEl, {
                left: gutters.left || '',
                right: gutters.right || '',
                top: gutters.top || '',
                bottom: gutters.bottom || ''
            });
        };
        return ScrollerCanvas;
    }());

    var EnhancedScroller = /** @class */ (function (_super) {
        __extends(EnhancedScroller, _super);
        function EnhancedScroller(overflowX, overflowY) {
            var _this = _super.call(this, overflowX, overflowY) || this;
            // Scroll Events
            // ----------------------------------------------------------------------------------------------
            _this.reportScroll = function () {
                if (!_this.isScrolling) {
                    _this.reportScrollStart();
                }
                _this.trigger('scroll');
                _this.isMoving = true;
                _this.requestMovingEnd();
            };
            _this.reportScrollStart = function () {
                if (!_this.isScrolling) {
                    _this.isScrolling = true;
                    _this.trigger('scrollStart', _this.isTouching); // created in constructor
                }
            };
            // Touch Events
            // ----------------------------------------------------------------------------------------------
            // will fire *before* the scroll event is fired
            _this.reportTouchStart = function () {
                _this.isTouching = true;
            };
            _this.reportTouchEnd = function () {
                if (_this.isTouching) {
                    _this.isTouching = false;
                    // if touch scrolling was re-enabled during a recent touch scroll
                    // then unbind the handlers that are preventing it from happening.
                    if (_this.isTouchScrollEnabled) {
                        _this.unbindPreventTouchScroll(); // won't do anything if not bound
                    }
                    // if the user ended their touch, and the scroll area wasn't moving,
                    // we consider this to be the end of the scroll.
                    if (!_this.isMoving) {
                        _this.reportScrollEnd(); // won't fire if already ended
                    }
                }
            };
            _this.isScrolling = false;
            _this.isTouching = false;
            _this.isMoving = false;
            _this.isTouchScrollEnabled = true;
            _this.requestMovingEnd = core.debounce(_this.reportMovingEnd, 500);
            _this.canvas = new ScrollerCanvas();
            _this.el.appendChild(_this.canvas.el);
            _this.applyOverflow();
            _this.bindHandlers();
            return _this;
        }
        EnhancedScroller.prototype.destroy = function () {
            _super.prototype.destroy.call(this);
            this.unbindHandlers();
        };
        // Touch scroll prevention
        // ----------------------------------------------------------------------------------------------
        EnhancedScroller.prototype.disableTouchScroll = function () {
            this.isTouchScrollEnabled = false;
            this.bindPreventTouchScroll(); // will be unbound in enableTouchScroll or reportTouchEnd
        };
        EnhancedScroller.prototype.enableTouchScroll = function () {
            this.isTouchScrollEnabled = true;
            // only immediately unbind if a touch event is NOT in progress.
            // otherwise, it will be handled by reportTouchEnd.
            if (!this.isTouching) {
                this.unbindPreventTouchScroll();
            }
        };
        EnhancedScroller.prototype.bindPreventTouchScroll = function () {
            if (!this.preventTouchScrollHandler) {
                this.el.addEventListener('touchmove', (this.preventTouchScrollHandler = core.preventDefault));
            }
        };
        EnhancedScroller.prototype.unbindPreventTouchScroll = function () {
            if (this.preventTouchScrollHandler) {
                this.el.removeEventListener('touchmove', this.preventTouchScrollHandler);
                this.preventTouchScrollHandler = null;
            }
        };
        // Handlers
        // ----------------------------------------------------------------------------------------------
        EnhancedScroller.prototype.bindHandlers = function () {
            this.el.addEventListener('scroll', this.reportScroll);
            this.el.addEventListener('touchstart', this.reportTouchStart, { passive: true });
            this.el.addEventListener('touchend', this.reportTouchEnd);
        };
        EnhancedScroller.prototype.unbindHandlers = function () {
            this.el.removeEventListener('scroll', this.reportScroll);
            this.el.removeEventListener('touchstart', this.reportTouchStart, { passive: true });
            this.el.removeEventListener('touchend', this.reportTouchEnd);
        };
        EnhancedScroller.prototype.reportMovingEnd = function () {
            this.isMoving = false;
            // only end the scroll if not currently touching.
            // if touching, the scrolling will end later, on touchend.
            if (!this.isTouching) {
                this.reportScrollEnd();
            }
        };
        EnhancedScroller.prototype.reportScrollEnd = function () {
            if (this.isScrolling) {
                this.trigger('scrollEnd');
                this.isScrolling = false;
            }
        };
        // Horizontal Scroll Normalization
        // ----------------------------------------------------------------------------------------------
        // http://stackoverflow.com/questions/24276619/better-way-to-get-the-viewport-of-a-scrollable-div-in-rtl-mode/24394376#24394376
        // TODO: move all this to util functions
        /*
        If RTL, and scrolled to the left, returns NEGATIVE value (like Firefox)
        */
        EnhancedScroller.prototype.getScrollLeft = function () {
            var el = this.el;
            var direction = window.getComputedStyle(el).direction;
            var val = el.scrollLeft;
            if (direction === 'rtl') {
                switch (getRtlScrollSystem()) {
                    case 'positive':
                        val = (val + el.clientWidth) - el.scrollWidth;
                        break;
                    case 'reverse':
                        val = -val;
                        break;
                }
            }
            return val;
        };
        /*
        Accepts a NEGATIVE value for when scrolled in RTL
        */
        EnhancedScroller.prototype.setScrollLeft = function (val) {
            var el = this.el;
            var direction = window.getComputedStyle(el).direction;
            if (direction === 'rtl') {
                switch (getRtlScrollSystem()) {
                    case 'positive':
                        val = (val - el.clientWidth) + el.scrollWidth;
                        break;
                    case 'reverse':
                        val = -val;
                        break;
                }
            }
            el.scrollLeft = val;
        };
        /*
        Always returns the number of pixels scrolled from the leftmost position (even if RTL).
        Always positive.
        */
        EnhancedScroller.prototype.getScrollFromLeft = function () {
            var el = this.el;
            var direction = window.getComputedStyle(el).direction;
            var val = el.scrollLeft;
            if (direction === 'rtl') {
                switch (getRtlScrollSystem()) {
                    case 'negative':
                        val = (val - el.clientWidth) + el.scrollWidth;
                        break;
                    case 'reverse':
                        val = (-val - el.clientWidth) + el.scrollWidth;
                        break;
                }
            }
            return val;
        };
        return EnhancedScroller;
    }(core.ScrollComponent));
    core.EmitterMixin.mixInto(EnhancedScroller);
    // Horizontal Scroll System Detection
    // ----------------------------------------------------------------------------------------------
    var _rtlScrollSystem;
    function getRtlScrollSystem() {
        return _rtlScrollSystem || (_rtlScrollSystem = detectRtlScrollSystem());
    }
    function detectRtlScrollSystem() {
        var el = core.htmlToElement("<div style=\" position: absolute; top: -1000px; width: 1px; height: 1px; overflow: scroll; direction: rtl; font-size: 100px; \">A</div>");
        document.body.appendChild(el);
        var system;
        if (el.scrollLeft > 0) {
            system = 'positive';
        }
        else {
            el.scrollLeft = 1;
            if (el.scrollLeft > 0) {
                system = 'reverse';
            }
            else {
                system = 'negative';
            }
        }
        core.removeElement(el);
        return system;
    }

    /*
    A Scroller, but with a wrapping div that allows "clipping" away of native scrollbars,
    giving the appearance that there are no scrollbars.
    */
    var ClippedScroller = /** @class */ (function () {
        /*
        Received overflows can be set to 'clipped', meaning scrollbars shouldn't be visible
        to the user, but the area should still scroll.
        */
        function ClippedScroller(overflowX, overflowY, parentEl) {
            this.isHScrollbarsClipped = false;
            this.isVScrollbarsClipped = false;
            if (overflowX === 'clipped-scroll') {
                overflowX = 'scroll';
                this.isHScrollbarsClipped = true;
            }
            if (overflowY === 'clipped-scroll') {
                overflowY = 'scroll';
                this.isVScrollbarsClipped = true;
            }
            this.enhancedScroll = new EnhancedScroller(overflowX, overflowY);
            parentEl.appendChild(this.el = core.createElement('div', {
                className: 'fc-scroller-clip'
            }));
            this.el.appendChild(this.enhancedScroll.el);
        }
        ClippedScroller.prototype.destroy = function () {
            core.removeElement(this.el);
        };
        ClippedScroller.prototype.updateSize = function () {
            var enhancedScroll = this.enhancedScroll;
            var scrollEl = enhancedScroll.el;
            var edges = core.computeEdges(scrollEl);
            var cssProps = { marginLeft: 0, marginRight: 0, marginTop: 0, marginBottom: 0 };
            // give the inner scrolling div negative margins so that its scrollbars
            // are nudged outside of the bounding box of the wrapper, which is overflow:hidden
            if (this.isVScrollbarsClipped) {
                cssProps.marginLeft = -edges.scrollbarLeft;
                cssProps.marginRight = -edges.scrollbarRight;
            }
            if (this.isHScrollbarsClipped) {
                cssProps.marginBottom = -edges.scrollbarBottom;
            }
            core.applyStyle(scrollEl, cssProps);
            // if we are attempting to hide the scrollbars offscreen, OSX/iOS will still
            // display the floating scrollbars. attach a className to force-hide them.
            if ((this.isHScrollbarsClipped || (enhancedScroll.overflowX === 'hidden')) && // should never show?
                (this.isVScrollbarsClipped || (enhancedScroll.overflowY === 'hidden')) && // should never show?
                !( // doesn't have any scrollbar mass
                edges.scrollbarLeft ||
                    edges.scrollbarRight ||
                    edges.scrollbarBottom)) {
                scrollEl.classList.add('fc-no-scrollbars');
            }
            else {
                scrollEl.classList.remove('fc-no-scrollbars');
            }
        };
        ClippedScroller.prototype.setHeight = function (height) {
            this.enhancedScroll.setHeight(height);
        };
        /*
        Accounts for 'clipped' scrollbars
        */
        ClippedScroller.prototype.getScrollbarWidths = function () {
            var widths = this.enhancedScroll.getScrollbarWidths();
            if (this.isVScrollbarsClipped) {
                widths.left = 0;
                widths.right = 0;
            }
            if (this.isHScrollbarsClipped) {
                widths.bottom = 0;
            }
            return widths;
        };
        return ClippedScroller;
    }());

    var ScrollJoiner = /** @class */ (function () {
        function ScrollJoiner(axis, scrollers) {
            this.axis = axis;
            this.scrollers = scrollers;
            for (var _i = 0, _a = this.scrollers; _i < _a.length; _i++) {
                var scroller = _a[_i];
                this.initScroller(scroller);
            }
        }
        ScrollJoiner.prototype.initScroller = function (scroller) {
            var _this = this;
            var enhancedScroll = scroller.enhancedScroll;
            // when the user scrolls via mousewheel, we know for sure the target
            // scroller should be the master. capture the various x-browser events that fire.
            var onScroll = function () {
                _this.assignMasterScroller(scroller);
            };
            'wheel mousewheel DomMouseScroll MozMousePixelScroll'.split(' ').forEach(function (evName) {
                enhancedScroll.el.addEventListener(evName, onScroll);
            });
            enhancedScroll
                .on('scrollStart', function () {
                if (!_this.masterScroller) {
                    _this.assignMasterScroller(scroller);
                }
            })
                .on('scroll', function () {
                if (scroller === _this.masterScroller) {
                    for (var _i = 0, _a = _this.scrollers; _i < _a.length; _i++) {
                        var otherScroller = _a[_i];
                        if (otherScroller !== scroller) {
                            switch (_this.axis) {
                                case 'horizontal':
                                    otherScroller.enhancedScroll.el.scrollLeft = enhancedScroll.el.scrollLeft;
                                    break;
                                case 'vertical':
                                    otherScroller.enhancedScroll.setScrollTop(enhancedScroll.getScrollTop());
                                    break;
                            }
                        }
                    }
                }
            })
                .on('scrollEnd', function () {
                if (scroller === _this.masterScroller) {
                    _this.unassignMasterScroller();
                }
            });
        };
        ScrollJoiner.prototype.assignMasterScroller = function (scroller) {
            this.unassignMasterScroller();
            this.masterScroller = scroller;
            for (var _i = 0, _a = this.scrollers; _i < _a.length; _i++) {
                var otherScroller = _a[_i];
                if (otherScroller !== scroller) {
                    otherScroller.enhancedScroll.disableTouchScroll();
                }
            }
        };
        ScrollJoiner.prototype.unassignMasterScroller = function () {
            if (this.masterScroller) {
                for (var _i = 0, _a = this.scrollers; _i < _a.length; _i++) {
                    var otherScroller = _a[_i];
                    otherScroller.enhancedScroll.enableTouchScroll();
                }
                this.masterScroller = null;
            }
        };
        ScrollJoiner.prototype.update = function () {
            var allWidths = this.scrollers.map(function (scroller) { return scroller.getScrollbarWidths(); });
            var maxLeft = 0;
            var maxRight = 0;
            var maxTop = 0;
            var maxBottom = 0;
            var widths;
            var i;
            for (var _i = 0, allWidths_1 = allWidths; _i < allWidths_1.length; _i++) {
                widths = allWidths_1[_i];
                maxLeft = Math.max(maxLeft, widths.left);
                maxRight = Math.max(maxRight, widths.right);
                maxTop = Math.max(maxTop, widths.top);
                maxBottom = Math.max(maxBottom, widths.bottom);
            }
            for (i = 0; i < this.scrollers.length; i++) {
                var scroller = this.scrollers[i];
                widths = allWidths[i];
                scroller.enhancedScroll.canvas.setGutters(this.axis === 'horizontal' ?
                    {
                        left: maxLeft - widths.left,
                        right: maxRight - widths.right
                    } :
                    {
                        top: maxTop - widths.top,
                        bottom: maxBottom - widths.bottom
                    });
            }
        };
        return ScrollJoiner;
    }());

    var HeaderBodyLayout = /** @class */ (function () {
        /*
        verticalScroll = 'auto' | 'clipped-scroll'
        */
        function HeaderBodyLayout(headerContainerEl, bodyContainerEl, verticalScroll) {
            this.headerScroller = new ClippedScroller('clipped-scroll', 'hidden', headerContainerEl);
            this.bodyScroller = new ClippedScroller('auto', verticalScroll, bodyContainerEl);
            this.scrollJoiner = new ScrollJoiner('horizontal', [
                this.headerScroller,
                this.bodyScroller
            ]);
        }
        HeaderBodyLayout.prototype.destroy = function () {
            this.headerScroller.destroy();
            this.bodyScroller.destroy();
        };
        HeaderBodyLayout.prototype.setHeight = function (totalHeight, isAuto) {
            var bodyHeight;
            if (isAuto) {
                bodyHeight = 'auto';
            }
            else {
                bodyHeight = totalHeight - this.queryHeadHeight();
            }
            this.bodyScroller.setHeight(bodyHeight);
            this.headerScroller.updateSize(); // adjusts gutters and classNames
            this.bodyScroller.updateSize(); // adjusts gutters and classNames
            this.scrollJoiner.update();
        };
        HeaderBodyLayout.prototype.queryHeadHeight = function () {
            return this.headerScroller.enhancedScroll.canvas.contentEl.offsetHeight; // flawed?
        };
        return HeaderBodyLayout;
    }());

    var TimelineHeader = /** @class */ (function (_super) {
        __extends(TimelineHeader, _super);
        function TimelineHeader(context, parentEl) {
            var _this = _super.call(this, context) || this;
            parentEl.appendChild(_this.tableEl = core.createElement('table', {
                className: _this.theme.getClass('tableGrid')
            }));
            return _this;
        }
        TimelineHeader.prototype.destroy = function () {
            core.removeElement(this.tableEl);
            _super.prototype.destroy.call(this);
        };
        TimelineHeader.prototype.render = function (props) {
            this.renderDates(props.tDateProfile);
        };
        TimelineHeader.prototype.renderDates = function (tDateProfile) {
            var _a = this, dateEnv = _a.dateEnv, theme = _a.theme;
            var cellRows = tDateProfile.cellRows;
            var lastRow = cellRows[cellRows.length - 1];
            var isChrono = core.asRoughMs(tDateProfile.labelInterval) > core.asRoughMs(tDateProfile.slotDuration);
            var oneDay = core.isSingleDay(tDateProfile.slotDuration);
            var html = '<colgroup>';
            // needs to be a col for each body slat. header cells will have colspans
            for (var i = tDateProfile.slotCnt - 1; i >= 0; i--) {
                html += '<col/>';
            }
            html += '</colgroup>';
            html += '<tbody>';
            for (var _i = 0, cellRows_1 = cellRows; _i < cellRows_1.length; _i++) {
                var rowCells = cellRows_1[_i];
                var isLast = rowCells === lastRow;
                html += '<tr' + (isChrono && isLast ? ' class="fc-chrono"' : '') + '>';
                for (var _b = 0, rowCells_1 = rowCells; _b < rowCells_1.length; _b++) {
                    var cell = rowCells_1[_b];
                    var headerCellClassNames = [theme.getClass('widgetHeader')];
                    if (cell.isWeekStart) {
                        headerCellClassNames.push('fc-em-cell');
                    }
                    if (oneDay) {
                        headerCellClassNames = headerCellClassNames.concat(core.getDayClasses(cell.date, this.props.dateProfile, this.context, true) // adds "today" class and other day-based classes
                        );
                    }
                    html +=
                        '<th class="' + headerCellClassNames.join(' ') + '"' +
                            ' data-date="' + dateEnv.formatIso(cell.date, { omitTime: !tDateProfile.isTimeScale, omitTimeZoneOffset: true }) + '"' +
                            (cell.colspan > 1 ? ' colspan="' + cell.colspan + '"' : '') +
                            '>' +
                            '<div class="fc-cell-content">' +
                            cell.spanHtml +
                            '</div>' +
                            '</th>';
                }
                html += '</tr>';
            }
            html += '</tbody>';
            this.tableEl.innerHTML = html; // TODO: does this work cross-browser?
            this.slatColEls = core.findElements(this.tableEl, 'col');
            this.innerEls = core.findElements(this.tableEl.querySelector('tr:last-child'), // compound selector won't work because of query-root problem
            'th .fc-cell-text');
            core.findElements(this.tableEl.querySelectorAll('tr:not(:last-child)'), // compound selector won't work because of query-root problem
            'th .fc-cell-text').forEach(function (innerEl) {
                innerEl.classList.add('fc-sticky');
            });
        };
        return TimelineHeader;
    }(core.Component));

    var TimelineSlats = /** @class */ (function (_super) {
        __extends(TimelineSlats, _super);
        function TimelineSlats(context, parentEl) {
            var _this = _super.call(this, context) || this;
            parentEl.appendChild(_this.el = core.createElement('div', { className: 'fc-slats' }));
            return _this;
        }
        TimelineSlats.prototype.destroy = function () {
            core.removeElement(this.el);
            _super.prototype.destroy.call(this);
        };
        TimelineSlats.prototype.render = function (props) {
            this.renderDates(props.tDateProfile);
        };
        TimelineSlats.prototype.renderDates = function (tDateProfile) {
            var _a = this, theme = _a.theme, view = _a.view, dateEnv = _a.dateEnv;
            var slotDates = tDateProfile.slotDates, isWeekStarts = tDateProfile.isWeekStarts;
            var html = '<table class="' + theme.getClass('tableGrid') + '">' +
                '<colgroup>';
            for (var i = 0; i < slotDates.length; i++) {
                html += '<col/>';
            }
            html += '</colgroup>';
            html += '<tbody><tr>';
            for (var i = 0; i < slotDates.length; i++) {
                html += this.slatCellHtml(slotDates[i], isWeekStarts[i], tDateProfile);
            }
            html += '</tr></tbody></table>';
            this.el.innerHTML = html;
            this.slatColEls = core.findElements(this.el, 'col');
            this.slatEls = core.findElements(this.el, 'td');
            for (var i = 0; i < slotDates.length; i++) {
                view.publiclyTrigger('dayRender', [
                    {
                        date: dateEnv.toDate(slotDates[i]),
                        el: this.slatEls[i],
                        view: view
                    }
                ]);
            }
            this.outerCoordCache = new core.PositionCache(this.el, this.slatEls, true, // isHorizontal
            false // isVertical
            );
            // for the inner divs within the slats
            // used for event rendering and scrollTime, to disregard slat border
            this.innerCoordCache = new core.PositionCache(this.el, core.findChildren(this.slatEls, 'div'), true, // isHorizontal
            false // isVertical
            );
        };
        TimelineSlats.prototype.slatCellHtml = function (date, isEm, tDateProfile) {
            var _a = this, theme = _a.theme, dateEnv = _a.dateEnv;
            var classes;
            if (tDateProfile.isTimeScale) {
                classes = [];
                classes.push(core.isInt(dateEnv.countDurationsBetween(tDateProfile.normalizedRange.start, date, tDateProfile.labelInterval)) ?
                    'fc-major' :
                    'fc-minor');
            }
            else {
                classes = core.getDayClasses(date, this.props.dateProfile, this.context);
                classes.push('fc-day');
            }
            classes.unshift(theme.getClass('widgetContent'));
            if (isEm) {
                classes.push('fc-em-cell');
            }
            return '<td class="' + classes.join(' ') + '"' +
                ' data-date="' + dateEnv.formatIso(date, { omitTime: !tDateProfile.isTimeScale, omitTimeZoneOffset: true }) + '"' +
                '><div></div></td>';
        };
        TimelineSlats.prototype.updateSize = function () {
            this.outerCoordCache.build();
            this.innerCoordCache.build();
        };
        TimelineSlats.prototype.positionToHit = function (leftPosition) {
            var outerCoordCache = this.outerCoordCache;
            var tDateProfile = this.props.tDateProfile;
            var slatIndex = outerCoordCache.leftToIndex(leftPosition);
            if (slatIndex != null) {
                // somewhat similar to what TimeGrid does. consolidate?
                var slatWidth = outerCoordCache.getWidth(slatIndex);
                var partial = this.isRtl ?
                    (outerCoordCache.rights[slatIndex] - leftPosition) / slatWidth :
                    (leftPosition - outerCoordCache.lefts[slatIndex]) / slatWidth;
                var localSnapIndex = Math.floor(partial * tDateProfile.snapsPerSlot);
                var start = this.dateEnv.add(tDateProfile.slotDates[slatIndex], core.multiplyDuration(tDateProfile.snapDuration, localSnapIndex));
                var end = this.dateEnv.add(start, tDateProfile.snapDuration);
                return {
                    dateSpan: {
                        range: { start: start, end: end },
                        allDay: !this.props.tDateProfile.isTimeScale
                    },
                    dayEl: this.slatColEls[slatIndex],
                    left: outerCoordCache.lefts[slatIndex],
                    right: outerCoordCache.rights[slatIndex]
                };
            }
            return null;
        };
        return TimelineSlats;
    }(core.Component));

    var MIN_AUTO_LABELS = 18; // more than `12` months but less that `24` hours
    var MAX_AUTO_SLOTS_PER_LABEL = 6; // allows 6 10-min slots in an hour
    var MAX_AUTO_CELLS = 200; // allows 4-days to have a :30 slot duration
    core.config.MAX_TIMELINE_SLOTS = 1000;
    // potential nice values for slot-duration and interval-duration
    var STOCK_SUB_DURATIONS = [
        { years: 1 },
        { months: 1 },
        { days: 1 },
        { hours: 1 },
        { minutes: 30 },
        { minutes: 15 },
        { minutes: 10 },
        { minutes: 5 },
        { minutes: 1 },
        { seconds: 30 },
        { seconds: 15 },
        { seconds: 10 },
        { seconds: 5 },
        { seconds: 1 },
        { milliseconds: 500 },
        { milliseconds: 100 },
        { milliseconds: 10 },
        { milliseconds: 1 }
    ];
    function buildTimelineDateProfile(dateProfile, view) {
        var dateEnv = view.dateEnv;
        var tDateProfile = {
            labelInterval: queryDurationOption(view, 'slotLabelInterval'),
            slotDuration: queryDurationOption(view, 'slotDuration')
        };
        validateLabelAndSlot(tDateProfile, dateProfile, dateEnv); // validate after computed grid duration
        ensureLabelInterval(tDateProfile, dateProfile, dateEnv);
        ensureSlotDuration(tDateProfile, dateProfile, dateEnv);
        var input = view.opt('slotLabelFormat');
        var rawFormats = Array.isArray(input) ?
            input
            : (input != null) ?
                [input]
                :
                    computeHeaderFormats(tDateProfile, dateProfile, dateEnv, view);
        tDateProfile.headerFormats = rawFormats.map(function (rawFormat) {
            return core.createFormatter(rawFormat);
        });
        tDateProfile.isTimeScale = Boolean(tDateProfile.slotDuration.milliseconds);
        var largeUnit = null;
        if (!tDateProfile.isTimeScale) {
            var slotUnit = core.greatestDurationDenominator(tDateProfile.slotDuration).unit;
            if (/year|month|week/.test(slotUnit)) {
                largeUnit = slotUnit;
            }
        }
        tDateProfile.largeUnit = largeUnit;
        tDateProfile.emphasizeWeeks =
            core.isSingleDay(tDateProfile.slotDuration) &&
                currentRangeAs('weeks', dateProfile, dateEnv) >= 2 &&
                !view.opt('businessHours');
        /*
        console.log('label interval =', timelineView.labelInterval.humanize())
        console.log('slot duration =', timelineView.slotDuration.humanize())
        console.log('header formats =', timelineView.headerFormats)
        console.log('isTimeScale', timelineView.isTimeScale)
        console.log('largeUnit', timelineView.largeUnit)
        */
        var rawSnapDuration = view.opt('snapDuration');
        var snapDuration;
        var snapsPerSlot;
        if (rawSnapDuration) {
            snapDuration = core.createDuration(rawSnapDuration);
            snapsPerSlot = core.wholeDivideDurations(tDateProfile.slotDuration, snapDuration);
            // ^ TODO: warning if not whole?
        }
        if (snapsPerSlot == null) {
            snapDuration = tDateProfile.slotDuration;
            snapsPerSlot = 1;
        }
        tDateProfile.snapDuration = snapDuration;
        tDateProfile.snapsPerSlot = snapsPerSlot;
        // more...
        var timeWindowMs = core.asRoughMs(dateProfile.maxTime) - core.asRoughMs(dateProfile.minTime);
        // TODO: why not use normalizeRange!?
        var normalizedStart = normalizeDate(dateProfile.renderRange.start, tDateProfile, dateEnv);
        var normalizedEnd = normalizeDate(dateProfile.renderRange.end, tDateProfile, dateEnv);
        // apply minTime/maxTime
        // TODO: View should be responsible.
        if (tDateProfile.isTimeScale) {
            normalizedStart = dateEnv.add(normalizedStart, dateProfile.minTime);
            normalizedEnd = dateEnv.add(core.addDays(normalizedEnd, -1), dateProfile.maxTime);
        }
        tDateProfile.timeWindowMs = timeWindowMs;
        tDateProfile.normalizedRange = { start: normalizedStart, end: normalizedEnd };
        var slotDates = [];
        var date = normalizedStart;
        while (date < normalizedEnd) {
            if (isValidDate(date, tDateProfile, dateProfile, view)) {
                slotDates.push(date);
            }
            date = dateEnv.add(date, tDateProfile.slotDuration);
        }
        tDateProfile.slotDates = slotDates;
        // more...
        var snapIndex = -1;
        var snapDiff = 0; // index of the diff :(
        var snapDiffToIndex = [];
        var snapIndexToDiff = [];
        date = normalizedStart;
        while (date < normalizedEnd) {
            if (isValidDate(date, tDateProfile, dateProfile, view)) {
                snapIndex++;
                snapDiffToIndex.push(snapIndex);
                snapIndexToDiff.push(snapDiff);
            }
            else {
                snapDiffToIndex.push(snapIndex + 0.5);
            }
            date = dateEnv.add(date, tDateProfile.snapDuration);
            snapDiff++;
        }
        tDateProfile.snapDiffToIndex = snapDiffToIndex;
        tDateProfile.snapIndexToDiff = snapIndexToDiff;
        tDateProfile.snapCnt = snapIndex + 1; // is always one behind
        tDateProfile.slotCnt = tDateProfile.snapCnt / tDateProfile.snapsPerSlot;
        // more...
        tDateProfile.isWeekStarts = buildIsWeekStarts(tDateProfile, dateEnv);
        tDateProfile.cellRows = buildCellRows(tDateProfile, dateEnv, view);
        return tDateProfile;
    }
    /*
    snaps to appropriate unit
    */
    function normalizeDate(date, tDateProfile, dateEnv) {
        var normalDate = date;
        if (!tDateProfile.isTimeScale) {
            normalDate = core.startOfDay(normalDate);
            if (tDateProfile.largeUnit) {
                normalDate = dateEnv.startOf(normalDate, tDateProfile.largeUnit);
            }
        }
        return normalDate;
    }
    /*
    snaps to appropriate unit
    */
    function normalizeRange(range, tDateProfile, dateEnv) {
        if (!tDateProfile.isTimeScale) {
            range = core.computeVisibleDayRange(range);
            if (tDateProfile.largeUnit) {
                var dayRange = range; // preserve original result
                range = {
                    start: dateEnv.startOf(range.start, tDateProfile.largeUnit),
                    end: dateEnv.startOf(range.end, tDateProfile.largeUnit)
                };
                // if date is partially through the interval, or is in the same interval as the start,
                // make the exclusive end be the *next* interval
                if (range.end.valueOf() !== dayRange.end.valueOf() || range.end <= range.start) {
                    range = {
                        start: range.start,
                        end: dateEnv.add(range.end, tDateProfile.slotDuration)
                    };
                }
            }
        }
        return range;
    }
    function isValidDate(date, tDateProfile, dateProfile, view) {
        if (view.dateProfileGenerator.isHiddenDay(date)) {
            return false;
        }
        else if (tDateProfile.isTimeScale) {
            // determine if the time is within minTime/maxTime, which may have wacky values
            var day = core.startOfDay(date);
            var timeMs = date.valueOf() - day.valueOf();
            var ms = timeMs - core.asRoughMs(dateProfile.minTime); // milliseconds since minTime
            ms = ((ms % 86400000) + 86400000) % 86400000; // make negative values wrap to 24hr clock
            return ms < tDateProfile.timeWindowMs; // before the maxTime?
        }
        else {
            return true;
        }
    }
    function queryDurationOption(view, name) {
        var input = view.opt(name);
        if (input != null) {
            return core.createDuration(input);
        }
    }
    function validateLabelAndSlot(tDateProfile, dateProfile, dateEnv) {
        var currentRange = dateProfile.currentRange;
        // make sure labelInterval doesn't exceed the max number of cells
        if (tDateProfile.labelInterval) {
            var labelCnt = dateEnv.countDurationsBetween(currentRange.start, currentRange.end, tDateProfile.labelInterval);
            if (labelCnt > core.config.MAX_TIMELINE_SLOTS) {
                console.warn('slotLabelInterval results in too many cells');
                tDateProfile.labelInterval = null;
            }
        }
        // make sure slotDuration doesn't exceed the maximum number of cells
        if (tDateProfile.slotDuration) {
            var slotCnt = dateEnv.countDurationsBetween(currentRange.start, currentRange.end, tDateProfile.slotDuration);
            if (slotCnt > core.config.MAX_TIMELINE_SLOTS) {
                console.warn('slotDuration results in too many cells');
                tDateProfile.slotDuration = null;
            }
        }
        // make sure labelInterval is a multiple of slotDuration
        if (tDateProfile.labelInterval && tDateProfile.slotDuration) {
            var slotsPerLabel = core.wholeDivideDurations(tDateProfile.labelInterval, tDateProfile.slotDuration);
            if (slotsPerLabel === null || slotsPerLabel < 1) {
                console.warn('slotLabelInterval must be a multiple of slotDuration');
                tDateProfile.slotDuration = null;
            }
        }
    }
    function ensureLabelInterval(tDateProfile, dateProfile, dateEnv) {
        var currentRange = dateProfile.currentRange;
        var labelInterval = tDateProfile.labelInterval;
        if (!labelInterval) {
            // compute based off the slot duration
            // find the largest label interval with an acceptable slots-per-label
            var input = void 0;
            if (tDateProfile.slotDuration) {
                for (var _i = 0, STOCK_SUB_DURATIONS_1 = STOCK_SUB_DURATIONS; _i < STOCK_SUB_DURATIONS_1.length; _i++) {
                    input = STOCK_SUB_DURATIONS_1[_i];
                    var tryLabelInterval = core.createDuration(input);
                    var slotsPerLabel = core.wholeDivideDurations(tryLabelInterval, tDateProfile.slotDuration);
                    if (slotsPerLabel !== null && slotsPerLabel <= MAX_AUTO_SLOTS_PER_LABEL) {
                        labelInterval = tryLabelInterval;
                        break;
                    }
                }
                // use the slot duration as a last resort
                if (!labelInterval) {
                    labelInterval = tDateProfile.slotDuration;
                }
                // compute based off the view's duration
                // find the largest label interval that yields the minimum number of labels
            }
            else {
                for (var _a = 0, STOCK_SUB_DURATIONS_2 = STOCK_SUB_DURATIONS; _a < STOCK_SUB_DURATIONS_2.length; _a++) {
                    input = STOCK_SUB_DURATIONS_2[_a];
                    labelInterval = core.createDuration(input);
                    var labelCnt = dateEnv.countDurationsBetween(currentRange.start, currentRange.end, labelInterval);
                    if (labelCnt >= MIN_AUTO_LABELS) {
                        break;
                    }
                }
            }
            tDateProfile.labelInterval = labelInterval;
        }
        return labelInterval;
    }
    function ensureSlotDuration(tDateProfile, dateProfile, dateEnv) {
        var currentRange = dateProfile.currentRange;
        var slotDuration = tDateProfile.slotDuration;
        if (!slotDuration) {
            var labelInterval = ensureLabelInterval(tDateProfile, dateProfile, dateEnv); // will compute if necessary
            // compute based off the label interval
            // find the largest slot duration that is different from labelInterval, but still acceptable
            for (var _i = 0, STOCK_SUB_DURATIONS_3 = STOCK_SUB_DURATIONS; _i < STOCK_SUB_DURATIONS_3.length; _i++) {
                var input = STOCK_SUB_DURATIONS_3[_i];
                var trySlotDuration = core.createDuration(input);
                var slotsPerLabel = core.wholeDivideDurations(labelInterval, trySlotDuration);
                if (slotsPerLabel !== null && slotsPerLabel > 1 && slotsPerLabel <= MAX_AUTO_SLOTS_PER_LABEL) {
                    slotDuration = trySlotDuration;
                    break;
                }
            }
            // only allow the value if it won't exceed the view's # of slots limit
            if (slotDuration) {
                var slotCnt = dateEnv.countDurationsBetween(currentRange.start, currentRange.end, slotDuration);
                if (slotCnt > MAX_AUTO_CELLS) {
                    slotDuration = null;
                }
            }
            // use the label interval as a last resort
            if (!slotDuration) {
                slotDuration = labelInterval;
            }
            tDateProfile.slotDuration = slotDuration;
        }
        return slotDuration;
    }
    function computeHeaderFormats(tDateProfile, dateProfile, dateEnv, view) {
        var format1;
        var format2;
        var labelInterval = tDateProfile.labelInterval;
        var unit = core.greatestDurationDenominator(labelInterval).unit;
        var weekNumbersVisible = view.opt('weekNumbers');
        var format0 = (format1 = (format2 = null));
        // NOTE: weekNumber computation function wont work
        if ((unit === 'week') && !weekNumbersVisible) {
            unit = 'day';
        }
        switch (unit) {
            case 'year':
                format0 = { year: 'numeric' }; // '2015'
                break;
            case 'month':
                if (currentRangeAs('years', dateProfile, dateEnv) > 1) {
                    format0 = { year: 'numeric' }; // '2015'
                }
                format1 = { month: 'short' }; // 'Jan'
                break;
            case 'week':
                if (currentRangeAs('years', dateProfile, dateEnv) > 1) {
                    format0 = { year: 'numeric' }; // '2015'
                }
                format1 = { week: 'narrow' }; // 'Wk4'
                break;
            case 'day':
                if (currentRangeAs('years', dateProfile, dateEnv) > 1) {
                    format0 = { year: 'numeric', month: 'long' }; // 'January 2014'
                }
                else if (currentRangeAs('months', dateProfile, dateEnv) > 1) {
                    format0 = { month: 'long' }; // 'January'
                }
                if (weekNumbersVisible) {
                    format1 = { week: 'short' }; // 'Wk 4'
                }
                format2 = { weekday: 'narrow', day: 'numeric' }; // 'Su 9'
                break;
            case 'hour':
                if (weekNumbersVisible) {
                    format0 = { week: 'short' }; // 'Wk 4'
                }
                if (currentRangeAs('days', dateProfile, dateEnv) > 1) {
                    format1 = { weekday: 'short', day: 'numeric', month: 'numeric', omitCommas: true }; // Sat 4/7
                }
                format2 = {
                    hour: 'numeric',
                    minute: '2-digit',
                    omitZeroMinute: true,
                    meridiem: 'short'
                };
                break;
            case 'minute':
                // sufficiently large number of different minute cells?
                if ((core.asRoughMinutes(labelInterval) / 60) >= MAX_AUTO_SLOTS_PER_LABEL) {
                    format0 = {
                        hour: 'numeric',
                        meridiem: 'short'
                    };
                    format1 = function (params) {
                        return ':' + core.padStart(params.date.minute, 2); // ':30'
                    };
                }
                else {
                    format0 = {
                        hour: 'numeric',
                        minute: 'numeric',
                        meridiem: 'short'
                    };
                }
                break;
            case 'second':
                // sufficiently large number of different second cells?
                if ((core.asRoughSeconds(labelInterval) / 60) >= MAX_AUTO_SLOTS_PER_LABEL) {
                    format0 = { hour: 'numeric', minute: '2-digit', meridiem: 'lowercase' }; // '8:30 PM'
                    format1 = function (params) {
                        return ':' + core.padStart(params.date.second, 2); // ':30'
                    };
                }
                else {
                    format0 = { hour: 'numeric', minute: '2-digit', second: '2-digit', meridiem: 'lowercase' }; // '8:30:45 PM'
                }
                break;
            case 'millisecond':
                format0 = { hour: 'numeric', minute: '2-digit', second: '2-digit', meridiem: 'lowercase' }; // '8:30:45 PM'
                format1 = function (params) {
                    return '.' + core.padStart(params.millisecond, 3);
                };
                break;
        }
        return [].concat(format0 || [], format1 || [], format2 || []);
    }
    // Compute the number of the give units in the "current" range.
    // Won't go more precise than days.
    // Will return `0` if there's not a clean whole interval.
    function currentRangeAs(unit, dateProfile, dateEnv) {
        var range = dateProfile.currentRange;
        var res = null;
        if (unit === 'years') {
            res = dateEnv.diffWholeYears(range.start, range.end);
        }
        else if (unit === 'months') {
            res = dateEnv.diffWholeMonths(range.start, range.end);
        }
        else if (unit === 'weeks') {
            res = dateEnv.diffWholeMonths(range.start, range.end);
        }
        else if (unit === 'days') {
            res = core.diffWholeDays(range.start, range.end);
        }
        return res || 0;
    }
    function buildIsWeekStarts(tDateProfile, dateEnv) {
        var slotDates = tDateProfile.slotDates, emphasizeWeeks = tDateProfile.emphasizeWeeks;
        var prevWeekNumber = null;
        var isWeekStarts = [];
        for (var _i = 0, slotDates_1 = slotDates; _i < slotDates_1.length; _i++) {
            var slotDate = slotDates_1[_i];
            var weekNumber = dateEnv.computeWeekNumber(slotDate);
            var isWeekStart = emphasizeWeeks && (prevWeekNumber !== null) && (prevWeekNumber !== weekNumber);
            prevWeekNumber = weekNumber;
            isWeekStarts.push(isWeekStart);
        }
        return isWeekStarts;
    }
    function buildCellRows(tDateProfile, dateEnv, view) {
        var slotDates = tDateProfile.slotDates;
        var formats = tDateProfile.headerFormats;
        var cellRows = formats.map(function (format) { return []; }); // indexed by row,col
        // specifically for navclicks
        var rowUnits = formats.map(function (format) {
            return format.getLargestUnit ? format.getLargestUnit() : null;
        });
        // builds cellRows and slotCells
        for (var i = 0; i < slotDates.length; i++) {
            var date = slotDates[i];
            var isWeekStart = tDateProfile.isWeekStarts[i];
            for (var row = 0; row < formats.length; row++) {
                var format = formats[row];
                var rowCells = cellRows[row];
                var leadingCell = rowCells[rowCells.length - 1];
                var isSuperRow = (formats.length > 1) && (row < (formats.length - 1)); // more than one row and not the last
                var newCell = null;
                if (isSuperRow) {
                    var text = dateEnv.format(date, format);
                    if (!leadingCell || (leadingCell.text !== text)) {
                        newCell = buildCellObject(date, text, rowUnits[row], view);
                    }
                    else {
                        leadingCell.colspan += 1;
                    }
                }
                else {
                    if (!leadingCell ||
                        core.isInt(dateEnv.countDurationsBetween(tDateProfile.normalizedRange.start, date, tDateProfile.labelInterval))) {
                        var text = dateEnv.format(date, format);
                        newCell = buildCellObject(date, text, rowUnits[row], view);
                    }
                    else {
                        leadingCell.colspan += 1;
                    }
                }
                if (newCell) {
                    newCell.weekStart = isWeekStart;
                    rowCells.push(newCell);
                }
            }
        }
        return cellRows;
    }
    function buildCellObject(date, text, rowUnit, view) {
        var spanHtml = core.buildGotoAnchorHtml(view, {
            date: date,
            type: rowUnit,
            forceOff: !rowUnit
        }, {
            'class': 'fc-cell-text'
        }, core.htmlEscape(text));
        return { text: text, spanHtml: spanHtml, date: date, colspan: 1, isWeekStart: false };
    }

    var TimelineNowIndicator = /** @class */ (function () {
        function TimelineNowIndicator(headParent, bodyParent) {
            this.headParent = headParent;
            this.bodyParent = bodyParent;
        }
        TimelineNowIndicator.prototype.render = function (coord, isRtl) {
            var styleProps = isRtl ? { right: -coord } : { left: coord };
            this.headParent.appendChild(this.arrowEl = core.createElement('div', {
                className: 'fc-now-indicator fc-now-indicator-arrow',
                style: styleProps
            }));
            this.bodyParent.appendChild(this.lineEl = core.createElement('div', {
                className: 'fc-now-indicator fc-now-indicator-line',
                style: styleProps
            }));
        };
        TimelineNowIndicator.prototype.unrender = function () {
            if (this.arrowEl) {
                core.removeElement(this.arrowEl);
            }
            if (this.lineEl) {
                core.removeElement(this.lineEl);
            }
        };
        return TimelineNowIndicator;
    }());

    var STICKY_PROP_VAL = computeStickyPropVal(); // if null, means not supported at all
    var IS_MS_EDGE = /Edge/.test(navigator.userAgent);
    var IS_SAFARI = STICKY_PROP_VAL === '-webkit-sticky'; // good b/c doesn't confuse chrome
    var STICKY_CLASSNAME = 'fc-sticky';
    /*
    useful beyond the native position:sticky for these reasons:
    - support in IE11
    - nice centering support
    */
    var StickyScroller = /** @class */ (function () {
        function StickyScroller(scroller, isRtl, isVertical) {
            var _this = this;
            this.usingRelative = null;
            /*
            known bug: called twice on init. problem when mixing with ScrollJoiner
            */
            this.updateSize = function () {
                var els = Array.prototype.slice.call(_this.scroller.canvas.el.querySelectorAll('.' + STICKY_CLASSNAME));
                var elGeoms = _this.queryElGeoms(els);
                var viewportWidth = _this.scroller.el.clientWidth;
                if (_this.usingRelative) {
                    var elDestinations = _this.computeElDestinations(elGeoms, viewportWidth); // read before prepPositioning
                    assignRelativePositions(els, elGeoms, elDestinations);
                }
                else {
                    assignStickyPositions(els, elGeoms, viewportWidth);
                }
            };
            this.scroller = scroller;
            this.usingRelative =
                !STICKY_PROP_VAL || // IE11
                    (IS_MS_EDGE && isRtl) || // https://developer.microsoft.com/en-us/microsoft-edge/platform/issues/18883305/
                    ((IS_MS_EDGE || IS_SAFARI) && isVertical); // because doesn't work with rowspan in tables, our only vertial use
            if (this.usingRelative) {
                scroller.on('scrollEnd', this.updateSize);
            }
        }
        StickyScroller.prototype.destroy = function () {
            this.scroller.off('scrollEnd', this.updateSize);
        };
        StickyScroller.prototype.queryElGeoms = function (els) {
            var canvasOrigin = this.scroller.canvas.el.getBoundingClientRect();
            var elGeoms = [];
            for (var _i = 0, els_1 = els; _i < els_1.length; _i++) {
                var el = els_1[_i];
                var parentBound = core.translateRect(el.parentNode.getBoundingClientRect(), -canvasOrigin.left, -canvasOrigin.top);
                var elRect = el.getBoundingClientRect();
                var computedStyles = window.getComputedStyle(el);
                var computedTextAlign = window.getComputedStyle(el.parentNode).textAlign; // ask the parent
                var intendedTextAlign = computedTextAlign;
                var naturalBound = null;
                if (computedStyles.position !== 'sticky') {
                    naturalBound = core.translateRect(elRect, -canvasOrigin.left - (parseFloat(computedStyles.left) || 0), // could be 'auto'
                    -canvasOrigin.top - (parseFloat(computedStyles.top) || 0));
                }
                if (el.hasAttribute('data-sticky-center')) {
                    intendedTextAlign = 'center';
                }
                elGeoms.push({
                    parentBound: parentBound,
                    naturalBound: naturalBound,
                    elWidth: elRect.width,
                    elHeight: elRect.height,
                    computedTextAlign: computedTextAlign,
                    intendedTextAlign: intendedTextAlign
                });
            }
            return elGeoms;
        };
        StickyScroller.prototype.computeElDestinations = function (elGeoms, viewportWidth) {
            var viewportLeft = this.scroller.getScrollFromLeft();
            var viewportTop = this.scroller.getScrollTop();
            var viewportRight = viewportLeft + viewportWidth;
            return elGeoms.map(function (elGeom) {
                var elWidth = elGeom.elWidth, elHeight = elGeom.elHeight, parentBound = elGeom.parentBound, naturalBound = elGeom.naturalBound;
                var destLeft; // relative to canvas topleft
                var destTop; // "
                switch (elGeom.intendedTextAlign) {
                    case 'left':
                        destLeft = viewportLeft;
                        break;
                    case 'right':
                        destLeft = viewportRight - elWidth;
                        break;
                    case 'center':
                        destLeft = (viewportLeft + viewportRight) / 2 - elWidth / 2;
                        break;
                }
                destLeft = Math.min(destLeft, parentBound.right - elWidth);
                destLeft = Math.max(destLeft, parentBound.left);
                destTop = viewportTop;
                destTop = Math.min(destTop, parentBound.bottom - elHeight);
                destTop = Math.max(destTop, naturalBound.top); // better to use natural top for upper bound
                return { left: destLeft, top: destTop };
            });
        };
        return StickyScroller;
    }());
    function assignRelativePositions(els, elGeoms, elDestinations) {
        els.forEach(function (el, i) {
            var naturalBound = elGeoms[i].naturalBound;
            core.applyStyle(el, {
                position: 'relative',
                left: elDestinations[i].left - naturalBound.left,
                top: elDestinations[i].top - naturalBound.top
            });
        });
    }
    function assignStickyPositions(els, elGeoms, viewportWidth) {
        els.forEach(function (el, i) {
            var stickyLeft = 0;
            if (elGeoms[i].intendedTextAlign === 'center') {
                stickyLeft = (viewportWidth - elGeoms[i].elWidth) / 2;
                // needs to be forced to left?
                if (elGeoms[i].computedTextAlign === 'center') {
                    el.setAttribute('data-sticky-center', '') // remember for next queryElGeoms
                    ;
                    el.parentNode.style.textAlign = 'left';
                }
            }
            core.applyStyle(el, {
                position: STICKY_PROP_VAL,
                left: stickyLeft,
                right: 0,
                top: 0
            });
        });
    }
    function computeStickyPropVal() {
        var el = core.htmlToElement('<div style="position:-webkit-sticky;position:sticky"></div>');
        var val = el.style.position;
        if (val.indexOf('sticky') !== -1) {
            return val;
        }
        else {
            return null;
        }
    }

    var TimeAxis = /** @class */ (function (_super) {
        __extends(TimeAxis, _super);
        function TimeAxis(context, headerContainerEl, bodyContainerEl) {
            var _this = _super.call(this, context) || this;
            var layout = _this.layout = new HeaderBodyLayout(headerContainerEl, bodyContainerEl, 'auto');
            var headerEnhancedScroller = layout.headerScroller.enhancedScroll;
            var bodyEnhancedScroller = layout.bodyScroller.enhancedScroll;
            // needs to go after layout, which has ScrollJoiner
            _this.headStickyScroller = new StickyScroller(headerEnhancedScroller, _this.isRtl, false); // isVertical=false
            _this.bodyStickyScroller = new StickyScroller(bodyEnhancedScroller, _this.isRtl, false); // isVertical=false
            _this.header = new TimelineHeader(context, headerEnhancedScroller.canvas.contentEl);
            _this.slats = new TimelineSlats(context, bodyEnhancedScroller.canvas.bgEl);
            _this.nowIndicator = new TimelineNowIndicator(headerEnhancedScroller.canvas.el, bodyEnhancedScroller.canvas.el);
            return _this;
        }
        TimeAxis.prototype.destroy = function () {
            this.layout.destroy();
            this.header.destroy();
            this.slats.destroy();
            this.nowIndicator.unrender();
            this.headStickyScroller.destroy();
            this.bodyStickyScroller.destroy();
            _super.prototype.destroy.call(this);
        };
        TimeAxis.prototype.render = function (props) {
            var tDateProfile = this.tDateProfile =
                buildTimelineDateProfile(props.dateProfile, this.view); // TODO: cache
            this.header.receiveProps({
                dateProfile: props.dateProfile,
                tDateProfile: tDateProfile
            });
            this.slats.receiveProps({
                dateProfile: props.dateProfile,
                tDateProfile: tDateProfile
            });
        };
        // Now Indicator
        // ------------------------------------------------------------------------------------------
        TimeAxis.prototype.getNowIndicatorUnit = function (dateProfile) {
            // yuck
            var tDateProfile = this.tDateProfile =
                buildTimelineDateProfile(dateProfile, this.view); // TODO: cache
            if (tDateProfile.isTimeScale) {
                return core.greatestDurationDenominator(tDateProfile.slotDuration).unit;
            }
        };
        // will only execute if isTimeScale
        TimeAxis.prototype.renderNowIndicator = function (date) {
            if (core.rangeContainsMarker(this.tDateProfile.normalizedRange, date)) {
                this.nowIndicator.render(this.dateToCoord(date), this.isRtl);
            }
        };
        // will only execute if isTimeScale
        TimeAxis.prototype.unrenderNowIndicator = function () {
            this.nowIndicator.unrender();
        };
        // Sizing
        // ------------------------------------------------------------------------------------------
        TimeAxis.prototype.updateSize = function (isResize, totalHeight, isAuto) {
            this.applySlotWidth(this.computeSlotWidth());
            // adjusts gutters. do after slot widths set
            this.layout.setHeight(totalHeight, isAuto);
            // pretty much just queries coords. do last
            this.slats.updateSize();
        };
        TimeAxis.prototype.updateStickyScrollers = function () {
            this.headStickyScroller.updateSize();
            this.bodyStickyScroller.updateSize();
        };
        TimeAxis.prototype.computeSlotWidth = function () {
            var slotWidth = this.opt('slotWidth') || '';
            if (slotWidth === '') {
                slotWidth = this.computeDefaultSlotWidth(this.tDateProfile);
            }
            return slotWidth;
        };
        TimeAxis.prototype.computeDefaultSlotWidth = function (tDateProfile) {
            var maxInnerWidth = 0; // TODO: harness core's `matchCellWidths` for this
            this.header.innerEls.forEach(function (innerEl, i) {
                maxInnerWidth = Math.max(maxInnerWidth, innerEl.getBoundingClientRect().width);
            });
            var headingCellWidth = Math.ceil(maxInnerWidth) + 1; // assume no padding, and one pixel border
            // in TimelineView.defaults we ensured that labelInterval is an interval of slotDuration
            // TODO: rename labelDuration?
            var slotsPerLabel = core.wholeDivideDurations(tDateProfile.labelInterval, tDateProfile.slotDuration);
            var slotWidth = Math.ceil(headingCellWidth / slotsPerLabel);
            var minWidth = window.getComputedStyle(this.header.slatColEls[0]).minWidth;
            if (minWidth) {
                minWidth = parseInt(minWidth, 10);
                if (minWidth) {
                    slotWidth = Math.max(slotWidth, minWidth);
                }
            }
            return slotWidth;
        };
        TimeAxis.prototype.applySlotWidth = function (slotWidth) {
            var _a = this, layout = _a.layout, tDateProfile = _a.tDateProfile;
            var containerWidth = '';
            var containerMinWidth = '';
            var nonLastSlotWidth = '';
            if (slotWidth !== '') {
                slotWidth = Math.round(slotWidth);
                containerWidth = slotWidth * tDateProfile.slotDates.length;
                containerMinWidth = '';
                nonLastSlotWidth = slotWidth;
                var availableWidth = layout.bodyScroller.enhancedScroll.getClientWidth();
                if (availableWidth > containerWidth) {
                    containerMinWidth = availableWidth;
                    containerWidth = '';
                    nonLastSlotWidth = Math.floor(availableWidth / tDateProfile.slotDates.length);
                }
            }
            layout.headerScroller.enhancedScroll.canvas.setWidth(containerWidth);
            layout.headerScroller.enhancedScroll.canvas.setMinWidth(containerMinWidth);
            layout.bodyScroller.enhancedScroll.canvas.setWidth(containerWidth);
            layout.bodyScroller.enhancedScroll.canvas.setMinWidth(containerMinWidth);
            if (nonLastSlotWidth !== '') {
                this.header.slatColEls.slice(0, -1).concat(this.slats.slatColEls.slice(0, -1)).forEach(function (el) {
                    el.style.width = nonLastSlotWidth + 'px';
                });
            }
        };
        // returned value is between 0 and the number of snaps
        TimeAxis.prototype.computeDateSnapCoverage = function (date) {
            var _a = this, dateEnv = _a.dateEnv, tDateProfile = _a.tDateProfile;
            var snapDiff = dateEnv.countDurationsBetween(tDateProfile.normalizedRange.start, date, tDateProfile.snapDuration);
            if (snapDiff < 0) {
                return 0;
            }
            else if (snapDiff >= tDateProfile.snapDiffToIndex.length) {
                return tDateProfile.snapCnt;
            }
            else {
                var snapDiffInt = Math.floor(snapDiff);
                var snapCoverage = tDateProfile.snapDiffToIndex[snapDiffInt];
                if (core.isInt(snapCoverage)) { // not an in-between value
                    snapCoverage += snapDiff - snapDiffInt; // add the remainder
                }
                else {
                    // a fractional value, meaning the date is not visible
                    // always round up in this case. works for start AND end dates in a range.
                    snapCoverage = Math.ceil(snapCoverage);
                }
                return snapCoverage;
            }
        };
        // for LTR, results range from 0 to width of area
        // for RTL, results range from negative width of area to 0
        TimeAxis.prototype.dateToCoord = function (date) {
            var tDateProfile = this.tDateProfile;
            var snapCoverage = this.computeDateSnapCoverage(date);
            var slotCoverage = snapCoverage / tDateProfile.snapsPerSlot;
            var slotIndex = Math.floor(slotCoverage);
            slotIndex = Math.min(slotIndex, tDateProfile.slotCnt - 1);
            var partial = slotCoverage - slotIndex;
            var _a = this.slats, innerCoordCache = _a.innerCoordCache, outerCoordCache = _a.outerCoordCache;
            if (this.isRtl) {
                return (outerCoordCache.rights[slotIndex] -
                    (innerCoordCache.getWidth(slotIndex) * partial)) - outerCoordCache.originClientRect.width;
            }
            else {
                return (outerCoordCache.lefts[slotIndex] +
                    (innerCoordCache.getWidth(slotIndex) * partial));
            }
        };
        TimeAxis.prototype.rangeToCoords = function (range) {
            if (this.isRtl) {
                return { right: this.dateToCoord(range.start), left: this.dateToCoord(range.end) };
            }
            else {
                return { left: this.dateToCoord(range.start), right: this.dateToCoord(range.end) };
            }
        };
        // Scrolling
        // ------------------------------------------------------------------------------------------
        TimeAxis.prototype.computeDateScroll = function (timeMs) {
            var dateEnv = this.dateEnv;
            var dateProfile = this.props.dateProfile;
            var left = 0;
            if (dateProfile) {
                left = this.dateToCoord(dateEnv.add(core.startOfDay(dateProfile.activeRange.start), // startOfDay needed?
                core.createDuration(timeMs)));
                // hack to overcome the left borders of non-first slat
                if (!this.isRtl && left) {
                    left += 1;
                }
            }
            return { left: left };
        };
        TimeAxis.prototype.queryDateScroll = function () {
            var enhancedScroll = this.layout.bodyScroller.enhancedScroll;
            return {
                left: enhancedScroll.getScrollLeft()
            };
        };
        TimeAxis.prototype.applyDateScroll = function (scroll) {
            // TODO: lame we have to update both. use the scrolljoiner instead maybe
            this.layout.bodyScroller.enhancedScroll.setScrollLeft(scroll.left || 0);
            this.layout.headerScroller.enhancedScroll.setScrollLeft(scroll.left || 0);
        };
        return TimeAxis;
    }(core.Component));

    // import { computeResourceEditable } from '@fullcalendar/resource-common' ... CAN'T HAVE THIS DEP! COPIED AND PASTED BELOW!
    var TimelineLaneEventRenderer = /** @class */ (function (_super) {
        __extends(TimelineLaneEventRenderer, _super);
        function TimelineLaneEventRenderer(context, masterContainerEl, timeAxis) {
            var _this = _super.call(this, context) || this;
            _this.masterContainerEl = masterContainerEl;
            _this.timeAxis = timeAxis;
            return _this;
        }
        TimelineLaneEventRenderer.prototype.renderSegHtml = function (seg, mirrorInfo) {
            var eventRange = seg.eventRange;
            var eventDef = eventRange.def;
            var eventUi = eventRange.ui;
            var isDraggable = eventUi.startEditable || computeResourceEditable(eventDef, this.timeAxis.calendar);
            var isResizableFromStart = seg.isStart && eventUi.durationEditable && this.context.options.eventResizableFromStart;
            var isResizableFromEnd = seg.isEnd && eventUi.durationEditable;
            var classes = this.getSegClasses(seg, isDraggable, isResizableFromStart || isResizableFromEnd, mirrorInfo);
            classes.unshift('fc-timeline-event', 'fc-h-event');
            var timeText = this.getTimeText(eventRange);
            return '<a class="' + classes.join(' ') + '" style="' + core.cssToStr(this.getSkinCss(eventUi)) + '"' +
                (eventDef.url ?
                    ' href="' + core.htmlEscape(eventDef.url) + '"' :
                    '') +
                '>' +
                '<div class="fc-content">' +
                (timeText ?
                    '<span class="fc-time">' +
                        core.htmlEscape(timeText) +
                        '</span>'
                    :
                        '') +
                '<span class="fc-title fc-sticky">' +
                (eventDef.title ? core.htmlEscape(eventDef.title) : '&nbsp;') +
                '</span>' +
                '</div>' +
                (isResizableFromStart ?
                    '<div class="fc-resizer fc-start-resizer"></div>' :
                    '') +
                (isResizableFromEnd ?
                    '<div class="fc-resizer fc-end-resizer"></div>' :
                    '') +
                '</a>';
        };
        TimelineLaneEventRenderer.prototype.computeDisplayEventTime = function () {
            return !this.timeAxis.tDateProfile.isTimeScale; // because times should be obvious via axis
        };
        TimelineLaneEventRenderer.prototype.computeDisplayEventEnd = function () {
            return false;
        };
        // Computes a default event time formatting string if `timeFormat` is not explicitly defined
        TimelineLaneEventRenderer.prototype.computeEventTimeFormat = function () {
            return {
                hour: 'numeric',
                minute: '2-digit',
                omitZeroMinute: true,
                meridiem: 'narrow'
            };
        };
        TimelineLaneEventRenderer.prototype.attachSegs = function (segs, mirrorInfo) {
            if (!this.el && this.masterContainerEl) {
                this.el = core.createElement('div', { className: 'fc-event-container' });
                if (mirrorInfo) {
                    this.el.classList.add('fc-mirror-container');
                }
                this.masterContainerEl.appendChild(this.el);
            }
            if (this.el) {
                for (var _i = 0, segs_1 = segs; _i < segs_1.length; _i++) {
                    var seg = segs_1[_i];
                    this.el.appendChild(seg.el);
                }
            }
        };
        TimelineLaneEventRenderer.prototype.detachSegs = function (segs) {
            for (var _i = 0, segs_2 = segs; _i < segs_2.length; _i++) {
                var seg = segs_2[_i];
                core.removeElement(seg.el);
            }
        };
        // computes AND assigns (assigns the left/right at least). bad
        TimelineLaneEventRenderer.prototype.computeSegSizes = function (segs) {
            var timeAxis = this.timeAxis;
            for (var _i = 0, segs_3 = segs; _i < segs_3.length; _i++) {
                var seg = segs_3[_i];
                var coords = timeAxis.rangeToCoords(seg); // works because Seg has start/end
                core.applyStyle(seg.el, {
                    left: (seg.left = coords.left),
                    right: -(seg.right = coords.right)
                });
            }
        };
        TimelineLaneEventRenderer.prototype.assignSegSizes = function (segs) {
            if (!this.el) {
                return;
            }
            // compute seg verticals
            for (var _i = 0, segs_4 = segs; _i < segs_4.length; _i++) {
                var seg = segs_4[_i];
                seg.height = core.computeHeightAndMargins(seg.el);
            }
            this.buildSegLevels(segs); // populates above/below props for computeOffsetForSegs
            var totalHeight = computeOffsetForSegs(segs); // also assigns seg.top
            core.applyStyleProp(this.el, 'height', totalHeight);
            // assign seg verticals
            for (var _a = 0, segs_5 = segs; _a < segs_5.length; _a++) {
                var seg = segs_5[_a];
                core.applyStyleProp(seg.el, 'top', seg.top);
            }
        };
        TimelineLaneEventRenderer.prototype.buildSegLevels = function (segs) {
            var segLevels = [];
            segs = this.sortEventSegs(segs);
            for (var _i = 0, segs_6 = segs; _i < segs_6.length; _i++) {
                var unplacedSeg = segs_6[_i];
                unplacedSeg.above = [];
                // determine the first level with no collisions
                var level = 0; // level index
                while (level < segLevels.length) {
                    var isLevelCollision = false;
                    // determine collisions
                    for (var _a = 0, _b = segLevels[level]; _a < _b.length; _a++) {
                        var placedSeg = _b[_a];
                        if (timeRowSegsCollide(unplacedSeg, placedSeg)) {
                            unplacedSeg.above.push(placedSeg);
                            isLevelCollision = true;
                        }
                    }
                    if (isLevelCollision) {
                        level += 1;
                    }
                    else {
                        break;
                    }
                }
                // insert into the first non-colliding level. create if necessary
                (segLevels[level] || (segLevels[level] = []))
                    .push(unplacedSeg);
                // record possible colliding segments below (TODO: automated test for this)
                level += 1;
                while (level < segLevels.length) {
                    for (var _c = 0, _d = segLevels[level]; _c < _d.length; _c++) {
                        var belowSeg = _d[_c];
                        if (timeRowSegsCollide(unplacedSeg, belowSeg)) {
                            belowSeg.above.push(unplacedSeg);
                        }
                    }
                    level += 1;
                }
            }
            return segLevels;
        };
        return TimelineLaneEventRenderer;
    }(core.FgEventRenderer));
    function computeOffsetForSegs(segs) {
        var max = 0;
        for (var _i = 0, segs_7 = segs; _i < segs_7.length; _i++) {
            var seg = segs_7[_i];
            max = Math.max(max, computeOffsetForSeg(seg));
        }
        return max;
    }
    function computeOffsetForSeg(seg) {
        if ((seg.top == null)) {
            seg.top = computeOffsetForSegs(seg.above);
        }
        return seg.top + seg.height;
    }
    function timeRowSegsCollide(seg0, seg1) {
        return (seg0.left < seg1.right) && (seg0.right > seg1.left);
    }
    // HACK
    function computeResourceEditable(eventDef, calendar) {
        var resourceEditable = eventDef.resourceEditable;
        if (resourceEditable == null) {
            var source = eventDef.sourceId && calendar.state.eventSources[eventDef.sourceId];
            if (source) {
                resourceEditable = source.extendedProps.resourceEditable; // used the Source::extendedProps hack
            }
            if (resourceEditable == null) {
                resourceEditable = calendar.opt('eventResourceEditable');
                if (resourceEditable == null) {
                    resourceEditable = true; // TODO: use defaults system instead
                }
            }
        }
        return resourceEditable;
    }

    var TimelineLaneFillRenderer = /** @class */ (function (_super) {
        __extends(TimelineLaneFillRenderer, _super);
        function TimelineLaneFillRenderer(context, masterContainerEl, timeAxis) {
            var _this = _super.call(this, context) || this;
            _this.masterContainerEl = masterContainerEl;
            _this.timeAxis = timeAxis;
            return _this;
        }
        TimelineLaneFillRenderer.prototype.attachSegs = function (type, segs) {
            if (segs.length) {
                var className = void 0;
                if (type === 'businessHours') {
                    className = 'bgevent';
                }
                else {
                    className = type.toLowerCase();
                }
                // making a new container each time is OKAY
                // all types of segs (background or business hours or whatever) are rendered in one pass
                var containerEl = core.createElement('div', { className: 'fc-' + className + '-container' });
                this.masterContainerEl.appendChild(containerEl);
                for (var _i = 0, segs_1 = segs; _i < segs_1.length; _i++) {
                    var seg = segs_1[_i];
                    containerEl.appendChild(seg.el);
                }
                return [containerEl]; // return value
            }
        };
        TimelineLaneFillRenderer.prototype.computeSegSizes = function (segs) {
            var timeAxis = this.timeAxis;
            for (var _i = 0, segs_2 = segs; _i < segs_2.length; _i++) {
                var seg = segs_2[_i];
                var coords = timeAxis.rangeToCoords(seg);
                seg.left = coords.left;
                seg.right = coords.right;
            }
        };
        TimelineLaneFillRenderer.prototype.assignSegSizes = function (segs) {
            for (var _i = 0, segs_3 = segs; _i < segs_3.length; _i++) {
                var seg = segs_3[_i];
                core.applyStyle(seg.el, {
                    left: seg.left,
                    right: -seg.right
                });
            }
        };
        return TimelineLaneFillRenderer;
    }(core.FillRenderer));

    var TimelineLane = /** @class */ (function (_super) {
        __extends(TimelineLane, _super);
        function TimelineLane(context, fgContainerEl, bgContainerEl, timeAxis) {
            var _this = _super.call(this, context, bgContainerEl) // should el be bgContainerEl???
             || this;
            _this.slicer = new TimelineLaneSlicer();
            _this.renderEventDrag = core.memoizeRendering(_this._renderEventDrag, _this._unrenderEventDrag);
            _this.renderEventResize = core.memoizeRendering(_this._renderEventResize, _this._unrenderEventResize);
            var fillRenderer = _this.fillRenderer = new TimelineLaneFillRenderer(context, bgContainerEl, timeAxis);
            var eventRenderer = _this.eventRenderer = new TimelineLaneEventRenderer(context, fgContainerEl, timeAxis);
            _this.mirrorRenderer = new TimelineLaneEventRenderer(context, fgContainerEl, timeAxis);
            _this.renderBusinessHours = core.memoizeRendering(fillRenderer.renderSegs.bind(fillRenderer, 'businessHours'), fillRenderer.unrender.bind(fillRenderer, 'businessHours'));
            _this.renderDateSelection = core.memoizeRendering(fillRenderer.renderSegs.bind(fillRenderer, 'highlight'), fillRenderer.unrender.bind(fillRenderer, 'highlight'));
            _this.renderBgEvents = core.memoizeRendering(fillRenderer.renderSegs.bind(fillRenderer, 'bgEvent'), fillRenderer.unrender.bind(fillRenderer, 'bgEvent'));
            _this.renderFgEvents = core.memoizeRendering(eventRenderer.renderSegs.bind(eventRenderer), eventRenderer.unrender.bind(eventRenderer));
            _this.renderEventSelection = core.memoizeRendering(eventRenderer.selectByInstanceId.bind(eventRenderer), eventRenderer.unselectByInstanceId.bind(eventRenderer), [_this.renderFgEvents]);
            _this.timeAxis = timeAxis;
            return _this;
        }
        TimelineLane.prototype.render = function (props) {
            var slicedProps = this.slicer.sliceProps(props, props.dateProfile, this.timeAxis.tDateProfile.isTimeScale ? null : props.nextDayThreshold, this, this.timeAxis);
            this.renderBusinessHours(slicedProps.businessHourSegs);
            this.renderDateSelection(slicedProps.dateSelectionSegs);
            this.renderBgEvents(slicedProps.bgEventSegs);
            this.renderFgEvents(slicedProps.fgEventSegs);
            this.renderEventSelection(slicedProps.eventSelection);
            this.renderEventDrag(slicedProps.eventDrag);
            this.renderEventResize(slicedProps.eventResize);
        };
        TimelineLane.prototype.destroy = function () {
            _super.prototype.destroy.call(this);
            this.renderBusinessHours.unrender();
            this.renderDateSelection.unrender();
            this.renderBgEvents.unrender();
            this.renderFgEvents.unrender();
            this.renderEventSelection.unrender();
            this.renderEventDrag.unrender();
            this.renderEventResize.unrender();
        };
        TimelineLane.prototype._renderEventDrag = function (state) {
            if (state) {
                this.eventRenderer.hideByHash(state.affectedInstances);
                this.mirrorRenderer.renderSegs(state.segs, { isDragging: true, sourceSeg: state.sourceSeg });
            }
        };
        TimelineLane.prototype._unrenderEventDrag = function (state) {
            if (state) {
                this.eventRenderer.showByHash(state.affectedInstances);
                this.mirrorRenderer.unrender(state.segs, { isDragging: true, sourceSeg: state.sourceSeg });
            }
        };
        TimelineLane.prototype._renderEventResize = function (state) {
            if (state) {
                // HACK. eventRenderer and fillRenderer both use these segs. would compete over seg.el
                var segsForHighlight = state.segs.map(function (seg) {
                    return __assign({}, seg);
                });
                this.eventRenderer.hideByHash(state.affectedInstances);
                this.fillRenderer.renderSegs('highlight', segsForHighlight);
                this.mirrorRenderer.renderSegs(state.segs, { isDragging: true, sourceSeg: state.sourceSeg });
            }
        };
        TimelineLane.prototype._unrenderEventResize = function (state) {
            if (state) {
                this.eventRenderer.showByHash(state.affectedInstances);
                this.fillRenderer.unrender('highlight');
                this.mirrorRenderer.unrender(state.segs, { isDragging: true, sourceSeg: state.sourceSeg });
            }
        };
        TimelineLane.prototype.updateSize = function (isResize) {
            var _a = this, fillRenderer = _a.fillRenderer, eventRenderer = _a.eventRenderer, mirrorRenderer = _a.mirrorRenderer;
            fillRenderer.computeSizes(isResize);
            eventRenderer.computeSizes(isResize);
            mirrorRenderer.computeSizes(isResize);
            fillRenderer.assignSizes(isResize);
            eventRenderer.assignSizes(isResize);
            mirrorRenderer.assignSizes(isResize);
        };
        return TimelineLane;
    }(core.DateComponent));
    var TimelineLaneSlicer = /** @class */ (function (_super) {
        __extends(TimelineLaneSlicer, _super);
        function TimelineLaneSlicer() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        TimelineLaneSlicer.prototype.sliceRange = function (origRange, timeAxis) {
            var tDateProfile = timeAxis.tDateProfile;
            var dateProfile = timeAxis.props.dateProfile;
            var normalRange = normalizeRange(origRange, tDateProfile, timeAxis.dateEnv);
            var segs = [];
            // protect against when the span is entirely in an invalid date region
            if (timeAxis.computeDateSnapCoverage(normalRange.start) < timeAxis.computeDateSnapCoverage(normalRange.end)) {
                // intersect the footprint's range with the grid's range
                var slicedRange = core.intersectRanges(normalRange, tDateProfile.normalizedRange);
                if (slicedRange) {
                    segs.push({
                        start: slicedRange.start,
                        end: slicedRange.end,
                        isStart: slicedRange.start.valueOf() === normalRange.start.valueOf() && isValidDate(slicedRange.start, tDateProfile, dateProfile, timeAxis.view),
                        isEnd: slicedRange.end.valueOf() === normalRange.end.valueOf() && isValidDate(core.addMs(slicedRange.end, -1), tDateProfile, dateProfile, timeAxis.view)
                    });
                }
            }
            return segs;
        };
        return TimelineLaneSlicer;
    }(core.Slicer));

    var TimelineView = /** @class */ (function (_super) {
        __extends(TimelineView, _super);
        function TimelineView(context, viewSpec, dateProfileGenerator, parentEl) {
            var _this = _super.call(this, context, viewSpec, dateProfileGenerator, parentEl) || this;
            _this.el.classList.add('fc-timeline');
            if (_this.opt('eventOverlap') === false) {
                _this.el.classList.add('fc-no-overlap');
            }
            _this.el.innerHTML = _this.renderSkeletonHtml();
            _this.timeAxis = new TimeAxis(_this.context, _this.el.querySelector('thead .fc-time-area'), _this.el.querySelector('tbody .fc-time-area'));
            _this.lane = new TimelineLane(_this.context, _this.timeAxis.layout.bodyScroller.enhancedScroll.canvas.contentEl, _this.timeAxis.layout.bodyScroller.enhancedScroll.canvas.bgEl, _this.timeAxis);
            context.calendar.registerInteractiveComponent(_this, {
                el: _this.timeAxis.slats.el
            });
            return _this;
        }
        TimelineView.prototype.destroy = function () {
            this.timeAxis.destroy();
            this.lane.destroy();
            _super.prototype.destroy.call(this);
            this.calendar.unregisterInteractiveComponent(this);
        };
        TimelineView.prototype.renderSkeletonHtml = function () {
            var theme = this.theme;
            return "<table class=\"" + theme.getClass('tableGrid') + "\"> <thead class=\"fc-head\"> <tr> <td class=\"fc-time-area " + theme.getClass('widgetHeader') + "\"></td> </tr> </thead> <tbody class=\"fc-body\"> <tr> <td class=\"fc-time-area " + theme.getClass('widgetContent') + "\"></td> </tr> </tbody> </table>";
        };
        TimelineView.prototype.render = function (props) {
            _super.prototype.render.call(this, props); // flags for updateSize, addScroll
            this.timeAxis.receiveProps({
                dateProfile: props.dateProfile
            });
            this.lane.receiveProps(__assign({}, props, { nextDayThreshold: this.nextDayThreshold }));
        };
        TimelineView.prototype.updateSize = function (isResize, totalHeight, isAuto) {
            this.timeAxis.updateSize(isResize, totalHeight, isAuto);
            this.lane.updateSize(isResize);
        };
        // Now Indicator
        // ------------------------------------------------------------------------------------------
        TimelineView.prototype.getNowIndicatorUnit = function (dateProfile) {
            return this.timeAxis.getNowIndicatorUnit(dateProfile);
        };
        TimelineView.prototype.renderNowIndicator = function (date) {
            this.timeAxis.renderNowIndicator(date);
        };
        TimelineView.prototype.unrenderNowIndicator = function () {
            this.timeAxis.unrenderNowIndicator();
        };
        // Scroll System
        // ------------------------------------------------------------------------------------------
        TimelineView.prototype.computeDateScroll = function (timeMs) {
            return this.timeAxis.computeDateScroll(timeMs);
        };
        TimelineView.prototype.applyScroll = function (scroll, isResize) {
            _super.prototype.applyScroll.call(this, scroll, isResize); // will call applyDateScroll
            // avoid updating stickyscroll too often
            // TODO: repeat code as ResourceTimelineView::updateSize
            var calendar = this.calendar;
            if (isResize || calendar.isViewUpdated || calendar.isDatesUpdated || calendar.isEventsUpdated) {
                this.timeAxis.updateStickyScrollers();
            }
        };
        TimelineView.prototype.applyDateScroll = function (scroll) {
            this.timeAxis.applyDateScroll(scroll);
        };
        TimelineView.prototype.queryScroll = function () {
            var enhancedScroll = this.timeAxis.layout.bodyScroller.enhancedScroll;
            return {
                top: enhancedScroll.getScrollTop(),
                left: enhancedScroll.getScrollLeft()
            };
        };
        // Hit System
        // ------------------------------------------------------------------------------------------
        TimelineView.prototype.buildPositionCaches = function () {
            this.timeAxis.slats.updateSize();
        };
        TimelineView.prototype.queryHit = function (positionLeft, positionTop, elWidth, elHeight) {
            var slatHit = this.timeAxis.slats.positionToHit(positionLeft);
            if (slatHit) {
                return {
                    component: this,
                    dateSpan: slatHit.dateSpan,
                    rect: {
                        left: slatHit.left,
                        right: slatHit.right,
                        top: 0,
                        bottom: elHeight
                    },
                    dayEl: slatHit.dayEl,
                    layer: 0
                };
            }
        };
        return TimelineView;
    }(core.View));

    var main = core.createPlugin({
        defaultView: 'timelineDay',
        views: {
            timeline: {
                class: TimelineView,
                eventResizableFromStart: true // how is this consumed for TimelineView tho?
            },
            timelineDay: {
                type: 'timeline',
                duration: { days: 1 }
            },
            timelineWeek: {
                type: 'timeline',
                duration: { weeks: 1 }
            },
            timelineMonth: {
                type: 'timeline',
                duration: { months: 1 }
            },
            timelineYear: {
                type: 'timeline',
                duration: { years: 1 }
            }
        }
    });

    exports.HeaderBodyLayout = HeaderBodyLayout;
    exports.ScrollJoiner = ScrollJoiner;
    exports.StickyScroller = StickyScroller;
    exports.TimeAxis = TimeAxis;
    exports.TimelineLane = TimelineLane;
    exports.TimelineView = TimelineView;
    exports.default = main;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
/*!
FullCalendar Resource Timeline Plugin v4.1.0
Docs & License: https://fullcalendar.io/scheduler
(c) 2019 Adam Shaw
*/
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('@fullcalendar/core'), require('@fullcalendar/timeline'), require('@fullcalendar/resource-common')) :
    typeof define === 'function' && define.amd ? define(['exports', '@fullcalendar/core', '@fullcalendar/timeline', '@fullcalendar/resource-common'], factory) :
    (global = global || self, factory(global.FullCalendarResourceTimeline = {}, global.FullCalendar, global.FullCalendarTimeline, global.FullCalendarResourceCommon));
}(this, function (exports, core, TimelinePlugin, ResourceCommonPlugin) { 'use strict';

    var TimelinePlugin__default = 'default' in TimelinePlugin ? TimelinePlugin['default'] : TimelinePlugin;
    var ResourceCommonPlugin__default = 'default' in ResourceCommonPlugin ? ResourceCommonPlugin['default'] : ResourceCommonPlugin;

    /*! *****************************************************************************
    Copyright (c) Microsoft Corporation. All rights reserved.
    Licensed under the Apache License, Version 2.0 (the "License"); you may not use
    this file except in compliance with the License. You may obtain a copy of the
    License at http://www.apache.org/licenses/LICENSE-2.0

    THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
    KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
    WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
    MERCHANTABLITY OR NON-INFRINGEMENT.

    See the Apache Version 2.0 License for specific language governing permissions
    and limitations under the License.
    ***************************************************************************** */
    /* global Reflect, Promise */

    var extendStatics = function(d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };

    function __extends(d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    }

    var __assign = function() {
        __assign = Object.assign || function __assign(t) {
            for (var s, i = 1, n = arguments.length; i < n; i++) {
                s = arguments[i];
                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
            }
            return t;
        };
        return __assign.apply(this, arguments);
    };

    var Row = /** @class */ (function (_super) {
        __extends(Row, _super);
        function Row(context, spreadsheetParent, spreadsheetNextSibling, timeAxisParent, timeAxisNextSibling) {
            var _this = _super.call(this, context) || this;
            _this.isSizeDirty = false;
            spreadsheetParent.insertBefore(_this.spreadsheetTr = document.createElement('tr'), spreadsheetNextSibling);
            timeAxisParent.insertBefore(_this.timeAxisTr = document.createElement('tr'), timeAxisNextSibling);
            return _this;
        }
        Row.prototype.destroy = function () {
            core.removeElement(this.spreadsheetTr);
            core.removeElement(this.timeAxisTr);
            _super.prototype.destroy.call(this);
        };
        Row.prototype.updateSize = function (isResize) {
            this.isSizeDirty = false;
        };
        return Row;
    }(core.Component));

    function updateExpanderIcon(el, isExpanded) {
        var classList = el.classList;
        if (isExpanded) {
            classList.remove('fc-icon-plus-square');
            classList.add('fc-icon-minus-square');
        }
        else {
            classList.remove('fc-icon-minus-square');
            classList.add('fc-icon-plus-square');
        }
    }
    function clearExpanderIcon(el) {
        var classList = el.classList;
        classList.remove('fc-icon-minus-square');
        classList.remove('fc-icon-plus-square');
    }
    function updateTrResourceId(tr, resourceId) {
        tr.setAttribute('data-resource-id', resourceId);
    }

    var GroupRow = /** @class */ (function (_super) {
        __extends(GroupRow, _super);
        function GroupRow() {
            var _this = _super !== null && _super.apply(this, arguments) || this;
            _this._renderCells = core.memoizeRendering(_this.renderCells, _this.unrenderCells);
            _this._updateExpanderIcon = core.memoizeRendering(_this.updateExpanderIcon, null, [_this._renderCells]);
            _this.onExpanderClick = function (ev) {
                var props = _this.props;
                _this.calendar.dispatch({
                    type: 'SET_RESOURCE_ENTITY_EXPANDED',
                    id: props.id,
                    isExpanded: !props.isExpanded
                });
            };
            return _this;
        }
        GroupRow.prototype.render = function (props) {
            this._renderCells(props.group, props.spreadsheetColCnt);
            this._updateExpanderIcon(props.isExpanded);
            this.isSizeDirty = true;
        };
        GroupRow.prototype.destroy = function () {
            _super.prototype.destroy.call(this);
            this._renderCells.unrender(); // should unrender everything else
        };
        GroupRow.prototype.renderCells = function (group, spreadsheetColCnt) {
            var spreadsheetContentEl = this.renderSpreadsheetContent(group);
            this.spreadsheetTr.appendChild(core.createElement('td', {
                className: 'fc-divider',
                colSpan: spreadsheetColCnt // span across all columns
            }, this.spreadsheetHeightEl = core.createElement('div', null, spreadsheetContentEl)) // needed by setTrInnerHeight
            );
            this.expanderIconEl = spreadsheetContentEl.querySelector('.fc-icon');
            this.expanderIconEl.parentElement.addEventListener('click', this.onExpanderClick);
            // insert a single cell, with a single empty <div>.
            // there will be no content
            this.timeAxisTr.appendChild(core.createElement('td', { className: 'fc-divider' }, this.timeAxisHeightEl = document.createElement('div')));
        };
        GroupRow.prototype.unrenderCells = function () {
            this.spreadsheetTr.innerHTML = '';
            this.timeAxisTr.innerHTML = '';
        };
        /*
        Renders the content wrapper element that will be inserted into this row's TD cell.
        */
        GroupRow.prototype.renderSpreadsheetContent = function (group) {
            var text = this.renderCellText(group);
            var contentEl = core.htmlToElement('<div class="fc-cell-content">' +
                '<span class="fc-expander">' +
                '<span class="fc-icon"></span>' +
                '</span>' +
                '<span class="fc-cell-text">' +
                (text ? core.htmlEscape(text) : '&nbsp;') +
                '</span>' +
                '</div>');
            var filter = group.spec.render;
            if (typeof filter === 'function') {
                contentEl = filter(contentEl, group.value) || contentEl;
            }
            return contentEl;
        };
        GroupRow.prototype.renderCellText = function (group) {
            var text = group.value || ''; // might be null/undefined if an ad-hoc grouping
            var filter = group.spec.text;
            if (typeof filter === 'function') {
                text = filter(text) || text;
            }
            return text;
        };
        GroupRow.prototype.getHeightEls = function () {
            return [this.spreadsheetHeightEl, this.timeAxisHeightEl];
        };
        GroupRow.prototype.updateExpanderIcon = function (isExpanded) {
            updateExpanderIcon(this.expanderIconEl, isExpanded);
        };
        return GroupRow;
    }(Row));
    GroupRow.addEqualityFuncs({
        group: ResourceCommonPlugin.isGroupsEqual // HACK for ResourceTimelineView::renderRows
    });

    var SpreadsheetRow = /** @class */ (function (_super) {
        __extends(SpreadsheetRow, _super);
        function SpreadsheetRow(context, tr) {
            var _this = _super.call(this, context) || this;
            _this._renderRow = core.memoizeRendering(_this.renderRow, _this.unrenderRow);
            _this._updateTrResourceId = core.memoizeRendering(updateTrResourceId, null, [_this._renderRow]);
            _this._updateExpanderIcon = core.memoizeRendering(_this.updateExpanderIcon, null, [_this._renderRow]);
            _this.onExpanderClick = function (ev) {
                var props = _this.props;
                _this.calendar.dispatch({
                    type: 'SET_RESOURCE_ENTITY_EXPANDED',
                    id: props.id,
                    isExpanded: !props.isExpanded
                });
            };
            _this.tr = tr;
            return _this;
        }
        SpreadsheetRow.prototype.render = function (props) {
            this._renderRow(props.resource, props.rowSpans, props.depth, props.colSpecs);
            this._updateTrResourceId(this.tr, props.resource.id); // TODO: only use public ID?
            this._updateExpanderIcon(props.hasChildren, props.isExpanded);
        };
        SpreadsheetRow.prototype.destroy = function () {
            _super.prototype.destroy.call(this);
            this._renderRow.unrender(); // should unrender everything else
        };
        SpreadsheetRow.prototype.renderRow = function (resource, rowSpans, depth, colSpecs) {
            var _a = this, tr = _a.tr, theme = _a.theme, calendar = _a.calendar, view = _a.view;
            var resourceFields = ResourceCommonPlugin.buildResourceFields(resource); // slightly inefficient. already done up the call stack
            var mainTd;
            for (var i = 0; i < colSpecs.length; i++) {
                var colSpec = colSpecs[i];
                var rowSpan = rowSpans[i];
                if (rowSpan === 0) { // not responsible for group-based rows. VRowGroup is
                    continue;
                }
                else if (rowSpan == null) {
                    rowSpan = 1;
                }
                var text = void 0;
                if (colSpec.field) {
                    text = resourceFields[colSpec.field];
                }
                else {
                    text = ResourceCommonPlugin.buildResourceTextFunc(colSpec.text, calendar)(resource);
                }
                var contentEl = core.htmlToElement('<div class="fc-cell-content">' +
                    (colSpec.isMain ? renderIconHtml(depth) : '') +
                    '<span class="fc-cell-text">' +
                    (text ? core.htmlEscape(text) : '&nbsp;') +
                    '</span>' +
                    '</div>');
                if (typeof colSpec.render === 'function') { // a filter function for the element
                    contentEl = colSpec.render(new ResourceCommonPlugin.ResourceApi(calendar, resource), contentEl) || contentEl;
                }
                if (rowSpan > 1) {
                    contentEl.classList.add('fc-sticky');
                }
                var td = core.createElement('td', {
                    className: theme.getClass('widgetContent'),
                    rowspan: rowSpan
                }, contentEl);
                // the first cell of the row needs to have an inner div for setTrInnerHeight
                if (colSpec.isMain) {
                    td.appendChild(this.heightEl = core.createElement('div', null, td.childNodes) // inner wrap
                    );
                    mainTd = td;
                }
                tr.appendChild(td);
            }
            this.expanderIconEl = tr.querySelector('.fc-expander-space .fc-icon');
            // wait until very end
            view.publiclyTrigger('resourceRender', [
                {
                    resource: new ResourceCommonPlugin.ResourceApi(calendar, resource),
                    el: mainTd,
                    view: view
                }
            ]);
        };
        SpreadsheetRow.prototype.unrenderRow = function () {
            this.tr.innerHTML = '';
        };
        SpreadsheetRow.prototype.updateExpanderIcon = function (hasChildren, isExpanded) {
            var expanderIconEl = this.expanderIconEl;
            var expanderEl = expanderIconEl.parentElement;
            if (expanderIconEl &&
                expanderEl // why would this be null?? was the case in IE11
            ) {
                if (hasChildren) {
                    expanderEl.addEventListener('click', this.onExpanderClick);
                    expanderEl.classList.add('fc-expander');
                    updateExpanderIcon(expanderIconEl, isExpanded);
                }
                else {
                    expanderEl.removeEventListener('click', this.onExpanderClick);
                    expanderEl.classList.remove('fc-expander');
                    clearExpanderIcon(expanderIconEl);
                }
            }
        };
        return SpreadsheetRow;
    }(core.Component));
    /*
    Renders the HTML responsible for the subrow expander area,
    as well as the space before it (used to align expanders of similar depths)
    */
    function renderIconHtml(depth) {
        var html = '';
        for (var i = 0; i < depth; i++) {
            html += '<span class="fc-icon"></span>';
        }
        html +=
            '<span class="fc-expander-space">' +
                '<span class="fc-icon"></span>' +
                '</span>';
        return html;
    }

    var ResourceRow = /** @class */ (function (_super) {
        __extends(ResourceRow, _super);
        function ResourceRow(context, a, b, c, d, timeAxis) {
            var _this = _super.call(this, context, a, b, c, d) || this;
            _this._updateTrResourceId = core.memoizeRendering(updateTrResourceId);
            _this.spreadsheetRow = new SpreadsheetRow(context, _this.spreadsheetTr);
            _this.timeAxisTr.appendChild(core.createElement('td', { className: _this.theme.getClass('widgetContent') }, _this.innerContainerEl = document.createElement('div')));
            _this.lane = new TimelinePlugin.TimelineLane(context, _this.innerContainerEl, _this.innerContainerEl, timeAxis);
            return _this;
        }
        ResourceRow.prototype.destroy = function () {
            this.spreadsheetRow.destroy();
            this.lane.destroy();
            _super.prototype.destroy.call(this);
        };
        ResourceRow.prototype.render = function (props) {
            // spreadsheetRow handles calling updateTrResourceId for spreadsheetTr
            this.spreadsheetRow.receiveProps({
                colSpecs: props.colSpecs,
                id: props.id,
                rowSpans: props.rowSpans,
                depth: props.depth,
                isExpanded: props.isExpanded,
                hasChildren: props.hasChildren,
                resource: props.resource
            });
            this._updateTrResourceId(this.timeAxisTr, props.resource.id);
            this.lane.receiveProps({
                dateProfile: props.dateProfile,
                nextDayThreshold: props.nextDayThreshold,
                businessHours: props.businessHours,
                eventStore: props.eventStore,
                eventUiBases: props.eventUiBases,
                dateSelection: props.dateSelection,
                eventSelection: props.eventSelection,
                eventDrag: props.eventDrag,
                eventResize: props.eventResize
            });
            this.isSizeDirty = true;
        };
        ResourceRow.prototype.updateSize = function (isResize) {
            _super.prototype.updateSize.call(this, isResize);
            this.lane.updateSize(isResize);
        };
        ResourceRow.prototype.getHeightEls = function () {
            return [this.spreadsheetRow.heightEl, this.innerContainerEl];
        };
        return ResourceRow;
    }(Row));
    ResourceRow.addEqualityFuncs({
        rowSpans: core.isArraysEqual // HACK for isSizeDirty, ResourceTimelineView::renderRows
    });

    var COL_MIN_WIDTH = 30;
    var SpreadsheetHeader = /** @class */ (function (_super) {
        __extends(SpreadsheetHeader, _super);
        function SpreadsheetHeader(context, parentEl) {
            var _this = _super.call(this, context) || this;
            _this.resizables = [];
            _this.colWidths = [];
            _this.emitter = new core.EmitterMixin();
            parentEl.appendChild(_this.tableEl = core.createElement('table', {
                className: _this.theme.getClass('tableGrid')
            }));
            return _this;
        }
        SpreadsheetHeader.prototype.destroy = function () {
            for (var _i = 0, _a = this.resizables; _i < _a.length; _i++) {
                var resizable = _a[_i];
                resizable.destroy();
            }
            core.removeElement(this.tableEl);
            _super.prototype.destroy.call(this);
        };
        SpreadsheetHeader.prototype.render = function (props) {
            var theme = this.theme;
            var colSpecs = props.colSpecs;
            var html = '<colgroup>' + props.colTags + '</colgroup>' +
                '<tbody>';
            if (props.superHeaderText) {
                html +=
                    '<tr class="fc-super">' +
                        '<th class="' + theme.getClass('widgetHeader') + '" colspan="' + colSpecs.length + '">' +
                        '<div class="fc-cell-content">' +
                        '<span class="fc-cell-text">' +
                        core.htmlEscape(props.superHeaderText) +
                        '</span>' +
                        '</div>' +
                        '</th>' +
                        '</tr>';
            }
            html += '<tr>';
            for (var i = 0; i < colSpecs.length; i++) {
                var o = colSpecs[i];
                var isLast = i === (colSpecs.length - 1);
                html +=
                    "<th class=\"" + theme.getClass('widgetHeader') + "\">" +
                        '<div>' +
                        '<div class="fc-cell-content">' +
                        (o.isMain ?
                            '<span class="fc-expander-space">' +
                                '<span class="fc-icon"></span>' +
                                '</span>' :
                            '') +
                        '<span class="fc-cell-text">' +
                        core.htmlEscape(o.labelText || '') + // what about normalizing this value ahead of time?
                        '</span>' +
                        '</div>' +
                        (!isLast ? '<div class="fc-col-resizer"></div>' : '') +
                        '</div>' +
                        '</th>';
            }
            html += '</tr>';
            html += '</tbody>';
            this.tableEl.innerHTML = html;
            this.thEls = Array.prototype.slice.call(this.tableEl.querySelectorAll('th'));
            this.colEls = Array.prototype.slice.call(this.tableEl.querySelectorAll('col'));
            this.resizerEls = Array.prototype.slice.call(this.tableEl.querySelectorAll('.fc-col-resizer'));
            this.initColResizing();
        };
        SpreadsheetHeader.prototype.initColResizing = function () {
            var _this = this;
            var ElementDraggingImpl = this.calendar.pluginSystem.hooks.elementDraggingImpl;
            if (ElementDraggingImpl) {
                this.resizables = this.resizerEls.map(function (handleEl, colIndex) {
                    var dragging = new ElementDraggingImpl(handleEl);
                    var startWidth;
                    dragging.emitter.on('dragstart', function () {
                        startWidth = _this.colWidths[colIndex];
                        if (typeof startWidth !== 'number') {
                            startWidth = _this.thEls[colIndex].getBoundingClientRect().width;
                        }
                    });
                    dragging.emitter.on('dragmove', function (pev) {
                        _this.colWidths[colIndex] = Math.max(startWidth + pev.deltaX * (_this.isRtl ? -1 : 1), COL_MIN_WIDTH);
                        _this.emitter.trigger('colwidthchange', _this.colWidths);
                    });
                    dragging.setAutoScrollEnabled(false); // because gets weird with auto-scrolling time area
                    return dragging;
                });
            }
        };
        return SpreadsheetHeader;
    }(core.Component));

    var Spreadsheet = /** @class */ (function (_super) {
        __extends(Spreadsheet, _super);
        function Spreadsheet(context, headParentEl, bodyParentEl) {
            var _this = _super.call(this, context) || this;
            _this._renderCells = core.memoizeRendering(_this.renderCells, _this.unrenderCells);
            _this.layout = new TimelinePlugin.HeaderBodyLayout(headParentEl, bodyParentEl, 'clipped-scroll');
            var headerEnhancedScroller = _this.layout.headerScroller.enhancedScroll;
            var bodyEnhancedScroller = _this.layout.bodyScroller.enhancedScroll;
            _this.header = new SpreadsheetHeader(context, headerEnhancedScroller.canvas.contentEl);
            _this.header.emitter.on('colwidthchange', function (colWidths) {
                _this.applyColWidths(colWidths);
            });
            bodyEnhancedScroller.canvas.contentEl
                .appendChild(_this.bodyContainerEl = core.createElement('div', { className: 'fc-rows' }, '<table>' +
                '<colgroup />' +
                '<tbody />' +
                '</table>'));
            _this.bodyColGroup = _this.bodyContainerEl.querySelector('colgroup');
            _this.bodyTbody = _this.bodyContainerEl.querySelector('tbody');
            return _this;
        }
        Spreadsheet.prototype.destroy = function () {
            this.header.destroy();
            this.layout.destroy();
            this._renderCells.unrender();
            _super.prototype.destroy.call(this);
        };
        Spreadsheet.prototype.render = function (props) {
            this._renderCells(props.superHeaderText, props.colSpecs);
        };
        Spreadsheet.prototype.renderCells = function (superHeaderText, colSpecs) {
            var colTags = this.renderColTags(colSpecs);
            this.header.receiveProps({
                superHeaderText: superHeaderText,
                colSpecs: colSpecs,
                colTags: colTags
            });
            this.bodyColGroup.innerHTML = colTags;
            this.bodyColEls = Array.prototype.slice.call(this.bodyColGroup.querySelectorAll('col'));
            this.applyColWidths(colSpecs.map(function (colSpec) { return colSpec.width; }));
        };
        Spreadsheet.prototype.unrenderCells = function () {
            this.bodyColGroup.innerHTML = '';
        };
        Spreadsheet.prototype.renderColTags = function (colSpecs) {
            var html = '';
            for (var _i = 0, colSpecs_1 = colSpecs; _i < colSpecs_1.length; _i++) {
                var o = colSpecs_1[_i];
                if (o.isMain) {
                    html += '<col class="fc-main-col"/>';
                }
                else {
                    html += '<col/>';
                }
            }
            return html;
        };
        Spreadsheet.prototype.updateSize = function (isResize, totalHeight, isAuto) {
            this.layout.setHeight(totalHeight, isAuto);
        };
        Spreadsheet.prototype.applyColWidths = function (colWidths) {
            var _this = this;
            colWidths.forEach(function (colWidth, colIndex) {
                var headEl = _this.header.colEls[colIndex]; // bad to access child
                var bodyEl = _this.bodyColEls[colIndex];
                var styleVal;
                if (typeof colWidth === 'number') {
                    styleVal = colWidth + 'px';
                }
                else if (typeof colWidth == null) {
                    styleVal = '';
                }
                headEl.style.width = bodyEl.style.width = styleVal;
            });
        };
        return Spreadsheet;
    }(core.Component));

    var MIN_RESOURCE_AREA_WIDTH = 30; // definitely bigger than scrollbars
    var ResourceTimelineView = /** @class */ (function (_super) {
        __extends(ResourceTimelineView, _super);
        function ResourceTimelineView(context, viewSpec, dateProfileGenerator, parentEl) {
            var _this = _super.call(this, context, viewSpec, dateProfileGenerator, parentEl) || this;
            _this.isStickyScrollDirty = false;
            _this.rowNodes = [];
            _this.rowComponents = [];
            _this.rowComponentsById = {};
            _this.resourceAreaWidthDraggings = [];
            _this.splitter = new ResourceCommonPlugin.ResourceSplitter(); // doesn't let it do businessHours tho
            _this.hasResourceBusinessHours = core.memoize(hasResourceBusinessHours);
            _this.buildRowNodes = core.memoize(ResourceCommonPlugin.buildRowNodes);
            _this.hasNesting = core.memoize(hasNesting);
            _this._updateHasNesting = core.memoizeRendering(_this.updateHasNesting);
            var allColSpecs = _this.opt('resourceColumns') || [];
            var labelText = _this.opt('resourceLabelText'); // TODO: view.override
            var defaultLabelText = 'Resources'; // TODO: view.defaults
            var superHeaderText = null;
            if (!allColSpecs.length) {
                allColSpecs.push({
                    labelText: labelText || defaultLabelText,
                    text: ResourceCommonPlugin.buildResourceTextFunc(_this.opt('resourceText'), _this.calendar)
                });
            }
            else {
                superHeaderText = labelText;
            }
            var plainColSpecs = [];
            var groupColSpecs = [];
            var groupSpecs = [];
            var isVGrouping = false;
            var isHGrouping = false;
            for (var _i = 0, allColSpecs_1 = allColSpecs; _i < allColSpecs_1.length; _i++) {
                var colSpec = allColSpecs_1[_i];
                if (colSpec.group) {
                    groupColSpecs.push(colSpec);
                }
                else {
                    plainColSpecs.push(colSpec);
                }
            }
            plainColSpecs[0].isMain = true;
            if (groupColSpecs.length) {
                groupSpecs = groupColSpecs;
                isVGrouping = true;
            }
            else {
                var hGroupField = _this.opt('resourceGroupField');
                if (hGroupField) {
                    isHGrouping = true;
                    groupSpecs.push({
                        field: hGroupField,
                        text: _this.opt('resourceGroupText'),
                        render: _this.opt('resourceGroupRender')
                    });
                }
            }
            var allOrderSpecs = core.parseFieldSpecs(_this.opt('resourceOrder'));
            var plainOrderSpecs = [];
            for (var _a = 0, allOrderSpecs_1 = allOrderSpecs; _a < allOrderSpecs_1.length; _a++) {
                var orderSpec = allOrderSpecs_1[_a];
                var isGroup = false;
                for (var _b = 0, groupSpecs_1 = groupSpecs; _b < groupSpecs_1.length; _b++) {
                    var groupSpec = groupSpecs_1[_b];
                    if (groupSpec.field === orderSpec.field) {
                        groupSpec.order = orderSpec.order; // -1, 0, 1
                        isGroup = true;
                        break;
                    }
                }
                if (!isGroup) {
                    plainOrderSpecs.push(orderSpec);
                }
            }
            _this.superHeaderText = superHeaderText;
            _this.isVGrouping = isVGrouping;
            _this.isHGrouping = isHGrouping;
            _this.groupSpecs = groupSpecs;
            _this.colSpecs = groupColSpecs.concat(plainColSpecs);
            _this.orderSpecs = plainOrderSpecs;
            // START RENDERING...
            _this.el.classList.add('fc-timeline');
            if (_this.opt('eventOverlap') === false) {
                _this.el.classList.add('fc-no-overlap');
            }
            _this.el.innerHTML = _this.renderSkeletonHtml();
            _this.resourceAreaHeadEl = _this.el.querySelector('thead .fc-resource-area');
            _this.setResourceAreaWidth(_this.opt('resourceAreaWidth'));
            _this.initResourceAreaWidthDragging();
            _this.miscHeight = _this.el.offsetHeight;
            _this.spreadsheet = new Spreadsheet(_this.context, _this.resourceAreaHeadEl, _this.el.querySelector('tbody .fc-resource-area'));
            _this.timeAxis = new TimelinePlugin.TimeAxis(_this.context, _this.el.querySelector('thead .fc-time-area'), _this.el.querySelector('tbody .fc-time-area'));
            var timeAxisRowContainer = core.createElement('div', { className: 'fc-rows' }, '<table><tbody /></table>');
            _this.timeAxis.layout.bodyScroller.enhancedScroll.canvas.contentEl.appendChild(timeAxisRowContainer);
            _this.timeAxisTbody = timeAxisRowContainer.querySelector('tbody');
            _this.lane = new TimelinePlugin.TimelineLane(_this.context, null, _this.timeAxis.layout.bodyScroller.enhancedScroll.canvas.bgEl, _this.timeAxis);
            _this.bodyScrollJoiner = new TimelinePlugin.ScrollJoiner('vertical', [
                _this.spreadsheet.layout.bodyScroller,
                _this.timeAxis.layout.bodyScroller
            ]);
            // after scrolljoiner
            _this.spreadsheetBodyStickyScroller = new TimelinePlugin.StickyScroller(_this.spreadsheet.layout.bodyScroller.enhancedScroll, _this.isRtl, true // isVertical
            );
            _this.spreadsheet.receiveProps({
                superHeaderText: _this.superHeaderText,
                colSpecs: _this.colSpecs
            });
            // Component...
            context.calendar.registerInteractiveComponent(_this, {
                el: _this.timeAxis.slats.el
            });
            return _this;
        }
        ResourceTimelineView.prototype.renderSkeletonHtml = function () {
            var theme = this.theme;
            return "<table class=\"" + theme.getClass('tableGrid') + "\"> <thead class=\"fc-head\"> <tr> <td class=\"fc-resource-area " + theme.getClass('widgetHeader') + "\"></td> <td class=\"fc-divider fc-col-resizer " + theme.getClass('widgetHeader') + "\"></td> <td class=\"fc-time-area " + theme.getClass('widgetHeader') + "\"></td> </tr> </thead> <tbody class=\"fc-body\"> <tr> <td class=\"fc-resource-area " + theme.getClass('widgetContent') + "\"></td> <td class=\"fc-divider fc-col-resizer " + theme.getClass('widgetHeader') + "\"></td> <td class=\"fc-time-area " + theme.getClass('widgetContent') + "\"></td> </tr> </tbody> </table>";
        };
        ResourceTimelineView.prototype.render = function (props) {
            _super.prototype.render.call(this, props);
            var splitProps = this.splitter.splitProps(props);
            var hasResourceBusinessHours = this.hasResourceBusinessHours(props.resourceStore);
            this.timeAxis.receiveProps({
                dateProfile: props.dateProfile
            });
            // for all-resource bg events / selections / business-hours
            this.lane.receiveProps(__assign({}, splitProps[''], { dateProfile: props.dateProfile, nextDayThreshold: this.nextDayThreshold, businessHours: hasResourceBusinessHours ? null : props.businessHours }));
            var newRowNodes = this.buildRowNodes(props.resourceStore, this.groupSpecs, this.orderSpecs, this.isVGrouping, props.resourceEntityExpansions, this.opt('resourcesInitiallyExpanded'));
            this._updateHasNesting(this.hasNesting(newRowNodes));
            this.diffRows(newRowNodes);
            this.renderRows(props.dateProfile, hasResourceBusinessHours ? props.businessHours : null, // CONFUSING, comment
            splitProps);
        };
        ResourceTimelineView.prototype.updateHasNesting = function (isNesting) {
            var classList = this.el.classList;
            if (isNesting) {
                classList.remove('fc-flat');
            }
            else {
                classList.add('fc-flat');
            }
        };
        ResourceTimelineView.prototype.diffRows = function (newNodes) {
            var oldNodes = this.rowNodes;
            var oldLen = oldNodes.length;
            var oldIndexHash = {}; // id -> index
            var oldI = 0;
            var newI = 0;
            for (oldI = 0; oldI < oldLen; oldI++) {
                oldIndexHash[oldNodes[oldI].id] = oldI;
            }
            // iterate new nodes
            for (oldI = 0, newI = 0; newI < newNodes.length; newI++) {
                var newNode = newNodes[newI];
                var oldIFound = oldIndexHash[newNode.id];
                if (oldIFound != null && oldIFound >= oldI) {
                    this.removeRows(newI, oldIFound - oldI, oldNodes); // won't do anything if same index
                    oldI = oldIFound + 1;
                }
                else {
                    this.addRow(newI, newNode);
                }
            }
            // old rows that weren't found need to be removed
            this.removeRows(newI, oldLen - oldI, oldNodes); // won't do anything if same index
            this.rowNodes = newNodes;
        };
        /*
        rowComponents is the in-progress result
        */
        ResourceTimelineView.prototype.addRow = function (index, rowNode) {
            var _a = this, rowComponents = _a.rowComponents, rowComponentsById = _a.rowComponentsById;
            var nextComponent = rowComponents[index];
            var newComponent = this.buildChildComponent(rowNode, this.spreadsheet.bodyTbody, nextComponent ? nextComponent.spreadsheetTr : null, this.timeAxisTbody, nextComponent ? nextComponent.timeAxisTr : null);
            rowComponents.splice(index, 0, newComponent);
            rowComponentsById[rowNode.id] = newComponent;
        };
        ResourceTimelineView.prototype.removeRows = function (startIndex, len, oldRowNodes) {
            if (len) {
                var _a = this, rowComponents = _a.rowComponents, rowComponentsById = _a.rowComponentsById;
                for (var i = 0; i < len; i++) {
                    var rowComponent = rowComponents[startIndex + i];
                    rowComponent.destroy();
                    delete rowComponentsById[oldRowNodes[i].id];
                }
                rowComponents.splice(startIndex, len);
            }
        };
        ResourceTimelineView.prototype.buildChildComponent = function (node, spreadsheetTbody, spreadsheetNext, timeAxisTbody, timeAxisNext) {
            if (node.group) {
                return new GroupRow(this.context, spreadsheetTbody, spreadsheetNext, timeAxisTbody, timeAxisNext);
            }
            else if (node.resource) {
                return new ResourceRow(this.context, spreadsheetTbody, spreadsheetNext, timeAxisTbody, timeAxisNext, this.timeAxis);
            }
        };
        ResourceTimelineView.prototype.renderRows = function (dateProfile, fallbackBusinessHours, splitProps) {
            var _a = this, rowNodes = _a.rowNodes, rowComponents = _a.rowComponents;
            for (var i = 0; i < rowNodes.length; i++) {
                var rowNode = rowNodes[i];
                var rowComponent = rowComponents[i];
                if (rowNode.group) {
                    rowComponent.receiveProps({
                        spreadsheetColCnt: this.colSpecs.length,
                        id: rowNode.id,
                        isExpanded: rowNode.isExpanded,
                        group: rowNode.group
                    });
                }
                else {
                    var resource = rowNode.resource;
                    rowComponent.receiveProps(__assign({}, splitProps[resource.id], { dateProfile: dateProfile, nextDayThreshold: this.nextDayThreshold, businessHours: resource.businessHours || fallbackBusinessHours, colSpecs: this.colSpecs, id: rowNode.id, rowSpans: rowNode.rowSpans, depth: rowNode.depth, isExpanded: rowNode.isExpanded, hasChildren: rowNode.hasChildren, resource: rowNode.resource }));
                }
            }
        };
        ResourceTimelineView.prototype.updateSize = function (isResize, viewHeight, isAuto) {
            // FYI: this ordering is really important
            var calendar = this.calendar;
            var isBaseSizing = isResize || calendar.isViewUpdated || calendar.isDatesUpdated || calendar.isEventsUpdated;
            if (isBaseSizing) {
                this.syncHeadHeights();
                this.timeAxis.updateSize(isResize, viewHeight - this.miscHeight, isAuto);
                this.spreadsheet.updateSize(isResize, viewHeight - this.miscHeight, isAuto);
            }
            var rowSizingCnt = this.updateRowSizes(isResize);
            this.lane.updateSize(isResize); // is efficient. uses flags
            if (isBaseSizing || rowSizingCnt) {
                this.bodyScrollJoiner.update();
                this.timeAxis.layout.scrollJoiner.update(); // hack
                this.rowPositions = new core.PositionCache(this.timeAxis.slats.el, this.rowComponents.map(function (rowComponent) {
                    return rowComponent.timeAxisTr;
                }), false, // isHorizontal
                true // isVertical
                );
                this.rowPositions.build();
                this.isStickyScrollDirty = true;
            }
        };
        ResourceTimelineView.prototype.syncHeadHeights = function () {
            var spreadsheetHeadEl = this.spreadsheet.header.tableEl;
            var timeAxisHeadEl = this.timeAxis.header.tableEl;
            spreadsheetHeadEl.style.height = '';
            timeAxisHeadEl.style.height = '';
            var max = Math.max(spreadsheetHeadEl.offsetHeight, timeAxisHeadEl.offsetHeight);
            spreadsheetHeadEl.style.height =
                timeAxisHeadEl.style.height = max + 'px';
        };
        ResourceTimelineView.prototype.updateRowSizes = function (isResize) {
            var dirtyRowComponents = this.rowComponents;
            if (!isResize) {
                dirtyRowComponents = dirtyRowComponents.filter(function (rowComponent) {
                    return rowComponent.isSizeDirty;
                });
            }
            var elArrays = dirtyRowComponents.map(function (rowComponent) {
                return rowComponent.getHeightEls();
            });
            // reset to natural heights
            for (var _i = 0, elArrays_1 = elArrays; _i < elArrays_1.length; _i++) {
                var elArray = elArrays_1[_i];
                for (var _a = 0, elArray_1 = elArray; _a < elArray_1.length; _a++) {
                    var el = elArray_1[_a];
                    el.style.height = '';
                }
            }
            // let rows update their contents' heights
            for (var _b = 0, dirtyRowComponents_1 = dirtyRowComponents; _b < dirtyRowComponents_1.length; _b++) {
                var rowComponent = dirtyRowComponents_1[_b];
                rowComponent.updateSize(isResize); // will reset isSizeDirty
            }
            var maxHeights = elArrays.map(function (elArray) {
                var maxHeight = null;
                for (var _i = 0, elArray_2 = elArray; _i < elArray_2.length; _i++) {
                    var el = elArray_2[_i];
                    var height = el.getBoundingClientRect().height;
                    if (maxHeight === null || height > maxHeight) {
                        maxHeight = height;
                    }
                }
                return maxHeight;
            });
            for (var i = 0; i < elArrays.length; i++) {
                for (var _c = 0, _d = elArrays[i]; _c < _d.length; _c++) {
                    var el = _d[_c];
                    el.style.height = maxHeights[i] + 'px';
                }
            }
            return dirtyRowComponents.length;
        };
        ResourceTimelineView.prototype.destroy = function () {
            for (var _i = 0, _a = this.rowComponents; _i < _a.length; _i++) {
                var rowComponent = _a[_i];
                rowComponent.destroy();
            }
            this.rowNodes = [];
            this.rowComponents = [];
            this.spreadsheet.destroy();
            this.timeAxis.destroy();
            for (var _b = 0, _c = this.resourceAreaWidthDraggings; _b < _c.length; _b++) {
                var resourceAreaWidthDragging = _c[_b];
                resourceAreaWidthDragging.destroy();
            }
            this.spreadsheetBodyStickyScroller.destroy();
            _super.prototype.destroy.call(this);
            this.calendar.unregisterInteractiveComponent(this);
        };
        // Now Indicator
        // ------------------------------------------------------------------------------------------
        ResourceTimelineView.prototype.getNowIndicatorUnit = function (dateProfile) {
            return this.timeAxis.getNowIndicatorUnit(dateProfile);
        };
        ResourceTimelineView.prototype.renderNowIndicator = function (date) {
            this.timeAxis.renderNowIndicator(date);
        };
        ResourceTimelineView.prototype.unrenderNowIndicator = function () {
            this.timeAxis.unrenderNowIndicator();
        };
        // Scrolling
        // ------------------------------------------------------------------------------------------------------------------
        // this is useful for scrolling prev/next dates while resource is scrolled down
        ResourceTimelineView.prototype.queryScroll = function () {
            var scroll = _super.prototype.queryScroll.call(this);
            if (this.props.resourceStore) {
                __assign(scroll, this.queryResourceScroll());
            }
            return scroll;
        };
        ResourceTimelineView.prototype.applyScroll = function (scroll, isResize) {
            _super.prototype.applyScroll.call(this, scroll, isResize);
            if (this.props.resourceStore) {
                this.applyResourceScroll(scroll);
            }
            // avoid updating stickyscroll too often
            if (isResize || this.isStickyScrollDirty) {
                this.isStickyScrollDirty = false;
                this.spreadsheetBodyStickyScroller.updateSize();
                this.timeAxis.updateStickyScrollers();
            }
        };
        ResourceTimelineView.prototype.computeDateScroll = function (timeMs) {
            return this.timeAxis.computeDateScroll(timeMs);
        };
        ResourceTimelineView.prototype.queryDateScroll = function () {
            return this.timeAxis.queryDateScroll();
        };
        ResourceTimelineView.prototype.applyDateScroll = function (scroll) {
            this.timeAxis.applyDateScroll(scroll);
        };
        ResourceTimelineView.prototype.queryResourceScroll = function () {
            var _a = this, rowComponents = _a.rowComponents, rowNodes = _a.rowNodes;
            var scroll = {};
            var scrollerTop = this.timeAxis.layout.bodyScroller.el.getBoundingClientRect().top; // fixed position
            for (var i = 0; i < rowComponents.length; i++) {
                var rowComponent = rowComponents[i];
                var rowNode = rowNodes[i];
                var el = rowComponent.timeAxisTr;
                var elBottom = el.getBoundingClientRect().bottom; // fixed position
                if (elBottom > scrollerTop) {
                    scroll.rowId = rowNode.id;
                    scroll.bottom = elBottom - scrollerTop;
                    break;
                }
            }
            // TODO: what about left scroll state for spreadsheet area?
            return scroll;
        };
        ResourceTimelineView.prototype.applyResourceScroll = function (scroll) {
            var rowId = scroll.forcedRowId || scroll.rowId;
            if (rowId) {
                var rowComponent = this.rowComponentsById[rowId];
                if (rowComponent) {
                    var el = rowComponent.timeAxisTr;
                    if (el) {
                        var innerTop = this.timeAxis.layout.bodyScroller.enhancedScroll.canvas.el.getBoundingClientRect().top;
                        var rowRect = el.getBoundingClientRect();
                        var scrollTop = (scroll.forcedRowId ?
                            rowRect.top : // just use top edge
                            rowRect.bottom - scroll.bottom) - // pixels from bottom edge
                            innerTop;
                        this.timeAxis.layout.bodyScroller.enhancedScroll.setScrollTop(scrollTop);
                        this.spreadsheet.layout.bodyScroller.enhancedScroll.setScrollTop(scrollTop);
                    }
                }
            }
        };
        // TODO: scrollToResource
        // Hit System
        // ------------------------------------------------------------------------------------------
        ResourceTimelineView.prototype.buildPositionCaches = function () {
            this.timeAxis.slats.updateSize();
            this.rowPositions.build();
        };
        ResourceTimelineView.prototype.queryHit = function (positionLeft, positionTop) {
            var rowPositions = this.rowPositions;
            var slats = this.timeAxis.slats;
            var rowIndex = rowPositions.topToIndex(positionTop);
            if (rowIndex != null) {
                var resource = this.rowNodes[rowIndex].resource;
                if (resource) { // not a group
                    var slatHit = slats.positionToHit(positionLeft);
                    if (slatHit) {
                        return {
                            component: this,
                            dateSpan: {
                                range: slatHit.dateSpan.range,
                                allDay: slatHit.dateSpan.allDay,
                                resourceId: resource.id
                            },
                            rect: {
                                left: slatHit.left,
                                right: slatHit.right,
                                top: rowPositions.tops[rowIndex],
                                bottom: rowPositions.bottoms[rowIndex]
                            },
                            dayEl: slatHit.dayEl,
                            layer: 0
                        };
                    }
                }
            }
        };
        // Resource Area
        // ------------------------------------------------------------------------------------------------------------------
        ResourceTimelineView.prototype.setResourceAreaWidth = function (widthVal) {
            this.resourceAreaWidth = widthVal;
            core.applyStyleProp(this.resourceAreaHeadEl, 'width', widthVal || '');
        };
        ResourceTimelineView.prototype.initResourceAreaWidthDragging = function () {
            var _this = this;
            var resourceAreaDividerEls = Array.prototype.slice.call(this.el.querySelectorAll('.fc-col-resizer'));
            var ElementDraggingImpl = this.calendar.pluginSystem.hooks.elementDraggingImpl;
            if (ElementDraggingImpl) {
                this.resourceAreaWidthDraggings = resourceAreaDividerEls.map(function (el) {
                    var dragging = new ElementDraggingImpl(el);
                    var dragStartWidth;
                    var viewWidth;
                    dragging.emitter.on('dragstart', function () {
                        dragStartWidth = _this.resourceAreaWidth;
                        if (typeof dragStartWidth !== 'number') {
                            dragStartWidth = _this.resourceAreaHeadEl.getBoundingClientRect().width;
                        }
                        viewWidth = _this.el.getBoundingClientRect().width;
                    });
                    dragging.emitter.on('dragmove', function (pev) {
                        var newWidth = dragStartWidth + pev.deltaX * (_this.isRtl ? -1 : 1);
                        newWidth = Math.max(newWidth, MIN_RESOURCE_AREA_WIDTH);
                        newWidth = Math.min(newWidth, viewWidth - MIN_RESOURCE_AREA_WIDTH);
                        _this.setResourceAreaWidth(newWidth);
                    });
                    dragging.setAutoScrollEnabled(false); // because gets weird with auto-scrolling time area
                    return dragging;
                });
            }
        };
        ResourceTimelineView.needsResourceData = true; // for ResourceViewProps
        return ResourceTimelineView;
    }(core.View));
    function hasResourceBusinessHours(resourceStore) {
        for (var resourceId in resourceStore) {
            var resource = resourceStore[resourceId];
            if (resource.businessHours) {
                return true;
            }
        }
        return false;
    }
    function hasNesting(nodes) {
        for (var _i = 0, nodes_1 = nodes; _i < nodes_1.length; _i++) {
            var node = nodes_1[_i];
            if (node.group) {
                return true;
            }
            else if (node.resource) {
                if (node.hasChildren) {
                    return true;
                }
            }
        }
        return false;
    }

    var main = core.createPlugin({
        deps: [ResourceCommonPlugin__default, TimelinePlugin__default],
        defaultView: 'resourceTimelineDay',
        views: {
            resourceTimeline: {
                class: ResourceTimelineView,
                resourceAreaWidth: '30%',
                resourcesInitiallyExpanded: true,
                eventResizableFromStart: true // TODO: not DRY with this same setting in the main timeline config
            },
            resourceTimelineDay: {
                type: 'resourceTimeline',
                duration: { days: 1 }
            },
            resourceTimelineWeek: {
                type: 'resourceTimeline',
                duration: { weeks: 1 }
            },
            resourceTimelineMonth: {
                type: 'resourceTimeline',
                duration: { months: 1 }
            },
            resourceTimelineYear: {
                type: 'resourceTimeline',
                duration: { years: 1 }
            }
        }
    });

    exports.ResourceTimelineView = ResourceTimelineView;
    exports.default = main;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
