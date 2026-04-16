<?php

class ActivityController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function index(): void
    {
        Auth::check($this->conn);

        $user   = Auth::user();
        $userId = Auth::userId();

        $logModel = new ActivityLog($this->conn);

        // Pagination
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        // Filter
        $filterAction = $_GET['action_filter'] ?? '';

        $totalLogs  = $logModel->countByUser($userId, $filterAction);
        $totalPages = max(1, ceil($totalLogs / $perPage));
        $logs       = $logModel->getByUser($userId, $filterAction, $perPage, $offset);
        $summary    = $logModel->getSummaryByUser($userId);

        // Action labels & icons
        $actionMap = [
            'login'           => ['Đăng nhập', 'fa-sign-in-alt', '#28a745'],
            'login_google'    => ['Đăng nhập Google', 'fa-google', '#4285f4'],
            'login_failed'    => ['Đăng nhập thất bại', 'fa-times-circle', '#dc3545'],
            'signup'          => ['Đăng ký', 'fa-user-plus', '#17a2b8'],
            'signup_google'   => ['Đăng ký Google', 'fa-google', '#4285f4'],
            'change_password' => ['Đổi mật khẩu', 'fa-key', '#ffc107'],
            'forgot_password' => ['Quên mật khẩu', 'fa-envelope', '#fd7e14'],
            'reset_password'  => ['Đặt lại mật khẩu', 'fa-lock-open', '#e83e8c'],
            'upload_file'     => ['Upload file', 'fa-cloud-upload-alt', '#667eea'],
            'delete_file'     => ['Xóa file', 'fa-trash-alt', '#dc3545'],
            'upgrade_plan'    => ['Nâng cấp gói', 'fa-crown', '#f5a623'],
        ];

        view('activity/index', compact(
            'user', 'logs', 'summary', 'actionMap', 'filterAction',
            'page', 'totalPages', 'totalLogs', 'perPage'
        ));
    }
}
