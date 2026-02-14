const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const SQL = require("./SQL");

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