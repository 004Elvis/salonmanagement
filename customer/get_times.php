<?php
// customer/get_times.php
require '../config/db.php';

if (isset($_GET['staff_id']) && isset($_GET['date'])) {
    $staff_id = $_GET['staff_id'];
    $date = $_GET['date']; // Format: YYYY-MM-DD
    
    // Find out what day of the week this date is
    $day_of_week = date('l', strtotime($date)); 
    
    // 1. Check if the staff member is working on this day
    $stmt = $pdo->prepare("SELECT is_working, start_time, end_time FROM staff_availability WHERE staff_id = ? AND day_of_week = ?");
    $stmt->execute([$staff_id, $day_of_week]);
    $schedule = $stmt->fetch();
    
    if (!$schedule || $schedule['is_working'] == 0) {
        echo "<option value=''>Not working on this day</option>";
        exit();
    }
    
    // 2. Get all appointments this staff member ALREADY has on this date
    $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE staff_id = ? AND appointment_date = ? AND status != 'Cancelled'");
    $stmt->execute([$staff_id, $date]);
    $booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $booked_slots = array_map(function($time) {
        return date('H:i', strtotime($time));
    }, $booked_times);

    // 3. Generate the available time slots
    $start_time = strtotime($schedule['start_time']);
    $end_time = strtotime($schedule['end_time']);
    
    // NEW: Get current time to prevent booking past slots if the date is today
    $current_time = time();
    $is_today = ($date === date('Y-m-d'));

    $options = "<option value=''>-- Select a Time --</option>";
    $has_open_slots = false;
    
    while ($start_time < $end_time) {
        $time_slot_value = date('H:i', $start_time); 
        
        $is_booked = in_array($time_slot_value, $booked_slots);
        $is_past = $is_today && ($start_time <= $current_time);
        
        // If it's NOT booked AND NOT in the past, offer it to the customer
        if (!$is_booked && !$is_past) {
            $display_time = date('h:i A', $start_time); 
            $options .= "<option value='{$time_slot_value}'>{$display_time}</option>";
            $has_open_slots = true;
        }
        
        $start_time = strtotime('+60 minutes', $start_time); 
    }
    
    if ($has_open_slots) {
        echo $options;
    } else {
        echo "<option value=''>Fully booked for this day</option>";
    }
}
?>