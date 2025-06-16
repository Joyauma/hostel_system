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
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <input type="text" class="form-control" name="course" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year of Study</label>
                            <select class="form-select" name="year_of_study" required>
                                <option value="">Select Year</option>
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                                <option value="4">Year 4</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2" required></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" class="form-control" name="guardian_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Guardian Phone</label>
                            <input type="tel" class="form-control" name="guardian_phone" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Guardian Address</label>
                        <textarea class="form-control" name="guardian_address" rows="2" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addStudent()">Add Student</button>
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
                    <input type="hidden" name="student_id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <input type="text" class="form-control" name="course" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year of Study</label>
                            <select class="form-select" name="year_of_study" required>
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                                <option value="4">Year 4</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" class="form-control" name="guardian_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Guardian Phone</label>
                            <input type="tel" class="form-control" name="guardian_phone" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Guardian Address</label>
                        <textarea class="form-control" name="guardian_address" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateStudent()">Update Student</button>
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
            <div class="modal-body" id="studentDetails">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<'SCRIPT'
<script>
let students = [];
let currentPage = 1;
let totalPages = 1;
const itemsPerPage = 10;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadStudents();
    loadCoursesList();
    setupFilters();
});

function setupFilters() {
    const searchInput = document.getElementById('searchInput');
    const courseFilter = document.getElementById('courseFilter');
    const yearFilter = document.getElementById('yearFilter');
    const statusFilter = document.getElementById('statusFilter');

    // Add event listeners for filters
    searchInput.addEventListener('input', filterStudents);
    courseFilter.addEventListener('change', filterStudents);
    yearFilter.addEventListener('change', filterStudents);
    statusFilter.addEventListener('change', filterStudents);
}

