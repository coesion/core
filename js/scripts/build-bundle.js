const fs = require("node:fs");
const path = require("node:path");

const header = "// Generated bundle entry for Core JS\n";
const payload = "module.exports = require('../src/index');\n";

const outFile = path.join(__dirname, "..", "dist", "core.js");
fs.mkdirSync(path.dirname(outFile), { recursive: true });
fs.writeFileSync(outFile, header + payload, "utf8");
console.log(`Wrote ${outFile}`);