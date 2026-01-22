<?php include __DIR__ . '/../../crud/ar/custumer.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custumer Details </title>
 <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?> 

<?php include __DIR__ . '/../../filtering/ar/custumer.html'; ?>
<br>
    <?php include __DIR__ . '/../../modal/ar/custumer.html'; ?>
    <?php include __DIR__ . '/../../table/ar/custumer.html';?>

</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>


</body>

</html>