<?php

class HomeController
{
    public function index(): void
    {
        if (isset($_SESSION['user'])) {
            header("Location: dashboard.php");
            exit();
        }
        view('home/index');
    }

    public function notFound(): void
    {
        http_response_code(404);
        $isLoggedIn = isset($_SESSION['user']);
        view('errors/404', compact('isLoggedIn'));
    }
}
