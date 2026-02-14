const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

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