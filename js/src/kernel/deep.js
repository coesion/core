function isObj(x) { return x && typeof x === "object" && !Array.isArray(x); }

function deepGet(input, path, fallback) {
  const parts = String(path).split(".").filter(Boolean);
  let ref = input;
  for (const part of parts) {
    if (ref === null || ref === undefined || !(part in ref)) return fallback;
    ref = ref[part];
  }
  return ref;
}

function deepSet(input, path, value) {
  const parts = String(path).split(".").filter(Boolean);
  let ref = input;
  for (let i = 0; i < parts.length - 1; i += 1) {
    const k = parts[i];
    if (!isObj(ref[k])) ref[k] = {};
    ref = ref[k];
  }
  ref[parts.at(-1)] = value;
  return value;
}

function merge(target, source, left = false) {
  const out = { ...target };
  for (const [k, v] of Object.entries(source || {})) {
    if (isObj(v) && isObj(out[k])) out[k] = merge(out[k], v, left);
    else if (!(left && k in out)) out[k] = v;
  }
  return out;
}

module.exports = { deepGet, deepSet, merge };