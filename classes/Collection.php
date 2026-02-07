<?php

class Collection {

  public static function fromSQL($resource, $sql, $params=[]){
    $count = SQL::value(preg_replace(
      [
        '(SELECT(.+)FROM)i',
        '(LIMIT\s+\d+\s+)i',
        '(OFFSET\s+\d+\s+)i'
      ],
      [
        'SELECT COUNT(1) FROM',
        '',
        '',
      ],
    // Lo spazio appeso permette regex più semplici
    $sql.' '), $params, 0);

    $page       = Filter::with(["api.$resource.page", "api.page"], max(1,Request::get('page',1)));
    $limit      = Filter::with(["api.$resource.limit", "api.limit"], max(1,Request::get('limit',10)));
    $page       = min($page, ceil($count/$limit));
    $offset     = max(0, $page-1);

    $sql        = "$sql LIMIT $limit OFFSET $offset";

    return static::wrap(
      $resource,
      SQL::each($sql, $params),
      $page, $limit, $count
    );
    
  }

  public static function wrap($resource, $data, $page, $limit, $count){

    if (!class_exists($resource)) throw new Exception("[API] Resource class $resource doesn't exists", 1);
    $projector = $resource::buildProjector();

    return [
      "data" => array_map(function($e) use ($resource, $projector){
        return new $resource($e, $projector);
      }, $data),
      "pagination" => [
        "page"  => $page,
        "limit" => $limit,
        "count" => $count,
      ]
    ];
  }

}