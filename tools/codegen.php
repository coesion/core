<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Generate deterministic class/doc/test scaffolds.
 */
class CodegenTool {

    /**
     * @param array $argv
     * @return int
     */
    public static function run(array $argv) {
        $opts = static::parseOptions($argv);
        if (!empty($opts['help'])) {
            fwrite(STDOUT, static::usage());
            return 0;
        }

        if ($opts['type'] !== 'class') {
            return static::fail("[codegen] unsupported --type '{$opts['type']}', expected 'class'", $opts['format']);
        }

        if (!static::isValidClassName($opts['name'])) {
            return static::fail("[codegen] invalid --name '{$opts['name']}'", $opts['format']);
        }

        if ($opts['namespace'] !== '' && !static::isValidNamespace($opts['namespace'])) {
            return static::fail("[codegen] invalid --namespace '{$opts['namespace']}'", $opts['format']);
        }

        $name = $opts['name'];
        $root = rtrim($opts['root'], DIRECTORY_SEPARATOR);
        $classPath = $root . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $name . '.php';
        $docPath = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $name . '.md';
        $testPath = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $name . 'Test.php';

        $results = [
            'created' => [],
            'skipped' => [],
            'errors' => [],
        ];

        static::writeFile($classPath, static::classTemplate($name, $opts['namespace']), $opts['force'], $results);
        static::writeFile($docPath, static::docTemplate($name, $opts['namespace']), $opts['force'], $results);
        static::writeFile($testPath, static::testTemplate($name, $opts['namespace']), $opts['force'], $results);

        $payload = [
            'type' => 'class',
            'name' => $name,
            'namespace' => $opts['namespace'],
            'root' => $root,
            'result' => $results,
        ];

        static::emit($payload, $opts['format']);
        return empty($results['errors']) ? 0 : 1;
    }

    /**
     * @param string $message
     * @param string $format
     * @return int
     */
    protected static function fail($message, $format) {
        $payload = [
            'result' => [
                'created' => [],
                'skipped' => [],
                'errors' => [$message],
            ],
        ];
        static::emit($payload, $format);
        return 1;
    }

    /**
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv) {
        $opts = [
            'type' => 'class',
            'name' => '',
            'namespace' => '',
            'root' => dirname(__DIR__),
            'force' => false,
            'format' => 'json',
            'help' => false,
        ];

        foreach (array_slice($argv, 1) as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $opts['help'] = true;
                continue;
            }
            if ($arg === '--force') {
                $opts['force'] = true;
                continue;
            }
            if (strpos($arg, '--type=') === 0) {
                $opts['type'] = strtolower(trim(substr($arg, 7)));
                continue;
            }
            if (strpos($arg, '--name=') === 0) {
                $opts['name'] = trim(substr($arg, 7), " \t\n\r\0\x0B'\"");
                continue;
            }
            if (strpos($arg, '--namespace=') === 0) {
                $opts['namespace'] = trim(substr($arg, 12), " \t\n\r\0\x0B'\"");
                continue;
            }
            if (strpos($arg, '--root=') === 0) {
                $opts['root'] = rtrim(trim(substr($arg, 7), " \t\n\r\0\x0B'\""));
                continue;
            }
            if (strpos($arg, '--format=') === 0) {
                $format = strtolower(trim(substr($arg, 9)));
                if (in_array($format, ['json', 'md'], true)) {
                    $opts['format'] = $format;
                }
                continue;
            }
        }

        return $opts;
    }

    /**
     * @param string $path
     * @param string $contents
     * @param bool $force
     * @param array $results
     * @return void
     */
    protected static function writeFile($path, $contents, $force, &$results) {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $results['errors'][] = "Cannot create directory: $dir";
            return;
        }

        if (is_file($path) && !$force) {
            $results['skipped'][] = $path;
            return;
        }

        if (file_put_contents($path, $contents) === false) {
            $results['errors'][] = "Cannot write file: $path";
            return;
        }

        $results['created'][] = $path;
    }

    /**
     * @param array $payload
     * @param string $format
     * @return void
     */
    protected static function emit(array $payload, $format) {
        if ($format === 'md') {
            $md = [];
            $md[] = '# Code Generation Output';
            $md[] = '';
            $md[] = '```json';
            $md[] = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $md[] = '```';
            $md[] = '';
            fwrite(STDOUT, implode("\n", $md));
            return;
        }

        fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    /**
     * @param string $name
     * @return bool
     */
    protected static function isValidClassName($name) {
        return (bool) preg_match('/^[A-Z][A-Za-z0-9_]*$/', (string) $name);
    }

    /**
     * @param string $namespace
     * @return bool
     */
    protected static function isValidNamespace($namespace) {
        return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', (string) $namespace);
    }

    /**
     * @param string $name
     * @param string $namespace
     * @return string
     */
    protected static function classTemplate($name, $namespace) {
        $ns = '';
        if ($namespace !== '') {
            $ns = "namespace $namespace;\n\n";
        }

        return "<?php\n\n"
            . $ns
            . "/**\n"
            . " * $name\n"
            . " *\n"
            . " * TODO: add class description.\n"
            . " *\n"
            . " * @package core\n"
            . " * @author Stefano Azzolini <lastguest@gmail.com>\n"
            . " * @copyright Coesion - 2026\n"
            . " */\n\n"
            . "class $name {\n"
            . "}\n";
    }

    /**
     * @param string $name
     * @param string $namespace
     * @return string
     */
    protected static function docTemplate($name, $namespace) {
        $label = $namespace ? $namespace . '\\\\' . $name : $name;
        return "# $name\n\n"
            . "Overview:\n"
            . "`$label` autogenerated scaffold class.\n\n"
            . "Public API:\n"
            . "- _Add methods and usage details._\n";
    }

    /**
     * @param string $name
     * @param string $namespace
     * @return string
     */
    protected static function testTemplate($name, $namespace) {
        $fqcn = $namespace ? '\\\\' . $namespace . '\\\\' . $name : $name;
        return "<?php\n\n"
            . "use PHPUnit\\Framework\\TestCase;\n\n"
            . "class {$name}Test extends TestCase {\n\n"
            . "    public function testClassCanBeInstantiated(): void {\n"
            . "        \$instance = new $fqcn();\n"
            . "        \$this->assertInstanceOf($fqcn::class, \$instance);\n"
            . "    }\n"
            . "}\n";
    }

    /**
     * @return string
     */
    protected static function usage() {
        return <<<TXT
Usage: php tools/codegen.php [options]

Options:
  --type=class                     Scaffold type (current: class)
  --name=<ClassName>               Class name (PascalCase)
  --namespace=<Namespace>          Optional class namespace (e.g. App\\Domain)
  --root=<path>                    Target project root (default: current repo root)
  --force                          Overwrite existing files
  --format=json|md                 Output format (default: json)
  --help, -h                       Show this help

Examples:
  php tools/codegen.php --type=class --name=Report
  php tools/codegen.php --type=class --name=Report --namespace=App\\Domain --force --format=md

TXT;
    }
}

exit(CodegenTool::run($argv));
