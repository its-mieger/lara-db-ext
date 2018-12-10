<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 15:20
	 */

	use Faker\Generator as Faker;

	/** @var \Illuminate\Database\Eloquent\Factory $factory */
	$factory->define(\ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderBelongsBelongs::class, function (Faker $faker) {
		return [
			'belongs_table_id' => function () {
				return factory(\ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderBelongs::class)->create()->id;
			},
			'b_name'        => $faker->name,
			'b_x'           => $faker->randomNumber(6)
		];
	});