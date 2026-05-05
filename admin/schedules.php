<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_schedule'])) {
        $doctorId = intval($_POST['doctor_id']);
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Delete existing schedules
        $stmt = $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
        $stmt->execute([$doctorId]);
        
        // Insert new schedules
        foreach ($days as $day) {
            if (isset($_POST['available_' . $day]) && $_POST['available_' . $day] == '1') {
                $startTime = $_POST['start_' . $day] ?? '09:00';
                $endTime = $_POST['end_' . $day] ?? '17:00';
                $slotDuration = $_POST['slot_duration'] ?? 30;
                
                $stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$doctorId, $day, $startTime, $endTime, $slotDuration]);
            }
        }
        $message = 'Schedule updated successfully!';
    }
}

// Get all doctors with their schedules
$stmt = $pdo->query("
    SELECT d.id, d.specialty, u.full_name 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.is_active = 1
    ORDER BY u.full_name
");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get schedules for all doctors
$schedules = [];
foreach ($doctors as $doc) {
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ?");
    $stmt->execute([$doc['id']]);
    $docSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $schedules[$doc['id']] = [];
    foreach ($docSchedules as $sched) {
        $schedules[$doc['id']][$sched['day_of_week']] = $sched;
    }
}

$selectedDoctor = $_GET['doctor_id'] ?? ($doctors[0]['id'] ?? null);

$pageTitle = 'Manage Schedules';
require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Manage Schedules</h1>
                <p class="text-muted">Set working hours for doctors</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($doctors)): ?>
            <div class="card">
                <div class="card-body">
                    <p class="text-muted text-center py-4">No active doctors found. Please add doctors first.</p>
                    <div class="text-center">
                        <a href="doctors.php" class="btn btn-primary">Add Doctor</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
        
        <div class="grid" style="grid-template-columns: 300px 1fr; gap: 1.5rem;">
            <!-- Doctor List -->
            <div class="card">
                <div class="card-header">
                    <h3>Doctors</h3>
                </div>
                <div class="card-body p-0">
                    <div class="doctor-list">
                        <?php foreach ($doctors as $doc): ?>
                        <a href="?doctor_id=<?php echo $doc['id']; ?>" 
                           class="doctor-list-item <?php echo $selectedDoctor == $doc['id'] ? 'active' : ''; ?>">
                            <div class="doctor-avatar">
                                <?php echo strtoupper(substr($doc['full_name'], 0, 1)); ?>
                            </div>
                            <div class="doctor-info">
                                <strong><?php echo htmlspecialchars($doc['full_name']); ?></strong>
                                <small><?php echo htmlspecialchars($doc['specialty']); ?></small>
                            </div>
                            <?php if (!empty($schedules[$doc['id']])): ?>
                            <span class="schedule-badge">
                                <?php echo count($schedules[$doc['id']]); ?> days
                            </span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Schedule Editor -->
            <div class="card">
                <div class="card-header">
                    <h3>Weekly Schedule</h3>
                </div>
                <div class="card-body">
                    <?php
                    $currentDoctor = null;
                    foreach ($doctors as $doc) {
                        if ($doc['id'] == $selectedDoctor) {
                            $currentDoctor = $doc;
                            break;
                        }
                    }
                    
                    if ($currentDoctor):
                        $doctorSchedule = $schedules[$currentDoctor['id']] ?? [];
                    ?>
                    <form method="POST" action="">
                        <input type="hidden" name="update_schedule" value="1">
                        <input type="hidden" name="doctor_id" value="<?php echo $currentDoctor['id']; ?>">
                        
                        <div class="schedule-header">
                            <h4>Dr. <?php echo htmlspecialchars($currentDoctor['full_name']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($currentDoctor['specialty']); ?></p>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label class="form-label">Appointment Duration</label>
                            <select name="slot_duration" class="form-control" style="max-width: 200px;">
                                <option value="15">15 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                            </select>
                        </div>
                        
                        <div class="schedule-grid">
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
                                $isAvailable = isset($doctorSchedule[$key]);
                                $startTime = $isAvailable ? $doctorSchedule[$key]['start_time'] : '09:00';
                                $endTime = $isAvailable ? $doctorSchedule[$key]['end_time'] : '17:00';
                            ?>
                            <div class="schedule-row <?php echo $isAvailable ? 'active' : ''; ?>">
                                <div class="schedule-day-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="available_<?php echo $key; ?>" value="1" 
                                               <?php echo $isAvailable ? 'checked' : ''; ?>
                                               onchange="this.closest('.schedule-row').classList.toggle('active', this.checked)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="day-label"><?php echo $label; ?></span>
                                </div>
                                <div class="schedule-times">
                                    <input type="time" name="start_<?php echo $key; ?>" 
                                           value="<?php echo substr($startTime, 0, 5); ?>" class="form-control">
                                    <span>to</span>
                                    <input type="time" name="end_<?php echo $key; ?>" 
                                           value="<?php echo substr($endTime, 0, 5); ?>" class="form-control">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Save Schedule</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <p class="text-muted text-center py-4">Select a doctor to edit their schedule.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</main>

<style>
.doctor-list {
    display: flex;
    flex-direction: column;
}

.doctor-list-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    text-decoration: none;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
}

.doctor-list-item:hover {
    background: var(--gray-50);
}

.doctor-list-item.active {
    background: var(--primary-light);
    border-left: 3px solid var(--primary-color);
}

.doctor-list-item:last-child {
    border-bottom: none;
}

.doctor-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.doctor-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.doctor-info small {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.schedule-badge {
    background: var(--gray-100);
    color: var(--text-secondary);
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
}

.schedule-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.schedule-header h4 {
    margin: 0 0 0.25rem 0;
}

.schedule-header p {
    margin: 0;
}

.schedule-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.schedule-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
    opacity: 0.6;
}

.schedule-row.active {
    opacity: 1;
    background: var(--primary-light);
}

.schedule-day-toggle {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.day-label {
    font-weight: 500;
    min-width: 100px;
}

.schedule-times {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.schedule-times .form-control {
    width: 130px;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--gray-300);
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: var(--primary-color);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

@media (max-width: 992px) {
    .grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
