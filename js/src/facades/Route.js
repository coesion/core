const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const RouteGroup = require("./RouteGroup");
const Options = require("./Options");
const Request = require("./Request");
const Response = require("./Response");
const URL = require("./URL");
const Event = require("./Event");
const Filter = require("./Filter");

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
