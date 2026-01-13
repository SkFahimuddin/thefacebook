<?php
/* delete_message.php */
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$current_user = get_logged_in_user();

if (isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    
    // Verify that the current user is either sender or receiver
    $check_query = "SELECT * FROM messages 
                    WHERE id = $message_id 
                    AND (sender_id = {$current_user['id']} OR receiver_id = {$current_user['id']})";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $delete_query = "DELETE FROM messages WHERE id = $message_id";
        mysqli_query($conn, $delete_query);
    }
}

header("Location: messages.php");
exit();
?>