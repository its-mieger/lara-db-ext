<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 24.01.19
	 * Time: 15:42
	 */

	namespace ItsMieger\LaraDbExt\Connection;

	/**
	 * Lets adds a configuration option, which activates automatic adaption of date timezones to application timezone
	 * @package ItsMieger\LaraDbExt\Connection
	 */
	trait AdaptsTimezone
	{
		/** @noinspection PhpDocMissingThrowsInspection */

		/**
		 * Prepares the date bindings with adapted timezone
		 * @param array $bindings The bindings
		 */
		protected function prepareDateBindings(array &$bindings) {

			// check if the timezone should be adapted
			if ($this->getConfig('adapt_timezone')) {

				/** @noinspection PhpUnhandledExceptionInspection */
				$now           = new \DateTime();
				$defaultOffset = $now->getOffset();
				$defaultTz     = $now->getTimezone();

				foreach ($bindings as $key => $value) {

					// if we have a date time instance, we will replace it with a new date in application timezone
					if ($value instanceof \DateTimeInterface && $value->getOffset() != $defaultOffset) {
						/** @noinspection PhpUnhandledExceptionInspection */
						$bindings[$key] = (new \DateTime($value->format('@U')))->setTimezone($defaultTz);
					}

				}

			}
		}

		/**
		 * @inheritDoc
		 */
		public function prepareBindings(array $bindings) {

			$this->prepareDateBindings($bindings);

			return parent::prepareBindings($bindings);
		}


	}