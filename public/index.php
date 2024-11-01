<?php

require_once __DIR__ . '/../Core/XPressDB.php';
require_once __DIR__ . '/../Core/Enums/EWhereCond.php';
require_once __DIR__ . '/../Core/Enums/EOrderBy.php';

$db = new XPressDB([
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'testdb'
]);

// Example 1: Select all records
$users = $db->table('users')
    ->select(['id', 'name', 'email', 'status'])
    ->findMany();

echo '<h1>All Users</h1><br>';
foreach ($users as $user) {
    echo $user['id'] . ' - ' . $user['name'] . ' - ' . $user['email'] . ' - ' . $user['status'] . '<br>';
}

// Example 2: Select specific fields with field ordering
$userData = $db->table('users')
    ->select(['id', 'name', 'email'])
    ->find(1);

echo '<h1>User with ID: 1</h1><br>';
echo $userData['id'] . ' - ' . $userData['name'] . ' - ' . $userData['email'] . '<br>';

// Example 3: Multiple record retrieval with conditional filtering
$activeUsers = $db->table('users')
    ->where('status', EWhereCond::EQ, 'active')
    ->select(['id', 'name'])
    ->findMany();

echo '<h1>Active Users</h1><br>';
foreach ($activeUsers as $user) {
    echo $user['id'] . ' - ' . $user['name'] . '<br>';
}

// Example 4: Adding comparisons and sorting to the query
$youngActiveUsers = $db->table('users')
    ->where('status',  EWhereCond::EQ, 'active')
    ->where('age', EWhereCond::GT, 25)
    ->orderBy('created_at', EOrderBy::DESC)
    ->findMany();

echo '<h1>Young Active Users</h1><br>';
foreach ($youngActiveUsers as $user) {
    echo $user['id'] . ' - ' . $user['name'] . ' - ' . $user['age'] . '<br>';
}

// Example 5: Limiting results
$limitedUsers = $db->table('users')
    ->where('status', EWhereCond::EQ, 'active')
    ->limit()
    ->findMany();

echo '<h1>Limited Users</h1><br>';
foreach ($limitedUsers as $user) {
    echo $user['id'] . ' - ' . $user['name'] . '<br>';
}

// Example 6: Simple Insert with returned ID
/*
$newUserId = $db->table('users')->insert([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'status' => 'active'
]);

echo '<h1>New User ID</h1>';
echo $newUserId;
*/

// Example 7: Update with conditional fields
$updateCount = $db->table('users')
    ->where('id', EWhereCond::EQ, 4)
    ->update([
        'status' => 'inactive'
    ]);

echo '<h1>Update Status</h1><br>';
echo $updateCount;

// Example 8: Deleting records based on conditions
$deleteCount = $db->table('users')
    ->where('status', EWhereCond::EQ, 'inactive')
    ->where('age', EWhereCond::LTE, 16)
    ->delete();

echo '<h1>Deleted Users</h1><br>';
echo $deleteCount;

// Example 9: Aggregation functions
$averageAge = $db->table('users')->avg('age');
$totalUsers = $db->table('users')->count();
$totalUsersAbove30 = $db->table('users')
    ->where('age', EWhereCond::GT, 30)
    ->count();

echo '<h1>Statistics</h1><br>';
echo 'Average Age: ' . $averageAge . '<br>';
echo 'Total Users: ' . $totalUsers . '<br>';
echo 'Total Users Above 30: ' . $totalUsersAbove30;

// Example 10: Transaction management with error handling. Uncomment to run.
$userId = 1;

try {
    $db->beginTransaction();

    $db->table('orders')->insert([
        'user_id' => $userId,
        'amount' => 49
    ]);

    $db->table('non_existent_table')->insert([
        'user_id' => $userId,
        'invalid_column' => 'some_value'
    ]);

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
}

// Example 11: Summation of a field
$orderSum = $db->table('orders')
    ->where('user_id', EWhereCond::EQ, 1)
    ->sum('amount');

echo '<h1>Order Sum</h1><br>';
echo $orderSum;

// Example 12: Maximum value of a field
$highestOrder = $db->table('orders')
    ->where('user_id', EWhereCond::EQ, 1)
    ->max('amount');

echo '<h1>Highest Order</h1><br>';
echo $highestOrder;

// Example 13: Grouping records
$userStatusCounts = $db->table('users')
    ->select(['status', 'COUNT(id) as user_count'])
    ->groupBy('status')
    ->findMany();

echo '<h1>User Counts by Status</h1><br>';
foreach ($userStatusCounts as $row) {
    echo 'Status: ' . $row['status'] . ' - User Count: ' . $row['user_count'] . '<br>';
}

// Example 14: Joining tables and aggregating results
$userOrders = $db->table('users')
    ->select(['users.id', 'users.name', 'SUM(orders.amount) as total_spent'])
    ->join('orders', 'orders.user_id', 'users.id')
    ->groupBy('users.id')
    ->findMany();

echo '<h1>User Total Spending</h1><br>';
foreach ($userOrders as $row) {
    echo 'User ID: ' . $row['id'] . ' - Name: ' . $row['name'] . ' - Total Spent: ' . $row['total_spent'] . '<br>';
}

$db->close();