async function loadStudents() {
    try {
        const response = await fetch('../api/students.php');
        const data = await response.json();
        if (data.success) {
            students = data.data;
            updateStudentsTable();
        } else {
            showAlert(data.message || 'Failed to load students', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Failed to load students', 'danger');
    }
}

async function loadCoursesList() {
    try {
        const response = await fetch('../api/students.php?action=courses');
        const data = await response.json();
        if (data.success) {
            const courseFilter = document.getElementById('courseFilter');
            data.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course;
                option.textContent = course;
                courseFilter.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

function filterStudents() {
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    const course = document.getElementById('courseFilter').value;
    const year = document.getElementById('yearFilter').value;
    const status = document.getElementById('statusFilter').value;

    const filtered = students.filter(student => {
        const matchesSearch = searchText === '' || 
            student.first_name.toLowerCase().includes(searchText) ||
            student.last_name.toLowerCase().includes(searchText) ||
            student.registration_number.toLowerCase().includes(searchText);
        
        const matchesCourse = course === '' || student.course === course;
        const matchesYear = year === '' || student.year_of_study.toString() === year;
        const matchesStatus = status === '' || student.status === status;

        return matchesSearch && matchesCourse && matchesYear && matchesStatus;
    });

    updateStudentsTable(filtered);
}

function updateStudentsTable(filteredStudents = students) {
    const tbody = document.getElementById('studentsTable');
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const paginatedStudents = filteredStudents.slice(start, end);
    totalPages = Math.ceil(filteredStudents.length / itemsPerPage);

    if (paginatedStudents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No students found</td></tr>';
    } else {
        tbody.innerHTML = paginatedStudents.map(student => `
            <tr>                <td>${student.roll}</td>
                <td>${student.first_name} ${student.last_name}</td>
                <td>${student.course}</td>
                <td>Year ${student.year_of_study}</td>
                <td>${student.room_number || '-'}</td>
                <td>
                    <small>${student.phone}<br>${student.email}</small>
                </td>
                <td>
                    <span class="badge bg-${student.status === 'active' ? 'success' : 'secondary'}">
                        ${student.status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info me-1" onclick="viewStudent(${student.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary me-1" onclick="editStudent(${student.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    updatePagination();
}

function updatePagination() {
    const pagination = document.getElementById('pagination');
    let html = '';

    if (totalPages > 1) {
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Previous</a>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            html += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>
            `;
        }

        html += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a>
            </li>
        `;
    }

    pagination.innerHTML = html;
}

function changePage(page) {
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        filterStudents();
    }
}

async function addStudent() {
    const form = document.getElementById('addStudentForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const formData = new FormData(form);
    try {
        const response = await fetch('../api/students.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('Student added successfully', 'success');
            $('#addStudentModal').modal('hide');
            form.reset();
            loadStudents();
        } else {
            throw new Error(data.message || 'Failed to add student');
        }
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}

async function editStudent(id) {
    try {
        const response = await fetch(`../api/students.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const form = document.getElementById('editStudentForm');
            const student = data.data;

            // Populate form fields
            form.student_id.value = student.id;
            form.first_name.value = student.first_name;
            form.last_name.value = student.last_name;
            form.email.value = student.email;
            form.phone.value = student.phone;
            form.course.value = student.course;
            form.year_of_study.value = student.year_of_study;
            form.guardian_name.value = student.guardian_name;
            form.guardian_phone.value = student.guardian_phone;
            form.guardian_address.value = student.guardian_address;
            form.status.value = student.status;

            $('#editStudentModal').modal('show');
        } else {
            throw new Error(data.message || 'Failed to load student details');
        }
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}

async function updateStudent() {
    const form = document.getElementById('editStudentForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const formData = new FormData(form);
    try {
        const response = await fetch('../api/students.php', {
            method: 'PUT',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('Student updated successfully', 'success');
            $('#editStudentModal').modal('hide');
            loadStudents();
        } else {
            throw new Error(data.message || 'Failed to update student');
        }
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}

async function viewStudent(id) {
    try {
        const response = await fetch(`../api/students.php?id=${id}&include=room,fees`);
        const data = await response.json();
        
        if (data.success) {
            const student = data.data;
            const details = document.getElementById('studentDetails');
            
            details.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Personal Information</h6>
                        <p>
                            <strong>Name:</strong> ${student.first_name} ${student.last_name}<br>                            <strong>Roll:</strong> ${student.roll}<br>
                            <strong>Course:</strong> ${student.course}<br>
                            <strong>Year of Study:</strong> ${student.year_of_study}<br>
                            <strong>Gender:</strong> ${student.gender}<br>
                            <strong>Date of Birth:</strong> ${new Date(student.dob).toLocaleDateString()}<br>
                            <strong>Status:</strong> ${student.status}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Contact Information</h6>
                        <p>
                            <strong>Email:</strong> ${student.email}<br>
                            <strong>Phone:</strong> ${student.phone}<br>
                            <strong>Address:</strong> ${student.address}
                        </p>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Guardian Information</h6>
                        <p>
                            <strong>Name:</strong> ${student.guardian_name}<br>
                            <strong>Phone:</strong> ${student.guardian_phone}<br>
                            <strong>Address:</strong> ${student.guardian_address}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Room Information</h6>
                        <p>
                            ${student.room ? 
                                `<strong>Room Number:</strong> ${student.room.room_number}<br>
                                <strong>Block:</strong> ${student.room.block}<br>
                                <strong>Floor:</strong> ${student.room.floor}` 
                                : 'No room allocated'}
                        </p>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Fee Status</h6>
                        <p>
                            <strong>Total Due:</strong> Ksh ${student.fees.total_due.toLocaleString()}<br>
                            <strong>Last Payment:</strong> ${student.fees.last_payment ? 
                                `Ksh ${student.fees.last_payment.amount.toLocaleString()} on 
                                ${new Date(student.fees.last_payment.date).toLocaleDateString()}` 
                                : 'No payments recorded'}
                        </p>
                    </div>
                </div>
            `;

            $('#viewStudentModal').modal('show');
        } else {
            throw new Error(data.message || 'Failed to load student details');
        }
    } catch (error) {
        showAlert(error.message, 'danger');
    }
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.card-body').prepend(alertDiv);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>
SCRIPT;

include_once '../includes/template.php';
?>
