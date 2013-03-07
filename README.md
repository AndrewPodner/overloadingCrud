#Overloading CRUD

This is a simple class designed to illustrate how you can use overloading to accomplish basic CRUD (Create, Retrieve, Update, and Delete) from a MySQL database.  It will parse a method name and determine what action is to be taken, as well as what the table name and field name to be used are.  This can be very useful for applications where you need to access data in a variety of ways, but you do not want to keep coding methods for every permutation, or 3 or 4 lines of code to query the database everytime.

## License
Copyright (c) 2013, Andrew Podner

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

## Things to Know
> NOTE: This class is not designed as production code, it is merely an illustrative example of what can be done with overloading.  I have not put in any thing to filter input or escape output.  Additionally, it has only been tested with the most basic examples, it is not guaranteed to be bug free. If you have a suggestion for improvement, feel free to send a PR, or log an issue on Github.

* Database Tables must be lowercase, underscore spaced (e.g. `some_table`)
* Database Fields must be lowercase, underscore spaces (e.g. `some_field`)
* Method Names must be set up in camelCase as follows:
	* Create: `insertTableName()`
	* Retrieve: `getTableNameByFieldName()`
	* Update: `updateTableNameByFieldName()`
	* Delete: `deleteTableNameByFieldName()`

## Create
Use a camelCase method name, and pass a single asscoiative array as the parameter.  In the array, the key should be the database field name, and the value is the value to be inserted for that field. The format of the method name is `insertTableName()`

### Example
```
$arrayInsert = array(
	'number' => 12345,
	'name' => 'Joe Smith',
	'email' => 'joe@example.com'
);

$db = new Database($pdoInstance);
$result = $db->insertCustomers($arrInsert);

// Returns Number of Rows Inserted
```

## Retrieve
The format of the method name to retrieve a record is `getTableNameByFieldName($value)`.  The where clause of the method will be constructed as `WHERE field_name = '$value'`.  This is meant for relatively simple s`select` queries, so anything more complex would require you to revert to conventional means of building queries.

### Examples
```
$db = new Database($pdoInstance);
$result = $db->getCustomersById(5);

/* 
Produces this sql:
"select * from customers where id = '5'"

since this is a single row result you will get an associative array

array(
	'id' => 5,
	'customer_name' => 'Some Customer',
)

a multiple row result would produce a multidimensional array

$result = $db->getCustomersByAccountType('new');
array(
	0 => array('id' => 5, 'customer_name' => 'Some Customer', 'account_type' => 'new'),
	1 => array('id' => 6, 'customer_name' => 'Some Other Customer', 'account_type' => 'new'))
)

*/

```

## Update
This works very similar to retrieval, with one extra parameter which is an array of fields and values to make up the `SET` portion of the sql statement.  The method name format is`updateTableNameByFieldName($whereValue, $arrSet)`

### Example

```
$arrSet = array(
    'customer_name' => 'Updated Customer Name', 
    'account_type' => 'annual'
);

$db = new Database($pdoInstance);
$result = $db->updateCustomersById(5, $arrSet);

// returns 1 (number of records affected)
```

## Delete
Works like retrieve and update.  The method name format is `deleteTableNameByFieldName($value)`.  

###Example

```
$db = new Database($pdoInstance);
$result = $db->deleteCustomersById(5);

// returns 1 (number of affected rows)

```
