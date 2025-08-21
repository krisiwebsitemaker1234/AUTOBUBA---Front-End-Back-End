<?php
session_start();
require_once 'includes/config.php'; 
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireAuth();

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to a different page or show access denied
    header("Location: access-denied.php");
    exit();
}

$user = currentUser();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_event'])) {
        // Add new event
        $title = $_POST['title'];
        $event_type = $_POST['event_type'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $notes = $_POST['notes'];
        $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $vehicle_id = !empty($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
        $user_id = $_SESSION['user_id'];

        try {
            $stmt = $pdo->prepare("INSERT INTO calendar_events 
                (title, event_type, start_time, end_time, notes, customer_id, vehicle_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title, $event_type, $start_time, $end_time, 
                $notes, $customer_id, $vehicle_id, $user_id
            ]);
            
            $success_message = "Event added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding event: " . $e->getMessage();
        }
    } 
    elseif (isset($_POST['update_event'])) {
        // Update existing event
        $event_id = intval($_POST['event_id']);
        $title = $_POST['title'];
        $event_type = $_POST['event_type'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $notes = $_POST['notes'];
        $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $vehicle_id = !empty($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
        
        try {
            $stmt = $pdo->prepare("UPDATE calendar_events SET
                title = ?,
                event_type = ?,
                start_time = ?,
                end_time = ?,
                notes = ?,
                customer_id = ?,
                vehicle_id = ?
                WHERE id = ? AND user_id = ?");
            $stmt->execute([
                $title, $event_type, $start_time, $end_time, 
                $notes, $customer_id, $vehicle_id,
                $event_id, $_SESSION['user_id']
            ]);
            
            $success_message = "Event updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating event: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['delete_event'])) {
        // Delete event
        $event_id = intval($_POST['event_id']);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
            $stmt->execute([$event_id, $_SESSION['user_id']]);
            
            $success_message = "Event deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting event: " . $e->getMessage();
        }
    }
}

// Fetch events for calendar
$events = $pdo->query("SELECT * FROM calendar_events")->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming events (next 7 days)
$upcomingEvents = $pdo->query("
    SELECT e.*, c.name as customer_name, CONCAT(v.make, ' ', v.model) as vehicle_name 
    FROM calendar_events e
    LEFT JOIN customers c ON e.customer_id = c.id
    LEFT JOIN vehicles v ON e.vehicle_id = v.id
    WHERE start_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY start_time ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers and vehicles for dropdowns
$customers = $pdo->query("SELECT id, name FROM customers")->fetchAll();
$vehicles = $pdo->query("SELECT id, CONCAT(make, ' ', model) AS name FROM vehicles")->fetchAll();

// Get event counts by type
$eventCounts = $pdo->query("
    SELECT event_type, COUNT(*) as count 
    FROM calendar_events 
    GROUP BY event_type
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Current month and year
$currentMonth = date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar | <?= SITE_NAME ?></title>
    <link rel="icon" href="img/autobuba logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        /* Calendar styling with red and white palette */
        .fc-theme-standard .fc-scrollgrid {
            border-color: #f1f1f1;
        }

        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(225, 29, 72, 0.1) !important;
        }

        .fc .fc-button-primary {
            background-color: #e11d48;
            border-color: #e11d48;
        }

        .fc .fc-button-primary:hover {
            background-color: #be123c;
            border-color: #be123c;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active, 
        .fc .fc-button-primary:not(:disabled):active {
            background-color: #9f1239;
            border-color: #9f1239;
        }

        .fc .fc-toolbar-title {
            color: #e11d48;
            font-weight: 600;
        }

        .fc .fc-col-header-cell-cushion {
            color: #e11d48;
            font-weight: 600;
        }

        .fc .fc-daygrid-day-number {
            color: #333;
        }

        /* Event colors - black, red, and grey */
        .fc-event.event-test_drive {
            background-color: #000000;
            border-color: #000000;
            color: #ffffff;
        }

        .fc-event.event-service {
            background-color: #e11d48;
            border-color: #e11d48;
            color: #ffffff;
        }

        .fc-event.event-follow_up {
            background-color: #6b7280;
            border-color: #6b7280;
            color: #ffffff;
        }

        /* Card styling */
        .card {
            background-color: #ffffff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f1f1;
            padding: 1.25rem 1.5rem;
        }

        .card-header h5 {
            color: #e11d48;
            font-weight: 600;
        }

        .card-header i {
            color: #e11d48;
        }

        .card-body {
            padding: 1.25rem 1.5rem;
        }

        /* Stats section */
        .stats-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: #e11d48;
            color: white;
            font-size: 1.2rem;
            border-radius: 8px;
        }

        /* Upcoming events styling */
        .upcoming-events-card {
            height: 100%;
        }

        .event-item {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            background-color: #f9fafb;
            border-left: 4px solid #e11d48;
            transition: all 0.2s ease;
        }

        .event-item:hover {
            background-color: #f3f4f6;
            transform: translateX(3px);
        }

        .event-item.test_drive {
            border-left-color: #000000;
        }

        .event-item.service {
            border-left-color: #e11d48;
        }

        .event-item.follow_up {
            border-left-color: #6b7280;
        }

        .event-time {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .event-title {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .event-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
            margin-right: 5px;
        }

        .badge-test_drive {
            background-color: #000000;
            color: white;
        }

        .badge-service {
            background-color: #e11d48;
            color: white;
        }

        .badge-follow_up {
            background-color: #6b7280;
            color: white;
        }

        /* Stats cards */
        .stats-card {
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Button styling */
        .btn-primary {
            background-color: #e11d48;
            border-color: #e11d48;
        }

        .btn-primary:hover {
            background-color: #be123c;
            border-color: #be123c;
        }

        .btn-outline-primary {
            color: #e11d48;
            border-color: #e11d48;
        }

        .btn-outline-primary:hover {
            background-color: #e11d48;
            color: #ffffff;
        }

        /* Modal styling */
        .modal-header {
            border-bottom: 1px solid #f1f1f1;
        }

        .modal-header.bg-primary {
            background-color: #e11d48 !important;
        }

        /* Calendar container */
        #calendar-container {
            height: 1000px; /* Increased height */
            position: relative;
            overflow: visible; /* Prevent scrollbars */
            padding: 10px;  /* Add padding around the entire calendar */
        }

        #calendar {
            height: 100%;
        }

        /* Legend styling */
        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 10px 15px;
            background-color: #f9fafb;
            border-radius: 0 0 10px 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 6px;
        }

        /* Upcoming events horizontal layout */
        .upcoming-events-horizontal {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 15px;
            padding: 15px 0;
            margin-bottom: 20px;
        }

        .upcoming-event-card {
            flex: 0 0 250px;
            border-radius: 10px;
            padding: 15px;
            background-color: #ffffff;
            border-top: 4px solid #e11d48;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .upcoming-event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .upcoming-event-card.test_drive {
            border-top-color: #000000;
        }

        .upcoming-event-card.service {
            border-top-color: #e11d48;
        }

        .upcoming-event-card.follow_up {
            border-top-color: #6b7280;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .calendar-sidebar {
                margin-top: 20px;
            }
            
            .upcoming-events-horizontal {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Fix for FullCalendar to prevent scrollbars */
        .fc-scroller {
            overflow: hidden !important;
        }

        .fc-view-harness {
            height: auto !important;
        }

        /* Calendar toolbar responsive fixes */
        @media (max-width: 768px) {
            .fc-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .fc-toolbar-chunk {
                margin-bottom: 10px;
            }
        }

        .fc .fc-daygrid-day {
            padding: 0.75rem !important; /* Increased padding */
            border: 1px solid rgba(209, 0, 0, 0.1) !important;
            background-color: #ffffff !important;
        }

        .fc .fc-daygrid-day-frame {
            min-height: 6.5rem !important; /* Increased height */
            padding: 4px !important; /* Add padding inside each day cell */
        }

        .fc .fc-daygrid-event {
            margin: 3px 0 !important; /* More margin between events */
            padding: 4px 8px !important; /* More padding inside events */
        }

        .fc .fc-col-header-cell {
            padding: 12px 0 !important; /* More padding in column headers */
        }

        .fc .fc-daygrid-day-number {
            padding: 8px 8px 0 0 !important; /* More padding around day numbers */
        }

        .fc .fc-toolbar {
            margin-bottom: 1.5rem !important;
            padding: 0 10px !important; /* Add padding to the toolbar */
        }

        .fc .fc-timegrid-slot {
            height: 3em !important; /* Taller time slots */
            padding: 2px !important; /* Add padding to time slots */
        }

        .fc .fc-event-main {
            padding: 4px !important; /* Add padding inside events */
        }
        
        /* Alert styling */
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <?php include 'includes/topnav.php'; ?>

            <!-- Calendar Content -->
            <div class="content">
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <div>
                        <h4 class="mb-1">Calendar</h4>
                        <p class="text-muted mb-0">Manage your schedule and appointments</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="resetEventForm()">
                            <i class="fas fa-plus me-2"></i>Add Event
                        </button>
                    </div>
                </div>
                
                <!-- Success/Error Messages -->
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="stats-icon">
                                            <i class="fas fa-car-side fa-fw"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Test Drives</div>
                                        <div class="h3 mb-0"><?= $eventCounts['test_drive'] ?? 0 ?></div>
                                        <div class="small text-muted">Scheduled appointments</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 shadow stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="stats-icon">
                                            <i class="fas fa-wrench fa-fw"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Service Appointments</div>
                                        <div class="h3 mb-0"><?= $eventCounts['service'] ?? 0 ?></div>
                                        <div class="small text-muted">Maintenance & repairs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 shadow stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="stats-icon">
                                            <i class="fas fa-phone fa-fw"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Follow-ups</div>
                                        <div class="h3 mb-0"><?= $eventCounts['follow_up'] ?? 0 ?></div>
                                        <div class="small text-muted">Customer follow-ups</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events (Horizontal Layout) -->
                <?php if (!empty($upcomingEvents)): ?>
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Upcoming Events</h5>
                        <a href="all_events.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-3">
                        <div class="upcoming-events-horizontal">
                            <?php foreach ($upcomingEvents as $event): ?>
                                <div class="upcoming-event-card <?= $event['event_type'] ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="event-title"><?= sanitize($event['title']) ?></div>
                                        <span class="event-badge badge-<?= $event['event_type'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $event['event_type'])) ?>
                                        </span>
                                    </div>
                                    <div class="event-time mb-2">
                                        <i class="far fa-clock me-1"></i>
                                        <?= date('M j, g:i a', strtotime($event['start_time'])) ?>
                                    </div>
                                    <?php if (!empty($event['customer_name'])): ?>
                                        <div class="small">
                                            <i class="fas fa-user me-1 text-muted"></i>
                                            <?= sanitize($event['customer_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($event['vehicle_name'])): ?>
                                        <div class="small">
                                            <i class="fas fa-car me-1 text-muted"></i>
                                            <?= sanitize($event['vehicle_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Full Width Calendar -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i> Calendar</h5>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" id="todayBtn">Today</button>
                            <button class="btn btn-sm btn-outline-primary" id="monthViewBtn">Month</button>
                            <button class="btn btn-sm btn-outline-primary" id="weekViewBtn">Week</button>
                            <button class="btn btn-sm btn-outline-primary" id="dayViewBtn">Day</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="calendar-container">
                            <div id="calendar"></div>
                        </div>
                    </div>
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: #000000;"></span>
                            <span>Test Drive</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: #e11d48;"></span>
                            <span>Service</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: #6b7280;"></span>
                            <span>Follow Up</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Modal (Add/Edit) -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="event_id" id="event_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-calendar-plus me-2"></i>Add Event
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Title *</label>
                            <input type="text" name="title" id="event_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Event Type *</label>
                            <select name="event_type" id="event_type" class="form-select" required>
                                <option value="test_drive">Test Drive</option>
                                <option value="service">Service</option>
                                <option value="follow_up">Follow Up</option>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label>Start Time *</label>
                                <input type="datetime-local" name="start_time" id="start_time" class="form-control" required>
                            </div>
                            <div class="col">
                                <label>End Time *</label>
                                <input type="datetime-local" name="end_time" id="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select">
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Vehicle</label>
                            <select name="vehicle_id" id="vehicle_id" class="form-select">
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= sanitize($v['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Notes</label>
                            <textarea name="notes" id="event_notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_event" id="addEventBtn" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Event
                        </button>
                        <button type="submit" name="update_event" id="updateEventBtn" class="btn btn-success d-none">
                            <i class="fas fa-save me-1"></i> Update Event
                        </button>
                        <button type="submit" name="delete_event" id="deleteEventBtn" class="btn btn-danger d-none">
                            <i class="fas fa-trash me-1"></i> Delete Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="eventDetailsTitle">Event Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventDetailsContent">
                    <!-- Dynamic content inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editEventBtn">
                        <i class="fas fa-edit me-1"></i> Edit Event
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    
    <!-- Initialize Calendar -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize calendar
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            height: '100%',
            contentHeight: 'auto', // This helps prevent scrollbars
            dayMaxEventRows: 3, // Limit number of events per day to prevent overcrowding
            eventTimeFormat: { // Customize the time format
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            },
            slotLabelFormat: { // Format for time slots in week/day view
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            },
            dayHeaderFormat: { // Format for day headers
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            },
            views: {
                dayGridMonth: {
                    dayHeaderFormat: { weekday: 'short' }
                },
                timeGridWeek: {
                    dayHeaderFormat: { weekday: 'short', month: 'short', day: 'numeric' }
                },
                timeGridDay: {
                    dayHeaderFormat: { weekday: 'long', month: 'long', day: 'numeric' }
                }
            },
            events: <?= json_encode(array_map(function($event) {
                return [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'start' => $event['start_time'],
                    'end' => $event['end_time'],
                    'extendedProps' => [
                        'type' => $event['event_type'],
                        'notes' => $event['notes'],
                        'customer_id' => $event['customer_id'],
                        'vehicle_id' => $event['vehicle_id']
                    ]
                ];
            }, $events)) ?>,
            eventClassNames: function(arg) {
                return ['event-' + arg.event.extendedProps.type];
            },
            eventClick: function(info) {
                const event = info.event;
                const props = event.extendedProps;
                const start = event.start ? formatDateTime(event.start) : '';
                const end = event.end ? formatDateTime(event.end) : '';

                // Get customer and vehicle data from PHP
                const customers = <?= json_encode(array_column($customers, 'name', 'id')) ?>;
                const vehicles = <?= json_encode(array_column($vehicles, 'name', 'id')) ?>;

                // Build modal content
                const detailsHtml = `
                    <div class="event-header mb-4">
                        <h4>${event.title}</h4>
                        <span class="badge ${props.type === 'test_drive' ? 'bg-dark' : 
                                            props.type === 'service' ? 'bg-danger' : 'bg-secondary'}">
                            ${props.type.replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Start Time</label>
                                <p><i class="far fa-calendar-alt me-2"></i> ${start}</p>
                            </div>
                            ${event.end ? `
                            <div class="mb-3">
                                <label class="fw-bold">End Time</label>
                                <p><i class="far fa-clock me-2"></i> ${end}</p>
                            </div>` : ''}
                        </div>
                        <div class="col-md-6">
                            ${props.customer_id ? `
                            <div class="mb-3">
                                <label class="fw-bold">Customer</label>
                                <p><i class="fas fa-user me-2"></i> ${customers[props.customer_id] || 'No customer'}</p>
                            </div>` : ''}
                            ${props.vehicle_id ? `
                            <div class="mb-3">
                                <label class="fw-bold">Vehicle</label>
                                <p><i class="fas fa-car me-2"></i> ${vehicles[props.vehicle_id] || 'No vehicle'}</p>
                            </div>` : ''}
                        </div>
                    </div>
                    ${props.notes ? `
                    <div class="mb-3">
                        <label class="fw-bold">Notes</label>
                        <div class="p-3 bg-light rounded">${props.notes}</div>
                    </div>` : ''}
                `;

                // Update and show modal
                document.getElementById('eventDetailsTitle').textContent = 'Event Details: ' + event.title;
                document.getElementById('eventDetailsContent').innerHTML = detailsHtml;
                
                // Store event ID for edit button
                document.getElementById('editEventBtn').setAttribute('data-event-id', event.id);
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
                modal.show();
                
                info.jsEvent.preventDefault();
            },
            dateClick: function(info) {
                // Handle new event creation
                resetEventForm();
                
                // Set default times (09:00-10:00)
                const clickedDate = new Date(info.date);
                const startDate = new Date(clickedDate);
                startDate.setHours(9, 0, 0);
                
                const endDate = new Date(clickedDate);
                endDate.setHours(10, 0, 0);
                
                document.getElementById('start_time').value = formatDateTimeForInput(startDate);
                document.getElementById('end_time').value = formatDateTimeForInput(endDate);
                
                new bootstrap.Modal(document.getElementById('eventModal')).show();
            }
        });

        calendar.render();

        // View buttons
        document.getElementById('todayBtn').addEventListener('click', function() {
            calendar.today();
        });
        
        document.getElementById('monthViewBtn').addEventListener('click', function() {
            calendar.changeView('dayGridMonth');
        });
        
        document.getElementById('weekViewBtn').addEventListener('click', function() {
            calendar.changeView('timeGridWeek');
        });
        
        document.getElementById('dayViewBtn').addEventListener('click', function() {
            calendar.changeView('timeGridDay');
        });
        
        // Edit event button
        document.getElementById('editEventBtn').addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            if (!eventId) return;
            
            // Find the event in the calendar
            const event = calendar.getEventById(eventId);
            if (!event) return;
            
            // Populate the form with event data
            document.getElementById('event_id').value = eventId;
            document.getElementById('event_title').value = event.title;
            document.getElementById('event_type').value = event.extendedProps.type;
            document.getElementById('start_time').value = formatDateTimeForInput(event.start);
            document.getElementById('end_time').value = formatDateTimeForInput(event.end || event.start);
            document.getElementById('customer_id').value = event.extendedProps.customer_id || '';
            document.getElementById('vehicle_id').value = event.extendedProps.vehicle_id || '';
            document.getElementById('event_notes').value = event.extendedProps.notes || '';
            
            // Update modal title and buttons
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Event';
            document.getElementById('addEventBtn').classList.add('d-none');
            document.getElementById('updateEventBtn').classList.remove('d-none');
            document.getElementById('deleteEventBtn').classList.remove('d-none');
            
            // Close details modal and show edit modal
            bootstrap.Modal.getInstance(document.getElementById('eventDetailsModal')).hide();
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        });

        // Helper function for datetime formatting for display
        function formatDateTime(date) {
            const options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('en-US', options);
        }
        
        // Helper function for datetime formatting for input fields
        function formatDateTimeForInput(date) {
            if (!date) return '';
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        
        // Reset event form for adding new events
        window.resetEventForm = function() {
            document.getElementById('event_id').value = '';
            document.getElementById('event_title').value = '';
            document.getElementById('event_type').value = 'test_drive';
            document.getElementById('start_time').value = '';
            document.getElementById('end_time').value = '';
            document.getElementById('customer_id').value = '';
            document.getElementById('vehicle_id').value = '';
            document.getElementById('event_notes').value = '';
            
            // Update modal title and buttons
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Add Event';
            document.getElementById('addEventBtn').classList.remove('d-none');
            document.getElementById('updateEventBtn').classList.add('d-none');
            document.getElementById('deleteEventBtn').classList.add('d-none');
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            calendar.updateSize();
        });

        // Fix for scrollbar issues
        setTimeout(function() {
            calendar.updateSize();
        }, 200);
    });
    </script>
</body>
</html>