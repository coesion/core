<?php

$root = dirname(__DIR__);
$classesDir = $root . DIRECTORY_SEPARATOR . 'classes';
$distDir = $root . DIRECTORY_SEPARATOR . 'dist';
$corePath = $distDir . DIRECTORY_SEPARATOR . 'core.php';
$versionPath = $root . DIRECTORY_SEPARATOR . 'VERSION';
$generator = 'tools/build-core.php';
$sourceRepo = trim((string)@shell_exec('git config --get remote.origin.url'));
$sourceCommit = trim((string)@shell_exec('git rev-parse --short=12 HEAD'));
$sourceEpoch = getenv('SOURCE_DATE_EPOCH');
$sourceBuiltAt = '';

if (!is_file($versionPath)) {
  fwrite(STDERR, "Missing VERSION file: $versionPath\n");
  exit(1);
}

$version = trim((string)file_get_contents($versionPath));
if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
  fwrite(STDERR, "Invalid VERSION value '$version'. Expected X.Y.Z\n");
  exit(1);
}

if ($sourceEpoch !== false && preg_match('/^\d+$/', $sourceEpoch)) {
  $sourceBuiltAt = gmdate('c', (int)$sourceEpoch);
}

if (!is_dir($classesDir)) {
  fwrite(STDERR, "Classes directory not found: $classesDir\n");
  exit(1);
}

if (!is_dir($distDir) && !mkdir($distDir, 0775, true) && !is_dir($distDir)) {
  fwrite(STDERR, "Failed to create dist directory: $distDir\n");
  exit(1);
}

$files = [];
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($classesDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
  if (!$fileInfo->isFile()) {
    continue;
  }
  if (strtolower($fileInfo->getExtension()) !== 'php') {
    continue;
  }
  $files[] = $fileInfo->getPathname();
}

sort($files, SORT_STRING);

if ($files === []) {
  fwrite(STDERR, "No PHP class files found under: $classesDir\n");
  exit(1);
}

$metadata = [];
$symbolToFile = [];

foreach ($files as $file) {
  $code = file_get_contents($file);
  if ($code === false) {
    fwrite(STDERR, "Failed to read file: $file\n");
    exit(1);
  }

  $data = analyzeFile($code);
  $metadata[$file] = $data;

  foreach ($data['provides'] as $symbol) {
    if (isset($symbolToFile[$symbol]) && $symbolToFile[$symbol] !== $file) {
      fwrite(STDERR, "Duplicate symbol '$symbol' in:\n- {$symbolToFile[$symbol]}\n- $file\n");
      exit(1);
    }
    $symbolToFile[$symbol] = $file;
  }
}

$edges = [];
$inDegree = [];
foreach ($files as $file) {
  $edges[$file] = [];
  $inDegree[$file] = 0;
}

foreach ($files as $file) {
  $deps = [];
  foreach ($metadata[$file]['requires'] as $symbol) {
    if (!isset($symbolToFile[$symbol])) {
      continue;
    }
    $depFile = $symbolToFile[$symbol];
    if ($depFile === $file) {
      continue;
    }
    $deps[$depFile] = true;
  }

  $depFiles = array_keys($deps);
  sort($depFiles, SORT_STRING);
  foreach ($depFiles as $depFile) {
    $edges[$depFile][] = $file;
    $inDegree[$file]++;
  }
}

$ready = [];
foreach ($files as $file) {
  if ($inDegree[$file] === 0) {
    $ready[] = $file;
  }
}
sort($ready, SORT_STRING);

$ordered = [];
while ($ready !== []) {
  $file = array_shift($ready);
  $ordered[] = $file;

  $nextFiles = $edges[$file];
  sort($nextFiles, SORT_STRING);
  foreach ($nextFiles as $next) {
    $inDegree[$next]--;
    if ($inDegree[$next] === 0) {
      $ready[] = $next;
    }
  }
  sort($ready, SORT_STRING);
}

if (count($ordered) !== count($files)) {
  $remaining = [];
  foreach ($files as $file) {
    if ($inDegree[$file] > 0) {
      $remaining[] = $file;
    }
  }
  sort($remaining, SORT_STRING);
  fwrite(STDERR, "Could not resolve class dependency ordering. Remaining files:\n- " . implode("\n- ", $remaining) . "\n");
  exit(1);
}

