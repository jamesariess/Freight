
<?php 
include "../../crud/collection/reminder.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include "../../static/head/header.php" ?>
    <link rel="stylesheet" href="../../static/css/reminder.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include "../sidebar.php"; ?> 
   
        <!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-semibold">Confirm Action</h3>
            <button id="closeConfirmationModal" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <div class="mt-4 ">
            <p>Are you sure you want to send this reminder?</p>
        </div>
        <div class="mt-6 flex justify-end gap-4">
            <button id="cancelConfirmation" class="bg-red-300 hover:bg-red-400 px-4 py-2 rounded-lg">Cancel</button>
            <button id="confirmSend" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Confirm</button>
        </div>
    </div>
</div>
     

     
        
        <?php include __DIR__ . '/../../contents/collection/reminder.php'; ?> 
        <?php include __DIR__ . '/../../modal/collection/remindersmodal.html'; ?>
        
    </div>
</div>

<script src="<?php echo '../../static/js/filter.js'; ?>"></script>
<?php include "../../static/js/modal.php";?>

</body>
</html>

