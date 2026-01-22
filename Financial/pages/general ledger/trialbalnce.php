<!-- 
<?php include __DIR__ . '/../../crud/legder/trial.php';?> -->
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Management </title>
 <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?>  

    
   <?php include __DIR__ . '/../../cards/gl/add.php';?>
    <?php include __DIR__ . '/../../contents/gl/add.php';?>
    <?php include __DIR__ . '/../../table/general ledger/trialbalance.php';?>

<?php include __DIR__ . '/../../modal/general ledger/add.html';?>
</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
  <?php include "../../static/js/modal.php" ?>
</body>
 
</html> 