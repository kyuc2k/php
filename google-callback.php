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

            // Check if user exists by google_id or email
            $stmt_check = $conn->prepare("SELECT id, email_verified FROM users WHERE google_id = ? OR email = ?");
            $stmt_check->bind_param("ss", $google_id, $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows == 0) {
                // New user from Google - auto verify
                $stmt_check->close();
                $stmt = $conn->prepare("INSERT INTO users (google_id, name, email, avatar, email_verified) VALUES (?, ?, ?, ?, 1)");
                $stmt->bind_param("ssss", $google_id, $name, $email, $avatar);
                $stmt->execute();
                $userId = $stmt->insert_id;
            } else {
                $row = $result_check->fetch_assoc();
                $userId = $row['id'];
                $verified = $row['email_verified'];

                // If user exists but no google_id, update it (link account)
                if (empty($row['google_id'])) {
                    $stmt_check->close();
                    $stmt = $conn->prepare("UPDATE users SET google_id = ?, name = ?, avatar = ?, email_verified = 1 WHERE id = ?");
                    $stmt->bind_param("sssi", $google_id, $name, $avatar, $userId);
                    $stmt->execute();
                } else {
                    $stmt_check->close();
                }
            }

            // Login
            $_SESSION['user'] = [
                'id' => $userId,
                'google_id' => $google_id,
                'name' => $name,
                'email' => $email,
                'picture' => $avatar,
            ];
            header("Location: dashboard.php");
            exit();
        }
    }
}
// Nếu có lỗi (không có access_token hoặc user info), chuyển về login
header("Location: login.php");
exit();