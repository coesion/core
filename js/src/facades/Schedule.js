const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

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
