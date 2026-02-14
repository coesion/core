const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Session = require("./Session");

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