# WebSocket

Overview:
`WebSocket` provides a messaging facade with pluggable drivers for real-time communication. Ships with a Pusher adapter that uses the existing `HTTP` class (no new dependencies).

Key behavior:
- Driver selection follows the same FCFS pattern as `Cache::using()`.
- Events are fired on send/broadcast for local listeners.
- `WebSocket::subscribe()` registers local event callbacks for channel messages.
- The Pusher adapter authenticates via HMAC-SHA256 signing per the Pusher REST API spec.

Public API:
- `WebSocket::using($driver)` — load a driver (e.g., `'pusher'`).
- `WebSocket::send($channel, $data)` — send a message to a channel.
- `WebSocket::broadcast($channel, $data)` — broadcast to all subscribers.
- `WebSocket::subscribe($channel, $callback)` — register a local channel listener.
- `WebSocket::ready()` — check if a driver is loaded.

Example:
```php
// Configure
WebSocket::using([
    'pusher' => [
        'app_id'  => '12345',
        'key'     => 'your-key',
        'secret'  => 'your-secret',
        'cluster' => 'us2',
    ],
]);

// Send a message
WebSocket::send('notifications', ['type' => 'alert', 'text' => 'New order!']);

// Broadcast
WebSocket::broadcast('chat-room', ['user' => 'Alice', 'message' => 'Hello!']);

// Local event listener
WebSocket::subscribe('orders', function($channel, $data) {
    // Handle locally
});
```

Adapter interface (`WebSocket\Adapter`):
```php
interface Adapter {
    public function send($channel, $data);
    public function broadcast($channel, $data);
    public static function valid();
}
```
