<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 10.12.18
	 * Time: 01:12
	 */

	namespace ItsMieger\LaraDbExt\Connection;


	class MySqlConnection extends \Illuminate\Database\MySqlConnection implements Forkable
	{
		use ResolvesQuery;
		use ForksSelf;
		use AdaptsTimezone;

	}