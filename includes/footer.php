<?php
// Check if we're on a page that has the sidebar
$publicRoutes = ['login', 'signup'];
$currentAction = $path === '' ? 'dashboard' : $path;
?>

<?php if (!in_array($currentAction, $publicRoutes)): ?>
            </main>
        </div>
    </div>
<?php endif; ?>

<!-- Footer Section -->
<footer class="bg-dark text-light py-3 mt-auto">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($config['footer']) ?> </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">
                    <span class="me-3">v <?= date('Y') ?><?= htmlspecialchars($config['version'] ?? '.1.0.0') ?></span>
                    <?php if (isset($_SESSION['username'])): ?>
                        <span class="text-muted"><?= lang('logged_in_as') ?? 'Logged in as' ?>: <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Sidebar Toggle Script -->
<script src="/public/js/sidebar_toggle.js"></script>

<!-- Bootstrap JS -->
<script src="/public/js/bootstrap/bootstrap.bundle.min.js"></script>

</body>
</html>