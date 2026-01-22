
      <?php include __DIR__ . '/../../crud/budget/budget.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Allocation </title>
 <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?> 
    <br>
    <?php include  '../../cards/budget/budget.php'; ?>
     <?php include  '../../articial/budget/budget1.php'; ?>
  <?php include  '../../contents/budget/budget.php'; ?>
<br>   

</div> 

<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>

</body>

</html>