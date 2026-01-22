<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Cloudinary\Cloudinary;
include_once('../../utility/head.php');
include_once __DIR__ . '/../../utility/connn.php';
date_default_timezone_set('Asia/Manila');

$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = 'slatetransportsystem@gmail.com';
$password = 'mfkkigrgxtoascov';

$groq_api_key = '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';
$groq_api_url = 'https://api.groq.com/openai/v1/chat/completions';
$debug_log_file = __DIR__ . '/ai_invoice_debug.log';


$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'dccvicfzv',  
        'api_key'    => '868917412781798',
        'api_secret' => '3ZBsffXT_xh10IAgsjCKhqU7pyk'
    ]
]);

function log_debug($msg) {
    global $debug_log_file;
    file_put_contents($debug_log_file, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}
function addAuditLog($pdo, $user_name, $role, $action, $module, $details) {
    $ip_address = getUserIP();
    $sql = "INSERT INTO settings.audit_log (user_name, role, action, module, ip_address, details)
            VALUES (:user_name, :role, :action, :module, :ip_address, :details)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_name' => $user_name,
        ':role' => $role,
        ':action' => $action,
        ':module' => $module,
        ':ip_address' => $ip_address,
        ':details' => $details
    ]);
}

function addNotification($pdo, $user_id, $title, $message, $icon = 'fa-bell') {
    $sql = "INSERT INTO settings.notifications (user_id, title, message, icon)
            VALUES (:user_id, :title, :message, :icon)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':title' => $title,
        ':message' => $message,
        ':icon' => $icon
    ]);
}        

