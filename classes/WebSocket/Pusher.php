<?php

/**
 * WebSocket\Pusher
 *
 * Pusher WebSocket driver using the HTTP REST API.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace WebSocket;

class Pusher implements Adapter {

    protected $options;

    /**
     * Check if this driver is available (requires curl for HTTP class).
     *
     * @return bool
     */
    public static function valid() {
        return extension_loaded('curl');
    }

    /**
     * Create a new Pusher adapter.
     *
     * @param array $options Options: app_id, key, secret, cluster
     */
    public function __construct($options = []) {
        $this->options = (object) array_merge([
            'app_id'  => \Options::get('websocket.pusher.app_id', ''),
            'key'     => \Options::get('websocket.pusher.key', ''),
            'secret'  => \Options::get('websocket.pusher.secret', ''),
            'cluster' => \Options::get('websocket.pusher.cluster', 'mt1'),
        ], $options);
    }

    /**
     * Send a message to a specific channel.
     *
     * @param string $channel
     * @param mixed $data
     * @return bool
     */
    public function send($channel, $data) {
        return $this->trigger($channel, 'message', $data);
    }

    /**
     * Broadcast a message to a channel.
     *
     * @param string $channel
     * @param mixed $data
     * @return bool
     */
    public function broadcast($channel, $data) {
        return $this->trigger($channel, 'broadcast', $data);
    }

    /**
     * Trigger an event via Pusher REST API.
     *
     * @param string $channel
     * @param string $event
     * @param mixed $data
     * @return bool
     */
    protected function trigger($channel, $event, $data) {
        $body = json_encode([
            'name'     => $event,
            'channel'  => $channel,
            'data'     => json_encode($data),
        ]);

        $path = '/apps/' . $this->options->app_id . '/events';
        $timestamp = time();

        $params = [
            'auth_key'       => $this->options->key,
            'auth_timestamp' => $timestamp,
            'auth_version'   => '1.0',
            'body_md5'       => md5($body),
        ];

        ksort($params);
        $queryString = http_build_query($params);
        $signString = "POST\n{$path}\n{$queryString}";
        $signature = hash_hmac('sha256', $signString, $this->options->secret);

        $url = "https://api-{$this->options->cluster}.pusher.com{$path}?"
             . $queryString . '&auth_signature=' . $signature;

        $result = \HTTP::post($url, $body, ['Content-Type' => 'application/json']);
        return $result !== false;
    }
}
