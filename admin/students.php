<?php
session_start();
require_once '../includes/alerts.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect_with_error("Unauthorized access", "/hostel_system/index.php");
}

$pageTitle = "Student Management";
ob_start();
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Student Management</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-person-plus"></i> Add New Student
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search students...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="courseFilter">
                    <option value="">All Courses</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="yearFilter">
                    <option value="">All Years</option>
                    <option value="1">Year 1</option>
                    <option value="2">Year 2</option>
                    <option value="3">Year 3</option>
                    <option value="4">Year 4</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>                <th>Roll</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Room</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTable">
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center" id="pagination">
            </ul>
        </nav>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                            <div class="invalid-feedback">Please enter the student's full name</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" pattern="[0-9]{10}" required>
                            <div class="invalid-feedback">Please enter a 10-digit phone number</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select class="form-select" name="course" required>
                                <option value="">Select Course</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech">M.Tech</option>
                                <option value="BCA">BCA</option>
                                <option value="MCA">MCA</option>
                            </select>
                            <div class="invalid-feedback">Please select a course</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year of Study</label>
                            <select class="form-select" name="year_of_study" required>
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <div class="invalid-feedback">Please select year of study</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select gender</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" required>
                            <div class="invalid-feedback">Please enter date of birth</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Room Assignment</label>
                            <select class="form-select" name="room_id" id="addRoomSelect">
                                <option value="">Select Room</option>
                            </select>
                            <div class="form-text">Only available rooms are shown</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2" required></textarea>
                        <div class="invalid-feedback">Please enter address</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" class="form-control" name="guardian_name" required>
                            <div class="invalid-feedback">Please enter guardian's name</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Guardian Phone</label>
                            <input type="tel" class="form-control" name="guardian_phone" pattern="[0-9]{10}" required>
                            <div class="invalid-feedback">Please enter a valid 10-digit phone number</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Guardian Address</label>
                            <textarea class="form-control" name="guardian_address" rows="2" required></textarea>
                            <div class="invalid-feedback">Please enter guardian's address</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" form="addStudentForm">Add Student</button>
            </div>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="viewStudentDetails">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editStudentForm" class="needs-validation" novalidate>
                    <input type="hidden" name="student_id" id="editStudentId">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                            <div class="invalid-feedback">Please enter the full name</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" pattern="[0-9]{10}" required>
                            <div class="invalid-feedback">Please enter a valid 10-digit phone number</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select gender</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select class="form-select" name="course" required>
                                <option value="">Select Course</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech">M.Tech</option>
                                <option value="BCA">BCA</option>
                                <option value="MCA">MCA</option>
                            </select>
                            <div class="invalid-feedback">Please select a course</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Room Assignment</label>
                            <select class="form-select" name="room_id" id="editRoomSelect">
                                <option value="">Select Room</option>
                            </select>
                            <div class="form-text">Only available rooms are shown</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Year of Study</label>
                            <select class="form-select" name="year_of_study" required>
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <div class="invalid-feedback">Please select year of study</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback">Please select the status</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2" required></textarea>
                            <div class="invalid-feedback">Please enter the address</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" class="form-control" name="guardian_name" required>
                            <div class="invalid-feedback">Please enter guardian's name</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Guardian Phone</label>
                            <input type="tel" class="form-control" name="guardian_phone" pattern="[0-9]{10}" required>
                            <div class="invalid-feedback">Please enter a valid 10-digit phone number</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Guardian Address</label>
                            <textarea class="form-control" name="guardian_address" rows="2" required></textarea>
                            <div class="invalid-feedback">Please enter guardian's address</div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<'SCRIPT'