function process_emails($pdo, $hostname, $username, $password, $groq_api_url, $groq_api_key, $cloudinary) {
    $inbox = @imap_open($hostname, $username, $password);
    if (!$inbox) {
        log_debug('❌ Cannot connect to Gmail: ' . imap_last_error());
        return;
    }

    $emails = imap_search($inbox, 'UNSEEN');
    if (!$emails) {
        log_debug("📭 No unseen emails.");
        imap_close($inbox);
        return;
    }

    rsort($emails);
    log_debug("📧 Found " . count($emails) . " unseen email(s).");

    foreach ($emails as $email_number) {
        $header = imap_headerinfo($inbox, $email_number);
        $from_email = strtolower($header->from[0]->mailbox . '@' . $header->from[0]->host);
        log_debug("Processing email from: $from_email");

        $stmt = $pdo->prepare("SELECT vendor_id, vendor_name FROM ar_ap.vendor WHERE LOWER(Email)=:email AND Archive='NO'");
        $stmt->bindParam(':email', $from_email);
        $stmt->execute();
        $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vendor) {
            log_debug("🚫 No vendor match for $from_email — skipping.");
            continue;
        }

        $structure = imap_fetchstructure($inbox, $email_number);
        $attachments = [];

        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $part = $structure->parts[$i];
                if (isset($part->disposition) && in_array(strtolower($part->disposition), ['attachment', 'inline'])) {
                    $filename = $part->dparameters[0]->value ?? "file_$i";
                    $content = imap_fetchbody($inbox, $email_number, $i + 1);
                    if ($part->encoding == 3) $content = base64_decode($content);
                    elseif ($part->encoding == 4) $content = quoted_printable_decode($content);
                    $attachments[] = ['filename' => $filename, 'content' => $content];
                }
            }
        }

        foreach ($attachments as $file) {
            $filename = $file['filename'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $base_name = pathinfo($filename, PATHINFO_FILENAME);
            $unique_filename = $base_name . '_' . date('Ymd_His') . '.' . $ext;

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                log_debug("🗑 Unsupported file type ($ext), skipped.");
                continue;
            }

            $year = date('Y');
            $folderPath = "financial/$year/invoice";

            try {
                $tempPath = sys_get_temp_dir() . '/' . $unique_filename;
                file_put_contents($tempPath, $file['content']);

                $uploadResult = $cloudinary->uploadApi()->upload($tempPath, [
                    'folder' => $folderPath,
                    'public_id' => pathinfo($unique_filename, PATHINFO_FILENAME)
                ]);

                unlink($tempPath);

                $cloudinaryUrl = $uploadResult['secure_url'];
                log_debug("☁️ Uploaded to Cloudinary: $cloudinaryUrl");

                $base64 = base64_encode($file['content']);
                $mime = ($ext === 'pdf') ? 'application/pdf' : "image/{$ext}";
                $response = call_groq_extract_invoice($groq_api_url, $groq_api_key, $base64, $mime);
                $data = parse_invoice_data($response);

               
                $validationErrors = [];
                if (empty($data['amount']) || $data['amount'] <= 0) {
                    $validationErrors[] = "Invalid or missing amount.";
                }
                if (empty($data['bill_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['bill_date'])) {
                    $validationErrors[] = "Invalid or missing bill date.";
                }
                if (empty($data['due_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
                    $validationErrors[] = "Invalid or missing due date.";
                }
                if (empty($data['description'])) {
                    $validationErrors[] = "Missing description.";
                }

                if (!empty($validationErrors)) {
                    log_debug("⚠️ Validation errors for $filename: " . implode(' ', $validationErrors));
                    continue;
                }

 
                $pdo->beginTransaction();
                try {
                    $insert = $pdo->prepare("
                        INSERT INTO ar_ap.ap_bills (vendor_id, bill_date, due_date, amount, reference_no, description, file_path)
                        VALUES (:vendor_id, :bill_date, :due_date, :amount, :reference_no, :description, :file_path)
                    ");
                    $insert->execute([
                        ':vendor_id'   => $vendor['vendor_id'],
                        ':bill_date'   => $data['bill_date'],
                        ':due_date'    => $data['due_date'],
                        ':amount'      => $data['amount'],
                        ':reference_no'=> $data['reference_no'],
                        ':description' => $data['description'],
                        ':file_path'   => $cloudinaryUrl
                    ]);

                    $bill_id = $pdo->lastInsertId();
                    $pdo->commit();

               
                    $auditDescription = "Created bill #$bill_id for vendor '{$vendor['vendor_name']}' (Vendor ID: {$vendor['vendor_id']}). Details: Amount=₱" . number_format($data['amount'], 2) . ", Reference No='{$data['reference_no']}', Description='{$data['description']}', File='$cloudinaryUrl'.";
                    addAuditLog($pdo, 'System', 'System', 'Create', 'Bill', $auditDescription);
                    $notifMessage = "Bill #$bill_id created for vendor '{$vendor['vendor_name']}' (Amount: ₱" . number_format($data['amount'], 2) . ").";
                    addNotification($pdo, null, 'Bill Created', $notifMessage, 'fa-file-invoice');
                    log_debug("✅ Inserted bill #$bill_id: ₱" . number_format($data['amount'], 2) . " | {$data['description']} | File: $cloudinaryUrl");
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    log_debug("❌ Failed to insert bill for $filename: " . $e->getMessage());
                }
            } catch (Exception $e) {
                log_debug("❌ Cloudinary upload failed for $filename: " . $e->getMessage());
            }
        }

        imap_setflag_full($inbox, $email_number, "\\Seen");
    }

    imap_close($inbox);
    log_debug("✅ Finished processing all new emails.");
}

function call_groq_extract_invoice($api_url, $api_key, $base64_img, $mime) {
    $prompt = <<<PROMPT
You are an expert at reading vendor, supplier, or utility invoices from images.
Extract ONLY the following information and reply strictly in JSON format:
{
  "bill_date": "YYYY-MM-DD",
  "due_date": "YYYY-MM-DD",
  "amount": number,
  "reference_no": "invoice number or ref no",
  "description": "title or type of bill (e.g. Meralco Electric Bill, Water Bill, Fuel Invoice, Tax Invoice)"
}
If a field is missing, estimate logically (e.g., due_date = bill_date + 30 days).
PROMPT;

    $payload = [
        "model" => "meta-llama/llama-4-scout-17b-16e-instruct",
        "response_format" => ["type" => "json_object"],
        "messages" => [[
            "role" => "user",
            "content" => [
                ["type" => "text", "text" => $prompt],
                ["type" => "image_url", "image_url" => [
                    "url" => "data:{$mime};base64," . $base64_img
                ]]
            ]
        ]],
        "max_tokens" => 500
    ];

    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $api_key",
            "Content-Type: application/json"
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        log_debug("❌ cURL error: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

function parse_invoice_data($response) {
    $raw = $response['choices'][0]['message']['content'] ?? '';
    $data = json_decode($raw, true);

    if (!$data && preg_match('/\{.*\}/s', $raw, $m)) {
        $data = json_decode($m[0], true);
    }

    if (!$data) {
        log_debug("⚠️ Failed to parse invoice data from Groq response.");
        return [
            'bill_date'   => date('Y-m-d'),
            'due_date'    => date('Y-m-d', strtotime('+30 days')),
            'amount'      => 0,
            'reference_no'=> '',
            'description' => 'Unknown Bill'
        ];
    }

    $data['amount'] = isset($data['amount']) ? floatval(preg_replace('/[^0-9.]/', '', $data['amount'])) : 0;
    $data['bill_date'] = $data['bill_date'] ?? date('Y-m-d');
    $data['due_date'] = $data['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $data['reference_no'] = $data['reference_no'] ?? '';
    $data['description'] = $data['description'] ?? 'General Bill';

    return $data;
}

log_debug("⏰ Script triggered at " . date('Y-m-d H:i:s'));
process_emails($pdo, $hostname, $username, $password, $groq_api_url, $groq_api_key, $cloudinary);
log_debug("✅ Completed run.\n");
?>