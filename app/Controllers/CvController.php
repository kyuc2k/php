<?php

class CvController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function view(): void
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            http_response_code(404);
            header('Location: 404.php');
            exit();
        }

        $cvModel = new CvProfile($this->conn);
        $row = $cvModel->findByToken($token);

        if (!$row) {
            http_response_code(404);
            header('Location: 404.php');
            exit();
        }

        $cv = json_decode($row['parsed_data'], true) ?? [];
        $rawText = $row['raw_text'] ?? '';
        $ownerName = $row['owner_name'] ?? '';
        $ownerPicture = $row['owner_picture'] ?? '';

        // Check for extracted photo - match old file logic exactly
        $cvPhotoFile = BASE_PATH . '/uploads/cv_photos/' . $token . '.jpg';
        $cvPhotoUrl = file_exists($cvPhotoFile) ? 'uploads/cv_photos/' . $token . '.jpg' : ($ownerPicture ?? '');

        view('cv/view', compact('cv', 'token', 'cvPhotoUrl', 'ownerName', 'ownerPicture', 'rawText'));
    }
}
