<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
<div class="container-fluid py-4" id="userManagementContainer">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center rounded-top">
                    <h1 class="h2 mb-0 py-2" id="userManagementTitle">
                        <i class="bi bi-people me-2"></i><?= lang('users_management') ?>
                    </h1>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-start align-items-center mb-4">
                        <a href="/signup" class="btn btn-primary" id="signupNewUserBtn">
                            <i class="bi bi-person-plus me-2"></i> <?= lang('sign_up_new_user') ?>
                        </a>
                    </div>
                    
                    <div id="response-message" class="mt-3">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($_SESSION['message']['text']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['message']); ?>
                        <?php endif; ?>
                    </div>
                    
                    <h2 class="h4 mt-4 mb-3" id="listOfUsersHeading"><?= lang('list_of_users') ?></h2>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle" id="usersTable">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col"><?= lang('username') ?></th>
                                    <th scope="col"><?= lang('email') ?></th>
                                    <th scope="col"><?= lang('role') ?></th>
                                    <th scope="col"><?= lang('email_notify') ?></th>
                                    <th scope="col"><?= lang('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr id="userRow_<?= htmlspecialchars($user['id']) ?>">
                                        <td id="userName_<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['username']) ?></td>
                                        <td id="userEmail_<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['email']) ?></td>
                                        <td id="userRole_<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['role']) ?></td>
                                        <td id="userEmailNoti_<?= htmlspecialchars($user['id']) ?>">
                                            <?php if ($user['email_noti']): ?>
                                                <i class="bi bi-check-circle-fill text-success" title="Yes"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle-fill text-danger" title="No"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="/edit_user/<?= htmlspecialchars($user['id']) ?>"
                                                   class="btn btn-sm btn-warning me-2"
                                                   title="<?= lang('edit') ?>"
                                                   id="editUserBtn_<?= htmlspecialchars($user['id']) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST"
                                                      action="/delete_user/<?= htmlspecialchars($user['id']) ?>"
                                                      class="d-inline delete-user-form"
                                                      id="deleteUserForm_<?= htmlspecialchars($user['id']) ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <button type="submit"
                                                            class="btn btn-sm btn-danger"
                                                            title="<?= lang('delete') ?>"
                                                            id="deleteUserBtn_<?= htmlspecialchars($user['id']) ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-user-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            // Updated logic to extract the ID from the new clean URL
            const urlParts = form.action.split('/');
            const userId = urlParts[urlParts.length - 1]; 
            const confirmMessage = "<?= lang('confirm_delete') ?>";

            if (confirm(confirmMessage)) {
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
                    const messageContainer = document.getElementById('response-message');
                    // Clear previous messages
                    messageContainer.innerHTML = ''; 
                    
                    if (data.success) {
                        const row = document.getElementById(`userRow_${userId}`);
                        if (row) {
                            row.remove();
                        }
                        messageContainer.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                                                            ${data.message}
                                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                        </div>`;
                    } else {
                        messageContainer.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                            ${data.message}
                                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                        </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const messageContainer = document.getElementById('response-message');
                    messageContainer.innerHTML = `<div class="alert alert-danger">An unexpected error occurred.</div>`;
                });
            }
        });
    });
});
</script>
<?php endif; ?>