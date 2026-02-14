const { registerClass, classes, methods, extensions, classRef } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Route = require("./Route");

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