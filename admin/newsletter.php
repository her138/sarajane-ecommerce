<?php
$pageTitle = "Newsletter Subscribers";
require_once '../includes/header.php';
require_once '../includes/admin_check.php';
require_once '../includes/csrf.php';

$csrf_token = generateCSRFToken('admin_newsletter');

// Export CSV
if (isset($_GET['export'])) {
    $stmt = $pdo->query("SELECT email, subscribed_at FROM newsletter_subscribers WHERE is_active = 1 ORDER BY subscribed_at DESC");
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Email', 'Subscribed Date']);
    foreach ($subscribers as $row) {
        fputcsv($output, [$row['email'], $row['subscribed_at']]);
    }
    fclose($output);
    exit;
}

// Handle POST unsubscribe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubscribe'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '', 'admin_newsletter')) {
        die('Invalid security token');
    }
    $id = intval($_POST['unsubscribe']);
    $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: newsletter.php?msg=unsubscribed');
    exit;
}

// Fetch subscribers
$stmt = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Newsletter Subscribers</h1>
        <a href="?export=1" class="btn btn-success"><i class="fas fa-file-export me-2"></i> Export CSV</a>
    </div>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'unsubscribed'): ?>
        <div class="alert alert-success">Subscriber removed.</div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Subscribed Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscribers)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">No subscribers yet.</td>
                            </tr>
                        <?php else: ?>
                        </tr>
                            <?php foreach ($subscribers as $sub): ?>
                                <tr>
                                    <td><?php echo $sub['id']; ?></td>
                                    <td><?php echo htmlspecialchars($sub['email']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($sub['subscribed_at'])); ?></td>
                                    <td>
                                        <?php if ($sub['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($sub['is_active']): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Unsubscribe this email?')">
                                                <input type="hidden" name="unsubscribe" value="<?php echo $sub['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash-alt"></i> Unsubscribe
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>