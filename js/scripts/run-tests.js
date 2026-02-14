#!/usr/bin/env node

const fs = require("fs");
const path = require("path");
const { spawnSync } = require("child_process");

const root = process.cwd();
const testsDir = path.join(root, "tests");

function collectTestFiles(dir, out) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      collectTestFiles(full, out);
      continue;
    }
    if (entry.isFile() && entry.name.endsWith(".test.js")) {
      out.push(full);
    }
  }
}

if (!fs.existsSync(testsDir)) {
  console.error("[js:test] tests directory not found:", testsDir);
  process.exit(1);
}

const files = [];
collectTestFiles(testsDir, files);
files.sort();

if (files.length === 0) {
  console.error("[js:test] no *.test.js files found under", testsDir);
  process.exit(1);
}

const result = spawnSync(process.execPath, ["--test", ...files], {
  stdio: "inherit",
});

process.exit(result.status === null ? 1 : result.status);
