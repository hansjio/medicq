<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/doctor.php';

requireLogin();
requireRole('doctor');

// FIX: Doctor() takes no arguments — remove $pdo
$doctorObj  = new Doctor();
$doctorInfo = $doctorObj->getByUserId($_SESSION['user_id']);

if (!$doctorInfo) {
    redirect(SITE_URL . '/logout.php');
}

$message = '';
$error   = '';

// FIX: day names must be Title Case to match the ENUM in the DB schema
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_schedule'])) {
        // Delete existing schedules for this doctor
        $stmt = $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
        $stmt->execute([$doctorInfo['id']]);

        // Insert new schedule rows (Title Case day names)
        foreach ($days as $day) {
            $key = strtolower($day); // form fields are still lowercase for convenience
            if (!empty($_POST['available_' . $key])) {
                $startTime    = $_POST['start_' . $key] ?? '09:00';
                $endTime      = $_POST['end_' . $key]   ?? '17:00';
                $slotDuration = intval($_POST['slot_duration'] ?? 30);

                $stmt = $pdo->prepare(
                    "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration, is_available)
                     VALUES (?, ?, ?, ?, ?, 1)"
                );
                // FIX: $day is already Title Case e.g. 'Monday'
                $stmt->execute([$doctorInfo['id'], $day, $startTime, $endTime, $slotDuration]);
            }
        }
        $message = 'Schedule updated successfully!';
        // Reload doctor info and schedules after save
        $doctorInfo = $doctorObj->getByUserId($_SESSION['user_id']);
    }

    if (isset($_POST['block_slot'])) {
        $blockDate  = $_POST['block_date'];
        $blockStart = $_POST['block_start'];
        $blockEnd   = $_POST['block_end'];
        $blockReason = sanitize($_POST['block_reason'] ?? '');

        $stmt = $pdo->prepare(
            "INSERT INTO blocked_slots (doctor_id, blocked_date, start_time, end_time, reason)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$doctorInfo['id'], $blockDate, $blockStart, $blockEnd, $blockReason]);
        $message = 'Time slot blocked successfully!';
    }

    if (isset($_POST['unblock_slot'])) {
        $slotId = intval($_POST['slot_id']);
        $stmt = $pdo->prepare("DELETE FROM blocked_slots WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$slotId, $doctorInfo['id']]);
        $message = 'Time slot unblocked successfully!';
    }
}

// Load current schedule (keyed by day name)
$stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ?");
$stmt->execute([$doctorInfo['id']]);
$scheduleRows = $stmt->fetchAll();

$schedule = [];
foreach ($scheduleRows as $row) {
    $schedule[$row['day_of_week']] = $row; // key is Title Case e.g. 'Monday'
}

// Load blocked slots
$stmt = $pdo->prepare(
    "SELECT * FROM blocked_slots WHERE doctor_id = ? AND blocked_date >= CURDATE() ORDER BY blocked_date, start_time"
);
$stmt->execute([$doctorInfo['id']]);
$blockedSlots = $stmt->fetchAll();

$pageTitle = 'My Schedule';
require_once '../includes/header.php';
?>

<div class="container">
    <?php if ($message): ?>
    <div class="alert alert-success" data-dismiss><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger" data-dismiss><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="mb-8">
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">My Schedule</h1>
        <p class="text-muted">Set your weekly availability and block specific time slots</p>
    </div>

    <!-- Weekly Schedule Form -->
    <div class="card mb-6">
        <div class="card-header"><h4 style="margin:0;">Weekly Availability</h4></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group mb-4">
                    <label class="form-label">Appointment Slot Duration</label>
                    <select name="slot_duration" class="form-control" style="max-width:200px;">
                        <?php
                        $currentDuration = !empty($scheduleRows) ? $scheduleRows[0]['slot_duration'] : 30;
                        foreach ([15, 30, 45, 60] as $dur):
                        ?>
                        <option value="<?php echo $dur; ?>" <?php echo $currentDuration == $dur ? 'selected' : ''; ?>>
                            <?php echo $dur; ?> minutes
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; min-width:600px;">
                        <thead>
                            <tr style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                                <th style="padding: var(--spacing-3); text-align:left; width:130px;">Day</th>
                                <th style="padding: var(--spacing-3); text-align:left; width:100px;">Available</th>
                                <th style="padding: var(--spacing-3); text-align:left;">Start Time</th>
                                <th style="padding: var(--spacing-3); text-align:left;">End Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($days as $day):
                                $key  = strtolower($day);
                                $sched = $schedule[$day] ?? null; // lookup by Title Case
                                $isAvailable = $sched !== null;
                            ?>
                            <tr style="border-bottom: 1px solid var(--gray-100);">
                                <td style="padding: var(--spacing-3);"><strong><?php echo $day; ?></strong></td>
                                <td style="padding: var(--spacing-3);">
                                    <input type="checkbox" name="available_<?php echo $key; ?>" value="1"
                                           id="avail_<?php echo $key; ?>"
                                           <?php echo $isAvailable ? 'checked' : ''; ?>
                                           onchange="toggleDay('<?php echo $key; ?>', this.checked)">
                                </td>
                                <td style="padding: var(--spacing-3);">
                                    <input type="time" name="start_<?php echo $key; ?>"
                                           class="form-control" style="max-width:140px;"
                                           value="<?php echo $sched ? substr($sched['start_time'], 0, 5) : '09:00'; ?>"
                                           <?php echo !$isAvailable ? 'disabled' : ''; ?>
                                           id="start_<?php echo $key; ?>">
                                </td>
                                <td style="padding: var(--spacing-3);">
                                    <input type="time" name="end_<?php echo $key; ?>"
                                           class="form-control" style="max-width:140px;"
                                           value="<?php echo $sched ? substr($sched['end_time'], 0, 5) : '17:00'; ?>"
                                           <?php echo !$isAvailable ? 'disabled' : ''; ?>
                                           id="end_<?php echo $key; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: var(--spacing-4);">
                    <button type="submit" name="update_schedule" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Block a Specific Time Slot -->
    <div class="card mb-6">
        <div class="card-header"><h4 style="margin:0;">Block a Time Slot</h4></div>
        <div class="card-body">
            <form method="POST" style="display:grid; grid-template-columns: repeat(4, 1fr) auto; gap: var(--spacing-4); align-items:flex-end;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Date</label>
                    <input type="date" name="block_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="block_start" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">End Time</label>
                    <input type="time" name="block_end" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Reason (optional)</label>
                    <input type="text" name="block_reason" class="form-control" placeholder="e.g. Meeting, Break">
                </div>
                <button type="submit" name="block_slot" class="btn btn-warning">
                    <i class="fas fa-ban"></i> Block
                </button>
            </form>
        </div>
    </div>

    <!-- Blocked Slots List -->
    <?php if (!empty($blockedSlots)): ?>
    <div class="card">
        <div class="card-header"><h4 style="margin:0;">Upcoming Blocked Slots</h4></div>
        <div class="card-body" style="padding:0;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left;">Date</th>
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left;">Time</th>
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left;">Reason</th>
                        <th style="padding: var(--spacing-3) var(--spacing-4); text-align:left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blockedSlots as $slot): ?>
                    <tr style="border-bottom: 1px solid var(--gray-100);">
                        <td style="padding: var(--spacing-3) var(--spacing-4);"><?php echo formatDate($slot['blocked_date']); ?></td>
                        <td style="padding: var(--spacing-3) var(--spacing-4);"><?php echo formatTime($slot['start_time']); ?> – <?php echo formatTime($slot['end_time']); ?></td>
                        <td style="padding: var(--spacing-3) var(--spacing-4);"><?php echo htmlspecialchars($slot['reason'] ?: '—'); ?></td>
                        <td style="padding: var(--spacing-3) var(--spacing-4);">
                            <form method="POST" onsubmit="return confirm('Unblock this slot?')">
                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                <button type="submit" name="unblock_slot" class="btn btn-sm btn-link text-danger">
                                    <i class="fas fa-unlock"></i> Unblock
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleDay(day, enabled) {
    ['start_' + day, 'end_' + day].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !enabled;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>