<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 14.12.18
	 * Time: 09:26
	 */

	namespace ItsMieger\LaraDbExt\Connection;


	use Illuminate\Database\Connection;

	trait ForksSelf
	{
		protected $pdoProtected = false;

		protected $forkIndex = 0;

		protected $forkPdoAttributes = [];

		protected $isForked = false;

		protected $isForkDestroyed = false;

		/**
		 * @inheritDoc
		 */
		public function setPdo($pdo) {

			// ignore set-operation, if PDO is protected
			if ($this->pdoProtected)
				return $this;

			parent::setPdo($pdo);

			$this->applyForkedPdoAttributes($this->getPdo());

			return $this;
		}

		/**
		 * Set the PDO connection used for reading.
		 *
		 * @param \PDO|\Closure|null $pdo
		 * @return $this
		 */
		public function setReadPdo($pdo) {

			// ignore set-operation, if PDO is protected
			if ($this->pdoProtected)
				return $this;

			parent::setReadPdo($pdo);

			$this->applyForkedPdoAttributes($this->getReadPdo());

			return $this;
		}

		/**
		 * Reconnect to the database.
		 *
		 * @return void
		 *
		 * @throws \LogicException
		 */
		public function reconnect() {

			if ($this->isForked && !$this->isForkDestroyed) {

				// we need to publish our connection config again
				return $this->withConnectionConfig($this->getName(), $this->getConfig(), function () {

					// create dummy connection and reconnect it
					/** @var Connection $dummy */
					$dummy = \DB::connection($this->getName());
					$dummy->reconnect();

					// copy PDO from reconnected dummy
					$this->setPdo($dummy->getPdo());
					$this->setReadPdo($dummy->getReadPdo());

					// delete the dummy again
					\DB::purge($dummy->getName());
				});
			}
			else {
				return parent::reconnect();
			}
		}



		/**
		 * Creates a new connection with same configuration as the current connection
		 * @param array $options Allows to override connection options
		 * @param array $attributes Allows to set PDO attributes
		 * @return Connection The new connection
		 */
		public function fork(array $options = [], array $attributes = []) {

			$name = $this->getName();

			// get original config and merge passed config
			$config = config("database.connections.$name");
			$config = array_merge($config, $options);

			// set the temporary configuration and create connection
			$forkedConnectionName = "$name-fork-" . ($this->forkIndex++);
			$fork = $this->withConnectionConfig($forkedConnectionName, $config, function() use ($forkedConnectionName) {
				/** @var Connection|ForksSelf $fork */
				return \DB::connection($forkedConnectionName);
			});


			$fork->withPdoProtected(function() use ($forkedConnectionName) {
				\DB::purge($forkedConnectionName);
			});

			$fork->isForked          = true;
			$fork->forkPdoAttributes = $attributes;

			// set the passed attributes for the connection
			$fork->applyForkedPdoAttributes($fork->getPdo());
			$fork->applyForkedPdoAttributes($fork->getReadPdo());

			return $fork;
		}

		/**
		 * Destroys the given connection if it is a forked connection
		 */
		public function destroyFork() {

			if (!$this->isForked)
				throw new \RuntimeException('Cannot destroy this connection fork because it is not a forked connection.');

			$this->disconnect();

			$this->isForkDestroyed = true;
		}

		/**
		 * Prevents the PDO connections from current instance to be modified while invoking callback
		 * @param callable $callback The callback
		 * @return mixed
		 */
		protected function withPdoProtected(callable $callback) {

			$this->pdoProtected = true;
			try {
				return call_user_func($callback);
			}
			finally {
				$this->pdoProtected = false;
			}

		}

		/**
		 * Configures given database connection only during callback execution
		 * @param string $name
		 * @param $config
		 * @param callable $callback
		 * @return mixed
		 */
		protected function withConnectionConfig($name, $config, callable $callback) {
			config()->set("database.connections.$name", $config);
			try {
				return call_user_func($callback);
			}
			finally {
				config()->set("database.connections.$name", null);
			}
		}

		/**
		 * Applies the forked PDO attributes to given PDO connection if this connection is forked
		 * @param \PDO|null $pdo The PDO connection
		 */
		protected function applyForkedPdoAttributes($pdo) {
			if ($pdo && $this->isForked) {
				foreach ($this->forkPdoAttributes as $attribute => $value) {
					$pdo->setAttribute($attribute, $value);
				}
			}
		}

	}