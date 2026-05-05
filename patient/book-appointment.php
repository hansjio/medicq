<?php
/**
 * MEDICQ - Book Appointment Wizard
 */

$pageTitle = 'Book Appointment';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/doctor.php';
require_once __DIR__ . '/../includes/appointment.php';

requireRole('patient');

$doctorModel = new Doctor();
$appointmentModel = new Appointment();

// Get all doctors and specializations
$doctors = $doctorModel->getAll();
$specializations = $doctorModel->getSpecializations();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $doctorId = (int)$_POST['doctor_id'];
    $date = sanitize($_POST['appointment_date']);
    $time = sanitize($_POST['appointment_time']);
    $consultationType = sanitize($_POST['consultation_type']);
    $reason = sanitize($_POST['reason'] ?? '');
    
    $result = $appointmentModel->create($_SESSION['user_id'], $doctorId, $date, $time, $consultationType, $reason);
    
    if ($result['success']) {
        setFlashMessage('success', 'Your appointment has been booked successfully! Waiting for confirmation.');
        redirect(SITE_URL . '/patient/appointments.php');
    } else {
        setFlashMessage('danger', $result['message']);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 900px;">
    <div class="mb-8 text-center">
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-2);">Book an Appointment</h1>
        <p class="text-muted">Schedule your medical consultation in just a few steps</p>
    </div>
    
    <!-- Progress Steps -->
    <div class="wizard-progress">
        <div class="wizard-step">
            <div class="step-item active" data-step="1">
                <div class="step-number">1</div>
                <span class="step-label">Type</span>
            </div>
            <div class="step-connector"></div>
            <div class="step-item" data-step="2">
                <div class="step-number">2</div>
                <span class="step-label">Doctor</span>
            </div>
            <div class="step-connector"></div>
            <div class="step-item" data-step="3">
                <div class="step-number">3</div>
                <span class="step-label">Schedule</span>
            </div>
            <div class="step-connector"></div>
            <div class="step-item" data-step="4">
                <div class="step-number">4</div>
                <span class="step-label">Review</span>
            </div>
        </div>
    </div>
    
    <form id="bookingForm" method="POST">
        <!-- Step 1: Consultation Type -->
        <div class="wizard-content" id="step1">
            <div class="card">
                <div class="card-body">
                    <h3 style="margin-bottom: var(--spacing-2);">Choose Consultation Type</h3>
                    <p class="text-muted mb-6">Select how you'd like to have your consultation</p>
                    
                    <div class="option-cards">
                        <label class="option-card selected">
                            <input type="radio" name="consultation_type" value="in-person" checked>
                            <div class="option-radio"></div>
                            <div class="option-content">
                                <h4><i class="fas fa-user" style="color: var(--primary); margin-right: var(--spacing-2);"></i> In-Person</h4>
                                <p>Visit the clinic</p>
                            </div>
                        </label>
                        
                        <label class="option-card">
                            <input type="radio" name="consultation_type" value="video-call">
                            <div class="option-radio"></div>
                            <div class="option-content">
                                <h4><i class="fas fa-video" style="color: var(--primary); margin-right: var(--spacing-2);"></i> Video Call</h4>
                                <p>Consult via video</p>
                            </div>
                        </label>
                        
                        <label class="option-card">
                            <input type="radio" name="consultation_type" value="phone-call">
                            <div class="option-radio"></div>
                            <div class="option-content">
                                <h4><i class="fas fa-phone" style="color: var(--primary); margin-right: var(--spacing-2);"></i> Phone Call</h4>
                                <p>Consult via phone</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Select Doctor -->
        <div class="wizard-content d-none" id="step2">
            <div class="card">
                <div class="card-body">
                    <h3 style="margin-bottom: var(--spacing-2);">Select a Doctor</h3>
                    <p class="text-muted mb-6">Choose your preferred healthcare provider</p>
                    
                    <!-- Filter by Specialization -->
                    <div class="form-group">
                        <label class="form-label">Filter by Specialization</label>
                        <select id="specializationFilter" class="form-control" style="max-width: 300px;">
                            <option value="">All Specializations</option>
                            <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec); ?>"><?php echo htmlspecialchars($spec); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="doctors-grid" id="doctorsGrid">
                        <?php foreach ($doctors as $doc): ?>
                        <div class="doctor-card" data-doctor-id="<?php echo $doc['id']; ?>" data-specialization="<?php echo htmlspecialchars($doc['specialization']); ?>">
                            <div class="doctor-card-header">
                                <div class="doctor-card-avatar">
                                    <?php echo strtoupper(substr($doc['full_name'], 0, 1)); ?>
                                </div>
                                <div class="doctor-card-info">
                                    <h4><?php echo htmlspecialchars($doc['full_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($doc['specialization']); ?></p>
                                </div>
                            </div>
                            <div class="doctor-card-details">
                                <p><i class="fas fa-hospital"></i> <?php echo htmlspecialchars($doc['clinic_name']); ?></p>
                                <p><i class="fas fa-clock"></i> <?php echo $doc['years_experience']; ?> years experience</p>
                                <p><i class="fas fa-peso-sign"></i> PHP <?php echo number_format($doc['consultation_fee'], 2); ?></p>
                            </div>
                            <input type="radio" name="doctor_id" value="<?php echo $doc['id']; ?>" style="display: none;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Select Date & Time -->
        <div class="wizard-content d-none" id="step3">
            <div class="card">
                <div class="card-body">
                    <h3 style="margin-bottom: var(--spacing-2);">Select Date & Time</h3>
                    <p class="text-muted mb-6">Choose your preferred appointment slot</p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-6);">
                        <div class="form-group">
                            <label class="form-label">Select Date</label>
                            <input type="date" id="appointmentDate" name="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Available Time Slots</label>
                            <div id="timeSlotsContainer">
                                <p class="text-muted">Please select a date first</p>
                            </div>
                            <input type="hidden" name="appointment_time" id="selectedTime">
                        </div>
                    </div>
                    
                    <div class="form-group mt-6">
                        <label class="form-label">Reason for Visit (Optional)</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Briefly describe your reason for this appointment..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 4: Review & Confirm -->
        <div class="wizard-content d-none" id="step4">
            <div class="card">
                <div class="card-body">
                    <h3 style="margin-bottom: var(--spacing-2);">Review Your Appointment</h3>
                    <p class="text-muted mb-6">Please confirm the details below</p>
                    
                    <div class="profile-section" style="background: var(--gray-50); margin: 0;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-6);">
                            <div>
                                <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Consultation Type</p>
                                <p id="reviewType" style="font-weight: 500; margin: 0;"></p>
                            </div>
                            <div>
                                <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Doctor</p>
                                <p id="reviewDoctor" style="font-weight: 500; margin: 0;"></p>
                            </div>
                            <div>
                                <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Date</p>
                                <p id="reviewDate" style="font-weight: 500; margin: 0;"></p>
                            </div>
                            <div>
                                <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Time</p>
                                <p id="reviewTime" style="font-weight: 500; margin: 0;"></p>
                            </div>
                            <div style="grid-column: span 2;">
                                <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-1);">Reason for Visit</p>
                                <p id="reviewReason" style="font-weight: 500; margin: 0;"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-6" style="margin-bottom: 0;">
                        <i class="fas fa-info-circle"></i>
                        <span>Your appointment will be pending until confirmed by the doctor.</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="wizard-actions">
            <button type="button" id="btnBack" class="btn btn-secondary d-none">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <div style="flex: 1;"></div>
            <button type="button" id="btnNext" class="btn btn-primary">
                Continue <i class="fas fa-arrow-right"></i>
            </button>
            <button type="submit" name="submit_booking" id="btnSubmit" class="btn btn-primary d-none">
                <i class="fas fa-check"></i> Confirm Booking
            </button>
        </div>
    </form>
