<?php

/**
 * WebSocket\Adapter
 *
 * WebSocket drivers common interface.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace WebSocket;

interface Adapter {

    /**
     * Send a message to a specific channel.
     *
     * @param string $channel
     * @param mixed $data
     * @return bool
     */
    public function send($channel, $data);

    /**
     * Broadcast a message to a channel (all subscribers).
     *
     * @param string $channel
     * @param mixed $data
     * @return bool
     */
    public function broadcast($channel, $data);

    /**
     * Check if this driver is available.
     *
     * @return bool
     */
    public static function valid();
}
