<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .container-list { margin-top: 20px; }
        .container-item { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px;
            background: #f9f9f9;
        }
        .container-item h3 { margin: 0 0 10px 0; }
        .actions { margin-top: 10px; }
        .actions a { 
            margin-right: 10px; 
            text-decoration: none; 
            padding: 5px 10px;
            border-radius: 3px;
        }
        .btn-start { background: #28a745; color: white; }
        .btn-stop { background: #dc3545; color: white; }
        .btn-open { background: #007bff; color: white; }
        .btn-create { background: #007bff; color: white; text-decoration: none; padding: 10px 20px; }
        .status { font-weight: bold; }
        .status.running { color: green; }
        .status.stopped { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard</h1>
        <a href="/logout" style="text-decoration: none;">Logout</a>
    </div>
    
    <a href="/vm/create" class="btn-create">Create VPS</a>
    
    <hr>
    
    <div class="container-list">
        <?php if (empty($instances)): ?>
            <p>No instances found. <a href="/vm/create">Create your first VPS</a></p>
        <?php else: ?>
            <?php foreach ($instances as $row): ?>
                <div class="container-item">
                    <h3>Container: <?= htmlspecialchars($row['container_name']) ?></h3>
                    <p>Status: <span class="status <?= $row['status'] ?>"><?= htmlspecialchars($row['status']) ?></span></p>
                    <div class="actions">
                        <a href="/vm/start?id=<?= $row['id'] ?>" class="btn-start">Start</a>
                        <a href="/vm/stop?id=<?= $row['id'] ?>" class="btn-stop">Stop</a>
                        <a href="http://103.245.236.153:<?= $row['port'] ?>/vnc.html" target="_blank" class="btn-open">Open VPS</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
