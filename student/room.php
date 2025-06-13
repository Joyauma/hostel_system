<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

$pageTitle = "Room Allocation";
ob_start();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Current Room</h5>
                <div id="currentRoom">
                    <!-- Current room info will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Available Rooms</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Room No</th>
                                <th>Type</th>
                                <th>Capacity</th>
                                <th>Available</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="availableRooms">
                            <!-- Available rooms will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<SCRIPT
<script>
function loadCurrentRoom() {
    fetch('../api/student_room.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('currentRoom');            if (data.room) {
                container.innerHTML = 
                    '<div class="text-center mb-3">' +
                        '<h1 class="display-4">' + data.room.room_no + '</h1>' +
                        '<p class="text-muted">' + data.room.type + ' Room</p>' +
                    '</div>' +
                    '<ul class="list-group">' +
                        '<li class="list-group-item d-flex justify-content-between">' +
                            '<span>Capacity:</span>' +
                            '<strong>' + data.room.capacity + '</strong>' +
                        '</li>' +
                        '<li class="list-group-item d-flex justify-content-between">' +
                            '<span>Roommates:</span>' +
                            '<strong>' + data.room.occupied + '</strong>' +
                        '</li>' +
                    '</ul>' +
                    '<button class="btn btn-danger w-100 mt-3" onclick="requestRoomChange()">' +
                        'Request Room Change' +
                    '</button>';
            } else {
                container.innerHTML = `
                    <div class="text-center">
                        <p class="text-muted">No room allocated yet</p>
                        <p>Please select from available rooms</p>
                    </div>`;
            }
        });
}

function loadAvailableRooms() {
    fetch('../api/available_rooms.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('availableRooms');            tbody.innerHTML = data.map(room => 
                '<tr>' +
                    '<td>' + room.room_no + '</td>' +
                    '<td>' + room.type + '</td>' +
                    '<td>' + room.capacity + '</td>' +
                    '<td>' + (room.capacity - room.occupied) + '</td>' +
                    '<td>' +
                        '<button class="btn btn-primary btn-sm" onclick="requestRoom(' + room.id + ')">' +
                            'Request' +
                        '</button>' +
                    '</td>' +
                '</tr>'
            ).join('');
        });
}

function requestRoom(roomId) {
    if (confirm('Are you sure you want to request this room?')) {
        fetch('../api/student_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ room_id: roomId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Room request submitted successfully');
                loadCurrentRoom();
                loadAvailableRooms();
            } else {
                alert(data.message);
            }
        });
    }
}

function requestRoomChange() {
    if (confirm('Are you sure you want to request a room change?')) {
        fetch('../api/student_room.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'change_request' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Room change request submitted successfully');
            } else {
                alert(data.message);
            }
        });
    }
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadCurrentRoom();
    loadAvailableRooms();
});
</script>
SCRIPT;

require_once '../includes/template.php';
?>
