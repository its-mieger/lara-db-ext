<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 11:49
	 */

	namespace ItsMieger\LaraDbExt\Eloquent;


	use Illuminate\Database\Eloquent\Builder as EloquentBuilder;


	/**
	 * Class Builder
	 * @mixin  \ItsMieger\LaraDbExt\Query\Builder
	 */
	class Builder extends EloquentBuilder
	{
		use WithJoined;


		/**
		 * @inheritDoc
		 */
		public function getModels($columns = ['*']) {

			return $this->hasModelsJoined() ? $this->getModelsWithJoined($columns) : parent::getModels($columns);
		}

		/**
		 * @inheritDoc
		 */
		public function cursor() {
			yield from ($this->hasModelsJoined() ? $this->cursorWithJoined([]) : parent::cursor());
		}

		/**
		 * Get a generator for the given query.
		 *
		 * @return \Generator
		 */
		public function generate() {
			foreach ($this->applyScopes()->query->generate() as $record) {
				yield $this->model->newFromBuilder($record);
			}
		}



	}