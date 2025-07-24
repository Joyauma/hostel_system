<?php
session_start();
require_once '../includes/alerts.php';
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect_with_error("Unauthorized access", "/hostel_system/index.php");
}

$pageTitle = "Student Management";
ob_start();

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

try {
    $query = "SELECT s.id, s.name, s.phone, s.status, u.email, r.room_no,
                     ra.created_at as room_assigned_date
              FROM students s
              LEFT JOIN users u ON s.user_id = u.id
              LEFT JOIN room_allocations ra ON s.id = ra.student_id 
                   AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
              LEFT JOIN rooms r ON ra.room_id = r.id
              ORDER BY s.id DESC";
    $stmt = $conn->query($query);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error loading students: " . e($e->getMessage()) . "</div>";
    $students = [];
}

try {
    $roomsQuery = "SELECT r.id, r.room_no, r.capacity, 
                          COUNT(DISTINCT CASE 
                              WHEN ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE 
                              THEN ra.student_id 
                              END
                          ) as occupied
                   FROM rooms r
                   LEFT JOIN room_allocations ra ON r.id = ra.room_id
                   GROUP BY r.id, r.room_no, r.capacity
                   HAVING occupied < capacity OR occupied IS NULL
                   ORDER BY r.room_no";
    $roomsStmt = $conn->query($roomsQuery);
    $availableRooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error loading rooms: " . e($e->getMessage()) . "</div>";
    $availableRooms = [];
}
?>

