<?php


	namespace ItsMieger\LaraDbExt\Model\Operations;


	use MehrIt\Buffer\FlushingBuffer;
	use RuntimeException;

	class PreparedBulkImport
	{

		/**
		 * @var FlushingBuffer
		 */
		protected $buffer;

		/**
		 * @var callable
		 */
		protected $callback;

		/**
		 * PreparedBulkImport constructor.
		 * @param FlushingBuffer $buffer The buffer
		 * @param callable $callback The callback
		 */
		public function __construct(FlushingBuffer $buffer, callable $callback) {
			$this->buffer   = $buffer;
			$this->callback = $callback;
		}

		/**
		 * Gets the buffer to fill
		 * @return FlushingBuffer The buffer to fill
		 */
		public function getBuffer(): FlushingBuffer {
			return $this->buffer;
		}

		/**
		 * Finishes the bulk import
		 */
		public function flush() {
			if (!$this->callback)
				throw new RuntimeException('Prepared bulk import already flushed.');

			call_user_func($this->callback);
			$this->callback = null;
		}

	}