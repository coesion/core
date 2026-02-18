<?php

/**
 * Form
 *
 * Request-bound form submission helper with validation and CSRF checks.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Form {
  use Module;

  protected static $errors = [];

  /**
   * Validate a form submission and return a deterministic envelope.
   *
   * @param array $rules
   * @param array $options
   * @return array
   */
  public static function submit($rules, $options = []) {
    $opts = array_merge([
      'source' => Options::get('core.form.source', 'input'),
      'defaults' => [],
      'only' => null,
      'normalizers' => [],
      'csrf' => Options::get('core.form.csrf', true),
      'csrf_methods' => Options::get('core.form.csrf_methods', ['post', 'put', 'patch', 'delete']),
      'csrf_options' => [],
      'flash_on_error' => Options::get('core.form.flash_on_error', true),
      'flash_key' => Options::get('core.form.flash_key', '_form_old'),
    ], (array) $options);

    $data = static::resolveData($opts['source']);

    if (!empty($opts['defaults']) && is_array($opts['defaults'])) {
      $data = array_merge($opts['defaults'], $data);
    }

    if (is_array($opts['only'])) {
      $filtered = [];
      foreach ($opts['only'] as $field) {
        if (array_key_exists($field, $data)) {
          $filtered[$field] = $data[$field];
        }
      }
      $data = $filtered;
    }

    static::applyNormalizers($data, (array) $opts['normalizers']);

    $csrfChecked = false;
    $csrfValid = true;

    if ($opts['csrf']) {
      $method = strtolower((string) Request::method());
      if (in_array($method, (array) $opts['csrf_methods'], true)) {
        $csrfChecked = true;
        $csrfValid = CSRF::verify((array) $opts['csrf_options']);
      }
    }

    $valid = Check::valid((array) $rules, $data);
    static::$errors = Check::errors();

    if (!$csrfValid) {
      static::$errors['_csrf'] = 'Invalid CSRF token.';
      $valid = false;
    }

    if (!$valid && $opts['flash_on_error']) static::flashWithKey($data, $opts['flash_key']);
    if ($valid) static::flashWithKey([], $opts['flash_key']);

    return [
      'valid' => (bool) $valid,
      'data' => $data,
      'errors' => static::errors(),
      'csrf' => [
        'checked' => (bool) $csrfChecked,
        'valid' => (bool) $csrfValid,
      ],
    ];
  }

  /**
   * Return validation errors from the last submit call.
   *
   * @return array
   */
  public static function errors() {
    return (array) static::$errors;
  }

  /**
   * Return old flashed value (or whole map if key is null).
   *
   * @param string|null $key
   * @param mixed $default
   * @return mixed
   */
  public static function old($key = null, $default = null) {
    $flashKey = Options::get('core.form.flash_key', '_form_old');
    $bag = (array) Session::get($flashKey, []);
    if ($key === null) {
      return $bag;
    }
    return array_key_exists($key, $bag)
      ? $bag[$key]
      : (is_callable($default) ? call_user_func($default) : $default);
  }

  /**
   * Flash old input data in session.
   *
   * @param array $data
   * @return void
   */
  public static function flash($data = []) {
    static::flashWithKey($data, Options::get('core.form.flash_key', '_form_old'));
  }

  /**
   * Return the current CSRF token.
   *
   * @return string
   */
  public static function csrfToken() {
    return (string) CSRF::token();
  }

  /**
   * Render hidden CSRF field HTML.
   *
   * @param string|null $name
   * @return string
   */
  public static function csrfField($name = null) {
    $input = $name ?: Options::get('core.csrf.input', '_csrf');
    return '<input type="hidden" name="' . htmlspecialchars($input, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars(static::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
  }

  /**
   * @param string $source
   * @return array
   */
  protected static function resolveData($source) {
    $source = strtolower((string) $source);
    switch ($source) {
      case 'post':
        $raw = Request::post();
      break;
      case 'get':
        $raw = Request::get();
      break;
      case 'input':
      default:
        $raw = Request::input();
      break;
    }

    if ($raw instanceof ArrayObject) {
      return $raw->getArrayCopy();
    }
    return (array) $raw;
  }

  /**
   * @param array $data
   * @param array $normalizers
   * @return void
   */
  protected static function applyNormalizers(&$data, $normalizers) {
    foreach ($normalizers as $field => $callback) {
      if (!is_callable($callback) || !array_key_exists($field, $data)) continue;
      $data[$field] = call_user_func($callback, $data[$field], $data);
    }
  }

  /**
   * @param array $data
   * @param string $key
   * @return void
   */
  protected static function flashWithKey($data, $key) {
    Session::set($key, (array) $data);
  }
}
