const crypto = require("node:crypto");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Session = require("./Session");
const Request = require("./Request");
const Options = require("./Options");
const Token = require("./Token");

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