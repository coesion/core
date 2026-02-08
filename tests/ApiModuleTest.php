<?php

use PHPUnit\Framework\TestCase;

if (!class_exists('CategoryResource')) {
  class CategoryResource extends Resource {
    public function expose($fields, $mode) {
      return [
        'id' => $fields->id,
        'name' => $fields->name,
      ];
    }
  }
}

if (!class_exists('ArticleResource')) {
  class ArticleResource extends Resource {
    public function expose($fields, $mode) {
      return [
        'id' => $fields->id,
        'title' => $fields->title,
        'meta' => [
          'author' => $fields->author,
        ],
      ];
    }
  }
}

if (!class_exists('ApiModuleTest', false)) {
class ApiModuleTest extends TestCase {

  protected function setUp(): void {
    parent::setUp();
    Options::set('core.response.autosend', false);
    Options::set('core.route.auto_optimize', false);
    Response::clean();
    Route::reset();
    $_GET = [];
  }

  protected function tearDown(): void {
    Filter::remove('core.request.method');
    Filter::remove('core.request.URI');
    Filter::remove('api.CategoryResource.getProjectionFields');
    Filter::remove('api.CategoryResource.page');
    Filter::remove('api.CategoryResource.limit');
    Filter::remove('api.ArticleResource.getProjectionFields');
    Filter::remove('api.resource.getProjectionFields');
    parent::tearDown();
  }

  private function mock_request($uri, $method) {
    Filter::remove('core.request.method');
    Filter::remove('core.request.URI');
    Filter::add('core.request.URI', function ($x) use ($uri) { return $uri; });
    Filter::add('core.request.method', function ($x) use ($method) { return $method; });
  }

  private function setup_categories_table() {
    SQL::close();
    SQL::connect('sqlite::memory:');
    SQL::exec('DROP TABLE IF EXISTS categories');
    SQL::exec('CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT, author TEXT)');
    SQL::exec('INSERT INTO categories (id, name, author) VALUES (1, "Lifestyle", "a")');
    SQL::exec('INSERT INTO categories (id, name, author) VALUES (2, "News", "b")');
  }

  public function testCollectionWrapCreatesResources() {
    $data = [
      (object)['id' => 1, 'name' => 'One'],
      (object)['id' => 2, 'name' => 'Two'],
    ];
    $wrapped = Collection::wrap(CategoryResource::class, $data, 1, 10, 2);

    $this->assertCount(2, $wrapped['data']);
    $this->assertInstanceOf(CategoryResource::class, $wrapped['data'][0]);
    $this->assertEquals(1, $wrapped['pagination']['page']);
    $this->assertEquals(10, $wrapped['pagination']['limit']);
    $this->assertEquals(2, $wrapped['pagination']['count']);
  }

  public function testCollectionFromSqlPagination() {
    $this->setup_categories_table();
    Filter::add('api.CategoryResource.page', function () { return 1; });
    Filter::add('api.CategoryResource.limit', function () { return 1; });

    $collection = CategoryResource::fromSQL('SELECT * FROM categories');
    $this->assertCount(1, $collection['data']);
    $this->assertEquals(1, $collection['pagination']['limit']);
    $this->assertEquals(2, $collection['pagination']['count']);
  }

  public function testResourceProjectionAndExposure() {
    Filter::add('api.ArticleResource.getProjectionFields', function () {
      return 'title';
    });
    $resource = new ArticleResource((object)[
      'id' => 10,
      'title' => 'Hello',
      'author' => 'Alice',
    ]);

    $payload = $resource->jsonSerialize();
    $this->assertEquals('Hello', $payload->title);
    $this->assertEquals(10, $payload->id);
    $this->assertObjectNotHasProperty('meta', $payload);
  }

  public function testApiResourceListAndProjection() {
    $this->setup_categories_table();
    $this->mock_request('/categories', 'get');
    $_GET['page'] = 1;
    $_GET['limit'] = 2;

    API::resource('/categories', [
      'class' => CategoryResource::class,
      'sql' => [
        'table' => 'categories',
        'primary_key' => 'id',
      ],
    ]);

    Route::dispatch('/categories', 'get');
    $body = json_decode(Response::body(), true);
    $this->assertCount(2, $body['data']);
    $this->assertEquals(2, $body['pagination']['count']);

    Response::clean();
    Route::dispatch('/categories/1', 'get');
    $body = json_decode(Response::body(), true);
    $this->assertEquals('Lifestyle', $body['data']['name']);
    $this->assertEquals(1, $body['data']['id']);
  }

  public function testRestExposeMapsMethods() {
    $this->mock_request('/bucket', 'get');
    REST::expose('bucket', [
      'list' => function () { return 'LIST'; },
      'read' => function ($id) { return "READ:$id"; },
    ]);
    Route::dispatch('/bucket', 'get');
    $this->assertEquals('LIST', Response::body());

    Route::reset();
    Response::clean();
    $this->mock_request('/bucket/7', 'get');
    REST::expose('bucket', [
      'list' => function () { return 'LIST'; },
      'read' => function ($id) { return "READ:$id"; },
    ]);
    Route::dispatch('/bucket/7', 'get');
    $this->assertEquals('READ:7', Response::body());
  }
}
}
