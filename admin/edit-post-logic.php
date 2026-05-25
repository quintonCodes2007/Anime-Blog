<?php
require '../config/bootstrap.php';
require '../config/session-timout.php';
require 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Make sure edit post button was clicked
if (isset($_POST['submit'])) {

    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $previous_thumbnail_name = filter_var($_POST['previous_thumbnail_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $body = filter_var($_POST['body'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $category_id = filter_var($_POST['category_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $thumbnail = $_FILES['thumbnail'];

    $thumbnail_name = null;

    // Validate input
    if (!$title) {
        $_SESSION['edit-post'] = "Enter post title";
    } elseif (!$category_id) {
        $_SESSION['edit-post'] = "Select post category";
    } elseif (!$body) {
        $_SESSION['edit-post'] = "Enter post body";
    } else {

        // If new thumbnail uploaded
        if ($thumbnail['name']) {

            $previous_thumbnail_path = '../images/' . $previous_thumbnail_name;

            if ($previous_thumbnail_name && file_exists($previous_thumbnail_path)) {
                unlink($previous_thumbnail_path);
            }

            // Rename image
            $time = time();
            $thumbnail_name = $time . '_' . $thumbnail['name'];
            $thumbnail_tmp_name = $thumbnail['tmp_name'];
            $thumbnail_destination_path = '../images/' . $thumbnail_name;

            $allowed_files = ['jpg', 'png', 'jpeg'];
            $extension = strtolower(pathinfo($thumbnail_name, PATHINFO_EXTENSION));

            if (in_array($extension, $allowed_files)) {

                if ($thumbnail['size'] < 2000000) {

                    if (!move_uploaded_file($thumbnail_tmp_name, $thumbnail_destination_path)) {
                        $_SESSION['edit-post'] = "Failed to upload thumbnail";
                    }
                } else {
                    $_SESSION['edit-post'] = "File size too large (max 2MB)";
                }
            } else {
                $_SESSION['edit-post'] = "File should be png, jpg, or jpeg";
            }
        }
    }

    // Redirect back if validation fails
    if (isset($_SESSION['edit-post'])) {

        $_SESSION['edit-post-data'] = $_POST;
        header('location: ' . ROOT_URL . 'admin/edit-post.php?id=' . $id);
        die();
    } else {

        // If this post is featured, reset all others
        if ($is_featured == 1) {

            $stmt_reset = mysqli_prepare($connection, "UPDATE posts SET is_featured = 0");

            if (!$stmt_reset) {
                $_SESSION['edit-post'] = "Prepare failed: " . mysqli_error($connection);
                header('location: ' . ROOT_URL . 'admin/edit-post.php?id=' . $id);
                die();
            }

            if (!mysqli_stmt_execute($stmt_reset)) {
                $_SESSION['edit-post'] = "Execution failed: " . mysqli_stmt_error($stmt_reset);
                header('location: ' . ROOT_URL . 'admin/edit-post.php?id=' . $id);
                die();
            }

            mysqli_stmt_close($stmt_reset);
        }

        // Keep old thumbnail if no new one uploaded
        $thumbnail_to_insert = $thumbnail_name ?? $previous_thumbnail_name;

        // Main UPDATE prepared statement
        $stmt = mysqli_prepare(
            $connection,
            "UPDATE posts 
             SET title = ?, 
                 body = ?, 
                 thumbnail = ?, 
                 category_id = ?, 
                 is_featured = ? 
             WHERE id = ? 
             LIMIT 1"
        );

        if (!$stmt) {
            $_SESSION['edit-post'] = "Prepare failed: " . mysqli_error($connection);
            header('location: ' . ROOT_URL . 'admin/edit-post.php?id=' . $id);
            die();
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssiii",
            $title,
            $body,
            $thumbnail_to_insert,
            $category_id,
            $is_featured,
            $id
        );

        if (!mysqli_stmt_execute($stmt)) {
            $_SESSION['edit-post'] = "Execution failed: " . mysqli_stmt_error($stmt);
            header('location: ' . ROOT_URL . 'admin/edit-post.php?id=' . $id);
            die();
        }

        mysqli_stmt_close($stmt);

        if (mysqli_affected_rows($connection) >= 0) {
            $_SESSION['edit-post-success'] = "Post updated successfully";
        } else {
            $_SESSION['edit-post'] = "No changes made to post";
        }
    }
} else {
    $_SESSION['edit-post'] = "Unauthorized access";
}

header('location: ' . ROOT_URL . 'admin/');
die();
