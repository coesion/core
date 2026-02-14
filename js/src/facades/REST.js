const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Route = require("./Route");

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