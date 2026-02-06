# Hash


Overview:
`Hash` provides hashing helpers including UUID generation and a murmur hash implementation.

Public API:
- `Hash::make($payload, $method = 'md5', $raw = false)` hashes payload (serialized).
- `Hash::verify($payload, $hash, $method = 'md5')` verifies payload.
- `Hash::methods()` lists supported algorithms.
- `Hash::can($algo)` checks availability.
- `Hash::uuid($type = 4, $namespace = '', $name = '')` generates UUID v3, v4, v5.
- `Hash::murmur($key, $seed = 0, $as_integer = false)` murmurhash3 implementation.
- `Hash::random($bytes = 9)` returns URL-safe random string.
- `Hash::random_bytes($bytes)` returns raw random bytes.

Example:
```php
$hash = Hash::make(['id' => 1], 'sha256');
$ok = Hash::verify(['id' => 1], $hash, 'sha256');
```

The Hash module allow you to make hashes of variables.

### Create an hash from a value
---

```php
$data = [1,2,3];
$hash = Hash::make($data);
echo $hash;
```
```
262bbc0aa0dc62a93e350f1f7df792b9
```

### Verify if an hash matches a value
---

```php
var_dump(
  Hash::verify([1,2,3],'262bbc0aa0dc62a93e350f1f7df792b9'),
  Hash::verify([1,2,3,4],'262bbc0aa0dc62a93e350f1f7df792b9')
);
```
```
bool(true)
bool(false)
```

### Create an hash with a specified algorithm
---

You can pass the hashing algorithm name as second optional parameter of the `make` method.

```php
$data = [1,2,3];
$hash = Hash::make($data,'sha256');
echo $hash;
```
```
5a7c86cf345a733f16365dfaa43fe6b5dbf0d4cfb192fa3186b11795edaab62c
```

As shorthand, you can use the callStatic magic method and pass the alghoritm name as the method name.

```php
$data = [1,2,3];
$hash = Hash::crc32($data);
echo $hash;
```
```
d109fdce
```

### List all registered hashing algorithms
---

```php
print_r( Hash::methods() );
```

```
Array
(
    [0] => md2
    [1] => md4
    [2] => md5
    [3] => sha1
    [4] => sha224
    [5] => sha256
    [6] => sha384
    [7] => sha512
    [8] => ripemd128
    [9] => ripemd160
    [10] => ripemd256
    [11] => ripemd320
    [12] => whirlpool
    [13] => tiger128,3
    [14] => tiger160,3
    [15] => tiger192,3
    [16] => tiger128,4
    [17] => tiger160,4
    [18] => tiger192,4
    [19] => snefru
    [20] => snefru256
    [21] => gost
    [22] => adler32
    [23] => crc32
    [24] => crc32b
    [25] => fnv132
    [26] => fnv164
    [27] => joaat
    [28] => haval128,3
    [29] => haval160,3
    [30] => haval192,3
    [31] => haval224,3
    [32] => haval256,3
    [33] => haval128,4
    [34] => haval160,4
    [35] => haval192,4
    [36] => haval224,4
    [37] => haval256,4
    [38] => haval128,5
    [39] => haval160,5
    [40] => haval192,5
    [41] => haval224,5
    [42] => haval256,5
)
```

### Check if you can use an algorithm
---

```php
var_dump(
  Hash::can('whirlpool'),
  Hash::can('funny_encoding')
);
```

```
bool(true)
bool(false)
```

### Generate an UUID
---