<style>
.table .btn {
    margin: 0 2px;
    padding: 0.25rem 0.5rem;
}
.table .btn i {
    font-size: 0.875rem;
}
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Student Management</h2>
        <button type="button" class="btn btn-primary" onclick="resetForm()" data-bs-toggle="modal" data-bs-target="#studentModal">
            <i class="bi bi-person-plus"></i> Add New Student
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Room</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="6" class="text-center">No students found</td></tr>
                <?php else: foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo e($student['id']); ?></td>
                        <td><?php echo e($student['name']); ?></td>
                        <td><?php echo e($student['email']); ?></td>
                        <td><?php echo e($student['phone']); ?></td>
                        <td><?php echo $student['room_no'] ? 'Room ' . e($student['room_no']) : 'Not Assigned'; ?></td>
                        <td class="text-center">
                            <button class="btn btn-info btn-sm" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="editStudent(<?php echo $student['id']; ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?php echo $student['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Student Modal for Add/Edit -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="studentForm" method="post" action="../api/students_new.php">
                <div class="modal-body">
                    <input type="hidden" id="student_id" name="student_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="room" class="form-label">Room</label>
                            <select class="form-control" id="room" name="room_id">
                                <option value="">Select Room</option>
                                <?php foreach ($availableRooms as $room): ?>
                                    <option value="<?php echo e($room['id']); ?>">
                                        Room <?php echo e($room['room_no']); ?> (<?php echo e($room['occupied']); ?>/<?php echo e($room['capacity']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="course" class="form-label">Course</label>
                            <input type="text" class="form-control" id="course" name="course">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year_of_study" class="form-label">Year of Study</label>
                            <input type="number" class="form-control" id="year_of_study" name="year_of_study" min="1" max="6">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        <div class="col-md-12"><h5>Guardian Information</h5></div>
                        <div class="col-md-6 mb-3">
                            <label for="guardian_name" class="form-label">Guardian Name</label>
                            <input type="text" class="form-control" id="guardian_name" name="guardian_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="guardian_phone" class="form-label">Guardian Phone</label>
                            <input type="tel" class="form-control" id="guardian_phone" name="guardian_phone">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="guardian_address" class="form-label">Guardian Address</label>
                            <textarea class="form-control" id="guardian_address" name="guardian_address" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewStudentModalLabel">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr><th width="35%">Name</th><td id="view_name"></td></tr>
                    <tr><th>Email</th><td id="view_email"></td></tr>
                    <tr><th>Phone</th><td id="view_phone"></td></tr>
                    <tr><th>Gender</th><td id="view_gender"></td></tr>
                    <tr><th>Date of Birth</th><td id="view_dob"></td></tr>
                    <tr><th>Course</th><td id="view_course"></td></tr>
                    <tr><th>Year of Study</th><td id="view_year"></td></tr>
                    <tr><th>Room</th><td id="view_room"></td></tr>
                    <tr><th>Address</th><td id="view_address"></td></tr>
                    <tr><th>Guardian Name</th><td id="view_guardian_name"></td></tr>
                    <tr><th>Guardian Phone</th><td id="view_guardian_phone"></td></tr>
                    <tr><th>Guardian Address</th><td id="view_guardian_address"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentForm = document.getElementById('studentForm');
    const addStudentModal = document.getElementById('addStudentModal');
    const modal = new bootstrap.Modal(addStudentModal);

    studentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = formData.get('student_id');
        const method = id ? 'PUT' : 'POST';
        try {
            const response = await fetch('../api/students.php', {
                method,
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error saving student');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving student');
        }
        window.viewStudent = async function(studentId) {
    try {
        const response = await fetch('../api/students.php?id=' + studentId);
        if (!response.ok) throw new Error('Network response was not ok');

        const data = await response.json();

        if (data.success) {
            const student = data.data;

            // fill modal student fields(assumes the elements already exist in html)
            document.getElementById('view_name').textContent = student.name;
            document.getElementById('view_email').textContent = student.email;
            document.getElementById('view_phone').textContent = student.phone;
            document.getElementById('view_room').textContent = student.room_id || 'Not assigned';

            // Show the modal
            const viewModalEl = document.getElementById('viewStudentModal');
            const viewModal = new bootstrap.Modal(viewModalEl);
            viewModal.show();
        } else {
            alert('Student not found.');
        }
    } catch (error) {
        console.error('Error fetching student:', error);
        alert('Failed to fetch student details.');
    }
};

    });    window.editStudent = async function(studentId) {
         console.log("Editing student:", studentId);
        try {
            const response = await fetch('../api/students.php?id=' + studentId);
            const data = await response.json();
            if (data.success) {
                const student = data.data;
                document.getElementById('student_id').value = student.id;
                document.getElementById('name').value = student.name;
                document.getElementById('email').value = student.email;
                document.getElementById('phone').value = student.phone;
                document.getElementById('room').value = student.room_id || '';
                document.getElementById('addStudentModalLabel').textContent = 'Edit Student';
                modal.show();
            } else {
                alert('Error fetching student details');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error fetching student');
        }
    }; 
  window.deleteStudent = async function(studentId) {
        if (confirm('Are you sure you want to delete this student?')) {
            try {
                 const formData = new URLSearchParams();
            formData.append('id', studentId);

            const response = await fetch('../api/students.php', {
                method: 'DELETE',
                  headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            });

            const data = await response.json();//debug ouput
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting student');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting student');
            }
        }
    };
});
document.addEventListener('DOMContentLoaded', function() {
    const studentForm = document.getElementById('studentForm');
    const addStudentModal = document.getElementById('addStudentModal');
    const modal = new bootstrap.Modal(addStudentModal);

    studentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        try {
            const response = await fetch('../api/students.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                //  Refresh the page to show the newly added student
                location.reload();
            } else {
                alert(data.message || 'Error saving student');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving student');
        }
    });
});

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentForm = document.getElementById('studentForm');
    const addStudentModal = document.getElementById('addStudentModal');
    const modal = new bootstrap.Modal(addStudentModal);

    studentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        try {
            const response = await fetch('../api/students.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                // Refresh the page to show the newly added student
                location.reload();
            } else {
                alert(data.message || 'Error saving student');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving student');
        }
    });
});
</script>
<?php
$scripts = <<<EOT
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentForm = document.getElementById('studentForm');
    const studentModal = document.getElementById('studentModal');
    const viewStudentModal = document.getElementById('viewStudentModal');
    const modal = new bootstrap.Modal(studentModal);
    const viewModal = new bootstrap.Modal(viewStudentModal);

    // Reset form when adding new student
    window.resetForm = function() {
        studentForm.reset();
        document.getElementById('student_id').value = '';
        document.getElementById('studentModalLabel').textContent = 'Add New Student';
    };

    // View student details
    window.viewStudent = async function(id) {
        try {
            const response = await fetch(`../api/students.php?id=\${id}`);
            const data = await response.json();
            
            if (data.success) {
                const student = data.data;
                document.getElementById('view_name').textContent = student.name || 'N/A';
                document.getElementById('view_email').textContent = student.email || 'N/A';
                document.getElementById('view_phone').textContent = student.phone || 'N/A';
                document.getElementById('view_gender').textContent = student.gender || 'N/A';
                document.getElementById('view_dob').textContent = student.dob || 'N/A';
                document.getElementById('view_course').textContent = student.course || 'N/A';
                document.getElementById('view_year').textContent = student.year_of_study || 'N/A';
                document.getElementById('view_room').textContent = student.room_no ? 
                    `Room \${student.room_no}` : 'Not Assigned';
                document.getElementById('view_address').textContent = student.address || 'N/A';
                document.getElementById('view_guardian_name').textContent = student.guardian_name || 'N/A';
                document.getElementById('view_guardian_phone').textContent = student.guardian_phone || 'N/A';
                document.getElementById('view_guardian_address').textContent = student.guardian_address || 'N/A';
                
                viewModal.show();
            } else {
                alert('Error fetching student details: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error fetching student details');
        }
    };

    // Edit student
    window.editStudent = async function(id) {
        try {
            const response = await fetch(`../api/students.php?id=\${id}`);
            const data = await response.json();
            
            if (data.success) {
                const student = data.data;
                document.getElementById('student_id').value = student.id;
                document.getElementById('name').value = student.name || '';
                document.getElementById('email').value = student.email || '';
                document.getElementById('phone').value = student.phone || '';
                document.getElementById('dob').value = student.dob || '';
                document.getElementById('gender').value = student.gender || '';
                document.getElementById('room').value = student.room_id || '';
                document.getElementById('course').value = student.course || '';
                document.getElementById('year_of_study').value = student.year_of_study || '';
                document.getElementById('address').value = student.address || '';
                document.getElementById('guardian_name').value = student.guardian_name || '';
                document.getElementById('guardian_phone').value = student.guardian_phone || '';
                document.getElementById('guardian_address').value = student.guardian_address || '';
                
                document.getElementById('studentModalLabel').textContent = 'Edit Student';
                modal.show();
            } else {
                alert('Error fetching student details: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error fetching student details');
        }
    };

    // Delete student
    window.deleteStudent = function(id) {
        if (!confirm('Are you sure you want to delete this student?')) return;
        
        $.ajax({
            url: '../api/students.php',
            type: 'DELETE',
            data: { student_id: id },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Error deleting student');
                }
            },
            error: function(xhr, status, error) {
                alert('Error deleting student: ' + error);
            }
        });
            
            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting student: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting student');
        }
    };

    // Handle form submission (Add/Edit)
    $('#studentForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id = formData.get('student_id');
        const method = id ? 'PUT' : 'POST';
        
        $.ajax({
            url: '../api/students.php',
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#studentModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || 'Error saving student');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Error saving student: ' + (xhr.responseJSON?.message || error));
            }
        });
    });
});
</script>
EOT;

$content = ob_get_clean();
require_once '../includes/template.php';
?>
