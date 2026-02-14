const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

function deepGet(obj, path) {
  const parts = String(path).split(".").filter(Boolean);
  let ref = obj;
  for (const part of parts) {
    if (ref === null || ref === undefined) return undefined;
    ref = ref[part];
  }
  return ref;
}

class Text extends ModuleSupport {
  static render(template, data = {}) {
    return String(template).replace(/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/g, (_, key) => {
      const val = deepGet(data, key);
      return val === undefined || val === null ? "" : String(val);
    });
  }

  static removeAccents(input) {
    return String(input).normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  static slugify(input) {
    return this.removeAccents(input)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "")
      .replace(/-+/g, "-");
  }

  static cut(text, start, end = null) {
    const source = String(text);
    const i = source.indexOf(String(start));
    if (i < 0) return "";
    const from = i + String(start).length;
    if (end === null) return source.slice(from);
    const j = source.indexOf(String(end), from);
    return j < 0 ? source.slice(from) : source.slice(from, j);
  }
}

registerClass("Text", Text);
module.exports = Text;