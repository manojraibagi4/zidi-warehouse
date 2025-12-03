<?php
require_once __DIR__ . '/../../../includes/header.php';

require_once __DIR__ . '/../../config/csrf.php'; // Needed to generate token

$isEdit = isset($user); // The controller sets $user for edit
$formAction = $isEdit ? "/edit_user/" . $user['id'] : "/signup";
?>

<div class="container d-flex justify-content-center">
    <div class="card shadow p-4 my-4" id="signup-container" style="max-width: 600px; width: 100%;">
        <div class="card-body text-center">
            <?php if ($isEdit): ?>
                <i class="bi bi-person-fill-gear text-primary mb-2" style="font-size: 2.5rem;"></i>
            <?php else: ?>
                <i class="bi bi-person-add text-primary mb-2" style="font-size: 2.5rem;"></i>
            <?php endif; ?>
            <h2 class="card-title mb-3" id="signup-title"><?= $isEdit ? lang('edit_user') : lang('signup') ?></h2>

            <div id="response-message"></div>
            <form id="signup-form" method="POST" action="<?= $formAction ?>">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label for="username" class="form-label form-label-sm d-block text-start"><?= lang('username') ?></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input id="username" name="username" type="text" class="form-control" required
                                    value="<?= htmlspecialchars($_POST['username'] ?? ($user['username'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-2">
                            <label for="email" class="form-label form-label-sm d-block text-start"><?= lang('email') ?></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input id="email" name="email" type="email" class="form-control" required
                                    value="<?= htmlspecialchars($_POST['email'] ?? ($user['email'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label for="password" class="form-label form-label-sm d-block text-start"><?= lang('password') ?></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input id="password" name="password" type="password" class="form-control" <?= $isEdit ? '' : 'required' ?> minlength="8">
                            </div>
                            <?php if ($isEdit): ?>
                                <small class="form-text text-muted text-start d-block"><?= lang('leave_blank_to_keep_password') ?></small>
                            <?php else: ?>
                                <small class="form-text text-muted text-start d-block">Minimum 8 characters</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-2">
                            <label for="confirm_password" class="form-label form-label-sm d-block text-start"><?= lang('confirm_password') ?></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input id="confirm_password" name="confirm_password" type="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-2 text-start" id="role-group">
                    <label for="role_id" class="form-label form-label-sm d-block"><?= lang('select_role') ?></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                        <select id="role_id" name="role_id" class="form-select form-select-sm" required>
                            <option value=""><?= lang('choose_role') ?></option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"
                                    <?= (($_POST['role_id'] ?? ($user['role_id'] ?? '')) == $role['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-2 form-check text-start d-flex align-items-center" id="email-noti-group">
                    <input type="checkbox" class="form-check-input me-2" id="email_noti" name="email_noti"
                        <?= (($_POST['email_noti'] ?? ($user['email_noti'] ?? 0)) ? 'checked' : '') ?>>
                    <i class="bi bi-bell-fill text-muted me-2" style="font-size: 1rem;"></i>
                    <label class="form-check-label form-label-sm" for="email_noti">
                        <?= lang('enable_low_stock_email') ?>
                    </label>
                </div>

                <button type="submit" id="signup-submit" class="btn btn-primary btn-sm w-100 mt-2">
                    <?= $isEdit ? lang('update') : lang('signup') ?>
                </button>

                <?php if (!$isEdit): ?>
                    <a href="/login" id="login-link" class="btn btn-link w-100 mt-1">
                        <?= lang('already_have_account_login') ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signup-form');
    let responseMessageContainer = document.getElementById('response-message');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            responseMessageContainer.innerHTML = '';
            
            const alertType = data.success ? 'success' : 'danger';
            const messageHtml = `<div class="alert alert-${alertType} alert-dismissible fade show" role="alert">
                                    ${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>`;
            
            responseMessageContainer.innerHTML = messageHtml;
            
            if (data.success && form.action.includes('action=signup')) {
                form.reset();
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(error => {
            console.error('Error:', error);
            responseMessageContainer.innerHTML = `<div class="alert alert-danger">An unexpected error occurred.</div>`;
        });
    });
});
</script>
<?php if (!$isEdit): ?>
    <?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
<?php endif; ?>
