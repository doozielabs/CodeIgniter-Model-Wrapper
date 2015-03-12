# CodeIgniter Model Wrapper
CI_Model wrapper to simplify database actions by predefined / easy to use functions

1. ## Setup
	1. Download CodeIgniter
	2. Place `MY_Model.php` in `application/core` folder

2. ## Creating Models
	1. Create a model `class` in `application/models` folder
	2. Extend your model from `MY_Model` instead of `CI_Model`
	3. Add following table bindings in you model class
		* `const table = '<TABLE_NAME>';`
		* `const pk  = '<COLUMN_NAME>';`
		* `const ai  = '<COLUMN_NAME>';`
		* `const ref = '<COLUMN_NAME>:<REF_MODEL_CLASS>.<REF_MODEL_COLUMN_NAME>[, ...]';`
		* E.g:
		```php
		class User extends MY_Model {
			/* Table Binding */
			const table = 'users';
			const pk    = 'id';
			const ai    = 'id';
			const ref   = 'id:History.user_id';
		}
		```
	4. Add columns of your table as `property` of your model `class`
		E.g:
		```php
		class User extends MY_Model {
			/* Table Binding */
			const table = 'users';
			const pk    = 'id';
			const ai    = 'id';
			const ref   = 'id:History.user_id';
			
			/* Column Binding */
			var $id;
			var $username;
			var $email;
			var $password;
		}
		```
	5. If you want to add `__construct` in your model, its first line should be `parent::__construct()`
		E.g:
		```php
		class User extends MY_Model {
			/* Table Binding */
			const table = 'users';
			const pk    = 'id';
			const ai    = 'id';
			const ref   = 'id:History.user_id';
			
			/* Column Binding */
			var $id;
			var $username;
			var $email;
			var $password;
			var $picture;
			
			/* Public Constructor */
			public function __construct ( ) {
				parent::__construct();
				
				$this->picture = 'some default value'; 
			}
		}
		```

