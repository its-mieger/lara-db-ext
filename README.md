# TODO:  Readme must be completed

### Custom connections


### selectPrefixed

Sometimes you want certain column names to be prepended with a given prefix. Following
example prefixes all returned column names with the `"user_"` prefix:

	DB:table('users')->selectPrefixed(['id', 'name'], 'user_')->get();
	
	// => [['user_id' => 1, 'user_name' => 'John']]
	
This becomes really useful when you're using joins on tables with conflicting column names:

	DB:table('users')
		->join('children', 'users.id', '=', 'children.user_id')
		->addSelectPrefixed('users.*', 'user_')
		->addSelectPrefixed('children.*', 'child_')
		->get();
	
	// => [[ 'user_id' => 1, 'user_name' => 'John', 'child_id' => 12, 'child_name' => 'Maria']]
	
Without prefixing, the `children` fields would silently overwrite the `users` fields.
As you see it also works using wildcard column selectors. And the best: it does
not produce an extra query to obtain the table structure. In fact it does not depend on
table columns at all:
 
It can also be used with expressions:

	DB:table('users')->selectPrefixed(new Expression('count(*) as myCount'), 'user_')->get();
	
	// => [['user_myCount' => 123]]
	
	
You can also set multiple prefixes in one call if you omit the prefix parameter and pass an
associative array as first parameter

	DB:table('users')
		->join('children', 'users.id', '=', 'children.user_id')
		->selectPrefixed([
			'user_'  => 'users.*',
			'child_' => ['id', 'name'],
		])
		->get();
	
	
### whereNotNested

If you want to negate a nested where clause, the new `whereNotNested` function comes in:

	DB:table('users')
		->whereNotNested(function($query) {
			$query->where('name', 'John');
			$query->where('age', '>', 49);
		})
		->get();
    		
This would produce following query:

	SELECT * FROM users WHERE NOT (first_name = 'John' AND age > 49) 
	
	
### whereMultiIn

Some SQL dialects allow to compare multiple columns using the `IN` operator. You may use
it using the `whereMultiIn` function:

	DB:table('users')
		->whereMultiIn(['name', 'age'], [
			['John', 38],
			['Ida', 49],
		])
		->get();
		
This would produce following query:

	SELECT * FROM users WHERE (name, age) IN ( ('John', 38), ('Ida', 49) )
	
You may even pass a sub select instead of a values array:

	DB:table('users')
		->whereMultiIn(['name', 'age'], function ($query) {
			return $query->select(['parent_name', 'parent_age'])
				->from('children')
				->where('age', '<', 3);
		})
		->get();


### whereMultiColumns

The `whereMultiColumns` accepts multiple columns to be compared:

	DB:table('users')
		->whereMultiColumns(['name', 'age'], ['n', 'a'])
		->get();

This would produce following query:

	SELECT * FROM users WHERE (name = n AND age = a)
	
Operators are applied to the combination of columns. That's why only `=`, `!=`, `<>` are
supported.

	DB:table('users')
		->whereMultiColumns(['name', 'age'], '!=', ['n', 'a'])
		->get();
	
This would produce following query:

	SELECT * FROM users WHERE NOT (name = n AND age = a)
	
	
### Automatic where detection

Another improvement is that the `where` functions now can be used with an array as
values parameter, so it get's automatically converted to `whereIn`. Of course this
also works with multiple columns:

	DB:table('users')
		->where('name', ['John', 'Ida'])
		->get();
		
	DB:table('users')
		->where(['name', 'age'], [
			['John', 38],
			['Ida', 49],
		])
		->get();
		
This works also for the `whereColumn` functions:

	DB:table('users')
		->whereColumn(['name', 'age'], ['n', 'a'])
		->get();
		
		
## Timezone handling

Time zone handling in database can be complicated. Laravel passes dates without timezone
information to the database. This behavior is correct as the data is usually stored without
any timezone information. When reading dates, Laravel (Eloquent) interprets dates using the
default application timezone.

But Laravel does not ensure that DateTime parameters are using the application timezone when
passing them to the database. So if you pass a date with a different timezone to database, it
will be interpreted using another timezone on reading.

To ensure all DateTime parameters are converted to application timezone before sending them to
database, this package adds the "adapt_timezone" configuration option for database connections.
If set to true, any DateTime values will be converted to the application timezone before passing
them to the database.

The `AdaptsAttributeTimezone` trait implements the timezone adaption
for Eloquent model attributes.

### Database session timezone
Some databases, such as MySQL use the database session timezone when converting dates to
timestamps (see [MySQL Documentation for details](https://dev.mysql.com/doc/refman/8.0/en/datetime.html)).
For MySQL this does only affect storing dates to TIMESTAMP columns (not for DATETIME columns) and
NOW() and CURTIME() functions. Therefore you should always configure the "timezone" parameter
for connections to the same value as the application timezone!