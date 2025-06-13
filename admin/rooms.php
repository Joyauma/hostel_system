<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$pageTitle = "Room Management";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Room Management</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
        <i class="bi bi-plus-circle"></i> Add New Room
    </button>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Rooms</h5>
                <h2 id="totalRooms">Loading...</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Available Rooms</h5>
                <h2 id="availableRooms">Loading...</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Occupied Rooms</h5>
                <h2 id="occupiedRooms">Loading...</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Occupancy Rate</h5>
                <h2 id="occupancyRate">Loading...</h2>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Room No</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Occupied</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="roomsTable">
            <!-- Rooms will be loaded here dynamically -->
        </tbody>
    </table>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRoomForm">
                    <div class="mb-3">
                        <label class="form-label">Room Number</label>
                        <input type="text" class="form-control" name="room_no" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Type</label>
                        <select class="form-select" name="type" required>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" class="form-control" name="capacity" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="addRoom()">Add Room</button>
            </div>
        </div>
    </div>
</div>

<!-- View Room Modal -->
<div class="modal fade" id="viewRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="fw-bold">Room Number:</label>
                    <p id="viewRoomNo"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Room Type:</label>
                    <p id="viewRoomType"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Capacity:</label>
                    <p id="viewRoomCapacity"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Occupied:</label>
                    <p id="viewRoomOccupied"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Current Occupants:</label>
                    <div id="viewRoomOccupants" class="list-group">
                        <!-- Occupants will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editRoomForm">
                    <input type="hidden" name="id" id="editRoomId">
                    <div class="mb-3">
                        <label class="form-label">Room Number</label>
                        <input type="text" class="form-control" name="room_no" id="editRoomNo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Type</label>
                        <select class="form-select" name="type" id="editRoomType" required>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" class="form-control" name="capacity" id="editRoomCapacity" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="updateRoom()">Update Room</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<SCRIPT
<script>
let rooms = [];

function loadRooms() {
    fetch('../api/rooms.php')
        .then(response => response.json())
        .then(data => {
            rooms = data;
            updateDashboard();
            updateTable();
        });
}

function updateDashboard() {
    const total = rooms.length;
    const occupied = rooms.filter(r => r.occupied > 0).length;
    const available = total - occupied;
    const rate = total ? Math.round((occupied/total) * 100) : 0;

    document.getElementById('totalRooms').textContent = total;
    document.getElementById('availableRooms').textContent = available;
    document.getElementById('occupiedRooms').textContent = occupied;
    document.getElementById('occupancyRate').textContent = rate + '%';
}

function updateTable() {
    const tbody = document.getElementById('roomsTable');
    tbody.innerHTML = rooms.map(room => 
        '<tr>' +
            '<td>' + room.room_no + '</td>' +
            '<td>' + room.type + '</td>' +
            '<td>' + room.capacity + '</td>' +
            '<td>' + room.occupied + '</td>' +
            '<td>' +
                '<span class="badge bg-' + (room.occupied < room.capacity ? 'success' : 'danger') + '">' +
                    (room.occupied < room.capacity ? 'Available' : 'Full') +
                '</span>' +
            '</td>' +
            '<td>' +
                '<button class="btn btn-sm btn-primary" onclick="viewRoom(' + room.id + ')">' +
                    '<i class="bi bi-eye"></i>' +
                '</button> ' +
                '<button class="btn btn-sm btn-warning" onclick="editRoom(' + room.id + ')">' +
                    '<i class="bi bi-pencil"></i>' +
                '</button> ' +
                '<button class="btn btn-sm btn-danger" onclick="deleteRoom(' + room.id + ')">' +
                    '<i class="bi bi-trash"></i>' +
                '</button>' +
            '</td>' +
        '</tr>'
    ).join('');
}

function addRoom() {
    const form = document.getElementById('addRoomForm');
    const formData = new FormData(form);

    fetch('../api/rooms.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadRooms();
            bootstrap.Modal.getInstance(document.getElementById('addRoomModal')).hide();
            form.reset();
        } else {
            alert(data.message);
        }
    });
}

function viewRoom(id) {
    const room = rooms.find(r => r.id == id);
    if (!room) return;

    document.getElementById('viewRoomNo').textContent = room.room_no;
    document.getElementById('viewRoomType').textContent = room.type;
    document.getElementById('viewRoomCapacity').textContent = room.capacity;
    document.getElementById('viewRoomOccupied').textContent = room.occupied;

    // Load room occupants
    fetch('../api/rooms.php?action=occupants&id=' + id)
        .then(response => response.json())
        .then(data => {
            const occupantsDiv = document.getElementById('viewRoomOccupants');
            if (data.length === 0) {
                occupantsDiv.innerHTML = '<p class="text-muted">No current occupants</p>';
            } else {
                occupantsDiv.innerHTML = data.map(student =>
                    '<div class="list-group-item">' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                            '<div>' +
                                '<h6 class="mb-0">' + student.name + '</h6>' +
                                '<small class="text-muted">Student ID: ' + student.student_id + '</small>' +
                            '</div>' +
                            '<small class="text-muted">Since: ' + student.allocation_date + '</small>' +
                        '</div>' +
                    '</div>'
                ).join('');
            }
        });

    new bootstrap.Modal(document.getElementById('viewRoomModal')).show();
}

function editRoom(id) {
    const room = rooms.find(r => r.id == id);
    if (!room) return;

    document.getElementById('editRoomId').value = room.id;
    document.getElementById('editRoomNo').value = room.room_no;
    document.getElementById('editRoomType').value = room.type;
    document.getElementById('editRoomCapacity').value = room.capacity;

    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}

function updateRoom() {
    const form = document.getElementById('editRoomForm');
    const formData = new FormData(form);
    formData.append('action', 'update');

    fetch('../api/rooms.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadRooms();
            bootstrap.Modal.getInstance(document.getElementById('editRoomModal')).hide();
        } else {
            alert(data.message);
        }
    });
}

function deleteRoom(id) {
    if (!confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    fetch('../api/rooms.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadRooms();
        } else {
            alert(data.message);
        }
    });
}

// Load rooms when page loads
document.addEventListener('DOMContentLoaded', loadRooms);
</script>
SCRIPT;

require_once '../includes/template.php';
?>
