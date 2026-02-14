const crypto = require("node:crypto");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

function murmurhash3_32_gc(key, seed = 0) {
  let remainder = key.length & 3;
  let bytes = key.length - remainder;
  let h1 = seed;
  let c1 = 0xcc9e2d51;
  let c2 = 0x1b873593;
  let i = 0;
  while (i < bytes) {
    let k1 =
      (key.charCodeAt(i) & 0xff) |
      ((key.charCodeAt(++i) & 0xff) << 8) |
      ((key.charCodeAt(++i) & 0xff) << 16) |
      ((key.charCodeAt(++i) & 0xff) << 24);
    ++i;
    k1 = (((k1 & 0xffff) * c1) + ((((k1 >>> 16) * c1) & 0xffff) << 16)) & 0xffffffff;
    k1 = (k1 << 15) | (k1 >>> 17);
    k1 = (((k1 & 0xffff) * c2) + ((((k1 >>> 16) * c2) & 0xffff) << 16)) & 0xffffffff;
    h1 ^= k1;
    h1 = (h1 << 13) | (h1 >>> 19);
    const h1b = (((h1 & 0xffff) * 5) + ((((h1 >>> 16) * 5) & 0xffff) << 16)) & 0xffffffff;
    h1 = (((h1b & 0xffff) + 0x6b64) + ((((h1b >>> 16) + 0xe654) & 0xffff) << 16));
  }

  let k1 = 0;
  switch (remainder) {
    case 3:
      k1 ^= (key.charCodeAt(i + 2) & 0xff) << 16;
    case 2:
      k1 ^= (key.charCodeAt(i + 1) & 0xff) << 8;
    case 1:
      k1 ^= (key.charCodeAt(i) & 0xff);
      k1 = (((k1 & 0xffff) * c1) + ((((k1 >>> 16) * c1) & 0xffff) << 16)) & 0xffffffff;
      k1 = (k1 << 15) | (k1 >>> 17);
      k1 = (((k1 & 0xffff) * c2) + ((((k1 >>> 16) * c2) & 0xffff) << 16)) & 0xffffffff;
      h1 ^= k1;
  }

  h1 ^= key.length;
  h1 ^= h1 >>> 16;
  h1 = (((h1 & 0xffff) * 0x85ebca6b) + ((((h1 >>> 16) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
  h1 ^= h1 >>> 13;
  h1 = (((h1 & 0xffff) * 0xc2b2ae35) + ((((h1 >>> 16) * 0xc2b2ae35) & 0xffff) << 16)) & 0xffffffff;
  h1 ^= h1 >>> 16;
  return h1 >>> 0;
}

class Hash extends ModuleSupport {
  static methods() {
    return crypto.getHashes();
  }

  static can(algo) {
    return algo === "murmur" || this.methods().includes(String(algo));
  }

  static make(payload, algo = "sha256") {
    const data = typeof payload === "string" ? payload : JSON.stringify(payload);
    if (algo === "murmur") return this.murmur(data);
    return crypto.createHash(algo).update(data).digest("hex");
  }

  static verify(payload, hash, algo = "sha256") {
    return this.make(payload, algo) === hash;
  }

  static random(size = 16) {
    return crypto.randomBytes(size).toString("hex");
  }

  static murmur(input, seed = 0, asInt = false) {
    const n = murmurhash3_32_gc(String(input), Number(seed));
    if (asInt) return n;
    return n.toString(36);
  }

  static uuid(version = 4, namespace = null, name = null) {
    if (version === 4 || typeof version === "undefined") return crypto.randomUUID();
    if (![3, 5].includes(Number(version))) return false;
    const ns = String(namespace || "").toLowerCase();
    if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/.test(ns)) return false;
    const nsBytes = Buffer.from(ns.replace(/-/g, ""), "hex");
    const data = Buffer.concat([nsBytes, Buffer.from(String(name || ""))]);
    const digest = crypto.createHash(version === 3 ? "md5" : "sha1").update(data).digest();
    const bytes = Buffer.from(digest.subarray(0, 16));
    bytes[6] = (bytes[6] & 0x0f) | (version << 4);
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = bytes.toString("hex");
    return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
  }
}

registerClass("Hash", Hash);
module.exports = Hash;