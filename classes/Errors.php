<?php

/**
 * Errors
 *
 * Handle system and application errors.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Errors {
    use Module, Events;

    const SIMPLE       = 0;
    const HTML         = 1;
    const SILENT       = 2;
    const JSON         = 3;
    const JSON_VERBOSE = 4;

    static $mode = self::SILENT;

    public static function capture($tracing_level=null){
      if($tracing_level!==null) error_reporting($tracing_level);
      set_error_handler(__CLASS__.'::traceError');
      set_exception_handler(__CLASS__.'::traceException');
    }

    public static function mode($mode=null){
      return $mode ? self::$mode=$mode : self::$mode;
    }

    public static function traceError($errno,$errstr,$errfile=null,$errline=null){
      // This error code is not included in error_reporting
      if (!(error_reporting() & $errno)) return;
      switch ( $errno ) {
        case E_USER_ERROR:
            $type = 'Fatal';
        break;
        case E_USER_WARNING:
        case E_WARNING:
            $type = 'Warning';
        break;
        case E_USER_NOTICE:
        case E_NOTICE:
        case E_STRICT:
            $type = 'Notice';
        break;
        default:
            $type = 'Error';
        break;
      }
      $e = new \ErrorException($type.': '.$errstr, 0, $errno, $errfile, $errline);
      $error_type = strtolower($type);
      $chk_specific = array_filter(array_merge(
                      (array)static::trigger($error_type,$e),
                      (array)Event::trigger("core.error.$error_type",$e)
                    ));
      $chk_general  = array_filter(array_merge(
                      (array)static::trigger('any',$e),
                      (array)Event::trigger('core.error',$e)
                    ));
      if (! ($chk_specific || $chk_general) ) static::traceException($e);
      return true;
    }

    public static function traceException($e){
      switch(self::$mode){
          case self::HTML :
              echo '<pre class="app error"><code>',$e->getMessage(),'</code></pre>',PHP_EOL;
              break;
          case self::JSON :
              echo json_encode(['error' => $e->getMessage()]);
              break;
          case self::JSON_VERBOSE :
              echo json_encode(static::structuredException($e));
              break;
          case self::SILENT :
              // Don't echo anything.
              break;
          case self::SIMPLE :
          default:
              echo $e->getMessage(),PHP_EOL;
              break;
      }
      return true;
    }

    /**
     * Build a structured error payload from an exception.
     *
     * @param \Throwable $e
     * @return array
     */
    public static function structuredException($e) {
        $data = [
            'error'   => $e->getMessage(),
            'type'    => get_class($e),
            'code'    => $e->getCode(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => [],
        ];
        foreach ($e->getTrace() as $frame) {
            $entry = [];
            if (isset($frame['file']))     $entry['file']     = $frame['file'];
            if (isset($frame['line']))     $entry['line']     = $frame['line'];
            $entry['function'] = $frame['function'];
            if (isset($frame['class']))    $entry['class']    = $frame['class'];
            $data['trace'][] = $entry;
        }
        if ($e->getPrevious()) {
            $data['previous'] = static::structuredException($e->getPrevious());
        }
        return $data;
    }

    /**
     * @deprecated Use Errors::on('fatal', $listener)
     */
    public static function onFatal(callable $listener){
      Event::on('core.error.fatal',$listener);
    }

    /**
     * @deprecated Use Errors::on('warning', $listener)
     */
    public static function onWarning(callable $listener){
      Event::on('core.error.warning',$listener);
    }

    /**
     * @deprecated Use Errors::on('notice', $listener)
     */
    public static function onNotice(callable $listener){
      Event::on('core.error.notice',$listener);
    }

    /**
     * @deprecated Use Errors::on('any', $listener)
     */
    public static function onAny(callable $listener){
      Event::on('core.error',$listener);
    }

}
