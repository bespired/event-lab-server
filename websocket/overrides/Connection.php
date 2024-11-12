<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Stream\DuplexResourceStream;
use React\Stream\Util;
use React\Stream\WritableResourceStream;
use React\Stream\WritableStreamInterface;

/**
 * The actual connection implementation for ConnectionInterface
 *
 * This class should only be used internally, see ConnectionInterface instead.
 *
 * @see ConnectionInterface
 * @internal
 */
class Connection extends EventEmitter implements ConnectionInterface {

	public $decor;

	public $unix = false;

	public $encryptionEnabled = true;

	public $stream;

	private $input;

	public function __construct($resource, LoopInterface $loop) {

		$this->input = new DuplexResourceStream(
			$resource,
			$loop,
			null,
			new WritableResourceStream($resource, $loop, null, null)
		);

		$this->stream = $resource;

		Util::forwardEvents($this->input, $this, array('data', 'end', 'error', 'close', 'pipe', 'drain'));

		$this->input->on('close', array($this, 'close'));
	}

	public function isReadable() {
		return $this->input->isReadable();
	}

	public function isWritable() {
		return $this->input->isWritable();
	}

	public function pause() {
		$this->input->pause();
	}

	public function resume() {
		$this->input->resume();
	}

	public function pipe(WritableStreamInterface $dest, array $options = array()) {
		return $this->input->pipe($dest, $options);
	}

	public function write($data) {
		return $this->input->write($data);
	}

	public function end($data = null) {
		$this->input->end($data);
	}

	public function close() {
		$this->input->close();
		$this->handleClose();
		$this->removeAllListeners();
	}

	public function handleClose() {
		if (!\is_resource($this->stream)) {
			return;
		}

		// Try to cleanly shut down socket and ignore any errors in case other
		// side already closed. Underlying Stream implementation will take care
		// of closing stream resource, so we otherwise keep this open here.
		@\stream_socket_shutdown($this->stream, \STREAM_SHUT_RDWR);
	}

	public function getRemoteAddress() {
		if (!\is_resource($this->stream)) {
			return null;
		}

		return $this->parseAddress(\stream_socket_get_name($this->stream, true));
	}

	public function getLocalAddress() {
		if (!\is_resource($this->stream)) {
			return null;
		}

		return $this->parseAddress(\stream_socket_get_name($this->stream, false));
	}

	private function parseAddress($address) {
		if ($address === false) {
			return null;
		}

		if ($this->unix) {
			// remove trailing colon from address for HHVM < 3.19: https://3v4l.org/5C1lo
			// note that technically ":" is a valid address, so keep this in place otherwise
			if (\substr($address, -1) === ':' && \defined('HHVM_VERSION_ID') && \HHVM_VERSION_ID  < 31900) {
				$address = (string) \substr($address, 0, -1); // @codeCoverageIgnore
			}

			// work around unknown addresses should return null value: https://3v4l.org/5C1lo and https://bugs.php.net/bug.php?id=74556
			// PHP uses "\0" string and HHVM uses empty string (colon removed above)
			if ($address === '' || $address[0] === "\x00") {
				return null; // @codeCoverageIgnore
			}

			return 'unix://' . $address;
		}

		// check if this is an IPv6 address which includes multiple colons but no square brackets
		$pos = \strrpos($address, ':');
		if ($pos !== false && \strpos($address, ':') < $pos && \substr($address, 0, 1) !== '[') {
			$address = '[' . \substr($address, 0, $pos) . ']:' . \substr($address, $pos + 1); // @codeCoverageIgnore
		}

		return ($this->encryptionEnabled ? 'tls' : 'tcp') . '://' . $address;
	}
}
