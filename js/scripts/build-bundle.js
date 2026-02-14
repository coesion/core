const fs = require("node:fs");
const path = require("node:path");

const rootDir = path.join(__dirname, "..");
const srcDir = path.join(rootDir, "src");
const entryFile = path.join(srcDir, "index.js");
const outFile = path.join(rootDir, "dist", "core.js");

function normalizePath(filePath) {
  return filePath.split(path.sep).join("/");
}

function resolveLocalModule(fromFile, request) {
  const basePath = path.resolve(path.dirname(fromFile), request);
  const candidates = [
    basePath,
    `${basePath}.js`,
    path.join(basePath, "index.js"),
  ];

  for (const candidate of candidates) {
    if (fs.existsSync(candidate) && fs.statSync(candidate).isFile()) {
      return candidate;
    }
  }

  throw new Error(`Unable to resolve "${request}" from "${fromFile}"`);
}

function compactSource(code) {
  const lines = code.split("\n").map((line) => line.replace(/[ \t]+$/u, ""));
  const output = [];
  let blankRun = 0;

  for (const line of lines) {
    if (line === "") {
      blankRun += 1;
      if (blankRun > 1) {
        continue;
      }
    } else {
      blankRun = 0;
    }
    output.push(line);
  }

  while (output.length > 0 && output[output.length - 1] === "") {
    output.pop();
  }

  return `${output.join("\n")}\n`;
}

const modules = new Map();
const ordered = [];

function visit(filePath) {
  const absolute = path.resolve(filePath);
  if (modules.has(absolute)) {
    return modules.get(absolute).id;
  }

  const id = modules.size;
  const rel = normalizePath(path.relative(rootDir, absolute));
  const record = { id, file: absolute, rel, code: "" };
  modules.set(absolute, record);
  ordered.push(record);

  const raw = fs.readFileSync(absolute, "utf8");
  const transformed = raw.replace(
    /require\s*\(\s*(['"])([^'"]+)\1\s*\)/g,
    (full, quote, specifier) => {
      if (!specifier.startsWith("./") && !specifier.startsWith("../")) {
        return full;
      }
      const target = resolveLocalModule(absolute, specifier);
      const targetId = visit(target);
      return `__core_require__(${targetId})`;
    },
  );

  record.code = compactSource(transformed);
  return id;
}

const entryId = visit(entryFile);

const moduleRows = ordered
  .map(
    (mod) =>
      `${mod.id}:function(module,exports,__core_require__){\n// ${mod.rel}\n${mod.code}},`,
  )
  .join("\n");

const bundle = `/* Core JS single-file minimized bundle (generated) */
(function(){
"use strict";
var __core_modules__={${moduleRows}
};
var __core_cache__={};
function __core_require__(id){
if(__core_cache__[id]){return __core_cache__[id].exports;}
var module={exports:{}};
__core_cache__[id]=module;
__core_modules__[id](module,module.exports,__core_require__);
return module.exports;
}
module.exports=__core_require__(${entryId});
})();\n`;

fs.mkdirSync(path.dirname(outFile), { recursive: true });
fs.writeFileSync(outFile, bundle, "utf8");
console.log(
  `Wrote ${outFile} (${ordered.length} modules, ${Buffer.byteLength(bundle, "utf8")} bytes)`,
);
