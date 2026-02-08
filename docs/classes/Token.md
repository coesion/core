# Token

Overview:
`Token` is a simple JWT implementation using HMAC algorithms.

Use `Token` to issue and verify signed JWT payloads for stateless authentication and trusted claims exchange.

Public API:
- `Token::encode($payload, $secret, $algo = 'HS256')`
- `Token::decode($jwt, $secret = null, $verify = true)`

Supported algorithms:
- `HS256`, `HS384`, `HS512`.

Security note:
- `Token::decode` throws if verification is enabled and the secret is empty.

Example:
```php
$jwt = Token::encode(['sub' => 1], 'secret');
$payload = Token::decode($jwt, 'secret');
```

The Token module exports methods for handling JWT tokens.

>JSON Web Token (JWT) is a means of representing claims to be transferred between two parties. The claims in a JWT are encoded as a JSON object that is digitally signed using JSON Web Signature (JWS) and/or encrypted using JSON Web Encryption (JWE).

See [JWT](http://openid.net/specs/draft-jones-json-web-token-07.html) specs online.

### Create a JWT token
---

You need to pass a shared secret for securely signing the payload.
You can use every JSON-encodable object as a payload.

**Important**: Payloads in JWT are user-readable, this format is not an obfuscation via encryption. JWT assure that no counterfeiting was applyed on received payload via shared secret signing.

```php
$payload = [1,2,3];
echo Token::encode($payload,"This is a secret code");
```

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.WzEsMiwzXQ.zQsu4G5B2CYZ9LI5IAMxY3GiWuvx6qL6ir7DcWompV8
```

### Decode a JWT token
---

```php
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.WzEsMiwzXQ.zQsu4G5B2CYZ9LI5IAMxY3GiWuvx6qL6ir7DcWompV8';
try {
	$payload = Token::decode($token,"This is a secret code");
} catch (Exception $e) {
	die( "Something fishy here : " . $e->getMessage() );
}

var_dump($payload);
```

```
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
```

The [Token](./Token.md) module exports methods for handling JWT tokens.

>JSON Web Token (JWT) is a means of representing claims to be transferred between two parties. The claims in a JWT are encoded as a JSON object that is digitally signed using JSON Web Signature (JWS) and/or encrypted using JSON Web Encryption (JWE).

See [JWT](http://openid.net/specs/draft-jones-json-web-token-07.html) specs online.
