<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get events for calendar
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            
            // Get events directly using the same function that works in index.php
            $events = getCalendarEvents($start, $end);
            
            // Debug output
            error_log("API fetched " . count($events) . " events between $start and $end");
            
            echo json_encode($events);
            break;
            
        case 'POST':
            // Create new event
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Format dates for database
            $input['start_time'] = $input['start'];
            $input['end_time'] = $input['end'];
            $input['event_type'] = $input['type'];
            
            // Add default customer_id if not provided
            if (!isset($input['customer_id'])) {
                $input['customer_id'] = 1; // Default to first customer for demo
            }
            
            $result = createEvent($input);
            echo json_encode(['success' => true, 'id' => $result, 'message' => 'Event created successfully']);
            break;
            
        case 'PUT':
            // Update existing event
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Format dates for database
            $input['start_time'] = $input['start'];
            $input['end_time'] = $input['end'];
            $input['event_type'] = $input['type'];
            
            $result = updateEvent($input);
            echo json_encode(['success' => $result, 'message' => 'Event updated successfully']);
            break;
            
        case 'DELETE':
            // Delete event
            $input = json_decode(file_get_contents('php://input'), true);
            $result = deleteEvent($input['id']);
            echo json_encode(['success' => $result, 'message' => 'Event deleted successfully']);
            break;
            
        default:
            throw new Exception('Unsupported request method');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>