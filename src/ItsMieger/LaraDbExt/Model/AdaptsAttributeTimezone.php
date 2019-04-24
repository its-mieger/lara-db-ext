<?php


	namespace ItsMieger\LaraDbExt\Model;


	use ItsMieger\LaraDbExt\Connection\AdaptsTimezone;

	trait AdaptsAttributeTimezone
	{
		protected $adaptAttributeTimezone = null;

		/**
		 * Convert a DateTime to a storable string.
		 *
		 * @param \DateTime|int|\Carbon\ $value
		 * @return string
		 */
		public function fromDateTime($value) {

			if (empty($value))
				return $value;

			$value = $this->asDateTime($value);

			// adapt attribute timezone to default timezone
			if ($this->adaptAttributeTimezone())
				$value = $value->setTimezone(date_default_timezone_get());

			return $value->format($this->getDateFormat());
		}

		/**
		 * Returns whether the timezone should be adapted or not
		 * @return bool True it to adapt. Else false.
		 */
		protected function adaptAttributeTimezone() {
			if ($this->adaptAttributeTimezone === null) {
				$connection = $this->getConnection();

				// Check whether to adapt the timezone. We check config option and trait implementing the behaviour,
				// to be consistent with the behaviour of raw connections, where config option without trait has no
				// effect as well
				$this->adaptAttributeTimezone = $connection->getConfig('adapt_timezone', false) &&
				                                in_array(AdaptsTimezone::class, class_uses_recursive($connection));
			}

			return $this->adaptAttributeTimezone;
		}

	}