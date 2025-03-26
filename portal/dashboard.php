<?php
session_start();

require 'vendor/autoload.php'; 

use Aws\S3\S3Client;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

$sdk = new Aws\Sdk([
    'region'   => 'us-east-2', 
    'version'  => 'latest'
]);

$dynamodb = $sdk->createDynamoDb();
$s3 = $sdk->createS3();
$marshaler = new Marshaler();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];

function uploadFileToS3($s3, $username, $file, $fileType) {
    $bucket = 'face-template';
    $key = $username . '/' . $fileType . '_' . time();
    try {
        $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'SourceFile' => $file['tmp_name']
        ]);
        return "https://s3.amazonaws.com/{$bucket}/{$key}";
    } catch (Aws\Exception\AwsException $e) {
        return null;
    }
}

function getUserDetails($dynamodb, $marshaler, $username) {
    $params = [
        'TableName' => 'user_details',
        'Key' => $marshaler->marshalJson(json_encode(['username' => $username]))
    ];

    try {
        $result = $dynamodb->getItem($params);
        return !empty($result['Item']) ? $marshaler->unmarshalItem($result['Item']) : null;
    } catch (DynamoDbException $e) {
        return null;
    }
}

function updateUserDetails($dynamodb, $marshaler, $username, $photoLink, $idPhotoLink, $existingDetails) {
    $existingPhotoLinks = $existingDetails['photo_links'] ?? [];
    $existingIdLinks = $existingDetails['id_links'] ?? [];

    array_push($existingPhotoLinks, $photoLink);
    array_push($existingIdLinks, $idPhotoLink);

    $eav = $marshaler->marshalJson(json_encode([
        'username' => $username,
        'photo_links' => $existingPhotoLinks,
        'id_links' => $existingIdLinks
    ]));

    $params = [
        'TableName' => 'user_details',
        'Item' => $eav
    ];

    try {
        $dynamodb->putItem($params);
    } catch (DynamoDbException $e) {
        // Handle error
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES['photo']) && isset($_FILES['id_photo'])) {
        $photoLink = uploadFileToS3($s3, $username, $_FILES['photo'], 'photo');
        $idPhotoLink = uploadFileToS3($s3, $username, $_FILES['id_photo'], 'id');

        if ($photoLink && $idPhotoLink) {
            $existingDetails = getUserDetails($dynamodb, $marshaler, $username);
            updateUserDetails($dynamodb, $marshaler, $username, $photoLink, $idPhotoLink, $existingDetails);
        } else {
            // Handle error in file upload
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
<nav>
    <ul>
        <li><a href='logout.php'>Logout</a></li>
    </ul>
</nav>
<h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="photo" required><br>
    <input type="file" name="id_photo" required><br>
    <input type="submit" value="Upload">
</form>

</body>
</html>
