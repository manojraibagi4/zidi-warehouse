<?php
class User {
    public $id;
    public $username;
    public $email;
    public $password;
    public $role_id;

    public function __construct($id, $username, $email, $password, $role_id) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->role_id = $role_id;
    }
}
