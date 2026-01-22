<?php include __DIR__ . '/../../crud/collection/plan.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Plan </title>
 <?php include "../../static/head/header.php" ?>

</head>
<body>
  <?php include "../sidebar.php"; ?> 
 
  
    <?php include __DIR__ . '/../../filtering/collection/planfilter.html';?>
      <?php include __DIR__ . '/../../modal/disbursement/disbursement.html'; ?>
    <br>
    <?php include __DIR__ . '/../../modal/collection/collectionplanmodal.html'; ?>
    <?php include __DIR__ . '/../../table/collection/collectiontable.html';?>

</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>

</body>

</html> 