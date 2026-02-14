const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Filter = require("./Filter");

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