</div>

<script>
const SITE_URL = '<?php echo SITE_URL; ?>';

let currentStep = 1;
const totalSteps = 4;

// DOM Elements
const steps = document.querySelectorAll('.step-item');
const stepContents = [
    document.getElementById('step1'),
    document.getElementById('step2'),
    document.getElementById('step3'),
    document.getElementById('step4')
];
const connectors = document.querySelectorAll('.step-connector');
const btnBack = document.getElementById('btnBack');
const btnNext = document.getElementById('btnNext');
const btnSubmit = document.getElementById('btnSubmit');

// Selected values
let selectedData = {
    consultationType: 'in-person',
    doctorId: null,
    doctorName: '',
    date: '',
    time: ''
};

// Initialize option cards
document.querySelectorAll('.option-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
        selectedData.consultationType = this.querySelector('input').value;
    });
});

// Initialize doctor cards
document.querySelectorAll('.doctor-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.doctor-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
        selectedData.doctorId = this.dataset.doctorId;
        selectedData.doctorName = this.querySelector('h4').textContent;
    });
});

// Specialization filter
document.getElementById('specializationFilter').addEventListener('change', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.doctor-card').forEach(card => {
        if (!filter || card.dataset.specialization.toLowerCase() === filter) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});

// Date selection - load available slots
document.getElementById('appointmentDate').addEventListener('change', function() {
    const date = this.value;
    const doctorId = selectedData.doctorId;
    
    if (!date || !doctorId) return;
    
    selectedData.date = date;
    
    const container = document.getElementById('timeSlotsContainer');
    container.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading available slots...</p>';
    
    fetch(`${SITE_URL}/api/get-slots.php?doctor_id=${doctorId}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.slots.length > 0) {
                container.innerHTML = `
                    <div class="time-slots-grid">
                        ${data.slots.map(slot => `
                            <div class="time-slot" data-time="${slot.start}">${slot.display}</div>
                        `).join('')}
                    </div>
                `;
                
                // Add click handlers
                container.querySelectorAll('.time-slot').forEach(slot => {
                    slot.addEventListener('click', function() {
                        container.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedData.time = this.dataset.time;
                        document.getElementById('selectedTime').value = this.dataset.time;
                    });
                });
            } else {
                container.innerHTML = '<p class="text-muted">No available slots for this date. Please select another date.</p>';
            }
        })
        .catch(err => {
            container.innerHTML = '<p class="text-danger">Failed to load available slots. Please try again.</p>';
        });
});

// Navigation
btnNext.addEventListener('click', () => {
    if (validateStep(currentStep)) {
        goToStep(currentStep + 1);
    }
});

btnBack.addEventListener('click', () => {
    goToStep(currentStep - 1);
});

function validateStep(step) {
    switch(step) {
        case 1:
            return true; // Always has a default selection
        case 2:
            if (!selectedData.doctorId) {
                alert('Please select a doctor');
                return false;
            }
            return true;
        case 3:
            if (!selectedData.date) {
                alert('Please select a date');
                return false;
            }
            if (!selectedData.time) {
                alert('Please select a time slot');
                return false;
            }
            return true;
        default:
            return true;
    }
}

function goToStep(step) {
    if (step < 1 || step > totalSteps) return;
    
    // Update step indicators
    steps.forEach((s, i) => {
        s.classList.remove('active', 'completed');
        if (i + 1 < step) {
            s.classList.add('completed');
        } else if (i + 1 === step) {
            s.classList.add('active');
        }
    });
    
    // Update connectors
    connectors.forEach((c, i) => {
        c.classList.toggle('completed', i + 1 < step);
    });
    
    // Show/hide content
    stepContents.forEach((content, i) => {
        content.classList.toggle('d-none', i + 1 !== step);
    });
    
    // Update buttons
    btnBack.classList.toggle('d-none', step === 1);
    btnNext.classList.toggle('d-none', step === totalSteps);
    btnSubmit.classList.toggle('d-none', step !== totalSteps);
    
    // Update review on step 4
    if (step === 4) {
        updateReview();
    }
    
    currentStep = step;
}

function updateReview() {
    const typeLabels = {
        'in-person': '<i class="fas fa-user"></i> In-Person',
        'video-call': '<i class="fas fa-video"></i> Video Call',
        'phone-call': '<i class="fas fa-phone"></i> Phone Call'
    };
    
    document.getElementById('reviewType').innerHTML = typeLabels[selectedData.consultationType];
    document.getElementById('reviewDoctor').textContent = selectedData.doctorName;
    document.getElementById('reviewDate').textContent = new Date(selectedData.date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    // Format time
    const timeDate = new Date('2000-01-01 ' + selectedData.time);
    document.getElementById('reviewTime').textContent = timeDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    
    const reason = document.querySelector('textarea[name="reason"]').value;
    document.getElementById('reviewReason').textContent = reason || 'Not specified';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
