/* Core JS single-file minimized bundle (generated) */
(function(){
"use strict";
var __core_modules__={0:function(module,exports,__core_require__){
// src/index.js
const Core = __core_require__(1);
const Module = __core_require__(3);
const Loader = __core_require__(5);
const Event = __core_require__(4);
const Filter = __core_require__(6);
const Options = __core_require__(7);
const Errors = __core_require__(8);
const Introspect = __core_require__(9);
const Request = __core_require__(12);
const Response = __core_require__(13);
const URL = __core_require__(14);
const RouteGroup = __core_require__(11);
const Route = __core_require__(10);
const SQL = __core_require__(15);
const Schema = __core_require__(18);
const Model = __core_require__(19);
const MemoryAdapter = __core_require__(16);
const SQLiteAdapter = __core_require__(17);
const Session = __core_require__(20);
const Message = __core_require__(21);
const Cache = __core_require__(22);
const Token = __core_require__(25);
const i18n = __core_require__(26);
const { Auth, CSRF } = __core_require__(27);
const Gate = __core_require__(28);
const RateLimiter = __core_require__(29);
const SecurityHeaders = __core_require__(30);
const File = __core_require__(31);
const Schedule = __core_require__(32);
const { Work, TaskCoroutine } = __core_require__(33);
const Text = __core_require__(34);
const Hash = __core_require__(35);
const Password = __core_require__(36);
const { HTTP, HTTP_Request, HTTP_Response } = __core_require__(37);
const CLI = __core_require__(38);
const Service = __core_require__(39);
const Dictionary = __core_require__(40);
const Map = __core_require__(42);
const Structure = __core_require__(43);
const Collection = __core_require__(44);
const Resource = __core_require__(45);
const REST = __core_require__(46);
const API = __core_require__(47);

module.exports = {
  Core,
  Module,
  Loader,
  Event,
  Filter,
  Options,
  Errors,
  Introspect,
  Request,
  Response,
  URL,
  RouteGroup,
  Route,
  SQL,
  Schema,
  Model,
  MemoryAdapter,
  SQLiteAdapter,
  Session,
  Message,
  Cache,
  Token,
  i18n,
  Auth,
  CSRF,
  Gate,
  RateLimiter,
  SecurityHeaders,
  File,
  Schedule,
  Work,
  TaskCoroutine,
  Text,
  Hash,
  Password,
  HTTP,
  HTTP_Request,
  HTTP_Response,
  CLI,
  Service,
  Dictionary,
  Map,
  Structure,
  Collection,
  Resource,
  REST,
  API,
};
},
1:function(module,exports,__core_require__){
// src/facades/Core.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Event = __core_require__(4);

class Core extends ModuleSupport {
  static VERSION = "0.1.0";

  static version() {
    return this.VERSION;
  }

  static diagnostics() {
    return {
      version: this.VERSION,
      node: process.version,
      platform: process.platform,
      arch: process.arch,
      pid: process.pid,
    };
  }

  static log(level, message, context = {}) {
    Event.trigger("core.log", level, message, context);
  }
}

registerClass("Core", Core);
module.exports = Core;
},
2:function(module,exports,__core_require__){
// src/kernel/registry.js
const state = {
  classes: new Map(),
  extensions: new Map(),
};

function registerClass(name, ref) {
  state.classes.set(name, ref);
  if (!state.extensions.has(name)) state.extensions.set(name, new Set());
}

function registerExtension(className, method) {
  if (!state.extensions.has(className)) state.extensions.set(className, new Set());
  state.extensions.get(className).add(method);
}

function classes() {
  return Array.from(state.classes.keys()).sort();
}

function methods(className) {
  const ref = state.classes.get(className);
  if (!ref) return [];
  const own = Object.getOwnPropertyNames(ref)
    .filter((k) => typeof ref[k] === "function" && !["length", "name", "prototype"].includes(k));
  const ext = Array.from(state.extensions.get(className) || []);
  return Array.from(new Set([...own, ...ext])).sort();
}

function extensions(className) {
  return Array.from(state.extensions.get(className) || []).sort();
}

function classRef(name) {
  return state.classes.get(name);
}

module.exports = {
  registerClass,
  registerExtension,
  classes,
  methods,
  extensions,
  classRef,
};
},
3:function(module,exports,__core_require__){
// src/facades/Module.js
const { registerExtension } = __core_require__(2);

class ModuleSupport {
  static __extensions = new Map();

  static extend(method, callback = null) {
    const methods = callback === null && typeof method === "object" ? method : { [method]: callback };
    for (const [name, fn] of Object.entries(methods)) {
      if (typeof fn !== "function") {
        throw new TypeError(`Extension '${name}' must be a function`);
      }
      this.__extensions.set(name, fn);
      this[name] = (...args) => fn.apply(this, args);
      registerExtension(this.name, name);
    }
  }
}

module.exports = ModuleSupport;
},
4:function(module,exports,__core_require__){
// src/facades/Event.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class Event extends ModuleSupport {
  static #events = new Map();

  static on(name, callback) {
    if (!this.#events.has(name)) this.#events.set(name, []);
    this.#events.get(name).push(callback);
    return callback;
  }

  static off(name, callback = null) {
    if (!this.#events.has(name)) return;
    if (!callback) {
      this.#events.delete(name);
      return;
    }
    const next = this.#events.get(name).filter((fn) => fn !== callback);
    if (next.length) this.#events.set(name, next);
    else this.#events.delete(name);
  }

  static trigger(name, ...args) {
    const handlers = this.#events.get(name) || [];
    for (const handler of handlers) handler(...args);
  }

  static has(name) {
    return (this.#events.get(name) || []).length > 0;
  }

  static reset() {
    this.#events.clear();
  }
}

registerClass("Event", Event);
module.exports = Event;
},
5:function(module,exports,__core_require__){
// src/facades/Loader.js
const path = require("node:path");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class Loader extends ModuleSupport {
  static #paths = [];

  static addPath(dirPath, name = null) {
    this.#paths.push({ path: dirPath, name });
  }

  static paths() {
    return [...this.#paths];
  }

  static register() {
    return true;
  }

  static resolve(className) {
    const normalized = className.replace(/\\/g, "/");
    const candidates = [
      `${normalized}.js`,
      `${normalized.replace(/_/g, "/")}.js`,
    ];
    for (const entry of this.#paths) {
      for (const candidate of candidates) {
        const full = path.join(entry.path, candidate);
        try {
          return require(full);
        } catch {
          // keep searching
        }
      }
    }
    return null;
  }
}

Loader.addPath(path.join(__dirname));
registerClass("Loader", Loader);
module.exports = Loader;
},
6:function(module,exports,__core_require__){
// src/facades/Filter.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class Filter extends ModuleSupport {
  static #filters = new Map();

  static add(name, callback) {
    if (!this.#filters.has(name)) this.#filters.set(name, []);
    this.#filters.get(name).push(callback);
    return callback;
  }

  static remove(name, callback = null) {
    if (!this.#filters.has(name)) return;
    if (!callback) {
      this.#filters.delete(name);
      return;
    }
    const next = this.#filters.get(name).filter((fn) => fn !== callback);
    if (next.length) this.#filters.set(name, next);
    else this.#filters.delete(name);
  }

  static apply(name, value, ...args) {
    const handlers = this.#filters.get(name) || [];
    return handlers.reduce((acc, fn) => fn(acc, ...args), value);
  }

  static has(name) {
    return (this.#filters.get(name) || []).length > 0;
  }

  static reset() {
    this.#filters.clear();
  }
}

registerClass("Filter", Filter);
module.exports = Filter;
},
7:function(module,exports,__core_require__){
// src/facades/Options.js
const fs = require("node:fs");
const path = require("node:path");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Filter = __core_require__(6);
const Event = __core_require__(4);

function asObject(value) {
  return value && typeof value === "object" && !Array.isArray(value);
}

function deepMerge(target, source) {
  const out = { ...target };
  for (const [key, value] of Object.entries(source)) {
    if (asObject(value) && asObject(out[key])) out[key] = deepMerge(out[key], value);
    else out[key] = value;
  }
  return out;
}

function setByPath(target, keyPath, value) {
  const parts = keyPath.split(".").filter(Boolean);
  if (!parts.length) return;
  let ref = target;
  for (let i = 0; i < parts.length - 1; i += 1) {
    const key = parts[i];
    if (!asObject(ref[key])) ref[key] = {};
    ref = ref[key];
  }
  ref[parts.at(-1)] = value;
}

function getByPath(target, keyPath, fallback = undefined) {
  const parts = keyPath.split(".").filter(Boolean);
  let ref = target;
  for (const part of parts) {
    if (!asObject(ref) && !Array.isArray(ref)) return fallback;
    if (!(part in ref)) return fallback;
    ref = ref[part];
  }
  return ref;
}

class Options extends ModuleSupport {
  static #fields = {};

  static reset() {
    this.#fields = {};
  }

  static all() {
    return structuredClone(this.#fields);
  }

  static get(pathKey, fallback = null) {
    return getByPath(this.#fields, pathKey, fallback);
  }

  static set(pathKey, value) {
    setByPath(this.#fields, pathKey, value);
    return value;
  }

  static merge(input) {
    this.#fields = deepMerge(this.#fields, input || {});
  }

  static loadArray(input, prefixPath = null) {
    const filtered = Filter.apply("load.array", Filter.apply("load", input));
    if (prefixPath) this.set(prefixPath, filtered);
    else this.merge(filtered);
    Event.trigger("options.loaded", filtered);
  }

  static loadJSON(filePath, prefixPath = null) {
    const data = JSON.parse(fs.readFileSync(filePath, "utf8"));
    const filtered = Filter.apply("load.json", Filter.apply("load", data));
    this.loadArray(filtered, prefixPath);
  }

  static loadENV(dir, envName = ".env", prefixPath = null) {
    const filePath = path.join(dir, envName);
    const data = fs.readFileSync(filePath, "utf8");
    const results = {};
    for (const raw of data.split(/\r?\n/)) {
      const line = raw.trim();
      if (!line || line.startsWith("#") || !line.includes("=")) continue;
      const idx = line.indexOf("=");
      const key = line.slice(0, idx).replace(/^export\s+/, "").trim();
      const value = line.slice(idx + 1).trim().replace(/^['"]|['"]$/g, "");
      results[key] = value.replace(/\$\{([A-Za-z0-9_]+)\}/g, (_, name) => results[name] || process.env[name] || "");
      process.env[key] = results[key];
    }
    const filtered = Filter.apply("load.env", Filter.apply("load", results));
    this.loadArray(filtered, prefixPath);
  }
}

registerClass("Options", Options);
module.exports = Options;
},
8:function(module,exports,__core_require__){
// src/facades/Errors.js
const os = require("node:os");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Event = __core_require__(4);

class Errors extends ModuleSupport {
  static JSON = "json";
  static JSON_VERBOSE = "json_verbose";
  static TEXT = "text";

  static #mode = Errors.TEXT;
  static #captured = false;

  static mode(nextMode = null) {
    if (nextMode !== null) this.#mode = nextMode;
    return this.#mode;
  }

  static capture() {
    if (this.#captured) return;
    this.#captured = true;
    process.on("uncaughtException", (error) => {
      this.report(error);
    });
    process.on("unhandledRejection", (error) => {
      this.report(error instanceof Error ? error : new Error(String(error)));
    });
  }

  static report(error) {
    const payload = {
      error: error.message,
      type: error.name,
      code: error.code || 0,
    };
    if (this.#mode === Errors.JSON_VERBOSE) {
      payload.file = error.fileName || "";
      payload.line = Number(error.lineNumber || 0);
      payload.trace = String(error.stack || "").split(os.EOL).filter(Boolean);
    }
    const output = this.#mode.startsWith("json") ? JSON.stringify(payload) : `${payload.type}: ${payload.error}`;
    Event.trigger("error", payload);
    return output;
  }
}

registerClass("Errors", Errors);
module.exports = Errors;
},
9:function(module,exports,__core_require__){
// src/facades/Introspect.js
const { registerClass, classes, methods, extensions, classRef } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Route = __core_require__(10);

class Introspect extends ModuleSupport {
  static classes() {
    return classes();
  }

  static methods(className) {
    return methods(className);
  }

  static extensions(className) {
    return extensions(className);
  }

  static routes() {
    return Route.routes().map((route) => ({
      pattern: route.pattern,
      methods: Array.from(route.methods),
      tag: route.tagName || "",
      dynamic: route.dynamic,
    }));
  }

  static capabilities() {
    return {
      node: process.version,
      fetch: typeof fetch === "function",
      worker_threads: (() => {
        try {
          require("node:worker_threads");
          return true;
        } catch {
          return false;
        }
      })(),
      webcrypto: !!(globalThis.crypto && globalThis.crypto.subtle),
      fs: true,
      http2: (() => {
        try {
          require("node:http2");
          return true;
        } catch {
          return false;
        }
      })(),
    };
  }

  static class(className) {
    return classRef(className) || null;
  }
}

registerClass("Introspect", Introspect);
module.exports = Introspect;
},
10:function(module,exports,__core_require__){
// src/facades/Route.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const RouteGroup = __core_require__(11);
const Options = __core_require__(7);
const Request = __core_require__(12);
const Response = __core_require__(13);
const URL = __core_require__(14);
const Event = __core_require__(4);
const Filter = __core_require__(6);

function stripQuery(uri) {
  return String(uri || "/").split("?")[0] || "/";
}

function normalizeUri(uri) {
  const clean = stripQuery(uri).replace(/\/+/g, "/");
  if (clean.length > 1 && clean.endsWith("/")) return clean.slice(0, -1);
  return clean || "/";
}

function compilePattern(pattern, rules = {}) {
  const params = [];
  let out = "";
  for (let i = 0; i < pattern.length; i += 1) {
    const ch = pattern[i];
    if (ch === "(") {
      out += "(?:";
      continue;
    }
    if (ch === ")") {
      out += ")?";
      continue;
    }
    if (ch === ":") {
      let j = i + 1;
      while (j < pattern.length && /[A-Za-z0-9_]/.test(pattern[j])) j += 1;
      const name = pattern.slice(i + 1, j);
      i = j - 1;
      params.push(name);
      out += `(?<${name}>${rules[name] || "[^/]+"})`;
      continue;
    }
    if (".^$+{}[]|".includes(ch)) out += `\\${ch}`;
    else out += ch;
  }
  return {
    regex: new RegExp(`^${out}$`),
    params,
    dynamic: params.length > 0 || pattern.includes("(") || pattern.includes(")"),
  };
}

function flattenPushInput(resource, as = "text") {
  const entries = [];
  if (Array.isArray(resource)) {
    for (const r of resource) entries.push(...flattenPushInput(r, as));
    return entries;
  }
  if (resource && typeof resource === "object") {
    for (const [k, v] of Object.entries(resource)) {
      const nextAs = Number.isInteger(Number(k)) ? "text" : k;
      entries.push(...flattenPushInput(v, nextAs));
    }
    return entries;
  }
  entries.push([resource, as]);
  return entries;
}

function buildTaggedURL(pattern, params = {}) {
  let working = pattern;
  const optionalGroup = /\(([^()]+)\)/g;
  while (optionalGroup.test(working)) {
    optionalGroup.lastIndex = 0;
    working = working.replace(optionalGroup, (_, inner) => {
      const names = [...inner.matchAll(/:([A-Za-z0-9_]+)/g)].map((m) => m[1]);
      if (names.length === 0) return inner;
      const include = names.some((name) => params[name] !== undefined && params[name] !== null);
      return include ? inner : "";
    });
  }
  working = working.replace(/:([A-Za-z0-9_]+)/g, (_, name) => {
    const value = params[name];
    if (value === undefined || value === null) return "";
    return String(value);
  });
  working = working.replace(/\(\)/g, "").replace(/\/+/g, "/");
  if (working.length > 1 && working.endsWith("/")) working = working.slice(0, -1);
  if (!working.startsWith("/")) working = `/${working}`;
  return working || "/";
}

class Route extends ModuleSupport {
  static #routes = [];
  static #groupStack = [];
  static #tags = new Map();
  static #compiled = null;
  static #events = new Map();
  static #stats = {
    dispatchCount: 0,
    staticHits: 0,
    dynamicHits: 0,
    misses: 0,
  };

  constructor(pattern, callback = null) {
    super();
    this.pattern = pattern;
    this.callback = callback;
    this.methods = new Set(["get"]);
    this.rulesMap = {};
    this.beforeList = [];
    this.afterList = [];
    this.tagName = null;
    this.pushList = [];
    this.compiled = compilePattern(pattern, this.rulesMap);
    this.dynamic = this.compiled.dynamic;
  }

  with(callback) {
    this.callback = callback;
    return this;
  }

  via(...methods) {
    const flat = methods.flat().map((x) => String(x).toLowerCase());
    this.methods = new Set(flat.length ? flat : ["get"]);
    return this;
  }

  rules(nextRules = {}) {
    this.rulesMap = { ...this.rulesMap, ...nextRules };
    this.compiled = compilePattern(this.pattern, this.rulesMap);
    this.dynamic = this.compiled.dynamic;
    Route.#compiled = null;
    return this;
  }

  before(callback) {
    this.beforeList.push(callback);
    return this;
  }

  after(callback) {
    this.afterList.push(callback);
    return this;
  }

  tag(name) {
    this.tagName = name;
    Route.#tags.set(name, this);
    return this;
  }

  push(resource, as = "text") {
    this.pushList.push(...flattenPushInput(resource, as));
    return this;
  }

  getURL(params = {}) {
    return new URL(buildTaggedURL(this.pattern, params));
  }

  async run(method, match) {
    Route.#trigger("start", this, match, method);

    for (let i = this.beforeList.length - 1; i >= 0; i -= 1) {
      const middleware = this.beforeList[i];
      Route.#trigger("before", this, middleware);
      const outcome = await middleware(match.params);
      if (outcome === false) return;
      if (outcome !== undefined && outcome !== null) Response.write(outcome);
    }

    for (const [resource, as] of this.pushList) Response.push(resource, as);

    let payload;
    if (typeof this.callback === "function") {
      payload = await this.callback(...match.args);
    } else {
      payload = this.callback;
    }

    const filtered = Filter.apply("core.route.response", payload);
    if (filtered !== undefined && filtered !== null) {
      if (typeof filtered === "object") Response.write(JSON.stringify(filtered));
      else Response.write(String(filtered));
    }

    for (const middleware of this.afterList) {
      Route.#trigger("after", this, middleware);
      const outcome = await middleware(match.params);
      if (outcome === false) break;
      if (outcome !== undefined && outcome !== null) Response.write(outcome);
    }

    Route.#trigger("end", this, match, method);
  }

  static #applyGroupContext(route) {
    for (const context of this.#groupStack) {
      for (const mw of context.before) route.before(mw);
      for (const mw of context.after) route.after(mw);
      for (const [res, as] of context.pushes) route.push(res, as);
      context.group.add(route);
    }
  }

  static #register(route) {
    this.#applyGroupContext(route);
    this.#routes.push(route);
    this.#compiled = null;
    return route;
  }

  static on(pattern, callback = null) {
    const prefix = this.#groupStack.map((g) => g.prefix).join("");
    const fullPattern = `${prefix}${pattern}` || "/";
    return this.#register(new Route(fullPattern, callback));
  }

  static get(pattern, callback = null) {
    return this.on(pattern, callback).via("get");
  }

  static post(pattern, callback = null) {
    return this.on(pattern, callback).via("post");
  }

  static any(pattern, callback = null) {
    return this.on(pattern, callback).via("*");
  }

  static map(pattern, callbacks = {}) {
    const route = this.on(pattern, null).via(...Object.keys(callbacks));
    route.callback = async (...args) => {
      const handler = callbacks[String(Request.method()).toLowerCase()] || callbacks["*"];
      return handler ? handler(...args) : undefined;
    };
    return route;
  }

  static group(prefix, callback) {
    const group = new RouteGroup();
    const context = { prefix, before: [], after: [], pushes: [], group };
    this.#groupStack.push(context);
    const bound = {
      before: (fn) => {
        context.before.push(fn);
        group.before(fn);
        return bound;
      },
      after: (fn) => {
        context.after.push(fn);
        group.after(fn);
        return bound;
      },
      push: (resource, as = "text") => {
        const entries = flattenPushInput(resource, as);
        context.pushes.push(...entries);
        group.push(resource, as);
        return bound;
      },
    };
    callback();
    this.#groupStack.pop();
    return bound;
  }

  static onEvent(name, callback) {
    if (!this.#events.has(name)) this.#events.set(name, []);
    this.#events.get(name).push(callback);
  }

  static off(name) {
    this.#events.delete(name);
  }

  static #trigger(name, ...args) {
    for (const callback of this.#events.get(name) || []) callback(...args);
  }

  static reset() {
    this.#routes = [];
    this.#groupStack = [];
    this.#tags = new Map();
    this.#compiled = null;
    this.#stats = { dispatchCount: 0, staticHits: 0, dynamicHits: 0, misses: 0 };
    this.#events = new Map();
  }

  static tagged(name) {
    return this.#tags.get(name) || false;
  }

  static URL(name, params = {}) {
    const tagged = this.tagged(name);
    if (!tagged) return false;
    return tagged.getURL(params);
  }

  static compile() {
    const compiled = { staticMap: new Map(), dynamic: [] };
    for (const route of this.#routes) {
      const methods = Array.from(route.methods);
      const hasWildcard = methods.includes("*");
      const routeMethods = hasWildcard ? ["*"] : methods;
      const uri = normalizeUri(route.pattern);
      if (!route.dynamic && !route.pattern.includes("(") && !route.pattern.includes(":") && !route.pattern.includes("?")) {
        for (const method of routeMethods) {
          const key = `${method}:${uri}`;
          compiled.staticMap.set(key, route);
        }
      } else {
        compiled.dynamic.push(route);
      }
    }
    this.#compiled = compiled;
    return compiled;
  }

  static #matchRoute(route, uri) {
    const match = route.compiled.regex.exec(uri);
    if (!match) return null;
    const params = match.groups || {};
    return {
      route,
      params,
      args: route.compiled.params.map((name) => params[name] ?? null),
    };
  }

  static #find(uri, method) {
    const lookupUri = normalizeUri(uri);
    const lookupMethod = String(method).toLowerCase();
    const compiled = this.#compiled || this.compile();

    const staticHit = compiled.staticMap.get(`${lookupMethod}:${lookupUri}`) || compiled.staticMap.get(`*:${lookupUri}`);
    if (staticHit) {
      this.#stats.staticHits += 1;
      return { route: staticHit, params: {}, args: [] };
    }

    for (const route of compiled.dynamic) {
      if (!route.methods.has("*") && !route.methods.has(lookupMethod)) continue;
      const hit = this.#matchRoute(route, lookupUri);
      if (hit) {
        this.#stats.dynamicHits += 1;
        return hit;
      }
    }
    this.#stats.misses += 1;
    return null;
  }

  static async dispatch(url = null, method = null, returnRoute = false) {
    const uri = normalizeUri(url || Request.URI());
    const verb = String(method || Request.method()).toLowerCase();
    Request.set({ uri, method: verb });
    this.#stats.dispatchCount += 1;

    const matched = this.#find(uri, verb);
    if (!matched) {
      this.#trigger("404");
      Event.trigger(404);
      return null;
    }

    if (returnRoute) return matched.route;

    await matched.route.run(verb, matched);
    return matched.route;
  }

  static debugTree() {
    const compiled = this.#compiled || this.compile();
    return {
      compiled: true,
      staticRoutes: compiled.staticMap.size,
      dynamicRoutes: compiled.dynamic.length,
    };
  }

  static stats() {
    return { ...this.#stats };
  }

  static routes() {
    return [...this.#routes];
  }
}

registerClass("Route", Route);
module.exports = Route;
},
11:function(module,exports,__core_require__){
// src/facades/RouteGroup.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class RouteGroup extends ModuleSupport {
  constructor() {
    super();
    this.routes = new Set();
    this.beforeMiddleware = [];
    this.afterMiddleware = [];
    this.pushes = [];
  }

  add(route) {
    this.routes.add(route);
    for (const middleware of this.beforeMiddleware) route.before(middleware);
    for (const middleware of this.afterMiddleware) route.after(middleware);
    for (const [resource, as] of this.pushes) route.push(resource, as);
    return this;
  }

  remove(route) {
    this.routes.delete(route);
    return this;
  }

  has(route) {
    return this.routes.has(route);
  }

  before(callback) {
    this.beforeMiddleware.push(callback);
    for (const route of this.routes) route.before(callback);
    return this;
  }

  after(callback) {
    this.afterMiddleware.push(callback);
    for (const route of this.routes) route.after(callback);
    return this;
  }

  push(resource, as = "text") {
    this.pushes.push([resource, as]);
    for (const route of this.routes) route.push(resource, as);
    return this;
  }
}

registerClass("RouteGroup", RouteGroup);
module.exports = RouteGroup;
},
12:function(module,exports,__core_require__){
// src/facades/Request.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Filter = __core_require__(6);

class Request extends ModuleSupport {
  static #state = {
    uri: "/",
    method: "get",
    headers: {},
    data: {},
    query: {},
  };

  static set(input = {}) {
    this.#state = { ...this.#state, ...input };
  }

  static URI() {
    return Filter.apply("core.request.URI", this.#state.uri || "/");
  }

  static method() {
    const method = String(this.#state.method || "get").toLowerCase();
    return Filter.apply("core.request.method", method);
  }

  static headers() { return { ...this.#state.headers }; }
  static data() { return { ...this.#state.data }; }
  static query() { return { ...this.#state.query }; }

  static get(key, fallback = null) {
    const q = this.query();
    if (key in q) return q[key];
    return fallback;
  }
}

registerClass("Request", Request);
module.exports = Request;
},
13:function(module,exports,__core_require__){
// src/facades/Response.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class Response extends ModuleSupport {
  static #status = 200;
  static #body = "";
  static #headers = new Map();

  static clean() {
    this.#status = 200;
    this.#body = "";
    this.#headers = new Map();
  }

  static status(code = null) {
    if (code !== null) this.#status = Number(code);
    return this.#status;
  }

  static write(chunk) {
    this.#body += String(chunk ?? "");
  }

  static body(value = undefined) {
    if (value !== undefined) this.#body = String(value ?? "");
    return this.#body;
  }

  static json(value, code = 200) {
    this.status(code);
    this.header("Content-Type", "application/json");
    this.body(JSON.stringify(value));
  }

  static text(value, code = 200) {
    this.status(code);
    this.header("Content-Type", "text/plain; charset=utf-8");
    this.body(value);
  }

  static header(name, value, append = true) {
    const key = String(name);
    if (!this.#headers.has(key) || !append) this.#headers.set(key, []);
    this.#headers.get(key).push([String(value)]);
  }

  static push(resource, as = "text") {
    if (Array.isArray(resource)) {
      for (const entry of resource) this.push(entry, as);
      return;
    }
    if (resource && typeof resource === "object") {
      for (const [kind, val] of Object.entries(resource)) {
        const type = Number.isInteger(Number(kind)) ? "text" : kind;
        this.push(val, type);
      }
      return;
    }
    this.header("Link", `<${resource}>; rel=preload; as=${as}`);
  }

  static headers() {
    const out = {};
    for (const [k, v] of this.#headers.entries()) out[k] = v;
    return out;
  }

  static send() {
    return {
      status: this.#status,
      headers: this.headers(),
      body: this.#body,
    };
  }
}

registerClass("Response", Response);
module.exports = Response;
},
14:function(module,exports,__core_require__){
// src/facades/URL.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

function encodePart(key, value) {
  if (value && typeof value === "object" && !Array.isArray(value)) {
    return Object.entries(value).flatMap(([k, v]) => encodePart(`${key}[${k}]`, v));
  }
  if (Array.isArray(value)) {
    return value.flatMap((v) => encodePart(`${key}[]`, v));
  }
  return [`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`];
}

function parseQuery(qs) {
  const out = {};
  const input = String(qs || "").replace(/^\?/, "");
  if (!input) return out;
  for (const part of input.split("&")) {
    if (!part) continue;
    const [k, v = ""] = part.split("=");
    const key = decodeURIComponent(k);
    const val = decodeURIComponent(v);
    if (key.endsWith("[]")) {
      const base = key.slice(0, -2);
      if (!Array.isArray(out[base])) out[base] = [];
      out[base].push(val);
    } else {
      out[key] = val;
    }
  }
  return out;
}

class URL extends ModuleSupport {
  constructor(input = "") {
    super();
    this.scheme = "";
    this.user = "";
    this.pass = "";
    this.host = "";
    this.port = "";
    this.path = "";
    this.query = {};
    this.fragment = "";

    if (input) this.#parse(input);
  }

  #parse(input) {
    const raw = String(input);
    let parsed;
    try {
      parsed = new globalThis.URL(raw);
    } catch {
      parsed = new globalThis.URL(raw, "http://placeholder.local");
      if (!/^[A-Za-z][A-Za-z0-9+.-]*:/.test(raw)) {
        this.path = raw;
        return;
      }
    }
    this.scheme = parsed.protocol.replace(/:$/, "");
    this.user = parsed.username;
    this.pass = parsed.password;
    this.host = parsed.hostname;
    this.port = parsed.port ? Number(parsed.port) : "";
    this.path = parsed.pathname;
    this.query = parseQuery(parsed.search);
    this.fragment = parsed.hash.replace(/^#/, "");
  }

  toString() {
    const auth = this.user ? `${this.user}${this.pass ? `:${this.pass}` : ""}@` : "";
    const scheme = this.scheme ? `${this.scheme}://` : "";
    const host = this.host || "";
    const port = this.port ? `:${this.port}` : "";
    const path = this.path ? (String(this.path).startsWith("/") ? this.path : `/${this.path}`) : "";
    const queryParts = Object.entries(this.query).flatMap(([k, v]) => encodePart(k, v));
    const query = queryParts.length ? `?${queryParts.join("&")}` : "";
    const frag = this.fragment ? `#${this.fragment}` : "";
    return `${scheme}${auth}${host}${port}${path}${query}${frag}` || "/";
  }

  valueOf() {
    return this.toString();
  }
}

registerClass("URL", URL);
module.exports = URL;
},
15:function(module,exports,__core_require__){
// src/facades/SQL.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const MemoryAdapter = __core_require__(16);
const SQLiteAdapter = __core_require__(17);

class SQL extends ModuleSupport {
  static #adapter = new MemoryAdapter();

  static connect(adapterOrDsn = null) {
    if (adapterOrDsn && typeof adapterOrDsn === "object") {
      this.#adapter = adapterOrDsn;
      return this.#adapter;
    }

    if (typeof adapterOrDsn === "string" && adapterOrDsn.startsWith("sqlite:")) {
      const file = adapterOrDsn.replace(/^sqlite:/, "") || ":memory:";
      this.#adapter = new SQLiteAdapter(file);
      return this.#adapter;
    }

    this.#adapter = new MemoryAdapter();
    return this.#adapter;
  }

  static adapter() { return this.#adapter; }
  static query(sql, params = []) { return this.#adapter.query(sql, params); }
  static each(sql, params = []) { return this.#adapter.each(sql, params); }
  static exec(sql) { return this.#adapter.exec ? this.#adapter.exec(sql) : this.#adapter.query(sql); }
}

registerClass("SQL", SQL);
module.exports = SQL;
},
16:function(module,exports,__core_require__){
// src/adapters/MemoryAdapter.js
class MemoryAdapter {
  constructor() {
    this.tablesMap = new Map();
    this.pk = new Map();
  }

  createTable(name, columns, primaryKey = "id") {
    this.tablesMap.set(name, []);
    this.pk.set(name, { primaryKey, columns: [...columns] });
  }

  tables() {
    return Array.from(this.tablesMap.keys());
  }

  describe(table) {
    const meta = this.pk.get(table);
    if (!meta) return [];
    return meta.columns.map((name) => ({
      name,
      type: "text",
      primary: name === meta.primaryKey,
      default: null,
    }));
  }

  findByPk(table, value) {
    const meta = this.pk.get(table);
    if (!meta) return null;
    return this.tablesMap.get(table).find((row) => row[meta.primaryKey] === value) || null;
  }

  all(table, page = 1, limit = -1) {
    const rows = [...(this.tablesMap.get(table) || [])];
    if (limit < 0) return rows;
    const start = (Math.max(page, 1) - 1) * limit;
    return rows.slice(start, start + limit);
  }

  findWhere(table, where = null, params = []) {
    const rows = [...(this.tablesMap.get(table) || [])];
    if (!where) return rows;
    const simpleEq = String(where).match(/^\s*([A-Za-z0-9_]+)\s*=\s*\?\s*$/);
    const simpleLike = String(where).match(/^\s*([A-Za-z0-9_]+)\s+LIKE\s+\?\s*$/i);
    if (simpleEq) {
      const [, field] = simpleEq;
      return rows.filter((r) => r[field] === params[0]);
    }
    if (simpleLike) {
      const [, field] = simpleLike;
      const rx = String(params[0] || "").replace(/%/g, ".*");
      const re = new RegExp(`^${rx}$`, "i");
      return rows.filter((r) => re.test(String(r[field] || "")));
    }
    return rows;
  }

  count(table, where = null, params = []) {
    return this.findWhere(table, where, params).length;
  }

  insert(table, row) {
    const meta = this.pk.get(table);
    if (!meta) throw new Error(`Unknown table '${table}'`);
    const data = { ...row };
    if (data[meta.primaryKey] === undefined || data[meta.primaryKey] === null) {
      const max = this.tablesMap.get(table).reduce((acc, r) => Math.max(acc, Number(r[meta.primaryKey] || 0)), 0);
      data[meta.primaryKey] = max + 1;
    }
    this.tablesMap.get(table).push(data);
    return { ...data };
  }

  update(table, row) {
    const meta = this.pk.get(table);
    if (!meta) throw new Error(`Unknown table '${table}'`);
    const pk = row[meta.primaryKey];
    const rows = this.tablesMap.get(table);
    const idx = rows.findIndex((r) => r[meta.primaryKey] === pk);
    if (idx < 0) throw new Error(`Row not found for ${table}.${meta.primaryKey}=${pk}`);
    rows[idx] = { ...rows[idx], ...row };
    return { ...rows[idx] };
  }
}

module.exports = MemoryAdapter;
},
17:function(module,exports,__core_require__){
// src/adapters/SQLiteAdapter.js
class SQLiteAdapter {
  constructor(file = ":memory:") {
    this.file = file;
    this.db = null;
    this.enabled = false;

    try {
      const sqlite = require("node:sqlite");
      this.db = new sqlite.DatabaseSync(file);
      this.enabled = true;
    } catch {
      this.enabled = false;
    }
  }

  assertEnabled() {
    if (!this.enabled) throw new Error("SQLite adapter requires Node runtime with node:sqlite (Node 22+)");
  }

  query(sql, params = []) {
    this.assertEnabled();
    const stmt = this.db.prepare(sql);
    if (/^\s*(insert|update|delete|create|drop|alter)/i.test(sql)) {
      return stmt.run(...(Array.isArray(params) ? params : Object.values(params || {})));
    }
    return stmt.all(...(Array.isArray(params) ? params : Object.values(params || {})));
  }

  each(sql, params = []) { return this.query(sql, params); }
  exec(sql) { return this.query(sql); }

  tables() {
    this.assertEnabled();
    return this.db.prepare("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'").all().map((r) => r.name);
  }

  describe(table) {
    this.assertEnabled();
    return this.db.prepare(`PRAGMA table_info(${table})`).all().map((r) => ({ name: r.name, type: r.type, primary: !!r.pk, default: r.dflt_value }));
  }

  findByPk(table, value) {
    const desc = this.describe(table);
    const pk = (desc.find((x) => x.primary) || { name: "id" }).name;
    return this.db.prepare(`SELECT * FROM ${table} WHERE ${pk} = ? LIMIT 1`).get(value) || null;
  }

  all(table, page = 1, limit = -1) {
    if (limit < 0) return this.db.prepare(`SELECT * FROM ${table}`).all();
    const offset = Math.max(0, (page - 1) * limit);
    return this.db.prepare(`SELECT * FROM ${table} LIMIT ? OFFSET ?`).all(limit, offset);
  }

  findWhere(table, where = null, params = []) {
    if (!where) return this.all(table);
    return this.db.prepare(`SELECT * FROM ${table} WHERE ${where}`).all(...(Array.isArray(params) ? params : Object.values(params || {})));
  }

  count(table, where = null, params = []) {
    const row = where
      ? this.db.prepare(`SELECT COUNT(1) as c FROM ${table} WHERE ${where}`).get(...(Array.isArray(params) ? params : Object.values(params || {})))
      : this.db.prepare(`SELECT COUNT(1) as c FROM ${table}`).get();
    return Number(row.c || 0);
  }

  insert(table, row) {
    const keys = Object.keys(row);
    const placeholders = keys.map(() => "?").join(",");
    this.db.prepare(`INSERT INTO ${table} (${keys.join(",")}) VALUES (${placeholders})`).run(...keys.map((k) => row[k]));
    const id = this.db.prepare("SELECT last_insert_rowid() AS id").get().id;
    const found = this.findByPk(table, id);
    return found || { ...row, id };
  }

  update(table, row) {
    const desc = this.describe(table);
    const pk = (desc.find((x) => x.primary) || { name: "id" }).name;
    const keys = Object.keys(row).filter((k) => k !== pk);
    const set = keys.map((k) => `${k} = ?`).join(", ");
    this.db.prepare(`UPDATE ${table} SET ${set} WHERE ${pk} = ?`).run(...keys.map((k) => row[k]), row[pk]);
    return this.findByPk(table, row[pk]);
  }
}

module.exports = SQLiteAdapter;
},
18:function(module,exports,__core_require__){
// src/facades/Schema.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const SQL = __core_require__(15);

class Schema extends ModuleSupport {
  static tables() {
    const adapter = SQL.adapter();
    if (typeof adapter.tables !== "function") return [];
    return adapter.tables();
  }

  static describe(table) {
    const adapter = SQL.adapter();
    if (typeof adapter.describe !== "function") return [];
    return adapter.describe(table);
  }
}

registerClass("Schema", Schema);
module.exports = Schema;
},
19:function(module,exports,__core_require__){
// src/facades/Model.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const SQL = __core_require__(15);

class Model extends ModuleSupport {
  static #tableByClass = new Map();
  static #pkByClass = new Map();

  static persistOn(table) {
    Model.#tableByClass.set(this, table);
    return this;
  }

  static table() {
    if (Model.#tableByClass.has(this)) return Model.#tableByClass.get(this);
    return `${this.name.toLowerCase()}s`;
  }

  static primaryKeyField() {
    return Model.#pkByClass.get(this) || "id";
  }

  static usePrimaryKey(field) {
    Model.#pkByClass.set(this, field);
    return this;
  }

  static schema() {
    const adapter = SQL.adapter();
    return typeof adapter.describe === "function" ? adapter.describe(this.table()) : [];
  }

  static fields() {
    return this.schema().map((x) => x.name);
  }

  static create(data = {}) {
    const adapter = SQL.adapter();
    const row = adapter.insert(this.table(), data);
    return new this(row);
  }

  static load(pk) {
    const adapter = SQL.adapter();
    const row = adapter.findByPk(this.table(), pk);
    return row ? new this(row) : null;
  }

  static where(where = null, params = []) {
    const adapter = SQL.adapter();
    return adapter.findWhere(this.table(), where, params).map((row) => new this(row));
  }

  static count(where = null, params = []) {
    const adapter = SQL.adapter();
    return adapter.count(this.table(), where, params);
  }

  static all(page = 1, limit = -1) {
    const adapter = SQL.adapter();
    return adapter.all(this.table(), page, limit).map((row) => new this(row));
  }

  constructor(data = {}) {
    super();
    Object.assign(this, data);
  }

  primaryKey() {
    return this[this.constructor.primaryKeyField()];
  }

  save() {
    const adapter = SQL.adapter();
    const row = adapter.update(this.constructor.table(), this.export());
    Object.assign(this, row);
    return this;
  }

  export(transformer = null) {
    const raw = { ...this };
    return typeof transformer === "function" ? transformer(raw) : raw;
  }
}

registerClass("Model", Model);
module.exports = Model;
},
20:function(module,exports,__core_require__){
// src/facades/Session.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class Session extends ModuleSupport {
  static #store = new Map();
  static #name = "core.sid";

  static set(key, value) {
    this.#store.set(String(key), value);
    return value;
  }

  static get(key, fallback = "") {
    return this.#store.has(String(key)) ? this.#store.get(String(key)) : fallback;
  }

  static delete(key) {
    this.#store.delete(String(key));
  }

  static exists(key) {
    return this.#store.has(String(key));
  }

  static flush() {
    this.#store.clear();
  }

  static name(value = null) {
    if (value !== null) this.#name = String(value);
    return this.#name;
  }
}

registerClass("Session", Session);
module.exports = Session;
},
21:function(module,exports,__core_require__){
// src/facades/Message.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Session = __core_require__(20);

class Message extends ModuleSupport {
  static #prefix = "core.message.";

  static set(key, value) {
    Session.set(`${this.#prefix}${key}`, value);
    return value;
  }

  static get(key) {
    const sessionKey = `${this.#prefix}${key}`;
    const value = Session.get(sessionKey, "");
    Session.delete(sessionKey);
    return value;
  }
}

registerClass("Message", Message);
module.exports = Message;
},
22:function(module,exports,__core_require__){
// src/facades/Cache.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const MemoryCacheAdapter = __core_require__(23);
const FilesCacheAdapter = __core_require__(24);

class Cache extends ModuleSupport {
  static #drivers = [];

  static using(input, options = {}) {
    const names = Array.isArray(input) ? input : [input];
    this.#drivers = names.map((name) => {
      if (typeof name === "object") return name;
      if (name === "memory") return new MemoryCacheAdapter(options.memory || {});
      if (name === "files") return new FilesCacheAdapter(options.files || options);
      throw new Error(`Unknown cache driver '${name}'`);
    });
    return this;
  }

  static #first() {
    if (!this.#drivers.length) this.using("memory");
    return this.#drivers[0];
  }

  static set(key, value, ttl = 0) {
    for (const driver of this.#drivers.length ? this.#drivers : [this.#first()]) {
      driver.set(key, value, ttl);
    }
    return value;
  }

  static get(key, fallback = undefined, ttl = 0) {
    const drivers = this.#drivers.length ? this.#drivers : [this.#first()];
    for (const driver of drivers) {
      const value = driver.get(key);
      if (value !== undefined) return value;
    }
    const resolved = typeof fallback === "function" ? fallback() : fallback;
    if (resolved !== undefined) this.set(key, resolved, ttl);
    return resolved;
  }

  static exists(key) {
    return this.#first().exists(key);
  }

  static delete(key) {
    for (const driver of this.#drivers.length ? this.#drivers : [this.#first()]) {
      driver.delete(key);
    }
  }

  static inc(key, by = 1) {
    return this.#first().inc(key, by);
  }

  static dec(key, by = 1) {
    return this.#first().dec(key, by);
  }
}

registerClass("Cache", Cache);
module.exports = Cache;
},
23:function(module,exports,__core_require__){
// src/adapters/cache/MemoryCacheAdapter.js
class MemoryCacheAdapter {
  constructor() {
    this.store = new Map();
  }

  get(key) {
    const row = this.store.get(key);
    if (!row) return undefined;
    if (row.expiresAt && row.expiresAt <= Date.now()) {
      this.store.delete(key);
      return undefined;
    }
    return row.value;
  }

  set(key, value, ttl = 0) {
    const expiresAt = ttl > 0 ? Date.now() + ttl * 1000 : 0;
    this.store.set(key, { value, expiresAt });
    return value;
  }

  exists(key) {
    return this.get(key) !== undefined;
  }

  delete(key) {
    this.store.delete(key);
  }

  inc(key, by = 1) {
    const now = Number(this.get(key) || 0) + by;
    this.set(key, now);
    return now;
  }

  dec(key, by = 1) {
    const now = Number(this.get(key) || 0) - by;
    this.set(key, now);
    return now;
  }

  flush() {
    this.store.clear();
  }
}

module.exports = MemoryCacheAdapter;
},
24:function(module,exports,__core_require__){
// src/adapters/cache/FilesCacheAdapter.js
const fs = require("node:fs");
const path = require("node:path");

class FilesCacheAdapter {
  constructor(options = {}) {
    this.cacheDir = options.cache_dir || path.join(process.cwd(), ".core-cache");
    fs.mkdirSync(this.cacheDir, { recursive: true });
  }

  #file(key) {
    const safe = Buffer.from(String(key)).toString("hex");
    return path.join(this.cacheDir, `${safe}.cache.json`);
  }

  get(key) {
    const file = this.#file(key);
    if (!fs.existsSync(file)) return undefined;
    const row = JSON.parse(fs.readFileSync(file, "utf8"));
    if (row.expiresAt && row.expiresAt <= Date.now()) {
      fs.rmSync(file, { force: true });
      return undefined;
    }
    return row.value;
  }

  set(key, value, ttl = 0) {
    const file = this.#file(key);
    const expiresAt = ttl > 0 ? Date.now() + ttl * 1000 : 0;
    fs.writeFileSync(file, JSON.stringify({ value, expiresAt }), "utf8");
    return value;
  }

  exists(key) {
    return this.get(key) !== undefined;
  }

  delete(key) {
    fs.rmSync(this.#file(key), { force: true });
  }

  inc(key, by = 1) {
    const now = Number(this.get(key) || 0) + by;
    this.set(key, now);
    return now;
  }

  dec(key, by = 1) {
    const now = Number(this.get(key) || 0) - by;
    this.set(key, now);
    return now;
  }
}

module.exports = FilesCacheAdapter;
},
25:function(module,exports,__core_require__){
// src/facades/Token.js
const crypto = require("node:crypto");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

function b64urlEncode(input) {
  return Buffer.from(input)
    .toString("base64")
    .replace(/=/g, "")
    .replace(/\+/g, "-")
    .replace(/\//g, "_");
}

function b64urlDecode(input) {
  const padded = input.replace(/-/g, "+").replace(/_/g, "/") + "===".slice((input.length + 3) % 4);
  return Buffer.from(padded, "base64").toString("utf8");
}

class Token extends ModuleSupport {
  static encode(payload, secret) {
    const header = { typ: "JWT", alg: "HS256" };
    const p1 = b64urlEncode(JSON.stringify(header));
    const p2 = b64urlEncode(JSON.stringify(payload));
    const sign = crypto.createHmac("sha256", secret).update(`${p1}.${p2}`).digest("base64")
      .replace(/=/g, "").replace(/\+/g, "-").replace(/\//g, "_");
    return `${p1}.${p2}.${sign}`;
  }

  static decode(token, secret) {
    const parts = String(token).split(".");
    if (parts.length !== 3) throw new Error("InvalidToken");
    const [p1, p2, sig] = parts;
    const expected = crypto.createHmac("sha256", secret).update(`${p1}.${p2}`).digest("base64")
      .replace(/=/g, "").replace(/\+/g, "-").replace(/\//g, "_");
    if (expected !== sig) throw new Error("WrongSecret");
    return JSON.parse(b64urlDecode(p2));
  }
}

registerClass("Token", Token);
module.exports = Token;
},
26:function(module,exports,__core_require__){
// src/facades/i18n.js
const fs = require("node:fs");
const path = require("node:path");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

function deepMerge(target, source) {
  const out = { ...target };
  for (const [k, v] of Object.entries(source || {})) {
    if (v && typeof v === "object" && !Array.isArray(v) && out[k] && typeof out[k] === "object" && !Array.isArray(out[k])) {
      out[k] = deepMerge(out[k], v);
    } else {
      out[k] = v;
    }
  }
  return out;
}

function getByDot(input, key) {
  return String(key).split(".").reduce((acc, part) => (acc && part in acc ? acc[part] : undefined), input);
}

class i18n extends ModuleSupport {
  static #dict = {};
  static #locale = "en";
  static #fallback = "en";

  static locale(value = null) {
    if (value !== null) this.#locale = value;
    return this.#locale;
  }

  static fallback(value = null) {
    if (value !== null) this.#fallback = value;
    return this.#fallback;
  }

  static load(locale, filePath) {
    const ext = path.extname(filePath).toLowerCase();
    if (ext === ".json") {
      this.loadArray(locale, JSON.parse(fs.readFileSync(filePath, "utf8")));
      return;
    }
    if (ext === ".php") {
      const raw = fs.readFileSync(filePath, "utf8");
      const match = raw.match(/\{[\s\S]*\}/);
      if (!match) return;
      const obj = JSON.parse(match[0].replace(/=>/g, ":").replace(/'/g, '"'));
      this.loadArray(locale, obj);
    }
  }

  static loadArray(locale, data) {
    this.#dict[locale] = deepMerge(this.#dict[locale] || {}, data || {});
  }

  static t(key, params = {}) {
    let value = getByDot(this.#dict[this.#locale] || {}, key);
    if (value === undefined) value = getByDot(this.#dict[this.#fallback] || {}, key);
    if (value === undefined) return key;
    return String(value).replace(/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/g, (_, name) => (params[name] ?? ""));
  }

  static has(key) {
    return this.t(key) !== key;
  }

  static all(locale = null) {
    return { ...(this.#dict[locale || this.#locale] || {}) };
  }

  static flush() {
    this.#dict = {};
    this.#locale = "en";
    this.#fallback = "en";
  }
}

registerClass("i18n", i18n);
module.exports = i18n;
},
27:function(module,exports,__core_require__){
// src/facades/Auth.js
const crypto = require("node:crypto");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Session = __core_require__(20);
const Request = __core_require__(12);
const Options = __core_require__(7);
const Token = __core_require__(25);

class CSRF extends ModuleSupport {
  static token() {
    const key = Options.get("core.csrf.session.key", "core.csrf.token");
    if (!Session.exists(key)) Session.set(key, crypto.randomBytes(16).toString("hex"));
    return Session.get(key);
  }

  static verify(candidate = null) {
    const key = Options.get("core.csrf.session.key", "core.csrf.token");
    const expected = Session.get(key, "");
    const headers = Request.headers();
    const incoming = candidate || headers["x-csrf-token"] || headers["X-CSRF-Token"] || "";
    return !!expected && expected === incoming;
  }
}

class Auth extends ModuleSupport {
  static #resolver = null;
  static #cache = undefined;

  static resolver(callback) {
    this.#resolver = callback;
  }

  static user() {
    if (this.#cache !== undefined) return this.#cache;
    const sessionEnabled = Options.get("core.auth.session.enabled", true);
    const bearerEnabled = Options.get("core.auth.bearer.enabled", true);
    const sessionKey = Options.get("core.auth.session.key", "auth.user");

    if (!this.#resolver) return null;

    if (sessionEnabled && Session.exists(sessionKey)) {
      this.#cache = this.#resolver(Session.get(sessionKey), "session") || null;
      if (this.#cache) return this.#cache;
    }

    if (bearerEnabled) {
      const headers = Request.headers();
      const authz = headers.authorization || headers.Authorization || "";
      const match = String(authz).match(/^Bearer\s+(.+)$/i);
      if (match) {
        const secret = Options.get("core.auth.jwt.secret", null);
        let identity = match[1];
        if (secret) {
          try { identity = Token.decode(match[1], secret); } catch { identity = null; }
        }
        if (identity !== null) {
          this.#cache = this.#resolver(identity, "bearer") || null;
          if (this.#cache) return this.#cache;
        }
      }
    }

    this.#cache = null;
    return null;
  }

  static check() {
    return !!this.user();
  }

  static guest() {
    return !this.check();
  }

  static flush() {
    this.#cache = undefined;
    this.#resolver = null;
  }
}

registerClass("CSRF", CSRF);
registerClass("Auth", Auth);
module.exports = { CSRF, Auth };
},
28:function(module,exports,__core_require__){
// src/facades/Gate.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Auth = __core_require__(27).Auth;

class Gate extends ModuleSupport {
  static #policies = new Map();

  static define(name, callback) {
    this.#policies.set(name, callback);
  }

  static allows(name, ...args) {
    const policy = this.#policies.get(name);
    if (!policy) return false;
    return !!policy(Auth.user(), ...args);
  }

  static denies(name, ...args) {
    return !this.allows(name, ...args);
  }

  static flush() {
    this.#policies.clear();
  }
}

registerClass("Gate", Gate);
module.exports = Gate;
},
29:function(module,exports,__core_require__){
// src/facades/RateLimiter.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class RateLimiter extends ModuleSupport {
  static #hits = new Map();

  static check(key, limit = 60, windowSeconds = 60) {
    const now = Date.now();
    const windowMs = windowSeconds * 1000;
    const events = (this.#hits.get(key) || []).filter((ts) => ts > now - windowMs);
    events.push(now);
    this.#hits.set(key, events);
    const remaining = Math.max(0, limit - events.length);
    return {
      allowed: events.length <= limit,
      limit,
      remaining,
      reset: Math.ceil((events[0] + windowMs) / 1000),
    };
  }

  static flush() {
    this.#hits.clear();
  }
}

registerClass("RateLimiter", RateLimiter);
module.exports = RateLimiter;
},
30:function(module,exports,__core_require__){
// src/facades/SecurityHeaders.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Response = __core_require__(13);

class SecurityHeaders extends ModuleSupport {
  static apply(overrides = {}) {
    const defaults = {
      "X-Frame-Options": "SAMEORIGIN",
      "X-Content-Type-Options": "nosniff",
      "Referrer-Policy": "strict-origin-when-cross-origin",
      "X-XSS-Protection": "0",
    };
    const headers = { ...defaults, ...overrides };
    for (const [k, v] of Object.entries(headers)) Response.header(k, v, false);
  }
}

registerClass("SecurityHeaders", SecurityHeaders);
module.exports = SecurityHeaders;
},
31:function(module,exports,__core_require__){
// src/facades/File.js
const fs = require("node:fs");
const path = require("node:path");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class File extends ModuleSupport {
  static #mounts = new Map();
  static #memory = new Map();

  static mount(name, driver = "native", options = {}) {
    this.#mounts.set(name, { driver, options });
  }

  static mounts() {
    return Array.from(this.#mounts.keys());
  }

  static #normalize(filePath) {
    const [scheme, rest] = String(filePath).includes("://") ? String(filePath).split("://") : [null, String(filePath)];
    const clean = path.posix.normalize(`/${rest}`).replace(/^\//, "");
    return { scheme, file: clean };
  }

  static #resolve(filePath) {
    const parsed = this.#normalize(filePath);
    if (parsed.scheme) return parsed;

    for (const [name, mount] of this.#mounts.entries()) {
      if (mount.driver === "native") {
        const full = path.join(mount.options.root || process.cwd(), parsed.file);
        if (fs.existsSync(full)) return { scheme: name, file: parsed.file };
      }
      if (mount.driver === "memory") {
        const key = `${name}://${parsed.file}`;
        if (this.#memory.has(key)) return { scheme: name, file: parsed.file };
      }
    }
    return { scheme: null, file: parsed.file };
  }

  static write(filePath, content) {
    const { scheme, file } = this.#resolve(filePath);
    const target = scheme ? `${scheme}://${file}` : filePath;
    const parsed = this.#normalize(target);
    const mount = parsed.scheme ? this.#mounts.get(parsed.scheme) : null;

    if (mount && mount.driver === "memory") {
      this.#memory.set(`${parsed.scheme}://${parsed.file}`, String(content));
      return;
    }

    const root = mount && mount.driver === "native" ? mount.options.root : process.cwd();
    const full = path.join(root, parsed.file);
    fs.mkdirSync(path.dirname(full), { recursive: true });
    fs.writeFileSync(full, String(content), "utf8");
  }

  static append(filePath, content) {
    const current = this.exists(filePath) ? this.read(filePath) : "";
    this.write(filePath, `${current}${content}`);
  }

  static read(filePath) {
    const { scheme, file } = this.#resolve(filePath);
    const parsed = this.#normalize(scheme ? `${scheme}://${file}` : filePath);
    const mount = parsed.scheme ? this.#mounts.get(parsed.scheme) : null;

    if (mount && mount.driver === "memory") {
      return this.#memory.get(`${parsed.scheme}://${parsed.file}`) || "";
    }

    const root = mount && mount.driver === "native" ? mount.options.root : process.cwd();
    const full = path.join(root, parsed.file);
    return fs.readFileSync(full, "utf8");
  }

  static exists(filePath) {
    const { scheme, file } = this.#resolve(filePath);
    if (!scheme) return false;
    const mount = this.#mounts.get(scheme);
    if (mount.driver === "memory") return this.#memory.has(`${scheme}://${file}`);
    return fs.existsSync(path.join(mount.options.root || process.cwd(), file));
  }

  static search(pattern = "*") {
    const isTxt = pattern.endsWith(".txt");
    const results = [];
    for (const [name, mount] of this.#mounts.entries()) {
      if (mount.driver === "memory") {
        for (const key of this.#memory.keys()) {
          if (!key.startsWith(`${name}://`)) continue;
          if (!isTxt || key.endsWith(".txt")) results.push(key);
        }
      } else if (mount.driver === "native") {
        const root = mount.options.root || process.cwd();
        for (const entry of fs.readdirSync(root)) {
          if (!isTxt || entry.endsWith(".txt")) results.push(`${name}://${entry}`);
        }
      }
    }
    return results;
  }
}

registerClass("File", File);
module.exports = File;
},
32:function(module,exports,__core_require__){
// src/facades/Schedule.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

function fieldMatch(field, value, min, max) {
  if (field === "*") return true;
  const parts = field.split(",");
  for (const part of parts) {
    const stepMatch = part.match(/^(\*|\d+-\d+)\/(\d+)$/);
    if (stepMatch) {
      const [, base, stepRaw] = stepMatch;
      const step = Number(stepRaw);
      if (base === "*" && value % step === 0) return true;
      if (base.includes("-")) {
        const [a, b] = base.split("-").map(Number);
        if (value >= a && value <= b && (value - a) % step === 0) return true;
      }
      continue;
    }
    if (part.includes("-")) {
      const [a, b] = part.split("-").map(Number);
      if (value >= a && value <= b) return true;
      continue;
    }
    if (Number(part) === value) return true;
  }
  return false;
}

class Schedule extends ModuleSupport {
  static #jobs = new Map();

  static register(name, cron, type, data = {}) {
    this.#jobs.set(name, { name, cron, type, data });
  }

  static unregister(name) {
    this.#jobs.delete(name);
  }

  static all() {
    return Object.fromEntries(this.#jobs.entries());
  }

  static flush() {
    this.#jobs.clear();
  }

  static matches(cron, timestamp = Date.now()) {
    const parts = String(cron).trim().split(/\s+/);
    if (parts.length !== 5) return false;
    const d = new Date(typeof timestamp === "number" && timestamp < 1e12 ? timestamp * 1000 : timestamp);
    const fields = [
      d.getUTCMinutes(),
      d.getUTCHours(),
      d.getUTCDate(),
      d.getUTCMonth() + 1,
      d.getUTCDay(),
    ];
    const bounds = [[0, 59], [0, 23], [1, 31], [1, 12], [0, 6]];
    return parts.every((p, i) => fieldMatch(p, fields[i], bounds[i][0], bounds[i][1]));
  }

  static due(timestamp = Date.now()) {
    return Array.from(this.#jobs.values()).filter((job) => this.matches(job.cron, timestamp));
  }
}

registerClass("Schedule", Schedule);
module.exports = Schedule;
},
33:function(module,exports,__core_require__){
// src/facades/Work.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class TaskCoroutine {
  constructor(id, iterator) {
    this.id = id;
    this.iterator = iterator;
    this.value = undefined;
    this.done = false;
    this.first = true;
  }

  pass(value) {
    this.value = value;
  }

  run() {
    if (this.done) return undefined;
    const result = this.first ? this.iterator.next() : this.iterator.next(this.value);
    this.first = false;
    this.done = !!result.done;
    return result.value;
  }

  complete() {
    return this.done;
  }
}

class Work extends ModuleSupport {
  static #queue = [];

  static add(taskFactory) {
    const iterator = taskFactory();
    this.#queue.push(new TaskCoroutine(this.#queue.length + 1, iterator));
  }

  static run() {
    while (this.#queue.some((task) => !task.complete())) {
      for (const task of this.#queue) {
        if (!task.complete()) task.run();
      }
    }
    this.#queue = [];
  }

  static flush() {
    this.#queue = [];
  }
}

registerClass("TaskCoroutine", TaskCoroutine);
registerClass("Work", Work);
module.exports = { Work, TaskCoroutine };
},
34:function(module,exports,__core_require__){
// src/facades/Text.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

function deepGet(obj, path) {
  const parts = String(path).split(".").filter(Boolean);
  let ref = obj;
  for (const part of parts) {
    if (ref === null || ref === undefined) return undefined;
    ref = ref[part];
  }
  return ref;
}

class Text extends ModuleSupport {
  static render(template, data = {}) {
    return String(template).replace(/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/g, (_, key) => {
      const val = deepGet(data, key);
      return val === undefined || val === null ? "" : String(val);
    });
  }

  static removeAccents(input) {
    return String(input).normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  static slugify(input) {
    return this.removeAccents(input)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "")
      .replace(/-+/g, "-");
  }

  static cut(text, start, end = null) {
    const source = String(text);
    const i = source.indexOf(String(start));
    if (i < 0) return "";
    const from = i + String(start).length;
    if (end === null) return source.slice(from);
    const j = source.indexOf(String(end), from);
    return j < 0 ? source.slice(from) : source.slice(from, j);
  }
}

registerClass("Text", Text);
module.exports = Text;
},
35:function(module,exports,__core_require__){
// src/facades/Hash.js
const crypto = require("node:crypto");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

function murmurhash3_32_gc(key, seed = 0) {
  let remainder = key.length & 3;
  let bytes = key.length - remainder;
  let h1 = seed;
  let c1 = 0xcc9e2d51;
  let c2 = 0x1b873593;
  let i = 0;
  while (i < bytes) {
    let k1 =
      (key.charCodeAt(i) & 0xff) |
      ((key.charCodeAt(++i) & 0xff) << 8) |
      ((key.charCodeAt(++i) & 0xff) << 16) |
      ((key.charCodeAt(++i) & 0xff) << 24);
    ++i;
    k1 = (((k1 & 0xffff) * c1) + ((((k1 >>> 16) * c1) & 0xffff) << 16)) & 0xffffffff;
    k1 = (k1 << 15) | (k1 >>> 17);
    k1 = (((k1 & 0xffff) * c2) + ((((k1 >>> 16) * c2) & 0xffff) << 16)) & 0xffffffff;
    h1 ^= k1;
    h1 = (h1 << 13) | (h1 >>> 19);
    const h1b = (((h1 & 0xffff) * 5) + ((((h1 >>> 16) * 5) & 0xffff) << 16)) & 0xffffffff;
    h1 = (((h1b & 0xffff) + 0x6b64) + ((((h1b >>> 16) + 0xe654) & 0xffff) << 16));
  }

  let k1 = 0;
  switch (remainder) {
    case 3:
      k1 ^= (key.charCodeAt(i + 2) & 0xff) << 16;
    case 2:
      k1 ^= (key.charCodeAt(i + 1) & 0xff) << 8;
    case 1:
      k1 ^= (key.charCodeAt(i) & 0xff);
      k1 = (((k1 & 0xffff) * c1) + ((((k1 >>> 16) * c1) & 0xffff) << 16)) & 0xffffffff;
      k1 = (k1 << 15) | (k1 >>> 17);
      k1 = (((k1 & 0xffff) * c2) + ((((k1 >>> 16) * c2) & 0xffff) << 16)) & 0xffffffff;
      h1 ^= k1;
  }

  h1 ^= key.length;
  h1 ^= h1 >>> 16;
  h1 = (((h1 & 0xffff) * 0x85ebca6b) + ((((h1 >>> 16) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
  h1 ^= h1 >>> 13;
  h1 = (((h1 & 0xffff) * 0xc2b2ae35) + ((((h1 >>> 16) * 0xc2b2ae35) & 0xffff) << 16)) & 0xffffffff;
  h1 ^= h1 >>> 16;
  return h1 >>> 0;
}

class Hash extends ModuleSupport {
  static methods() {
    return crypto.getHashes();
  }

  static can(algo) {
    return algo === "murmur" || this.methods().includes(String(algo));
  }

  static make(payload, algo = "sha256") {
    const data = typeof payload === "string" ? payload : JSON.stringify(payload);
    if (algo === "murmur") return this.murmur(data);
    return crypto.createHash(algo).update(data).digest("hex");
  }

  static verify(payload, hash, algo = "sha256") {
    return this.make(payload, algo) === hash;
  }

  static random(size = 16) {
    return crypto.randomBytes(size).toString("hex");
  }

  static murmur(input, seed = 0, asInt = false) {
    const n = murmurhash3_32_gc(String(input), Number(seed));
    if (asInt) return n;
    return n.toString(36);
  }

  static uuid(version = 4, namespace = null, name = null) {
    if (version === 4 || typeof version === "undefined") return crypto.randomUUID();
    if (![3, 5].includes(Number(version))) return false;
    const ns = String(namespace || "").toLowerCase();
    if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/.test(ns)) return false;
    const nsBytes = Buffer.from(ns.replace(/-/g, ""), "hex");
    const data = Buffer.concat([nsBytes, Buffer.from(String(name || ""))]);
    const digest = crypto.createHash(version === 3 ? "md5" : "sha1").update(data).digest();
    const bytes = Buffer.from(digest.subarray(0, 16));
    bytes[6] = (bytes[6] & 0x0f) | (version << 4);
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = bytes.toString("hex");
    return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
  }
}

registerClass("Hash", Hash);
module.exports = Hash;
},
36:function(module,exports,__core_require__){
// src/facades/Password.js
const crypto = require("node:crypto");
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class Password extends ModuleSupport {
  static make(value) {
    const salt = crypto.randomBytes(16);
    const hash = crypto.scryptSync(String(value), salt, 64);
    return `scrypt$${salt.toString("hex")}$${hash.toString("hex")}`;
  }

  static verify(value, encoded) {
    const [algo, saltHex, hashHex] = String(encoded).split("$");
    if (algo !== "scrypt" || !saltHex || !hashHex) return false;
    const hash = crypto.scryptSync(String(value), Buffer.from(saltHex, "hex"), 64);
    return crypto.timingSafeEqual(hash, Buffer.from(hashHex, "hex"));
  }

  static compare(a, b) {
    const left = Buffer.from(String(a));
    const right = Buffer.from(String(b));
    if (left.length !== right.length) return false;
    return crypto.timingSafeEqual(left, right);
  }
}

registerClass("Password", Password);
module.exports = Password;
},
37:function(module,exports,__core_require__){
// src/facades/HTTP.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class HTTP_Response {
  constructor(body = "", status = 200, headers = {}) {
    this.body = body;
    this.status = status;
    this.headers = headers;
  }

  toString() {
    return String(this.body);
  }
}

class HTTP_Request {
  constructor(method = "get", url = "", headers = {}, data = null) {
    this.method = String(method).toUpperCase();
    this.url = url;
    this.headers = headers;
    this.data = data;
  }

  toString() {
    const parsed = new globalThis.URL(String(this.url).startsWith("http") ? this.url : `http://${this.url}`);
    const start = `${this.method} ${parsed.pathname}${parsed.search} HTTP/1.1\r\n`;
    const host = `Host: ${parsed.host}\r\n`;
    const head = Object.entries(this.headers).map(([k, v]) => `${k}: ${v}\r\n`).join("");
    const body = this.data === null || this.data === undefined ? "" : (typeof this.data === "string" ? this.data : JSON.stringify(this.data));
    return `${start}${host}${head}\r\n${body}`;
  }
}

class HTTP extends ModuleSupport {
  static #headers = new Map();
  static #userAgent = "CoreJS";
  static #proxy = "";
  static #lastResponseHeader = "";

  static addHeader(name, value) { this.#headers.set(name, value); }
  static removeHeader(name) { this.#headers.delete(name); }
  static headers(name = null) {
    if (name !== null) return this.#headers.get(name) || "";
    return Object.fromEntries(this.#headers.entries());
  }

  static userAgent(value = null) { if (value !== null) this.#userAgent = value; return this.#userAgent; }
  static proxy(value = null) { if (value !== null) this.#proxy = value; return this.#proxy; }

  static setLastResponseHeader(raw) { this.#lastResponseHeader = String(raw || ""); }
  static lastResponseHeader() {
    const out = {};
    for (const line of this.#lastResponseHeader.split(/\r?\n/)) {
      const idx = line.indexOf(":");
      if (idx < 1) continue;
      const key = line.slice(0, idx).trim();
      const val = line.slice(idx + 1).trim();
      if (!out[key]) out[key] = [];
      out[key].push(val);
    }
    return out;
  }
}

registerClass("HTTP", HTTP);
registerClass("HTTP_Request", HTTP_Request);
registerClass("HTTP_Response", HTTP_Response);
module.exports = { HTTP, HTTP_Request, HTTP_Response };
},
38:function(module,exports,__core_require__){
// src/facades/CLI.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class CLI extends ModuleSupport {
  static #commands = [];

  static on(pattern, callback, description = "") {
    const parts = String(pattern).trim().split(/\s+/);
    const name = parts[0];
    const params = parts.slice(1).map((p) => p.replace(/^:/, "[").concat("]")).join(" ");
    this.#commands.push({ name, pattern, callback, description, params });
  }

  static commands() {
    return this.#commands.map(({ callback, ...rest }) => ({ ...rest }));
  }

  static flush() {
    this.#commands = [];
  }
}

registerClass("CLI", CLI);
module.exports = CLI;
},
39:function(module,exports,__core_require__){
// src/facades/Service.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

class Service extends ModuleSupport {
  static #singletons = new Map();
  static #factories = new Map();
  static #instances = new Map();

  static register(name, factory) {
    this.#singletons.set(name, factory);
    this[name] = (...args) => {
      if (this.#instances.has(name)) return this.#instances.get(name);
      const instance = factory(...args);
      this.#instances.set(name, instance);
      return instance;
    };
  }

  static registerFactory(name, factory) {
    this.#factories.set(name, factory);
    this[name] = (...args) => factory(...args);
  }

  static flush() {
    for (const name of [...this.#singletons.keys(), ...this.#factories.keys()]) delete this[name];
    this.#singletons.clear();
    this.#factories.clear();
    this.#instances.clear();
  }
}

registerClass("Service", Service);
module.exports = Service;
},
40:function(module,exports,__core_require__){
// src/facades/Dictionary.js
const { registerClass } = __core_require__(2);
const { deepGet, deepSet, merge } = __core_require__(41);

class Dictionary {
  static _fields = {};

  static clear() { this._fields = {}; }
  static load(data = {}) { this._fields = JSON.parse(JSON.stringify(data)); }
  static all() { return JSON.parse(JSON.stringify(this._fields || {})); }
  static set(path, value) { return deepSet(this._fields, path, value); }
  static exists(path) { return deepGet(this._fields, path, undefined) !== undefined; }
  static merge(data, left = false) { this._fields = merge(this._fields, data, !!left); }

  static get(path, fallback = null) {
    if (path && typeof path === "object" && !Array.isArray(path)) {
      const out = {};
      for (const [k, p] of Object.entries(path)) out[k] = this.get(p, fallback);
      return out;
    }
    const val = deepGet(this._fields, path, undefined);
    if (val !== undefined) return val;
    return typeof fallback === "function" ? fallback() : fallback;
  }
}

registerClass("Dictionary", Dictionary);
module.exports = Dictionary;
},
41:function(module,exports,__core_require__){
// src/kernel/deep.js
function isObj(x) { return x && typeof x === "object" && !Array.isArray(x); }

function deepGet(input, path, fallback) {
  const parts = String(path).split(".").filter(Boolean);
  let ref = input;
  for (const part of parts) {
    if (ref === null || ref === undefined || !(part in ref)) return fallback;
    ref = ref[part];
  }
  return ref;
}

function deepSet(input, path, value) {
  const parts = String(path).split(".").filter(Boolean);
  let ref = input;
  for (let i = 0; i < parts.length - 1; i += 1) {
    const k = parts[i];
    if (!isObj(ref[k])) ref[k] = {};
    ref = ref[k];
  }
  ref[parts.at(-1)] = value;
  return value;
}

function merge(target, source, left = false) {
  const out = { ...target };
  for (const [k, v] of Object.entries(source || {})) {
    if (isObj(v) && isObj(out[k])) out[k] = merge(out[k], v, left);
    else if (!(left && k in out)) out[k] = v;
  }
  return out;
}

module.exports = { deepGet, deepSet, merge };
},
42:function(module,exports,__core_require__){
// src/facades/Map.js
const { registerClass } = __core_require__(2);
const { deepGet, deepSet, merge } = __core_require__(41);

class Map {
  constructor() {
    this.fields = {};
  }

  all() { return JSON.parse(JSON.stringify(this.fields)); }
  load(data = {}) { this.fields = JSON.parse(JSON.stringify(data)); }
  clear() { this.fields = {}; }
  set(path, value) { return deepSet(this.fields, path, value); }
  get(path, fallback = null) {
    const val = deepGet(this.fields, path, undefined);
    if (val !== undefined) return val;
    return typeof fallback === "function" ? fallback() : fallback;
  }
  exists(path) { return deepGet(this.fields, path, undefined) !== undefined; }
  merge(data, left = false) { this.fields = merge(this.fields, data, !!left); }
}

registerClass("Map", Map);
module.exports = Map;
},
43:function(module,exports,__core_require__){
// src/facades/Structure.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);

function wrap(value) {
  if (value && typeof value === "object") return new Structure(value, true);
  return value;
}

class Structure extends ModuleSupport {
  constructor(input = {}, deep = true) {
    super();
    this.data = input;
    this.deep = deep;
    return new Proxy(this, {
      get: (target, prop) => {
        if (prop in target) return target[prop];
        return target.#read(prop);
      },
      set: (target, prop, value) => {
        target.data[prop] = value;
        return true;
      },
      has: (target, prop) => prop in target.data,
    });
  }

  #read(prop) {
    const value = this.data[prop];
    return this.deep ? wrap(value) : value;
  }

  static fetch(path, data, fallback = null) {
    const parts = String(path).split(".").filter(Boolean);
    let ref = data;
    for (const part of parts) {
      if (ref === null || ref === undefined || !(part in ref)) return fallback;
      ref = ref[part];
    }
    return JSON.parse(JSON.stringify(ref));
  }
}

registerClass("Structure", Structure);
module.exports = Structure;
},
44:function(module,exports,__core_require__){
// src/facades/Collection.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Filter = __core_require__(6);

class Collection extends ModuleSupport {
  static wrap(resourceClass, data, page, limit, count) {
    if (!resourceClass) throw new Error("[API] Resource class is required");
    const projector = resourceClass.buildProjector();
    return {
      data: data.map((row) => new resourceClass(row, projector)),
      pagination: { page, limit, count },
    };
  }

  static fromRows(resourceClass, rows, page = 1, limit = 10) {
    const p = Number(Filter.apply(`api.${resourceClass.name}.page`, page));
    const l = Number(Filter.apply(`api.${resourceClass.name}.limit`, limit));
    const start = Math.max(0, (p - 1) * l);
    const slice = rows.slice(start, start + l);
    return this.wrap(resourceClass, slice, p, l, rows.length);
  }
}

registerClass("Collection", Collection);
module.exports = Collection;
},
45:function(module,exports,__core_require__){
// src/facades/Resource.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Filter = __core_require__(6);
const Request = __core_require__(12);
const Collection = __core_require__(44);

function getDot(input, path) {
  return String(path).split(".").reduce((acc, p) => (acc && p in acc ? acc[p] : undefined), input);
}

class Resource extends ModuleSupport {
  static PKEY = "id";
  static exposureMode = "full";

  constructor(row, projector = null) {
    super();
    this.fields = row;
    this.projector = projector || this.constructor.buildProjector();
    this.exposed = null;
  }

  expose(fields, mode) { return fields; }

  jsonSerialize() {
    return this.projector(this.expose(this.fields, this.constructor.exposureMode));
  }

  toJSON() {
    return this.jsonSerialize();
  }

  static setExposure(mode) { this.exposureMode = mode; return mode; }

  static buildProjector() {
    const projection = Filter.apply(`api.${this.name}.getProjectionFields`, Filter.apply("api.resource.getProjectionFields", Request.get("fields", null)));
    if (!projection) return (x) => x;
    const fields = Array.from(new Set([this.PKEY, ...String(projection).split(/\s*,\s*/).filter(Boolean)]));
    return (element) => {
      const src = element && typeof element === "object" ? element : {};
      const out = {};
      for (const key of fields) {
        const value = getDot(src, key);
        if (value !== undefined) out[key] = value;
      }
      return out;
    };
  }

  static fromRows(rows) {
    return Collection.fromRows(this, rows, Request.get("page", 1), Request.get("limit", 10));
  }
}

registerClass("Resource", Resource);
module.exports = Resource;
},
46:function(module,exports,__core_require__){
// src/facades/REST.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Route = __core_require__(10);

class REST extends ModuleSupport {
  static expose(element, maps = null) {
    let collection = "";
    let handlers = maps;
    if (maps === null && element && typeof element === "object") {
      handlers = element;
      collection = "";
    } else {
      collection = `/${String(element).replace(/^\/+/, "")}`;
    }

    return Route.group(collection, () => {
      const root = {};
      if (handlers.list) root.get = handlers.list;
      if (handlers.create) root.post = handlers.create;
      if (handlers.clear) root.delete = handlers.clear;
      Route.map("/", root);

      const single = {};
      if (handlers.read) single.get = handlers.read;
      if (handlers.update) single.put = handlers.update;
      if (handlers.delete) single.delete = handlers.delete;
      Route.map("/:id", single);
    });
  }
}

registerClass("REST", REST);
module.exports = REST;
},
47:function(module,exports,__core_require__){
// src/facades/API.js
const { registerClass } = __core_require__(2);
const ModuleSupport = __core_require__(3);
const Route = __core_require__(10);

class API extends ModuleSupport {
  static resource(path, options = {}) {
    const cfg = {
      class: null,
      list_mode: "list",
      sql: { table: null, raw: null, primary_key: "id" },
      ...options,
      sql: { table: null, raw: null, primary_key: "id", ...(options.sql || {}) },
    };

    const resourceClass = cfg.class;
    if (!resourceClass) return;
    const endpoint = (String(path).replace(/\/+$/, "") || "/");

    Route.on(endpoint, async () => {
      resourceClass.setExposure(cfg.list_mode);
      const rows = await resourceClass.__rows();
      return resourceClass.fromRows(rows);
    });

    Route.on(`${endpoint}/:id`, async (id) => {
      const rows = await resourceClass.__rows();
      const pkey = cfg.sql.primary_key || "id";
      const row = rows.find((x) => String(x[pkey]) === String(id));
      return { data: row ? new resourceClass(row) : null };
    });

    Route.on(`${endpoint}/:id/:parameter`, async (id, parameter) => {
      const rows = await resourceClass.__rows();
      const pkey = cfg.sql.primary_key || "id";
      const row = rows.find((x) => String(x[pkey]) === String(id));
      if (!row) return { data: null };
      const projector = () => (x) => ({ [parameter]: x[parameter], [resourceClass.PKEY]: x[resourceClass.PKEY] });
      return { data: new resourceClass(row, projector()) };
    });
  }
}

registerClass("API", API);
module.exports = API;
},
};
var __core_cache__={};
function __core_require__(id){
if(__core_cache__[id]){return __core_cache__[id].exports;}
var module={exports:{}};
__core_cache__[id]=module;
__core_modules__[id](module,module.exports,__core_require__);
return module.exports;
}
module.exports=__core_require__(0);
})();
