<?php
/**
 * MEDICQ - Admin: Manage Doctor Schedules
 * Place this file at: admin/schedules.php
 *
 * FIXES applied:
 *   - d.specialization   (was: d.specialty — column doesn't exist)
 *   - Title Case day names in INSERT  (was: lowercase 'monday' etc.)
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

$message = '';
$error   = '';

// Title-Case day names to match the ENUM in doctor_schedules
$days = [
    'monday'    => 'Monday',
    'tuesday'   => 'Tuesday',
    'wednesday' => 'Wednesday',
    'thursday'  => 'Thursday',
    'friday'    => 'Friday',
    'saturday'  => 'Saturday',
    'sunday'    => 'Sunday',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $doctorId    = intval($_POST['doctor_id']);
    $slotDuration = intval($_POST['slot_duration'] ?? 30);

    // Delete existing schedules for this doctor
    $stmt = $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
    $stmt->execute([$doctorId]);

    // Insert new rows using Title Case day names
    foreach ($days as $key => $titleCaseDay) {
        if (!empty($_POST['available_' . $key])) {
            $startTime = $_POST['start_' . $key] ?? '09:00';
            $endTime   = $_POST['end_'   . $key] ?? '17:00';

            $stmt = $pdo->prepare("
                INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration, is_available)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            // FIX: $titleCaseDay is 'Monday', 'Tuesday' etc. — matches the ENUM
            $stmt->execute([$doctorId, $titleCaseDay, $startTime, $endTime, $slotDuration]);
        }
    }
    $message = 'Schedule updated successfully!';
}

// FIX: use d.specialization, not d.specialty
$stmt = $pdo->query("
    SELECT d.id, d.specialization, u.full_name
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE u.is_active = 1
    ORDER BY u.full_name
");
$doctors = $stmt->fetchAll();

// Load schedules for all doctors (keyed by Title-Case day)
$schedules = [];
foreach ($doctors as $doc) {
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ?");
    $stmt->execute([$doc['id']]);
    $rows = $stmt->fetchAll();
    $schedules[$doc['id']] = [];
    foreach ($rows as $row) {
        $schedules[$doc['id']][$row['day_of_week']] = $row; // key is 'Monday' etc.
    }
}

$selectedDoctor = $_GET['doctor_id'] ?? ($doctors[0]['id'] ?? null);

$pageTitle = 'Manage Schedules';
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
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">Manage Schedules</h1>
        <p class="text-muted">Set working hours for doctors</p>
    </div>

    <?php if (empty($doctors)): ?>
    <div class="card"><div class="card-body">
        <p class="text-muted text-center py-4">No active doctors found. Please add doctors first.</p>
        <div class="text-center"><a href="doctors.php" class="btn btn-primary">Add Doctor</a></div>
    </div></div>
    <?php else: ?>

    <div style="display: grid; grid-template-columns: 280px 1fr; gap: var(--spacing-6);">
        <!-- Doctor list sidebar -->
        <div class="card">
            <div class="card-header"><h4 style="margin:0;">Doctors</h4></div>
            <div class="card-body" style="padding:0;">
                <?php foreach ($doctors as $doc): ?>
                <a href="?doctor_id=<?php echo $doc['id']; ?>"
                   style="display:flex; align-items:center; gap: var(--spacing-3); padding: var(--spacing-3) var(--spacing-4);
                          text-decoration:none; color: var(--text-primary); border-bottom: 1px solid var(--gray-100);
                          <?php echo $selectedDoctor == $doc['id'] ? 'background: var(--primary-light); border-left: 3px solid var(--primary);' : ''; ?>">
                    <div style="width:36px; height:36px; border-radius:50%; background: var(--primary); color:white;
                                display:flex; align-items:center; justify-content:center; font-weight:600; flex-shrink:0;">
                        <?php echo strtoupper(substr($doc['full_name'], 0, 1)); ?>
                    </div>
                    <div style="flex:1;">
                        <strong style="font-size: var(--font-size-sm);"><?php echo htmlspecialchars($doc['full_name']); ?></strong><br>
                        <span class="text-muted" style="font-size:12px;"><?php echo htmlspecialchars($doc['specialization']); ?></span>
                    </div>
                    <?php if (!empty($schedules[$doc['id']])): ?>
                    <span style="background: var(--gray-100); font-size:11px; padding: 2px 8px; border-radius:99px;">
                        <?php echo count($schedules[$doc['id']]); ?> days
                    </span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Schedule editor -->
        <div class="card">
            <div class="card-header"><h4 style="margin:0;">Weekly Schedule</h4></div>
            <div class="card-body">
                <?php
                $currentDoctor = null;
                foreach ($doctors as $doc) {
                    if ($doc['id'] == $selectedDoctor) { $currentDoctor = $doc; break; }
                }
                if ($currentDoctor):
                    $doctorSchedule = $schedules[$currentDoctor['id']] ?? [];
                ?>
                <form method="POST">
                    <input type="hidden" name="update_schedule" value="1">
                    <input type="hidden" name="doctor_id" value="<?php echo $currentDoctor['id']; ?>">

                    <div style="margin-bottom: var(--spacing-6);">
                        <h4 style="margin: 0 0 var(--spacing-1);">Dr. <?php echo htmlspecialchars($currentDoctor['full_name']); ?></h4>
                        <p class="text-muted" style="margin:0;"><?php echo htmlspecialchars($currentDoctor['specialization']); ?></p>
                    </div>

                    <div class="form-group mb-6">
                        <label class="form-label">Appointment Duration</label>
                        <select name="slot_duration" class="form-control" style="max-width:200px;">
                            <?php foreach ([15, 30, 45, 60] as $dur): ?>
                            <option value="<?php echo $dur; ?>"><?php echo $dur; ?> minutes</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; min-width:500px;">
                            <thead>
                                <tr style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                                    <th style="padding: var(--spacing-3); text-align:left; width:130px;">Day</th>
                                    <th style="padding: var(--spacing-3); text-align:left; width:90px;">Available</th>
                                    <th style="padding: var(--spacing-3); text-align:left;">Start</th>
                                    <th style="padding: var(--spacing-3); text-align:left;">End</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($days as $key => $label):
                                    // Lookup by Title Case key (e.g. 'Monday') — matches how we stored it
                                    $sched       = $doctorSchedule[$label] ?? null;
                                    $isAvailable = $sched !== null;
                                ?>
                                <tr style="border-bottom: 1px solid var(--gray-100);">
                                    <td style="padding: var(--spacing-3);"><strong><?php echo $label; ?></strong></td>
                                    <td style="padding: var(--spacing-3);">
                                        <input type="checkbox" name="available_<?php echo $key; ?>" value="1"
                                               <?php echo $isAvailable ? 'checked' : ''; ?>
                                               onchange="toggleDay('<?php echo $key; ?>', this.checked)">
                                    </td>
                                    <td style="padding: var(--spacing-3);">
                                        <input type="time" name="start_<?php echo $key; ?>" id="start_<?php echo $key; ?>"
                                               class="form-control" style="max-width:130px;"
                                               value="<?php echo $sched ? substr($sched['start_time'], 0, 5) : '09:00'; ?>"
                                               <?php echo !$isAvailable ? 'disabled' : ''; ?>>
                                    </td>
                                    <td style="padding: var(--spacing-3);">
                                        <input type="time" name="end_<?php echo $key; ?>" id="end_<?php echo $key; ?>"
                                               class="form-control" style="max-width:130px;"
                                               value="<?php echo $sched ? substr($sched['end_time'], 0, 5) : '17:00'; ?>"
                                               <?php echo !$isAvailable ? 'disabled' : ''; ?>>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: var(--spacing-6);">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Schedule
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <p class="text-muted text-center py-4">Select a doctor from the left to edit their schedule.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleDay(key, enabled) {
    ['start_' + key, 'end_' + key].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !enabled;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
