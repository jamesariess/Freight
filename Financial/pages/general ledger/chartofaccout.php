
<?php include __DIR__ . '/../../crud/legder/chartofaccounts.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart Of Acoount </title>
    <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?> 
  

<br> <?php include __DIR__ . '/../../table/general ledger/charttable.html';?>
<?php include __DIR__ . '/../../filtering/generalledger/accountfilter.html'; ?>
   
    <?php include __DIR__ . '/../../modal/general ledger/chartmodal.html'; ?>
</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>

</body>

</html> 