<?php
require_once __DIR__ . '/../../../includes/header.php';

require_once __DIR__ . '/../../config/csrf.php'; // Load CSRF functions
?>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" id="login-container" style="max-width: 400px; width: 100%;">
        <div class="card-body text-center">
            <i class="bi bi-person-circle text-primary mb-3" style="font-size: 3rem;"></i>
            <h2 class="card-title mb-4" id="login-title"><?= lang('login') ?></h2>

            <div id="response-message">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" id="login-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
            </div>

            <form id="login-form" method="POST" action="/login">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                <div class="mb-3">
                    <label for="username" class="form-label text-start d-block"><?= lang('username') ?></label>
                    <div class="input-group input-group-lg" id="username-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input id="username" name="username" type="text" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label text-start d-block"><?= lang('password') ?></label>
                    <div class="input-group input-group-lg" id="password-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input id="password" name="password" type="password" class="form-control" required>
                    </div>
                </div>

                <button type="submit" id="login-submit" name="login" class="btn btn-primary btn-lg w-100">
                    <span id="button-text"><?= lang('login') ?></span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="spinner"></span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const responseMessage = document.getElementById('response-message');
    const loginButton = document.getElementById('login-submit');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show spinner and disable button
        loginButton.disabled = true;
        buttonText.textContent = '<?= lang('logging_in') ?>'; // Or 'Loading...'
        spinner.classList.remove('d-none');

        const formData = new FormData(loginForm);

        fetch(loginForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // Re-enable button and hide spinner on response
            loginButton.disabled = false;
            buttonText.textContent = '<?= lang('login') ?>';
            spinner.classList.add('d-none');
            
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                responseMessage.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            }
        })
        .catch(error => {
            // Re-enable button and hide spinner on error
            loginButton.disabled = false;
            buttonText.textContent = '<?= lang('login') ?>';
            spinner.classList.add('d-none');

            responseMessage.innerHTML = `<div class="alert alert-danger"><?= lang('request_failed') ?></div>`;
            console.error('Error:', error);
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>