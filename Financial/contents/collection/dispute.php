<?php
include_once __DIR__ . '/../../utility/connection.php';
require '../../vendor/autoload.php';
use Cloudinary\Cloudinary;

$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'dccvicfzv',
        'api_key'    => '868917412781798',
        'api_secret' => '3ZBsffXT_xh10IAgsjCKhqU7pyk'
    ]
]);

function generateDisputeID($pdo) {
    $sql = "SELECT Dispute_ID FROM dispute ORDER BY Dispute_ID DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $lastDisputeID = $stmt->fetchColumn();

    if ($lastDisputeID) {
        $num = intval(substr($lastDisputeID, 4)) + 1;
    } else {
        $num = 1;
    }

    return 'DSP-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}


if (!isset($_GET['invoice_id'])) {
    die("Invalid invoice ID.");
}

$invoice_id = filter_var($_GET['invoice_id'], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_dispute'])) {
    $description = trim($_POST['description'] ?? '');
    $disputeID = generateDisputeID($pdo);
    $cloudinaryUrl = null;

    if (isset($_FILES['dispute_image']) && $_FILES['dispute_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['dispute_image'];
        $fileTmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileType = $file['type'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowedTypes)) {
            $errorMessage = "Only JPEG, PNG, and GIF images are allowed.";
        } else {
            try {
                $year = date('Y');
                $uniqueName = pathinfo($fileName, PATHINFO_FILENAME) . '_' . time();

                $uploadResult = $cloudinary->uploadApi()->upload($fileTmpPath, [
                    'folder' => "financial/$year/dispute",
                    'public_id' => $uniqueName
                ]);

                $cloudinaryUrl = $uploadResult['secure_url'];

            } catch (Exception $e) {
                $errorMessage = "Cloudinary upload failed: " . $e->getMessage();
            }
        }
    }


    if (empty($errorMessage)) {
        try {
            if ($cloudinaryUrl) {
                $sql = "INSERT INTO dispute (Dispute_ID, invoice_id, issue, doc)
                        VALUES (:disputeID, :invoice_id, :issue, :doc)
                        ON DUPLICATE KEY UPDATE issue = VALUES(issue), doc = VALUES(doc)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':disputeID' => $disputeID,
                    ':invoice_id' => $invoice_id,
                    ':issue' => $description,
                    ':doc' => $cloudinaryUrl
                ]);
                $successMessage = "Dispute submitted successfully with image. Dispute ID: $disputeID";
            } else {
                $sql = "INSERT INTO dispute (Dispute_ID, invoice_id, issue)
                        VALUES (:disputeID, :invoice_id, :issue)
                        ON DUPLICATE KEY UPDATE issue = VALUES(issue)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':disputeID' => $disputeID,
                    ':invoice_id' => $invoice_id,
                    ':issue' => $description
                ]);
                $successMessage = "Dispute submitted successfully without image. Dispute ID: $disputeID";
            }
        } catch (PDOException $e) {
            $errorMessage = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Support - Invoice Dispute</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
        }
        .error { color: red; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h2>Customer Support - Invoice #<?php echo htmlspecialchars($invoice_id); ?></h2>

    <?php if ($errorMessage): ?>
        <p class="error"><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <p class="success"><?= htmlspecialchars($successMessage) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="description">Issue Description:</label>
            <textarea name="description" id="description" rows="5" required></textarea>
        </div>

        <div class="form-group">
            <label for="dispute_image">Upload Image (optional):</label>
            <input type="file" name="dispute_image" id="dispute_image" accept="image/jpeg,image/png,image/gif">
        </div>

        <button type="submit" name="submit_dispute">Submit Dispute</button>
    </form>
</body>
</html>
