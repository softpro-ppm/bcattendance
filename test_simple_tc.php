<?php
$pageTitle = 'Test TC Page';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Test TC Page']
];

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Test TC Page</h3>
            </div>
            <div class="card-body">
                <p>This is a test page to see if the basic structure loads.</p>
                <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
                <p>PHP version: <?php echo PHP_VERSION; ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
