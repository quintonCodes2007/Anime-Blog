<?php
require '../config/bootstrap.php';
require '../config/session-timout.php';
include 'partials/header.php';

if (isset($_SESSION['user-id'])) {

    $current_user_id = $_SESSION['user-id'];

    $query = "SELECT * FROM users WHERE id=$current_user_id";
    $result = mysqli_query($connection, $query);
    $user = mysqli_fetch_assoc($result);
} else {
    header('location: ' . ROOT_URL . 'admin/index.php');
    die();
}
?>


<section class="form__section">
    <div class="container form__section-container">

        <h2>Edit My Details</h2>

        <?php if (isset($_SESSION['edit-user'])) : ?>
            <div class="alert__message error">
                <p>
                    <?= $_SESSION['edit-user'];
                    unset($_SESSION['edit-user']);
                    ?>
                </p>
            </div>
        <?php endif ?>

        <form action="<?= ROOT_URL ?>admin/edit-account-logic.php" method="POST">

            <input type="hidden" name="id" value="<?= $user['id'] ?>">

            <input type="text" name="firstname" value="<?= $user['firstname'] ?>" placeholder="First Name">

            <input type="text" name="lastname" value="<?= $user['lastname'] ?>" placeholder="Last Name">

            <input type="text" name="username" value="<?= $user['username'] ?>" placeholder="Username">

            <input type="email" name="email" value="<?= $user['email'] ?>" placeholder="Email">

            <input type="password" name="oldPassword" placeholder="Current Password">

            <input type="password" name="newPassword" placeholder="New Password">

            <input type="password" name="confirmNewPassword" placeholder="Confirm New Password">

            <button type="submit" name="submit" class="btn">
                Update
            </button>

        </form>

    </div>
</section>

<?php include '../partials/footer.php'; ?>