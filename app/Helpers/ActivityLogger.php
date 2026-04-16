<?php

class ActivityLogger
{
    private static ?ActivityLog $model = null;

    private static function model(mysqli $conn): ActivityLog
    {
        if (!self::$model) {
            self::$model = new ActivityLog($conn);
        }
        return self::$model;
    }

    public static function log(mysqli $conn, ?int $userId, string $action, string $details = ''): void
    {
        self::model($conn)->log($userId, $action, $details);
    }
}
