<?php

abstract class Resource implements JsonSerializable {
  public $fields, $projector, $exposed;
  public static $PKEY         = 'id',
                $exposureMode = 'full';

  public function __construct($row, $projector = null){
    $this->projector = $projector ?: static::buildProjector();
    $this->fields    = (object)$row;
  }

  public function __get($n){
    if ($this->exposed === null) $this->exposed[static::$exposureMode] = $this->jsonSerialize();
    return isset($this->exposed[static::$exposureMode][$n])
            ? $this->exposed[static::$exposureMode][$n]
            : null;
  }

  public function __isset($n){
    if ($this->exposed === null) $this->exposed[static::$exposureMode] = $this->jsonSerialize();
    return isset($this->exposed[static::$exposureMode][$n]);
  }

  public function jsonSerialize(): mixed{
    return call_user_func($this->projector,$this->expose($this->fields, static::$exposureMode));
  }

  abstract public function expose($fields, $mode);

  public static function buildProjector(){
     // Build Projector
    if ($projection_fields = Filter::with([
      "api.".get_called_class().".getProjectionFields",
      "api.resource.getProjectionFields",
    ],Request::get('fields'))) {

     $projection_fields   = preg_split('~\s*,\s*~', $projection_fields);

     // Ensure primary key presence
     array_unshift($projection_fields, static::$PKEY);

     // Remove duplicate entries
     $projection_fields   = array_unique($projection_fields);

      $projector = function($element) use ($projection_fields) {
        // An unique placehodler to handle `field_get` errors.
        $unique_placeholder = '§@°§___CO('.time().')RE___§°@§';

        // Get an array field by dot.notation
        $field_get = function ($x, $path, $default=null){
          $current = (array)$x; $p = strtok($path, '.');
          while (isset($current[$p])) {
            if (!$p = strtok('.')) return $current;
            $current = (array)$current[$p];
          }
          return $default;
        };

        // Set an array field pointed by dot.notation
        $field_set = function (&$x, $path, $value){
          $current = $x; $p = strtok($path, '.');
          while ($p !== false) {
            $p = strtok('.');
            if (!isset($current[$p])) $current[$p]=[];
            $current = (array)$current[$p];
          }
          return $current;
        };

        $element = (object) $element; $obj = [];

        foreach ($projection_fields as $field_path) {
          if (isset($element->$field_path)){
            $obj[$field_path] = $element->$field_path;
          } else {
            if (($value = $field_get($element, $field_path, $unique_placeholder)) !== $unique_placeholder){
              var_dump($value, $element, $field_path, $unique_placeholder);
              $field_set($obj, $field_path, $value);
            }
          }
        }
        return (object) $obj;
      };
    } else {
      $projector = function($x){ return $x; };
    }

    return $projector;
  }
  
  public static function setExposure($exposureMode){
    return static::$exposureMode = $exposureMode;
  }

  public static function fromSQL($sql, $params=[]){
    return Collection::fromSQL(get_called_class(), $sql, $params);
  }

  public static function singleFromSQL($sql, $params=[]){
    return ($data = SQL::single($sql, $params)) ? new static($data) : false;
  }

}
