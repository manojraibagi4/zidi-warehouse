<?php
// ... existing require_once statements
require_once __DIR__ . '/../../includes/lang.php'; // <-- Load translations
require_once __DIR__ . '/../models/UserRepository.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/validation.php';

class AuthController {
    private $repo;
    private $isAjax;

    public function __construct($conn) {
        $this->repo = new UserRepository($conn);
        $this->isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function renderView($view, $data = []) {
        extract($data);
        include __DIR__ . '/../views/Auth/' . $view . '.php';
    }

    private function sendJsonResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit();
    }

    public function login()
    {
        $error = "";
        $roles = $this->repo->getRoles();

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                if ($this->isAjax) {
                    $this->sendJsonResponse(false, lang("csrf_failed") ?? "Security validation failed.");
                } else {
                    $error = lang("csrf_failed") ?? "Security validation failed.";
                    $this->renderView('login', ['error' => $error, 'roles' => $roles]);
                }
                return;
            }

            $errors = validateLogin($_POST);
            if (!empty($errors)) {
                $error = implode("<br>", $errors);
                if ($this->isAjax) {
                    $this->sendJsonResponse(false, $error);
                } else {
                    $this->renderView('login', ['error' => $error, 'roles' => $roles]);
                }
                return;
            }

            $username = trim($_POST["username"]);
            $password = $_POST["password"];

            $role_id_from_db = $this->repo->getRoleIdByUsername($username);
            $role = $this->repo->getRoleById($role_id_from_db);
            