<script>
// Function to load and display students
async function loadStudents() {
    try {
        const searchTerm = document.getElementById('searchInput').value;
        const course = document.getElementById('courseFilter').value;
        const year = document.getElementById('yearFilter').value;
        const status = document.getElementById('statusFilter').value;

        // Show loading state in table
        document.getElementById('studentsTable').innerHTML = `
            <tr>
                <td colspan="8" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `;

        // Build query parameters
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (course) params.append('course', course);
        if (year) params.append('year', year);
        if (status) params.append('status', status);

        const response = await fetch(`../api/students.php?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            if (!course) { // Only update course filter if no course is selected
                updateCourseFilter(data.data);
            }
            displayStudents(data.data);
        } else {
            showAlert('error', 'Failed to load students: ' + data.message);
        }
    } catch (error) {
        showAlert('error', 'Error loading students: ' + error.message);
    }
}

// Function to display students in the table
function displayStudents(students) {
    const tableBody = document.getElementById('studentsTable');
    
    if (!students || students.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center">No students found</td>
            </tr>
        `;
        return;
    }    tableBody.innerHTML = students.map(student => `
        <tr>
            <td>${student.roll || 'N/A'}</td>
            <td>${student.name}</td>
            <td>${student.course}</td>
            <td>Year ${student.year_of_study}</td>
            <td>${student.room_no || 'Not Assigned'}</td>
            <td>${student.phone}</td>
            <td>
                <span class="badge bg-${student.status === 'active' ? 'success' : 'secondary'}">
                    ${student.status}
                </span>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-info" onclick="viewStudent(${student.id})" title="View">
                        <i class="bi bi-eye"></i> View
                    </button>
                    <button class="btn btn-primary" onclick="editStudent(${student.id})" title="Edit">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button class="btn btn-danger" onclick="deleteStudent(${student.id})" title="Delete">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            </td>
        </tr>`).join('');
}

// Function to update course filter options
function updateCourseFilter(students) {
    const courses = [...new Set(students.map(s => s.course))].filter(Boolean);
    const courseFilter = document.getElementById('courseFilter');
    
    let options = '<option value="">All Courses</option>';
    courses.sort().forEach(course => {
        options += `<option value="${course}">${course}</option>`;
    });
    
    courseFilter.innerHTML = options;
}

// Load available rooms for student assignment
async function loadAvailableRooms() {
    try {
        const response = await fetch('../api/available_rooms.php');
        const data = await response.json();
        
        if (data.success) {
            // Update both add and edit form room selects
            const addSelect = document.getElementById('addRoomSelect');
            const editSelect = document.getElementById('editRoomSelect');
            const options = ['<option value="">Select Room</option>'];
            
            data.data.forEach(room => {
                const isAvailable = room.capacity > room.current_occupants;
                const availabilityText = isAvailable ? 
                    `(${room.current_occupants}/${room.capacity} occupied)` :
                    '(Full)';
                
                options.push(`
                    <option value="${room.id}" ${!isAvailable ? 'disabled' : ''}>
                        Room ${room.room_no} - ${room.type} ${availabilityText}
                    </option>
                `);
            });

            const optionsHtml = options.join('');
            if (addSelect) addSelect.innerHTML = optionsHtml;
            if (editSelect) editSelect.innerHTML = optionsHtml;
        } else {
            showAlert('error', 'Failed to load rooms: ' + data.message);
        }
    } catch (error) {
        showAlert('error', 'Error loading rooms: ' + error.message);
    }
}

// Function to edit student
async function editStudent(id) {
    try {
        // Show loading state
        document.body.style.cursor = 'wait';
        
        // First load available rooms
        await loadAvailableRooms();
        
        // Then fetch student details
        const response = await fetch(`../api/students.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const form = document.getElementById('editStudentForm');
            const student = data.data;
            
            // Populate form fields
            form.querySelector('#editStudentId').value = student.id;
            form.querySelector('[name="name"]').value = student.name;
            form.querySelector('[name="phone"]').value = student.phone;
            form.querySelector('[name="course"]').value = student.course;
            form.querySelector('[name="year_of_study"]').value = student.year_of_study;
            form.querySelector('[name="gender"]').value = student.gender;
            form.querySelector('[name="status"]').value = student.status;
            form.querySelector('[name="address"]').value = student.address;
            form.querySelector('[name="guardian_name"]').value = student.guardian_name;
            form.querySelector('[name="guardian_phone"]').value = student.guardian_phone;
            form.querySelector('[name="guardian_address"]').value = student.guardian_address;
            
            if (student.room_id) {
                form.querySelector('[name="room_id"]').value = student.room_id;
            }

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
            modal.show();
        } else {
            showAlert('error', 'Error loading student details: ' + data.message);
        }
    } catch (error) {
        showAlert('error', 'Error loading student details: ' + error.message);
    } finally {
        document.body.style.cursor = 'default';
    }
}

// Handle edit form submission
document.getElementById('editStudentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }

    try {
        const formData = new FormData(this);
        formData.append('_method', 'PUT');

        const response = await fetch('../api/students.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Student updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();
            loadStudents();
        } else {
            showAlert('error', 'Failed to update student: ' + data.message);
        }
    } catch (error) {
        showAlert('error', 'Error updating student: ' + error.message);
    }
});

// Handle add student form submission
document.getElementById('addStudentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }

    // Disable submit button and show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

    try {
        const formData = new FormData(this);

        const response = await fetch('../api/students.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to add student');
        }

        // Show success message
        showAlert('success', 'Student added successfully');
        
        // Reset form
        this.reset();
        this.classList.remove('was-validated');
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();
        
        // Reload student list
        await loadStudents();
        
    } catch (error) {
        showAlert('error', 'Error adding student: ' + error.message);
    } finally {
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
});

// Function to delete student
async function deleteStudent(id) {
    if (!confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        formData.append('student_id', id);

        const response = await fetch('../api/students.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to delete student');
        }

        showAlert('success', 'Student deleted successfully');
        loadStudents(); // Reload the student list
    } catch (error) {
        showAlert('error', 'Error deleting student: ' + error.message);
    }
}

// Function to show alerts
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.card-body');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 150);
    }, 5000);
}

// Function to view student details
async function viewStudent(id) {
    try {
        const response = await fetch(`../api/students.php?id=${id}`);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to load student details');
        }

        const student = data.data;
        
        document.getElementById('viewStudentDetails').innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>Roll Number:</strong>
                        <p class="text-muted mb-0">${student.roll || 'Not assigned'}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Full Name:</strong>
                        <p class="text-muted mb-0">${student.name}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Email:</strong>
                        <p class="text-muted mb-0">${student.email}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Phone:</strong>
                        <p class="text-muted mb-0">${student.phone}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Gender:</strong>
                        <p class="text-muted mb-0">${student.gender}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Course:</strong>
                        <p class="text-muted mb-0">${student.course}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>Year of Study:</strong>
                        <p class="text-muted mb-0">Year ${student.year_of_study}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Room Number:</strong>
                        <p class="text-muted mb-0">${student.room_no || 'Not assigned'}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Address:</strong>
                        <p class="text-muted mb-0">${student.address}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Guardian Name:</strong>
                        <p class="text-muted mb-0">${student.guardian_name}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Guardian Phone:</strong>
                        <p class="text-muted mb-0">${student.guardian_phone}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Guardian Address:</strong>
                        <p class="text-muted mb-0">${student.guardian_address}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <p class="mb-0">
                            <span class="badge bg-${student.status === 'active' ? 'success' : 'secondary'}">
                                ${student.status}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        `;

        const modal = new bootstrap.Modal(document.getElementById('viewStudentModal'));
        modal.show();
    } catch (error) {
        showAlert('error', error.message);
    }
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    loadStudents();

    // Search input handler with debounce
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadStudents, 500);
    });

    // Filter handlers
    document.getElementById('courseFilter').addEventListener('change', loadStudents);
    document.getElementById('yearFilter').addEventListener('change', loadStudents);
    document.getElementById('statusFilter').addEventListener('change', loadStudents);

    // Load available rooms when modals are shown
    document.getElementById('addStudentModal').addEventListener('show.bs.modal', loadAvailableRooms);
    document.getElementById('editStudentModal').addEventListener('show.bs.modal', loadAvailableRooms);
});
</script>
SCRIPT;

include_once '../includes/template.php';
?>
