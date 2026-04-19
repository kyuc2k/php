<!DOCTYPE html>
<html>
<head>
    <title>Admin - Create User</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 50px; }
        form { max-width: 300px; margin: 0 auto; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background: #218838; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <form method="post">
        <h2>Create User</h2>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Create User</button>
    </form>
</body>
</html>
