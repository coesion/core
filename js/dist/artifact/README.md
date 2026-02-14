# @coesion/core-js

Artifact-only distribution for Coesion Core JS.

## Install

```bash
npm install @coesion/core-js
```

## Usage

```js
const { Route, Response } = require('@coesion/core-js');

Route.get('/', () => 'Core JS loaded from artifact package.');
Response.send();
```

## Notes

- This package is generated from the factory repository.
- Runtime entrypoint is `core.js` (single-file minimized bundle).
- Source-only directories like src, tests, and scripts are excluded.
