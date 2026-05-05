<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

// Get statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'");
$stats['total_patients'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
$stats['total_doctors'] = $stmt->fetchColumn();

// Appointments stats
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments");
$stats['total_appointments'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$stats['pending_appointments'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'confirmed'");
$stats['confirmed_appointments'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'");
$stats['completed_appointments'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()");
$stats['today_appointments'] = $stmt->fetchColumn();

// New patients this month
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stats['new_patients_month'] = $stmt->fetchColumn();

// Recent appointments
$stmt = $pdo->query("
    SELECT a.*, u.full_name as patient_name, d.specialty, du.full_name as doctor_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users du ON d.user_id = du.id
    ORDER BY a.created_at DESC
    LIMIT 10
");
$recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent users
$stmt = $pdo->query("
    SELECT * FROM users 
    WHERE role = 'patient'
    ORDER BY created_at DESC
    LIMIT 5
");
$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Admin Dashboard</h1>
            <p class="text-muted">System overview and management</p>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid stats-grid-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary-light">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $stats['total_patients']; ?></span>
                    <span class="stat-label">Total Patients</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-success-light">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $stats['total_doctors']; ?></span>
                    <span class="stat-label">Total Doctors</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-warning-light">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $stats['total_appointments']; ?></span>
                    <span class="stat-label">Total Appointments</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-info-light">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $stats['today_appointments']; ?></span>
                    <span class="stat-label">Today's Appointments</span>
                </div>
            </div>
        </div>
        
        <!-- Secondary Stats -->
        <div class="stats-grid stats-grid-3 mt-4">
            <div class="stat-card stat-card-sm">
                <span class="stat-value text-warning"><?php echo $stats['pending_appointments']; ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card stat-card-sm">
                <span class="stat-value text-primary"><?php echo $stats['confirmed_appointments']; ?></span>
                <span class="stat-label">Confirmed</span>
            </div>
            <div class="stat-card stat-card-sm">
                <span class="stat-value text-success"><?php echo $stats['completed_appointments']; ?></span>
                <span class="stat-label">Completed</span>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="doctors.php" class="quick-action-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        Add New Doctor
                    </a>
                    <a href="appointments.php" class="quick-action-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Manage Appointments
                    </a>
                    <a href="patients.php" class="quick-action-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        View Patients
                    </a>
                    <a href="schedules.php" class="quick-action-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Manage Schedules
                    </a>
                </div>
            </div>
        </div>
        
        <div class="grid grid-2 mt-4">
            <!-- Recent Appointments -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Appointments</h3>
                    <a href="appointments.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentAppointments)): ?>
                        <p class="text-muted text-center">No appointments yet</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recentAppointments, 0, 5) as $apt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($apt['doctor_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($apt['specialty']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $apt['status']; ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Patients -->
            <div class="card">
                <div class="card-header">
                    <h3>New Patients</h3>
                    <a href="patients.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentUsers)): ?>
                        <p class="text-muted text-center">No patients yet</p>
                    <?php else: ?>
                        <div class="user-list">
                            <?php foreach ($recentUsers as $user): ?>
                            <div class="user-list-item">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                                <div class="user-info">
                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M d', strtotime($user['created_at'])); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.stats-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.stats-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.stat-card-sm {
    text-align: center;
    padding: 1rem;
}

.stat-card-sm .stat-value {
    font-size: 1.5rem;
}

.quick-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    background: var(--primary-color);
    color: white;
}

.user-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.user-list-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.user-list-item:last-child {
    border-bottom: none;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.user-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.user-info span {
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .stats-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