$chunks = [];
foreach ($ordered as $file) {
  $min = php_strip_whitespace($file);
  if ($min === '') {
    continue;
  }

  $min = preg_replace('/^\s*<\?php\s*/', '', $min, 1);
  if ($min === null) {
    fwrite(STDERR, "Regex error while processing: $file\n");
    exit(1);
  }

  $min = preg_replace('/\?>\s*$/', '', $min, 1);
  if ($min === null) {
    fwrite(STDERR, "Regex error while processing: $file\n");
    exit(1);
  }

  $namespace = $metadata[$file]['namespace'];
  if ($namespace !== '') {
    $nsPattern = '/^namespace\s+' . preg_quote($namespace, '/') . '\s*;\s*/';
    $min = preg_replace($nsPattern, '', $min, 1);
    if ($min === null) {
      fwrite(STDERR, "Regex error while stripping namespace in: $file\n");
      exit(1);
    }
  }

  $min = preg_replace('/\b(?:include|include_once|require|require_once)\s+[^;]+;/', '', $min);
  if ($min === null) {
    fwrite(STDERR, "Regex error while stripping include/require in: $file\n");
    exit(1);
  }

  $min = trim($min);
  if ($min !== '') {
    if (basename($file) === 'Core.php') {
      $min = preg_replace("/public\\s+const\\s+VERSION\\s*=\\s*'[^']*';/", "public const VERSION = '$version';", $min, 1);
      if ($min === null) {
        fwrite(STDERR, "Regex error while injecting version in: $file\n");
        exit(1);
      }
    }

    if ($namespace === '') {
      $chunks[] = 'namespace {' . $min . '}';
    } else {
      $chunks[] = 'namespace ' . $namespace . ' {' . $min . '}';
    }
  }
}

$meta = [
  'generator' => $generator,
  'source' => $sourceRepo !== '' ? $sourceRepo : 'unknown',
  'commit' => $sourceCommit !== '' ? $sourceCommit : 'unknown',
  'version' => $version,
];

if ($sourceBuiltAt !== '') {
  $meta['built_at'] = $sourceBuiltAt;
}

$output = "<?php\n\n";
$output .= "/**\n";
$output .= " * Core\n";
$output .= " *\n";
$output .= " * A modular class collection for rapid application development.\n";
$output .= " *\n";
$output .= " * @package core\n";
$output .= " * @author Stefano Azzolini <lastguest@gmail.com>\n";
$output .= " * @repository " . ($meta['source'] ?? 'unknown') . "\n";
$output .= " * @license MIT (LICENSE.md)\n";
$output .= " * @copyright Coesion - 2026\n";
$output .= " */\n";
$output .= '// Coesion Core artifact metadata: ' . json_encode($meta, JSON_UNESCAPED_SLASHES) . "\n";
$output .= "namespace { if (defined('COESION_CORE_LOADED')) { return; } define('COESION_CORE_LOADED', true); }\n";
$output .= implode("\n", $chunks) . "\n";

if (file_put_contents($corePath, $output) === false) {
  fwrite(STDERR, "Failed to write file: $corePath\n");
  exit(1);
}

fwrite(STDOUT, "Built $corePath\n");

function analyzeFile($code) {
  $tokens = token_get_all($code);
  $namespace = '';
  $provides = [];
  $requires = [];

  $count = count($tokens);
  $braceDepth = 0;
  $pendingClassBody = false;
  $classScopeDepths = [];

  for ($i = 0; $i < $count; $i++) {
    $token = $tokens[$i];

    if (is_string($token)) {
      if ($token === '{') {
        $braceDepth++;
        if ($pendingClassBody) {
          $classScopeDepths[] = $braceDepth;
          $pendingClassBody = false;
        }
      } elseif ($token === '}') {
        if ($braceDepth > 0) {
          if ($classScopeDepths !== [] && end($classScopeDepths) === $braceDepth) {
            array_pop($classScopeDepths);
          }
          $braceDepth--;
        }
      }
      continue;
    }

    $id = $token[0];

    if ($id === T_NAMESPACE) {
      $namespace = readNamespaceName($tokens, $i + 1);
      continue;
    }

    if ($id === T_CLASS || $id === T_INTERFACE || $id === T_TRAIT) {
      if ($id === T_CLASS && isAnonymousClass($tokens, $i)) {
        continue;
      }

      $name = readNextIdentifier($tokens, $i + 1);
      if ($name === null) {
        continue;
      }

      $fullName = qualifyNameForDeclaration($namespace, $name);
      $provides[$fullName] = true;

      $scan = $i + 1;
      while ($scan < $count) {
        $t = $tokens[$scan];
        if (is_string($t)) {
          if ($t === '{') {
            $pendingClassBody = true;
            break;
          }
          if ($t === ';') {
            break;
          }
          $scan++;
          continue;
        }

        $tid = $t[0];
        if ($tid === T_EXTENDS || $tid === T_IMPLEMENTS) {
          $depNames = readNameList($tokens, $scan + 1);
          foreach ($depNames as $dep) {
            $resolved = resolveName($dep, $namespace);
            $requires[$resolved] = true;
          }
        }
        $scan++;
      }

      continue;
    }

    if ($id === T_USE && $classScopeDepths !== []) {
      $depNames = readNameList($tokens, $i + 1);
      foreach ($depNames as $dep) {
        $resolved = resolveName($dep, $namespace);
        $requires[$resolved] = true;
      }
      continue;
    }

    if ($id === T_STRING && strtolower($token[1]) === 'class_alias') {
      $aliasTarget = readClassAliasTarget($tokens, $i + 1);
      if ($aliasTarget !== null) {
        $requires[resolveName($aliasTarget, $namespace)] = true;
      }
      continue;
    }
  }

  return [
    'namespace' => $namespace,
    'provides' => array_keys($provides),
    'requires' => array_keys($requires),
  ];
}

