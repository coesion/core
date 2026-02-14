# @coesionlabs/core

Artifact-only distribution for Coesion Core JS.

## Install

```bash
npm install @coesionlabs/core
```

## Usage

```js
const { Route, Response } = require('@coesionlabs/core');

Route.get('/', () => 'Core JS loaded from artifact package.');
Response.send();
```

## Notes

- This package is generated from the factory repository.
- Runtime entrypoint is `core.js` (single-file minimized bundle).
- Source-only directories like src, tests, and scripts are excluded.
