<?php

require 'config.php';

if(isset($_GET['code'])) {

    $code = $_GET['code'];

    $token_url = "https://oauth2.googleapis.com/token";

    $data = [
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ];

    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);

    $result = file_get_contents($token_url, false, $context);

    $response = json_decode($result, true);

    if (isset($response['access_token'])) {
        $access_token = $response['access_token'];

        $user_info = file_get_contents(
            "https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$access_token
        );

        $user = json_decode($user_info, true);

        if (isset($user['id'])) {
            $google_id = $user['id'];
            $name = $user['name'];
            $email = $user['email'];
            $avatar = $user['picture'];

            // Check user với prepared statement để tránh SQL injection
            $stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ?");
            $stmt->bind_param("s", $google_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows == 0){
                $stmt = $conn->prepare("INSERT INTO users (google_id, name, email, avatar) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $google_id, $name, $email, $avatar);
                $stmt->execute();
            }

            $stmt->close();

            $_SESSION['user'] = $user;

            header("Location: dashboard.php");
            exit();
        }
    }
}
// Nếu có lỗi (không có access_token hoặc user info), chuyển về login
header("Location: login.php");
exit();