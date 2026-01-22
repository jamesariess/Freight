<?php include __DIR__ . '/../../crud/budget/adjustment.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Adjustment </title>
 <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?> 
  

    <?php include __DIR__ . '/../../contents/budget/allocationadjustment.php'; ?>

<br>

    <?php include __DIR__ . '/../../table/budgetmanagement.html/adjustmenttable.html';?>

</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>


</body>

</html>