            $user = $this->repo->getUserByCredentials($username, $role_id_from_db);
            if ($user && password_verify($password, $user["password"])) {
                $_SESSION["username"] = $username;
                $_SESSION["role_id"] = $role_id_from_db;
                $_SESSION["role"] = $role;
                $_SESSION["user_id"] = $user["id"];
                
                if ($this->isAjax) {
                    // Update to the new clean URL for dashboard
                    $this->sendJsonResponse(true, lang("login_successful"), ['redirect' => '/dashboard']);
                } else {
                    // Update to the new clean URL for dashboard
                    header("Location: /dashboard");
                    exit();
                }
            } else {
                $error = lang("invalid_credentials");
                if ($this->isAjax) {
                    $this->sendJsonResponse(false, $error);
                } else {
                    $this->renderView('login', ['error' => $error, 'roles' => $roles]);
                }
            }
        } else {
            // Initial page load for GET request
            $this->renderView('login', ['error' => $error, 'roles' => $roles]);
        }
    }

    public function signup()
    {
        // For non-AJAX, we still need to redirect if not admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
            if ($this->isAjax) {
                $this->sendJsonResponse(false, lang('access_denied'));
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('access_denied')];
                // Update to the new clean URL
                header("Location: /dashboard");
                exit();
            }
        }

        $roles = $this->repo->getRoles();
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                if ($this->isAjax) {
                    $this->sendJsonResponse(false, lang("csrf_failed") ?? "Security validation failed.");
                } else {
                    $error = lang("csrf_failed") ?? "Security validation failed.";
                }
            } else {
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'] ?? '';
                $role_id = intval($_POST['role_id'] ?? 0);
                $email_noti = isset($_POST['email_noti']) ? 1 : 0;

                if ($this->repo->getUserByUsername($username)) {
                    $error = lang("username_exists") ?? "Username already taken.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = lang("invalid_email") ?? "Invalid email format.";
                } elseif ($this->repo->getUserByEmail($email)) {
                    $error = lang("email_exists") ?? "Email already registered.";
                } elseif ($password !== $confirm_password) {
                    $error = lang("password_mismatch");
                } elseif (!in_array($role_id, array_column($roles, 'id'))) {
                    $error = lang("invalid_role");
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    if ($this->repo->createUser($username, $email, $hashedPassword, $role_id, $email_noti)) {
                        $success = lang("signup_success");
                    } else {
                        $error = lang("signup_error");
                    }
                }
            }

            if ($this->isAjax) {
                if ($success) {
                    $this->sendJsonResponse(true, $success);
                } else {
                    $this->sendJsonResponse(false, $error);
                }
            }
        }

        // Only render the view for initial GET request or non-AJAX POST errors
        if (!$this->isAjax) {
            $this->renderView('signup', ['roles' => $roles, 'error' => $error, 'success' => $success]);
        }
    }


    public function users()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
            if ($this->isAjax) {
                // Update to the new clean URL
                $this->sendJsonResponse(false, lang('access_denied'), ['redirect' => '/dashboard']);
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('access_denied')];
                // Update to the new clean URL
                header("Location: /dashboard");
                exit();
            }
        }

        $users = $this->repo->getAllUsers();
        // The view will handle the session message
        $this->renderView('users', ['users' => $users]);
    }

    public function editUser()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
            if ($this->isAjax) {
                // Update to the new clean URL
                $this->sendJsonResponse(false, lang('access_denied'), ['redirect' => '/dashboard']);
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('access_denied')];
                // Update to the new clean URL
                header("Location: /dashboard");
                exit();
            }
        }

        $roles = $this->repo->getRoles();
        global $id;
        $id = (int)$id;
        $user = $this->repo->getUserById($id);

        if (!$user) {
            if ($this->isAjax) {
                // Update to the new clean URL
                $this->sendJsonResponse(false, lang('user_not_found'), ['redirect' => '/users']);
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('user_not_found')];
                // Update to the new clean URL
                header("Location: /users");
                exit();
            }
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                if ($this->isAjax) {
                    $this->sendJsonResponse(false, lang("csrf_failed"));
                } else {
                    $error = lang("csrf_failed");
                }
            } else {
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $role_id = intval($_POST['role_id'] ?? 0);
                $email_noti = isset($_POST['email_noti']) ? 1 : 0;
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if ($password && $password !== $confirm_password) {
                    $error = lang("password_mismatch");
                } else {
                    $hashedPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
                    if ($this->repo->updateUser($id, $username, $email, $hashedPassword, $role_id, $email_noti)) {
                        $success = lang("user_updated");
                        $user = $this->repo->getUserById($id); // Reload user data
                    } else {
                        $error = lang("update_failed");
                    }
                }
            }

            if ($this->isAjax) {
                if ($success) {
                    $this->sendJsonResponse(true, $success);
                } else {
                    $this->sendJsonResponse(false, $error);
                }
            }
        }

        if (!$this->isAjax) {
            $this->renderView('signup', ['user' => $user, 'roles' => $roles, 'error' => $error, 'success' => $success]);
        }
    }


    public function deleteUser()
    {
        // For AJAX requests, we'll return JSON messages instead of setting session messages.
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
            if ($this->isAjax) {
                $this->sendJsonResponse(false, lang('access_denied'));
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('access_denied')];
                // Update to the new clean URL
                header("Location: /dashboard");
            }
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjax) {
                $this->sendJsonResponse(false, lang('invalid_request_method'));
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('invalid_request_method')];
                // Update to the new clean URL
                header("Location: /users");
            }
            exit();
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            if ($this->isAjax) {
                $this->sendJsonResponse(false, lang('csrf_failed'));
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('csrf_failed')];
                // Update to the new clean URL
                header("Location: /users");
            }
            exit();
        }

        global $id;
        $id = (int)$id;
        if ($id <= 0) {
            if ($this->isAjax) {
                $this->sendJsonResponse(false, lang('invalid_user_id'));
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('invalid_user_id')];
                // Update to the new clean URL
                header("Location: /users");
            }
            exit();
        }

        if ($id == ($_SESSION['user_id'] ?? 0)) {
            if ($this->isAjax) {
                $this->sendJsonResponse(false, lang('cannot_delete_self'));
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('cannot_delete_self')];
                // Update to the new clean URL
                header("Location: /users");
            }
            exit();
        }

        if ($this->repo->deleteUser($id)) {
            if ($this->isAjax) {
                $this->sendJsonResponse(true, lang('user_deleted'));
            } else {
                $_SESSION['message'] = ['type' => 'success', 'text' => lang('user_deleted')];
                // Update to the new clean URL
                header("Location: /users");
            }
        } else {
            if ($this->isAjax) {
                $this->sendJsonResponse(false, lang('delete_failed'));
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => lang('delete_failed')];
                // Update to the new clean URL
                header("Location: /users");
            }
        }
        exit();
    }


    public function logout() {
        session_unset();
        session_destroy();
        // Update to the new clean URL for login
        header("Location: /login");
        exit();
    }
}