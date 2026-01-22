<?php 
include "../../crud/collection/reminder.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow Reports</title>
 <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?> 
   
  

        <?php include __DIR__ . '/../../filtering/collection/reminderfilter.html'; ?>
        <br>
        <?php include __DIR__ . '/../../modal/disbursement/disbursement.html'; ?>
        <?php include __DIR__ . '/../../table/collection/reminderstable.html'; ?>

</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>
</body>
</html>