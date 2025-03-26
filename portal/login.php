<?php
session_start();

require 'vendor/autoload.php'; // Include AWS SDK for PHP

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

$sdk = new Aws\Sdk([
    'region'   => 'us-east-2', 
    'version'  => 'latest'
]);

$dynamodb = $sdk->createDynamoDb();
$marshaler = new Marshaler();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Validate email format
    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if username exists
        $eav = $marshaler->marshalJson('{"username": "' . $username . '"}');
        $params = [
            'TableName' => 'users',
            'KeyConditionExpression' => 'username = :username',
            'ExpressionAttributeValues' => $eav
        ];

        try {
            $result = $dynamodb->query($params);
            if (!empty($result['Items'])) {
                $user = $marshaler->unmarshalItem($result['Items'][0]);
                // Verify password
                if (password_verify($password, $user['password'])) {
                    $_SESSION['username'] = $username;
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = "Incorrect password";
                }
            } else {
                $error = "Username not found";
            }
        } catch (DynamoDbException $e) {
            $error = "Unable to query DynamoDB: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
<nav>
    <ul>
        <li><a href='register.php'>Register</a></li>
    </ul>
</nav>
<h2>User Login</h2>

<?php if (!empty($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="post" action="login.php">
    Username (Email): <input type="email" name="username" required><br>
    Password: <input type="password" name="password" required><br>
    <input type="submit" value="Login">
</form>
</body>
</html>
