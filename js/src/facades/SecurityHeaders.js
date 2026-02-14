const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Response = require("./Response");

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