Generate an [RFC 4122](https://www.ietf.org/rfc/rfc4122.txt) compliant Universally Unique IDentifier  
Interface : `uuid($version = 4, $namespace = '', $name = '')`  

```php
print_r([
  Hash::uuid(),
  Hash::uuid(),
]);
```

```
Array
(
    [0] => 33869452-f4c6-46da-a93a-e61f8579e9e7
    [1] => 0759d22e-d44f-46ca-bfd5-2984c64a1efc
)
```
| Version | Description | Usage |
|---|---|---|
| 3 | This generates a UUID from a MD5 hash of a namespace (another valid UUID) and name. Given the same namespace and name, the output is always the same. | Used for backwards compatibility |
| 4 | UUIDs are pseudo-random.| Random ID |
| 5 | This generates a UUID from an SHA-1 hash of a namespace (another valid UUID) and name. Given the same namespace and name, the output is always the same. | Preferred |

The [Hash](./Hash.md) module allow you to make hashes of variables.

### Create an hash from a value
---

```php
$data = [1,2,3];
$hash = Hash::make($data);
echo $hash;
```
```
262bbc0aa0dc62a93e350f1f7df792b9
```

### Verify if an hash matches a value
---

```php
var_dump(
  Hash::verify([1,2,3],'262bbc0aa0dc62a93e350f1f7df792b9'),
  Hash::verify([1,2,3,4],'262bbc0aa0dc62a93e350f1f7df792b9')
);
```
```
bool(true)
bool(false)
```

### Create an hash with a specified algorithm
---

You can pass the hashing algorithm name as second optional parameter of the `make` method.

```php
$data = [1,2,3];
$hash = Hash::make($data,'sha256');
echo $hash;
```
```
5a7c86cf345a733f16365dfaa43fe6b5dbf0d4cfb192fa3186b11795edaab62c
```

As shorthand, you can use the callStatic magic method and pass the alghoritm name as the method name.

```php
$data = [1,2,3];
$hash = Hash::crc32($data);
echo $hash;
```
```
d109fdce
```

### List all registered hashing algorithms
---

```php
print_r( Hash::methods() );
```

```
Array
(
    [0] => md2
    [1] => md4
    [2] => md5
    [3] => sha1
    [4] => sha224
    [5] => sha256
    [6] => sha384
    [7] => sha512
    [8] => ripemd128
    [9] => ripemd160
    [10] => ripemd256
    [11] => ripemd320
    [12] => whirlpool
    [13] => tiger128,3
    [14] => tiger160,3
    [15] => tiger192,3
    [16] => tiger128,4
    [17] => tiger160,4
    [18] => tiger192,4
    [19] => snefru
    [20] => snefru256
    [21] => gost
    [22] => adler32
    [23] => crc32
    [24] => crc32b
    [25] => fnv132
    [26] => fnv164
    [27] => joaat
    [28] => haval128,3
    [29] => haval160,3
    [30] => haval192,3
    [31] => haval224,3
    [32] => haval256,3
    [33] => haval128,4
    [34] => haval160,4
    [35] => haval192,4
    [36] => haval224,4
    [37] => haval256,4
    [38] => haval128,5
    [39] => haval160,5
    [40] => haval192,5
    [41] => haval224,5
    [42] => haval256,5
)
```

### Check if you can use an algorithm
---

```php
var_dump(
  Hash::can('whirlpool'),
  Hash::can('funny_encoding')
);
```

```
bool(true)
bool(false)
```

### Generate an UUID
---

Generate an [RFC 4122](https://www.ietf.org/rfc/rfc4122.txt) compliant Universally Unique IDentifier  
Interface : `uuid($version = 4, $namespace = '', $name = '')`  

```php
print_r([
  Hash::uuid(),
  Hash::uuid(),
]);
```

```
Array
(
    [0] => 33869452-f4c6-46da-a93a-e61f8579e9e7
    [1] => 0759d22e-d44f-46ca-bfd5-2984c64a1efc
)
```
| Version | Description | Usage |
|---|---|---|
| 3 | This generates a UUID from a MD5 hash of a namespace (another valid UUID) and name. Given the same namespace and name, the output is always the same. | Used for backwards compatibility |
| 4 | UUIDs are pseudo-random.| Random ID |
| 5 | This generates a UUID from an SHA-1 hash of a namespace (another valid UUID) and name. Given the same namespace and name, the output is always the same. | Preferred |



