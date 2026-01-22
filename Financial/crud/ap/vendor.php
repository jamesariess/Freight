<?php
include_once __DIR__ . '/../../utility/connection.php';
include_once('../../utility/head.php');
date_default_timezone_set('Asia/Manila');

$successMessage = '';
$errorMessage = '';

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "SELECT * FROM ar_ap.vendor WHERE vendor_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        $errorMessage = "❌ Error fetching vendor: " . $e->getMessage();
        error_log("Fetch vendor error for vendor_id #$id: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['archive'])) {
        $archive_vendorID = $_POST['archive_collectionID'];

     
        $stmtVendor = $pdo->prepare("SELECT vendor_name FROM ar_ap.vendor WHERE vendor_id = :vendor_id");
        $stmtVendor->execute([':vendor_id' => $archive_vendorID]);
        $vendorData = $stmtVendor->fetch(PDO::FETCH_ASSOC);
        $vendorName = $vendorData ? $vendorData['vendor_name'] : 'Unknown Vendor';

        $sql = "UPDATE ar_ap.vendor SET Archive = 'YES' WHERE vendor_id = :archive_vendorID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':archive_vendorID', $archive_vendorID);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new PDOException("No rows updated for vendor_id: $archive_vendorID");
            }
          
            $auditDescription = "Archived vendor #$archive_vendorID ('$vendorName').";
            addAuditLog($pdo, $user_name, $role, 'Archive', 'Vendor', $auditDescription);
            $notifMessage = "Vendor #$archive_vendorID ('$vendorName') archived.";
            addNotification($pdo, $user_id, 'Vendor Archived', $notifMessage, 'fa-archive');
            $successMessage = "✅ Vendor archived successfully.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Error archiving vendor: " . $e->getMessage();
            error_log("Archive vendor error for vendor_id #$archive_vendorID: " . $e->getMessage());
        }
    }

    if (isset($_POST['update'])) {
        $vendor_id = $_POST['vendors_id'];
        $vendor_name = trim($_POST['name'] ?? '');
        $contact_info = trim($_POST['contact'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_person = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? '';

        $validationErrors = [];
        if (empty($vendor_name)) {
            $validationErrors[] = "Vendor name is required.";
        } elseif (strlen($vendor_name) > 255) {
            $validationErrors[] = "Vendor name must not exceed 255 characters.";
        }

        if (empty($contact_info)) {
            $validationErrors[] = "Contact information is required.";
        }

        if (empty($address)) {
            $validationErrors[] = "Address is required.";
        }

        if (empty($email)) {
            $validationErrors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationErrors[] = "Invalid email format.";
        }

        if (empty($contact_person)) {
            $validationErrors[] = "Contact person/phone is required.";
        }

        if (empty($status)) {
            $validationErrors[] = "Status is required.";
        }

        if (empty($validationErrors)) {
   
            $stmtVendor = $pdo->prepare("
                SELECT vendor_name, contact_info, address, Email, Contact_person, Status 
                FROM vendor 
                WHERE vendor_id = :vendor_id
            ");
            $stmtVendor->execute([':vendor_id' => $vendor_id]);
            $vendorData = $stmtVendor->fetch(PDO::FETCH_ASSOC);
            $oldVendorName = $vendorData ? $vendorData['vendor_name'] : 'N/A';
            $oldContactInfo = $vendorData ? $vendorData['contact_info'] : 'N/A';
            $oldAddress = $vendorData ? $vendorData['address'] : 'N/A';
            $oldEmail = $vendorData ? $vendorData['Email'] : 'N/A';
            $oldContactPerson = $vendorData ? $vendorData['Contact_person'] : 'N/A';
            $oldStatus = $vendorData ? $vendorData['Status'] : 'N/A';

            $sql = "UPDATE ar_ap.vendor SET 
                    vendor_name = :vendor_name,
                    contact_info = :contact_info,
                    address = :address,
                    Email = :email,
                    Contact_person = :contact_person,
                    Status = :status
                    WHERE vendor_id = :vendor_id";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':vendor_name', $vendor_name);
            $stmt->bindParam(':contact_info', $contact_info);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':contact_person', $contact_person);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':vendor_id', $vendor_id);

            try {
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    throw new PDOException("No rows updated for vendor_id: $vendor_id");
                }
                
                $auditDescription = "Updated vendor #$vendor_id. Changes: Name from '$oldVendorName' to '$vendor_name', Contact Info from '$oldContactInfo' to '$contact_info', Address from '$oldAddress' to '$address', Email from '$oldEmail' to '$email', Contact Person from '$oldContactPerson' to '$contact_person', Status from '$oldStatus' to '$status'.";
                addAuditLog($pdo, $user_name, $role, 'Update', 'Vendor', $auditDescription);
                $notifMessage = "Vendor #$vendor_id ('$vendor_name') updated.";
                addNotification($pdo, $user_id, 'Vendor Updated', $notifMessage, 'fa-edit');
                $successMessage = "✅ Vendor updated successfully.";
            } catch (PDOException $e) {
                $errorMessage = "❌ Error updating vendor: " . $e->getMessage();
                error_log("Update vendor error for vendor_id #$vendor_id: " . $e->getMessage());
            }
        } else {
            $errorMessage = "❌ Validation errors: " . implode(' ', $validationErrors);
            error_log("Validation errors for vendor update (vendor_id #$vendor_id): " . implode(' ', $validationErrors));
        }
    }

    if (isset($_POST['create'])) {
        $vendor_name = trim($_POST['vendorName'] ?? '');
        $contact_info = trim($_POST['contactInfo'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_person = trim($_POST['contactPerson'] ?? '');

        $validationErrors = [];
        if (empty($vendor_name)) {
            $validationErrors[] = "Vendor name is required.";
        } elseif (strlen($vendor_name) > 255) {
            $validationErrors[] = "Vendor name must not exceed 255 characters.";
        }

        if (empty($contact_info)) {
            $validationErrors[] = "Contact information is required.";
        }

        if (empty($address)) {
            $validationErrors[] = "Address is required.";
        }

        if (empty($email)) {
            $validationErrors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationErrors[] = "Invalid email format.";
        }

        if (empty($contact_person)) {
            $validationErrors[] = "Contact person/phone is required.";
        }

        if (empty($validationErrors)) {
            $sql = "INSERT INTO ar_ap.vendor (vendor_name, contact_info, address, Email, Contact_person, Status) 
                    VALUES (:vendor_name, :contact_info, :address, :email, :contact_person, 'Active')";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':vendor_name', $vendor_name);
            $stmt->bindParam(':contact_info', $contact_info);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':contact_person', $contact_person);

            try {
                $stmt->execute();
                $vendor_id = $pdo->lastInsertId();
        
                $auditDescription = "Created vendor #$vendor_id: Name='$vendor_name', Contact Info='$contact_info', Address='$address', Email='$email', Contact Person='$contact_person', Status='Active'.";
                addAuditLog($pdo, $user_name, $role, 'Create', 'Vendor', $auditDescription);
                $notifMessage = "Vendor #$vendor_id ('$vendor_name') created.";
                addNotification($pdo, $user_id, 'Vendor Created', $notifMessage, 'fa-plus');
                $successMessage = "✅ Vendor created successfully.";
            } catch (PDOException $e) {
                $errorMessage = "❌ Error creating vendor: " . $e->getMessage();
                error_log("Create vendor error: " . $e->getMessage());
            }
        } else {
            $errorMessage = "❌ Validation errors: " . implode(' ', $validationErrors);
            error_log("Validation errors for vendor creation: " . implode(' ', $validationErrors));
        }
    }
}

try {
    $sql = "SELECT * FROM ar_ap.vendor WHERE Archive = 'NO' ORDER BY vendor_id ASC";
    $stmt = $pdo->query($sql);
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "❌ Error fetching vendors: " . $e->getMessage();
    error_log("Fetch vendors error: " . $e->getMessage());
}
?>