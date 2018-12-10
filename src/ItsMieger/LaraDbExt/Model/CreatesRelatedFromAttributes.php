<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 15:50
	 */

	namespace ItsMieger\LaraDbExt\Model;


	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Database\Eloquent\Relations\HasOne;
	use Illuminate\Database\Eloquent\Relations\Relation;

	trait CreatesRelatedFromAttributes
	{
		/**
		 * Create a new model instance that is existing.
		 *
		 * @param  array $attributes
		 * @param  string|null $connection
		 * @return static
		 */
		public function newFromBuilder($attributes = [], $connection = null) {


			$attributes = (array)$attributes;

			// first we have to separate related data from own attributes
			$relatedData = [];
			foreach($attributes as $key => $value) {

				// attributes prefixed with '::' contain related data
				if (substr($key, 0, 2) == '::') {
					unset($attributes[$key]);
					$relatedData[substr($key, 2)] = $value;
				}
			}


			// the attributes are cleaned, so we can create the model instance
			$model = parent::newFromBuilder($attributes, $connection);


			// after we have created the model instance it is time to create the related models
			foreach($relatedData as $relationName => $relData) {

				/** first we get the defined relation */
				/** @var Relation $relation */
				$relation = null;
				try {
					$relation = $model->{$relationName}();
				}
				catch (\BadMethodCallException $ex) {
				}
				if (empty($relation) || !($relation instanceof Relation))
					throw new \RuntimeException("Related data was passed, but relation \"$relationName\" does not exist for model " . get_class($this));


				// depending on relation type, we build the related model's data
				if ($relation instanceof BelongsTo || $relation instanceof HasOne) {
					// here we handle one-to-one relations

					// create the related model instance and link it to current model
					$relatedInstances = $relation->newModelInstance()->newFromBuilder($relData, $connection);
					$model->setRelation($relationName, $relatedInstances);
				}
				else if ($relation instanceof HasMany) {
					// here we handle one-to-many relations

					// create the related model instances and link them to current model
					$relatedInstances = $relation->newModelInstance()->hydrate(array_values($relData));
					$model->setRelation($relationName, $relatedInstances);
				}
				else {
					throw new \RuntimeException("Relation data was passed, but relation \"$relationName\" of model " . get_class($this) . ' has unsupported type ' . get_class($relation));
				}
			}


			return $model;
		}
	}