3. ## Function Details
In this section, a brief description of each MY_Model `function` is provided.

	1. ### Function `select()`
	This is a **_Query Builder_** function. And it is used to specify columns to be selected in search query. If this function is not used to specify columns, search query will return '*' (all) columns

		1. #### Description
			```php
			object select( string $column[, string ... ] )
			```

			`select()` specifies columns for results of search query

		2. #### Parameters
			##### `$column`
			- Name of column to be selected

			##### `$...`
			- Can provide unlimited column names as separate arguments

		3. #### Return Values
			Returns self object to support continious operations.

		4. #### Examples
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('user', null, true);
					$this->user->select('username', 'email');
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT username, email FROM users;
			```

	2. ### Function `from()`
	This is a **_Query Builder_** function. And it is used to specify table to be searched by search query. If this function is not used to specify table(s), search query will use `const table` as searching table. And in case `const table` is not defined, Search query will assume Model `class` name as the table name.

		1. #### Description
			```php
			object from( string $table )
			```

			`from()` specifies table for searching. It can also be used for `join`(s)

		2. #### Parameters
			##### `$table`
			- Name of table(s) to be selected

		4. #### Return Values
		   Returns self object to support continious operations.

		4. #### Examples
			##### Single Table
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('user', null, true);
					$this->user
						->select('order_id', 'user_id')
						->from('orders');
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT order_id, user_id FROM orders;
			```

			##### Multiple Tables
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('user', null, true);
					$this->user
						->from('users, orders');

				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users, orders;
			```

			##### Join
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('user', null, true);
					$this->user
						->select('username', 'email')
						->from('users INNER JOIN orders ON users.id = orders.user_id');

				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT username, email FROM users INNER JOIN orders ON users.id = orders.user_id;
			```

	3. ### Function `where()`
	This is a **_Query Builder_** function. And it is used to filter resulting rows.

		1. #### Description
			```php
			object where( string $query[, mixed ...] )
			```

			`where()` specifies where clause in search query. This function automatically escapes the provided values, to prevent database hacks.

		2. #### Parameters
			##### `$query`
			- Query filter
			- If you wish to put values in your filter, and you want them to be database hack proof, just add a `?` instead of that value and provide that value in 2nd argumet.
				E.g. `username = ? and password = ?`

			##### `$...`
			- Values agains `?` in filter template. Can provide multiple values as separate parameters.
			- In case or array, `where()` function treats each index of that array as a separate argument.
			- Array values are useful in case of dynamically generate matching criteria

		3. #### Return Values
			Returns self object to support continious operations.

		4. #### Examples
			##### Simple (without values)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('user', null, true);
					$this->user
						->select('email')
						->where('id = 1');
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT email FROM users WHERE id = 1;
			```

			##### Normal (with values)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('user', null, true);
					$this->user
						->select('email')
						->where('username = ? and password = ?', $username, $password);
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT email FROM users WHERE username = 'thomas' and password = 'abc1234';
			```

			##### Extreme (with mix values)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$array_values = array('thomas', 'abc1234');

					$this->load->model('user', null, true);
					$this->user
						->select('email')
						->where('username = ? and password = ? and name like ?', $array_values, '%Methew%');
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT email FROM users WHERE username = 'thomas' and password = 'abc1234' and name like '%Methew%';
			```

	4. ### Function `make_wherein()`
	This `function` is used to generate template for where in claues.

		1. #### Description
			```php
			string make_wherein( string $column, array $values )
			```

			Generates a template, which you need to pass in `where()` function

		2. #### Parameters
			##### `$column`
			- Name of column to match values in

			##### `$values`
			- Array of possible values to match provided column

		3. #### Return Values
			Returns generated *where in* template string

		4. #### Examples
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$values = array('thomas', 'james', 'clark', 'hanks');
					$this->load->model('user', null, true);
					$this->user
						->select('email')
						->where( $this->user->make_wherein('username', $values), $values);
				}

			}
			```
			Result of `make_wherein()` will be `username IN (?, ?, ?, ?)`

			Resulting MySQL query:
			```sql
			SELECT email FROM users WHERE username IN ('thomas', 'james', 'clark', 'hanks');
			```

	5. ### Function `group()`
	This is a **_Query Builder_** function. And it is used to specify grouping column(s) for search query.

		1. #### Description
			```php
			string group( string $column[, string ...] )
			```

			Generates a template, which you need to pass in `where()` function

		2. #### Parameters
			##### `$column`
			- Name of column to group results

			##### `$...`
			- Can provide multiple columns as separate parameters

		3. #### Return Values
			Returns self object to support continious operations

		4. #### Examples
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$values = array('thomas', 'james', 'clark', 'hanks');
					$this->load->model('user', null, true);
					$this->user
						->select('email')
						->group( 'country asc', 'status desc' );
				}

			}
			```
			Resulting query
			```sql
			SELECT email FROM users GROUP BY country asc, status desc;
			```

	6. ### Function `having()`
	This is a **_Query Builder_** function. And it is used to specify the condition according to which grouped data is filtered

		1. #### Description
			```php
			object having( string $query[, mixed ...] )
			```

			- `having()` specifies having clause in search query. This function automatically escapes the provided values, to prevent database hacks.
			- `having()` function works similar to `where()` function. The only difference is that it allows to filter using MySQL group functions

		2. #### Parameters
			##### `$query`
			- Query filter
			- If you wish to put values in your filter, and you want them to be database hack proof, just add a `?` instead of that value and provide that value in 2nd argumet.
				E.g. `"MAX(order_count) > ?"`

			##### `$...`
			- Values agains `?` in filter template. Can provide multiple values as separate parameters.
			- In case or array, `having()` function treats each index of that array as a separate argument.
			- Array values are useful in case of dynamically generate matching criteria

		3. #### Return Values
			Returns self object to support continious operations.

		4. #### Examples
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('order', null, true);
					$this->user
						->select('user_id')
						->group( 'user_id asc' )
						->having( 'COUNT(user_id) > ?', 30 );
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT user_id FROM orders GROUP BY user_id asc HAVING COUNT(user_id) > 30;
			```

	7. ### Function `order()`
	This is a **_Query Builder_** function. And it is used to specify sorting order of resulting rows.

		1. #### Description
			```php
			object order( string $column[, string ...] )
			```

			Resulting rows will be sorted according to the columns specified in this function

		2. #### Parameters
			##### `$column`
			- Column name with sorting order

			##### `$...`
			- Can provide multiple columns as separate parameters

		3. #### Return Values
			Returns self object to support continious operations.

		4. #### Examples
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);
					$this->user
						->select('*')
						->order('name asc', 'status desc');
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users ORDER BY name asc, status desc;
			```

	8. ### Function `limit()`
	This is a **_Query Builder_** function. And it is used to specify number of rows to be selected. Could be useful in for paginating results.

		1. #### Description
			```php
			object limit( int $skip[, int $numrows] )
			```

			Limits the number of rows to be selected

		2. #### Parameters
			##### `$skip`
			- Number of rows to skip from resulting rows.
			- If 2nd Argument is not provided, `$skip` will become `$numrows`

			##### `$numrows`
			- Number of rows to be selected

		3. #### Return Values
			Returns self object to support continious operations.

		4. #### Examples
			##### With Single Argument
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);
					$this->user
						->select('*')
						->limit(10);
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users LIMIT 10;
			```

			##### With All Argument
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);
					$this->user
						->select('*')
						->limit(10, 2);
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users LIMIT 10, 2;
			```

	9. ### Function `find()`
	This is a **_Query Executer_** `function`, and is used to execute the search query.

		1. #### Description
			```php
			array find( [boolean $load_refs[, boolean $same_obj]] )
			```

			Executes the query built from above mentioned **_Query Builder_** functions

		2. #### Parameters
			##### `$load_refs`
			- If set to `true`, `find()` function will load references of resulting rows.
			- See function `load_refs()` for details

			##### `$same_obj`
			- If set to `true`, resulting rows will always be `object` of same `class` as your model.
			- If set to `false` and you have sepecified any other table to select data from, resulting rows will be unknown object.
			- If set to `false` and no other table is specified for query, result will be same as in case of `true`.

		3. #### Return Values
			Returns `array` of resulting row `object`s

		4. #### Examples
			##### Simple (without arguments)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);
					$results = $this->user->find();

					print_r( $results );
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users;
			```

			Output will be like:
			```
			Array (
				[0]	=> User Object
					(
						[id] => 123
						[name] => Tom Hanks
						[email] => tom.hanks@doozielabs.com
						[username] => hanks
						[password] => abc1234
					)
			)
			```

			##### With References (`$load_refs = true`)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);

					// Assuming that User model have defined `const ref = 'id:History.user_id';`
					$results = $this->user->find(true);

					print_r( $results );
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users;
			```

			Output will be like:
			```
			Array (
				[0]	=> User Object
					(
						[id] => 123
						[name] => Methew Thomas
						[email] => methew.thomas@doozielabs.com
						[username] => methomas
						[password] => abc1234
						[ref] => Array
								(
									[id:History] => Array
												(
													[0]	=> History Object
														(
															[id] => 151
															[user_id] => 123
															[action_time] => 2015-03-01 10:55:01
															[action] => Logged in
															[ip] => 182.178.65.119
														)
													[1]	=> History Object
														(
															...
														)
													...
												)
								)
					)
				[1] => User Object
					(
						...
					)
				...
			)
			```

	10. ### Function `find_one()`
	This is a **_Query Executer_** `function`, and is similar to `find()`. The only difference is that it returns single row instead of `array`

		1. #### Description
			```php
			mixed find_one( [boolean $load_refs[, boolean $same_obj]] )
			```

			Executes the query built from above mentioned **_Query Builder_** functions. And returns a single resulting row.
			It works similar to `$model->limit(1)->find()`, but instead of returning single indexed array, it returns `[0]` index of result (if exists)

		2. #### Parameters
			##### `$load_refs`
			- See function `find()` for details

			##### `$same_obj`
			- See function `find()` for details

		3. #### Return Values
			In case of results, returns 1st `object` of resulting rows. Otherwise returns `false`

		4. #### Examples
			##### Simple (without arguments)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);
					$results = $this->user->find_one();

					print_r( $results );
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users;
			```

			Output will be like:
			```
			User Object
			(
				[id] => 123
				[name] => Tom Hanks
				[email] => tom.hanks@doozielabs.com
				[username] => hanks
				[password] => abc1234
			)
			```

			##### With References (`$load_refs = true`)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);

					// Assuming that User model have defined `const ref = 'id:History.user_id';`
					$results = $this->user->find_one(true);

					print_r( $results );
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users;
			```

			Output will be like:
			```
			User Object
			(
				[id] => 123
				[name] => Tom Hanks
				[email] => tom.hanks@doozielabs.com
				[username] => hanks
				[password] => abc1234
				[ref] => Array
						(
							[id:History] => Array
										(
											[0]	=> History Object
												(
													[id] => 151
													[user_id] => 123
													[action_time] => 2015-03-01 10:55:01
													[action] => Logged in
													[ip] => 182.178.65.119
												)
											[1]	=> History Object
												(
													...
												)
											...
										)
						)
			)
			```

	11. ### Function `load()`
	This is a **_Query Executer_** `function`, and is similar to `find_one()`. The only difference is that it instead of returning result, it loads that in itself.

		1. #### Description
			```php
			boolean load( [boolean $load_refs[, boolean $same_obj]] )
			```

			Executes the query built from above mentioned **_Query Builder_** functions.
			It works similar to `find_one()`, but instead of returning `[0]` index of result, it loads that in itself

		2. #### Parameters
			##### `$load_refs`
			- See function `find()` for details

			##### `$same_obj`
			- See function `find()` for details

		3. #### Return Values
			Returns `true` in case of successful loading. Otherwise returns `false`

		4. #### Examples
			##### Simple (without arguments)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);
					$this->user->load();

					print_r( $this->user );
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users;
			```

			Output will be like:
			```
			User Object
			(
				[id] => 123
				[name] => Tom Hanks
				[email] => tom.hanks@doozielabs.com
				[username] => hanks
				[password] => abc1234
			)
			```

			##### With References (`$load_refs = true`)
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);

					// Assuming that User model have defined `const ref = 'id:History.user_id';`
					$this->user->find_one(true);

					print_r( $this->user );
				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users;
			```

			Output will be like:
			```
			User Object
			(
				[id] => 123
				[name] => Tom Hanks
				[email] => tom.hanks@doozielabs.com
				[username] => hanks
				[password] => abc1234
				[ref] => Array
						(
							[id:History] => Array
										(
											[0]	=> History Object
												(
													[id] => 151
													[user_id] => 123
													[action_time] => 2015-03-01 10:55:01
													[action] => Logged in
													[ip] => 182.178.65.119
												)
											[1]	=> History Object
												(
													...
												)
											...
										)
						)
			)
			```

	12. ### Function `load_refs()`
	This is a **_Query Executer_** `function`, and is used to load references that are defined in model as `const ref`
	See how to define `const ref` in [Creating Models](#creating-models "Creating Models")

		1. #### Description
			```php
			boolean load_refs( )
			```

			Executes the query built from above mentioned **_Query Builder_** functions.
			This function finds and loads references of current loaded values of model.

		2. #### Parameters
			- No parameters

		3. #### Return Values
			Returns `true` in case of successful loading. Otherwise returns `false`

		4. #### Examples
			```php
			class CI_Model_Test_Controller extends CI_Controller {
				
				public function index() {
					$this->load->model('users', null, true);

					// Assuming that User model have defined `const ref = 'id:History.user_id, id:Order.user_id';`
					$this->user->find_one();
					print_r( $this->user );

					$this->user->load_refs();
					print_r( $this->user );

				}

			}
			```
			Resulting MySQL query:
			```sql
			SELECT * FROM users;
			```

			Output will be like:
			```
			User Object
			(
				[id] => 123
				[name] => Tom Hanks
				[email] => tom.hanks@doozielabs.com
				[username] => hanks
				[password] => abc1234
			)
			User Object
			(
				[id] => 123
				[name] => Tom Hanks
				[email] => tom.hanks@doozielabs.com
				[username] => hanks
				[password] => abc1234
				[ref] => Array
						(
							[id:History] => Array
										(
											[0]	=> History Object
												(
													[id] => 151
													[user_id] => 123
													[action_time] => 2015-03-01 10:55:01
													[action] => Logged in
													[ip] => 182.178.65.119
												)
											[1]	=> History Object
												(
													...
												)
											...
										)
							[id:Order] => Array
										(
											[0]	=> Order Object
												(
													...
												)
											...
										)
						)
			)
			```
