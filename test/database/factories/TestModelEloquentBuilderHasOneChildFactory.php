<?php

	use Faker\Generator as Faker;

	/** @var \Illuminate\Database\Eloquent\Factory $factory */
	$factory->define(\ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderHasOneChild::class, function (Faker $faker) {
		return [
			'name'    => $faker->name,
			'x'       => $faker->randomNumber(6),
			'root_id' => function () {
				return factory(\ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderHasOneRoot::class)->create()->id;
			}
		];
	});