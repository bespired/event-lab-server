<?php
namespace MyApp;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat implements MessageComponentInterface {
	protected $clients;
	public $parent;

	public function __construct() {
		$this->clients = new \SplObjectStorage;
	}

	public function onOpen(ConnectionInterface $conn) {
		// Store the new connection to send messages to later
		$this->clients->attach($conn);

		echo "New connection! ({$conn->resourceId})\n";
	}

	public function onMessage(ConnectionInterface $from, $msg) {

		$json = @json_decode($msg);

		// only accept json messages.
		if (!$json) {
			return;
		}

		// for now send everyone the message
		foreach ($this->clients as $client) {
			$client->send($msg);
		}

	}

	public function onClose(ConnectionInterface $conn) {

		// The connection is closed, remove it, as we can no longer send it messages
		$this->clients->detach($conn);

		// if (isset($this->inRoom[$conn->resourceId])) {
		// 	unset($this->inRoom[$conn->resourceId]);
		// }

		echo "Connection {$conn->resourceId} has disconnected\n";
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		echo "An error has occurred: {$e->getMessage()}\n";

		// if (isset($this->inRoom[$conn->resourceId])) {
		// 	// tell everyone resourceId is leaving the room.
		// 	unset($this->inRoom[$conn->resourceId]);
		// }

		$conn->close();
	}
}