function readNamespaceName($tokens, $start) {
  $parts = [];
  $count = count($tokens);
  for ($i = $start; $i < $count; $i++) {
    $token = $tokens[$i];
    if (is_string($token)) {
      if ($token === ';' || $token === '{') {
        break;
      }
      continue;
    }

    if (isNameToken($token[0])) {
      $parts[] = $token[1];
    }
  }

  return trim(implode('', $parts), '\\');
}

function readNextIdentifier($tokens, $start) {
  $count = count($tokens);
  for ($i = $start; $i < $count; $i++) {
    $token = $tokens[$i];
    if (is_array($token) && $token[0] === T_STRING) {
      return $token[1];
    }
  }

  return null;
}

function readNameList($tokens, $start) {
  $names = [];
  $buffer = '';
  $count = count($tokens);

  for ($i = $start; $i < $count; $i++) {
    $token = $tokens[$i];

    if (is_string($token)) {
      if ($token === ',') {
        if ($buffer !== '') {
          $names[] = trim($buffer);
          $buffer = '';
        }
        continue;
      }

      if ($token === ';' || $token === '{' || $token === ')') {
        break;
      }

      continue;
    }

    $id = $token[0];

    if (isNameToken($id)) {
      $buffer .= $token[1];
      continue;
    }

    if ($id === T_WHITESPACE) {
      continue;
    }

    if ($id === T_AS || $id === T_INSTEADOF) {
      break;
    }

    if ($id === T_EXTENDS || $id === T_IMPLEMENTS) {
      if ($buffer !== '') {
        $names[] = trim($buffer);
      }
      return $names;
    }
  }

  if ($buffer !== '') {
    $names[] = trim($buffer);
  }

  return array_values(array_filter($names, static fn($name) => $name !== ''));
}

function isAnonymousClass($tokens, $classIndex) {
  for ($i = $classIndex - 1; $i >= 0; $i--) {
    $token = $tokens[$i];
    if (is_string($token)) {
      continue;
    }

    if ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT || $token[0] === T_ATTRIBUTE) {
      continue;
    }

    return $token[0] === T_NEW;
  }

  return false;
}

function isNameToken($tokenId) {
  if ($tokenId === T_STRING || $tokenId === T_NS_SEPARATOR) {
    return true;
  }

  if (defined('T_NAME_QUALIFIED') && $tokenId === T_NAME_QUALIFIED) {
    return true;
  }

  if (defined('T_NAME_FULLY_QUALIFIED') && $tokenId === T_NAME_FULLY_QUALIFIED) {
    return true;
  }

  if (defined('T_NAME_RELATIVE') && $tokenId === T_NAME_RELATIVE) {
    return true;
  }

  return false;
}

function resolveName($name, $namespace) {
  $name = trim($name);
  if ($name === '') {
    return '';
  }

  if ($name[0] === '\\') {
    return ltrim($name, '\\');
  }

  if ($namespace === '') {
    return ltrim($name, '\\');
  }

  if (str_starts_with($name, 'namespace\\')) {
    return $namespace . '\\' . substr($name, strlen('namespace\\'));
  }

  return $namespace . '\\' . $name;
}

function qualifyNameForDeclaration($namespace, $name) {
  if ($namespace === '') {
    return $name;
  }

  return $namespace . '\\' . $name;
}

function readClassAliasTarget($tokens, $start) {
  $count = count($tokens);
  $seenParen = false;

  for ($i = $start; $i < $count; $i++) {
    $token = $tokens[$i];
    if (is_string($token)) {
      if ($token === '(') {
        $seenParen = true;
        continue;
      }

      if ($token === ')' || $token === ';') {
        break;
      }

      continue;
    }

    if (!$seenParen) {
      continue;
    }

    if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
      return trim($token[1], "'\"");
    }

    if ($token[0] !== T_WHITESPACE) {
      break;
    }
  }

  return null;
}
