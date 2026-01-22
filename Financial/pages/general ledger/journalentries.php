<?php include __DIR__ . '/../../crud/legder/entries.php';?>

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
 
<?php include __DIR__ . '/../../filtering/generalledger/journal.html'; ?>
<br>
   
<?php include __DIR__ . '/../../table/general ledger/journaltable.html';?>

</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>
</body>

</html>   