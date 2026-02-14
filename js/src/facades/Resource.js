const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Filter = require("./Filter");
const Request = require("./Request");
const Collection = require("./Collection");

function getDot(input, path) {
  return String(path).split(".").reduce((acc, p) => (acc && p in acc ? acc[p] : undefined), input);
}

class Resource extends ModuleSupport {
  static PKEY = "id";
  static exposureMode = "full";

  constructor(row, projector = null) {
    super();
    this.fields = row;
    this.projector = projector || this.constructor.buildProjector();
    this.exposed = null;
  }

  expose(fields, mode) { return fields; }

  jsonSerialize() {
    return this.projector(this.expose(this.fields, this.constructor.exposureMode));
  }

  toJSON() {
    return this.jsonSerialize();
  }

  static setExposure(mode) { this.exposureMode = mode; return mode; }

  static buildProjector() {
    const projection = Filter.apply(`api.${this.name}.getProjectionFields`, Filter.apply("api.resource.getProjectionFields", Request.get("fields", null)));
    if (!projection) return (x) => x;
    const fields = Array.from(new Set([this.PKEY, ...String(projection).split(/\s*,\s*/).filter(Boolean)]));
    return (element) => {
      const src = element && typeof element === "object" ? element : {};
      const out = {};
      for (const key of fields) {
        const value = getDot(src, key);
        if (value !== undefined) out[key] = value;
      }
      return out;
    };
  }

  static fromRows(rows) {
    return Collection.fromRows(this, rows, Request.get("page", 1), Request.get("limit", 10));
  }
}

registerClass("Resource", Resource);
module.exports = Resource;
