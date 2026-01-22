<?php include __DIR__ . '/../../crud/disbursement/approver.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release Budget</title>
 <?php include "../../static/head/header.php" ?>

</head>
<body>
<?php include "../sidebar.php"; ?> 
<?php include __DIR__ . '/../../cards/disbursement/cashrelease.php'; ?>
<br>
<?php include __DIR__ . '/../../contents/disbursement/approver.php';?>

<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>

</body>
</html>