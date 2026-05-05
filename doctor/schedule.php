<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/doctor.php';

requireLogin();
requireRole('doctor');

$doctor = new Doctor($pdo);
$doctorInfo = $doctor->getByUserId($_SESSION['user_id']);

if (!$doctorInfo) {
    header('Location: ../logout.php');
    exit;
}

$message = '';
$error = '';

// Handle schedule update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_schedule'])) {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Delete existing schedules
        $stmt = $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
        $stmt->execute([$doctorInfo['id']]);
        
        // Insert new schedules
        foreach ($days as $day) {
            if (isset($_POST['available_' . $day]) && $_POST['available_' . $day] == '1') {
                $startTime = $_POST['start_' . $day] ?? '09:00';
                $endTime = $_POST['end_' . $day] ?? '17:00';
                $slotDuration = $_POST['slot_duration'] ?? 30;
                
                $stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$doctorInfo['id'], $day, $startTime, $endTime, $slotDuration]);
            }
        }
        $message = 'Schedule updated successfully!';
    }
    
    // Handle blocking time slots
    if (isset($_POST['block_slot'])) {
        $blockDate = $_POST['block_date'];
        $blockStart = $_POST['block_start'];
        $blockEnd = $_POST['block_end'];
        $blockReason = $_POST['block_reason'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO blocked_slots (doctor_id, blocked_date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$doctorInfo['id'], $blockDate, $blockStart, $blockEnd, $blockReason]);
        $message = 'Time slot blocked successfully!';
    }
    
    // Handle unblocking
    if (isset($_POST['unblock_slot'])) {
        $slotId = $_POST['slot_id'];
        $stmt = $pdo->prepare("DELETE FROM blocked_slots WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$slotId, $doctorInfo['id']]);
        $message = 'Time slot unblocked successfully!';
    }
}

// Get current schedules
$stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')");
$stmt->execute([$doctorInfo['id']]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scheduleByDay = [];
foreach ($schedules as $schedule) {
    $scheduleByDay[$schedule['day_of_week']] = $schedule;
}

// Get blocked slots
$stmt = $pdo->prepare("SELECT * FROM blocked_slots WHERE doctor_id = ? AND blocked_date >= CURDATE() ORDER BY blocked_date, start_time");
$stmt->execute([$doctorInfo['id']]);
$blockedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Schedule';
require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>My Schedule</h1>
            <p class="text-muted">Manage your availability and working hours</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="grid grid-2">
            <!-- Weekly Schedule -->
            <div class="card">
                <div class="card-header">
                    <h3>Weekly Availability</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="update_schedule" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Appointment Duration (minutes)</label>
                            <select name="slot_duration" class="form-control">
                                <option value="15">15 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                            </select>
                        </div>
                        
                        <?php
                        $days = [
                            'monday' => 'Monday',
                            'tuesday' => 'Tuesday',
                            'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday',
                            'friday' => 'Friday',
                            'saturday' => 'Saturday',
                            'sunday' => 'Sunday'
                        ];
                        
                        foreach ($days as $key => $label):
                            $isAvailable = isset($scheduleByDay[$key]);
                            $startTime = $isAvailable ? $scheduleByDay[$key]['start_time'] : '09:00';
                            $endTime = $isAvailable ? $scheduleByDay[$key]['end_time'] : '17:00';
                        ?>
                        <div class="schedule-day">
                            <div class="schedule-day-header">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="available_<?php echo $key; ?>" value="1" <?php echo $isAvailable ? 'checked' : ''; ?>>
                                    <span><?php echo $label; ?></span>
                                </label>
                            </div>
                            <div class="schedule-day-times">
                                <input type="time" name="start_<?php echo $key; ?>" value="<?php echo substr($startTime, 0, 5); ?>" class="form-control form-control-sm">
                                <span>to</span>
                                <input type="time" name="end_<?php echo $key; ?>" value="<?php echo substr($endTime, 0, 5); ?>" class="form-control form-control-sm">
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <button type="submit" class="btn btn-primary btn-block mt-3">Save Schedule</button>
                    </form>
                </div>
            </div>
            
            <!-- Block Time Slots -->
            <div>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3>Block Time Slot</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="block_slot" value="1">
                            
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type="date" name="block_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="block_start" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="block_end" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Reason (optional)</label>
                                <input type="text" name="block_reason" class="form-control" placeholder="e.g., Personal appointment, Conference">
                            </div>
                            
                            <button type="submit" class="btn btn-secondary btn-block">Block Time Slot</button>
                        </form>
                    </div>
                </div>
                
                <!-- Blocked Slots List -->
                <div class="card">
                    <div class="card-header">
                        <h3>Blocked Time Slots</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($blockedSlots)): ?>
                            <p class="text-muted text-center">No blocked time slots</p>
                        <?php else: ?>
                            <div class="blocked-slots-list">
                                <?php foreach ($blockedSlots as $slot): ?>
                                <div class="blocked-slot-item">
                                    <div class="blocked-slot-info">
                                        <strong><?php echo date('M d, Y', strtotime($slot['blocked_date'])); ?></strong>
                                        <span><?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?></span>
                                        <?php if ($slot['reason']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($slot['reason']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="unblock_slot" value="1">
                                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.schedule-day {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.schedule-day:last-of-type {
    border-bottom: none;
}

.schedule-day-header {
    flex: 1;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.schedule-day-times {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.schedule-day-times .form-control-sm {
    width: 120px;
}

.blocked-slots-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.blocked-slot-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
}

.blocked-slot-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.blocked-slot-info strong {
    color: var(--text-primary);
}

.blocked-slot-info span {
    font-size: 0.875rem;
    color: var(--text-secondary);
}
</style>

<?php require_once '../includes/footer.php'; ?>
