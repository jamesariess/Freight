<?php include __DIR__ . '/../../crud/ar/invoice.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AR INVOICE </title>
 <?php include "../../static/head/header.php" ?>
</head>
<body>
    <?php include "../sidebar.php"; ?> 

<?php include __DIR__ . '/../../filtering/ar/invoice.html'; ?>
<br>
    <?php include __DIR__ . '/../../modal/ar/invoice.html'; ?>
    <?php include __DIR__ . '/../../table/ar/invoce.php';?>

</div>
<script src="<?php echo '../../static/js/filter.js';?>"></script>
<?php include "../../static/js/modal.php" ?>

</body>

</html>s