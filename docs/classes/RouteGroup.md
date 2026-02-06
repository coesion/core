# RouteGroup

Overview:
RouteGroup is a helper for grouping routes, letting you apply `before`, `after`, and `push` to multiple routes at once.

Public API:
- `new RouteGroup()` creates and registers the group.
- `add($route)` attaches a route to the group.
- `remove($route)` detaches a route.
- `before($callbacks)` applies before middleware to all routes.
- `after($callbacks)` applies after middleware to all routes.
- `push($links, $type = 'text')` applies HTTP/2 push to all routes.


