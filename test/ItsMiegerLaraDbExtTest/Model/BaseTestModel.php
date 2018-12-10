<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 15:16
	 */

	namespace ItsMiegerLaraDbExtTest\Model;


	use Illuminate\Database\Eloquent\Model;
	use ItsMieger\LaraDbExt\Model\CreatesRelatedFromAttributes;
	use ItsMieger\LaraDbExt\Model\ResolvesBuilders;

	abstract class BaseTestModel extends Model
	{
		use ResolvesBuilders;
		use CreatesRelatedFromAttributes;
	}