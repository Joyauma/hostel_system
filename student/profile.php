<?php
session_start();
require_once '../includes/alerts.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    redirect_with_error("Unauthorized access", "/hostel_system/index.php");
}

$pageTitle = "My Profile";
ob_start();

require_once '../config/database.php';

// Fetch student details
try {
    $stmt = $conn->prepare("SELECT s.*, u.email, u.username 
                           FROM students s 
                           JOIN users u ON s.user_id = u.id 
                           WHERE s.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching student profile: " . $e->getMessage());
    $student = null;
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">My Profile</h5>
                </div>
                <div class="card-body">
                    <?php if ($student): ?>
                    <form id="profileForm" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Registration Number</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['roll']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['username']); ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                            </div>
                            
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <!-- <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" value="<?php echo htmlspecialchars($student['dob']); ?>" required>
                            </div> -->
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender" required>
                                    <option value="Male" <?php echo $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $student['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Course</label>
                                <input type="text" class="form-control" name="course" value="<?php echo htmlspecialchars($student['course']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Year of Study</label>
                                <select class="form-select" name="year_of_study" required>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $student['year_of_study'] == $i ? 'selected' : ''; ?>>Year <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Guardian Name</label>
                                <input type="text" class="form-control" name="guardian_name" value="<?php echo htmlspecialchars($student['guardian_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Guardian Phone</label>
                                <input type="tel" class="form-control" name="guardian_phone" value="<?php echo htmlspecialchars($student['guardian_phone']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Guardian Address</label>
                            <textarea class="form-control" name="guardian_address" rows="3" required><?php echo htmlspecialchars($student['guardian_address']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-primary w-100" onclick="updateProfile()">
                                    Update Profile
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    Change Password
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-danger">
                        Failed to load profile information. Please try again later.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="passwordForm" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="8">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="changePassword()">Update Password</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<'SCRIPT'
<script>
async function updateProfile() {
    const form = document.getElementById('profileForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const formData = new FormData(form);
    try {
        const response = await fetch('../api/update_profile.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Profile updated successfully');
        } else {
            throw new Error(data.message || 'Failed to update profile');
        }
    } catch (error) {
        showAlert('danger', error.message);
    }
}

async function changePassword() {
    const form = document.getElementById('passwordForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const formData = new FormData(form);
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        showAlert('danger', 'New passwords do not match');
        return;
    }

    try {
        const response = await fetch('../api/change_password.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Password changed successfully');
            $('#changePasswordModal').modal('hide');
            form.reset();
        } else {
            throw new Error(data.message || 'Failed to change password');
        }
    } catch (error) {
        showAlert('danger', error.message);
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const cardBody = document.querySelector('.card-body');
    cardBody.insertBefore(alertDiv, cardBody.firstChild);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>
SCRIPT;

include_once '../includes/template.php';
?>
