<?php
function display_alert($messages, $type = 'error') {
    if (empty($messages)) {
        return;
    }

    // Convert single string message to array
    if (!is_array($messages)) {
        $messages = [$messages];
    }

    // Convert URL encoded message strings with || separator
    if (count($messages) === 1 && strpos($messages[0], '||') !== false) {
        $messages = explode('||', urldecode($messages[0]));
    }

    $alertClass = ($type === 'success') ? 'alert-success' : 'alert-danger';
    
    foreach ($messages as $message) {
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

// Function to set error message and redirect
function redirect_with_error($message, $location) {
    if (is_array($message)) {
        $message = implode('||', $message);
    }
    header("Location: " . $location . "?error=" . urlencode($message));
    exit();
}

// Function to set success message and redirect
function redirect_with_success($message, $location) {
    header("Location: " . $location . "?success=" . urlencode($message));
    exit();
}
?>
