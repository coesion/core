<?php

class API {

  public static function resource($path, array $options){
    $options = array_replace_recursive([
      "class"    => null,
      "list_mode" => "list",
      "sql"      => [
        "table"       => null,
        "raw"         => null,
        "primary_key" => "id",
      ],
    ], $options);

    $path      = rtrim($path,"/") ?: "/";
    $resource  = $options["class"];
    $table     = $options["sql"]["table"];
    $raw       = $options["sql"]["raw"];
    $pkey      = $options["sql"]["primary_key"];

    $sql_list   = $raw ?: "SELECT * FROM $table";
    $sql_single = strpos($sql_list, "WHERE")===false 
                ? "$sql_list WHERE $pkey=:$pkey" 
                : str_replace("WHERE ","WHERE $pkey=:$pkey AND ",$sql_list);
    $sql_single .= " LIMIT 1";

    // Ensure endpoint options sanity
    if (class_exists($resource, false) && ($table || $raw) && $pkey ) {

      // List
      Route::on($path, function() use ($resource, $table, $sql_list, $options) {
        $resource::setExposure($options["list_mode"]);
        return $resource::fromSQL($sql_list);
      });

      // Single
      Route::on("$path/:id", function($id) use ($resource, $table, $pkey, $sql_single) {
        return ['data' => ($resource::singleFromSQL($sql_single,["$pkey"=>$id])
               ?: API::error("Not found",404))];
      });

      // Projection short-hand
      Route::on("$path/:id/:parameter", function($id, $parameter) use ($resource, $table, $pkey, $sql_single) {
        Filter::add("api.$resource.getProjectionFields", function($t) use ($parameter){
          return $parameter;
        });
        return ['data' => ($resource::singleFromSQL($sql_single,["$pkey"=>$id])
               ?: API::error("Not found",404))];
      });

    }
  }

  public static function error($message, $status=501){
    Event::trigger('api.error',[$message,$status]);
    Response::status($status);
    Response::json([
      'error'   => [
        'type'     => 'fatal',
        'status'   => $status,
        'message'  => $message,
      ],
    ]);
    Response::send();
    exit;
  }

}