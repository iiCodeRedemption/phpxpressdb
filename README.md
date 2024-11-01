# PHPXPressDB

PHPXPressDB is a simple and lightweight PHP library for working with MySQL databases. Forget about writing full SQL queries, PHPXPressDB will do it for you.

## Installation

Clone the repository and copy all files under the `Core` directory to your project:

```bash
cp -r Core/* /path/to/your/project
```

On Windows:

```bash
xcopy Core /path/to/your/project /E /H /I
```

## Usage

Include `XPressDB.php` in your project:

```php
require_once 'Core/XPressDB.php';
```

Create a new instance of `XPressDB`. Make sure to replace the values with your database connection details:

```php
$db = new XPressDB([
    'host' => 'host',
    'username' => 'username',
    'password' => 'password',
    'database' => 'yourdb'
]);
```

Done! :tada: You can now start using XPressDB to interact with your database.

## Examples

1. **Get all rows from a table:**

```php
$users = $db->table('users')->findMany();
```

If you want to get only specific columns:

```php
$usersSpecific = $db->table('users')
    ->select(['id', 'name', 'email'])
    ->findMany();
```

2. **Get a single row by ID:**

```php
$user = $db->table('users')->find(1);
```

Internally, XPressDB will detect the primary key of the table and use it to fetch the row.

You can also specify what fields to fetch:

```php
$userSpecific = $db->table('users')
    ->select(['id', 'name', 'email'])
    ->find(1);
```

3. **Insert a new row:**

```php
$newUserId = $db->table('users')->insert([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// You can then use $newUserId to get the new user
$newUser = $db->table('users')->find($newUserId);
```

4. **Update a row:**

```php
$updateCount = $db->table('users')
    ->where('id', EWhereCond::EQ, 1)
    ->update([
        'email' => 'janeupdated@example.com'
    ]);

// You can then use the $updateCount to check if the update was successful
if ($updateCount > 0) {
    echo 'User updated successfully';
}
```

5. **Delete a row:**

```php
$deleteCount = $db->table('users')
    ->where('id', EWhereCond::EQ, 1)
    ->delete();

// Same as update, you can use $deleteCount to check if to delete was successful
if ($deleteCount > 0) {
    echo 'User deleted successfully';
}
```

6. **Aggregates:**

```php
// Get the total number of users
$totalUsers = $db->table('users')->count();
echo 'Total users: ' . $totalUsers . <br>;

// Sum of all user ages
$sumOfAges = $db->table('users')->sum('age');
echo 'Sum of ages: ' . $sumOfAges . <br>;

// Average user age
$averageAge = $db->table('users')->avg('age');
echo 'Average age: ' . $averageAge . <br>;
```

7. **Raw queries:**

```php
// Get all users
$users = $db->rawQuery('SELECT id, name, age FROM users');
```

8. Transactions:

```php

try {
    $db->beginTransaction();
    
    $newUserId = $db->table('users')->insert([
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'age' => 25
    ]);
    
    $updateCount = $db->table('users')
        ->where('id', EWhereCond::EQ, $newUserId)
        ->update([
            'age' => 26
        ]);
       
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    echo 'An error occurred: ' . $e->getMessage();
}
```

You can also use enumerations to store table names to avoid typos:

```php
enum ETable: string {
    case USERS = 'users';
    case ORDERS = 'orders';
}

$db->table(ETable::USERS->value)
    ->select(['id', 'name', 'email'])
    ->findMany();
```