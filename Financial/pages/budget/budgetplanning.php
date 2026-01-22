

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planning </title>
 <?php include "../../static/head/header.php" ?>
</head>
<body> 
    <?php include "../sidebar.php"; ?>  
   
 
  <div id="msg" class="mb-4"></div>
     <?php include __DIR__ . '/../../contents/budget/planning.php'; ?>
     <?php include __DIR__ . '/../../filtering/budget/planning.html'; ?>
     <?php include __DIR__ . '/../../modal/disbursement/disbursement.html'; ?>
   
<br> 
    
    <?php include __DIR__ . '/../../table/budgetmanagement.html/planningtable.html';?>

</div> 
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>


</body>

</html>