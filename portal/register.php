<?php
require 'vendor/autoload.php'; // Include AWS SDK for PHP

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

// AWS SDK simplified configuration
$sdk = new Aws\Sdk([
    'region'   => 'us-east-2', 
    'version'  => 'latest'
]);

$s3 = new Aws\S3\S3Client([
    'region'  => 'us-east-2',
    'version' => 'latest',
]);

$dynamodb = $sdk->createDynamoDb();
$marshaler = new Marshaler();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate inputs
    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        // Check if username already exists
        $eav = $marshaler->marshalJson('{"username": "' . $username . '"}');
        $params = [
            'TableName' => 'users',
            'KeyConditionExpression' => 'username = :username',
            'ExpressionAttributeValues' => $eav
        ];

        try {
            $result = $dynamodb->query($params);
            if (!empty($result['Items'])) {
                $error = "Username already taken";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Store user in DynamoDB
                $eav = $marshaler->marshalJson('{
                    "username": "' . $username . '",
                    "password": "' . $hashedPassword . '"
                }');

                $params = [
                    'TableName' => 'users',
                    'Item' => $eav
                ];

                $dynamodb->putItem($params);

                // Create folder in S3 for user
                try {
                    $s3->putObject([
                        'Bucket' => 'face-template',
                        'Key' => $username . '/',
                        'Body' => ''
                    ]);
                    $success = "User registered successfully";
                } catch (Aws\Exception\AwsException $e) {
                    $error .= 'Error creating folder in S3: ' . $e->getMessage();
                }
            }
        } catch (DynamoDbException $e) {
            $error = "Unable to query DynamoDB: " . $e->getMessage();
        }
    }
}

// HTML part starts here
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
<nav>
    <ul>
        <li><a href='login.php'>Login</a></li>
    </ul>
</nav>
<h2>User Registration</h2>

<?php if (!empty($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<form method="post" action="register.php">
    Username (Email): <input type="email" name="username" required><br>
    Password: <input type="password" name="password" required><br>
    Confirm Password: <input type="password" name="confirmPassword" required><br>
    <input type="submit" value="Register">
</form>
</body>
</html>
