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
		protected $forkIndex = 0;

		protected $forkPdoAttributes = [];

		protected $isForked = false;

		/**
		 * @inheritDoc
		 */
		public function setPdo($pdo) {
			$ret = parent::setPdo($pdo);

			if ($this->isForked && ($pdo = $this->getPdo())) {
				/** @var \PDO $pdo */
				foreach ($this->forkPdoAttributes as $attribute => $value) {
					$pdo->setAttribute($attribute, $value);
				}

			}

			return $ret;
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
			config()->set("database.connections.$forkedConnectionName", $config);
			/** @var Connection|ForksSelf $fork */
			$fork = \DB::connection($forkedConnectionName);

			// set the passed attributes for the connection
			/** @var \PDO $pdo */
			$pdo = $fork->getPdo();
			foreach($attributes as $attribute => $value) {
				$pdo->setAttribute($attribute, $value);
			}

			$fork->isForked = true;
			$fork->forkPdoAttributes = $attributes;

			return $fork;
		}

		/**
		 * Destroys the given connection if it is a forked connection
		 */
		public function destroyFork() {

			if (!$this->isForked)
				throw new \RuntimeException('Cannot destroy this connection fork because it is not a forked connection.');

			config()->set('database.connections.' . $this->getName(), null);

			$this->disconnect();
		}

	}