const state = {
  classes: new Map(),
  extensions: new Map(),
};

function registerClass(name, ref) {
  state.classes.set(name, ref);
  if (!state.extensions.has(name)) state.extensions.set(name, new Set());
}

function registerExtension(className, method) {
  if (!state.extensions.has(className)) state.extensions.set(className, new Set());
  state.extensions.get(className).add(method);
}

function classes() {
  return Array.from(state.classes.keys()).sort();
}

function methods(className) {
  const ref = state.classes.get(className);
  if (!ref) return [];
  const own = Object.getOwnPropertyNames(ref)
    .filter((k) => typeof ref[k] === "function" && !["length", "name", "prototype"].includes(k));
  const ext = Array.from(state.extensions.get(className) || []);
  return Array.from(new Set([...own, ...ext])).sort();
}

function extensions(className) {
  return Array.from(state.extensions.get(className) || []).sort();
}

function classRef(name) {
  return state.classes.get(name);
}

module.exports = {
  registerClass,
  registerExtension,
  classes,
  methods,
  extensions,
  classRef,
};