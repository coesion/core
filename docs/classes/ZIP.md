# ZIP

Overview:
`ZIP` wraps `ZipArchive` and provides helpers to create, write, and download zip files.

Use `ZIP` to build downloadable archives or package generated files programmatically without manual ZipArchive boilerplate.

Public API:
- `ZIP::create($name = '')` constructs a ZIP file.
- `new ZIP($name = '')` creates and opens an archive.
- `->path()` returns the file path.
- `->write($filename, $data)` writes an entry.
- `->addDirectory($folder, $root = null)` adds a directory recursively.
- `->close()` closes the archive.
- `->download()` streams the ZIP to the browser.

Example:
```php
$zip = ZIP::create('backup');
$zip->write('hello.txt', 'Hello');
$zip->close();
```
