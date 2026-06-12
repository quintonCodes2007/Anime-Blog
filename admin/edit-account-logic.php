<?php
require '../config/bootstrap.php';
require '../config/session-timout.php';
require '../config/database.php';

if (isset($_POST['submit'])) {

    $id = $_SESSION['user-id'];

    $firstname = filter_var($_POST['firstname'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $lastname = filter_var($_POST['lastname'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $username = filter_var($_POST['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    $oldPassword = $_POST['oldPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmNewPassword = $_POST['confirmNewPassword'];

    // Basic validation
    if (!$firstname) {

        $_SESSION['edit-user'] = "Please enter your first name.";
    } elseif (!$lastname) {

        $_SESSION['edit-user'] = "Please enter your last name.";
    } elseif (!$username) {

        $_SESSION['edit-user'] = "Please enter your username.";
    } elseif (!$email) {

        $_SESSION['edit-user'] = "Please enter a valid email.";
    } else {

        // Get current user
        $user_query = "SELECT * FROM users WHERE id=$id LIMIT 1";
        $user_result = mysqli_query($connection, $user_query);
        $user = mysqli_fetch_assoc($user_result);

        // Default = keep existing password
        $hashed_password = $user['password'];

        // User wants to change password
        if (!empty($oldPassword) || !empty($newPassword) || !empty($confirmNewPassword)) {

            if (!password_verify($oldPassword, $user['password'])) {

                $_SESSION['edit-user'] = "Current password is incorrect.";
            } elseif (strlen($newPassword) < 8) {

                $_SESSION['edit-user'] = "New password must be at least 8 characters.";
            } elseif ($newPassword !== $confirmNewPassword) {

                $_SESSION['edit-user'] = "New passwords do not match.";
            } else {

                $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }

        // Check username/email only if no errors
        if (!isset($_SESSION['edit-user'])) {

            $check_query = "SELECT * FROM users
                            WHERE (username='$username' OR email='$email')
                            AND id != $id";

            $check_result = mysqli_query($connection, $check_query);

            if (mysqli_num_rows($check_result) > 0) {

                $_SESSION['edit-user'] = "Username or email already exists.";
            } else {

                $update_query = "UPDATE users SET
                                    firstname='$firstname',
                                    lastname='$lastname',
                                    username='$username',
                                    email='$email',
                                    password='$hashed_password'
                                WHERE id=$id
                                LIMIT 1";

                $update_result = mysqli_query($connection, $update_query);

                if (mysqli_errno($connection)) {

                    $_SESSION['edit-user'] = "Failed to update account.";
                } else {

                    $_SESSION['edit-user-success'] = "Account updated successfully.";

                    header('location: ' . ROOT_URL . 'admin/index.php');
                    die();
                }
            }
        }
    }

    header('location: ' . ROOT_URL . 'admin/edit-account.php');
    die();
} else {

    header('location: ' . ROOT_URL . 'admin/edit-account.php');
    die();
}
