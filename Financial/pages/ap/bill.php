<?php include __DIR__ . '/../../crud/ap/bill.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AR Adjustment </title>
 <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?>  

<?php include __DIR__ . '/../../filtering/ap/bill.html'; ?>
<br>
    <?php include __DIR__ . '/../../modal/ap/bill.html'; ?>
    <?php include __DIR__ . '/../../table/ap/bill.php';?>

</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>


</body>

</html>