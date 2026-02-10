<?php
session_start();
$_SESSION['role'] = 'pelajar';
$activePage = 'maklum-balas';

// Check if program ID is provided
$programId = $_GET['id'] ?? null;
$programName = $_GET['program'] ?? 'Workshop Pemikiran Kritikal';
$programDate = $_GET['date'] ?? '15 Januari 2026';
$programLocation = $_GET['location'] ?? 'Bilik Seminar A';

// Check if form is submitted
$submitted = isset($_POST['submit_feedback']);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maklum Balas Program | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Feedback Form Styling */
        .feedback-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-section {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        
        .form-header {
            margin-bottom: 32px;
        }
        
        .program-info {
            background: var(--background);
            padding: 16px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            border-left: 4px solid var(--primary);
        }
        
        .question-group {
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid var(--border);
        }
        
        .question-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .question-label {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 16px;
            display: block;
        }
        
        /* Star Rating */
        .star-rating {
            display: flex;
            gap: 12px;
            margin: 16px 0;
        }
        
        .star-btn {
            background: none;
            border: none;
            font-size: 32px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 4px;
        }
        
        .star-btn:hover {
            transform: scale(1.1);
        }
        
        .star-btn.active {
            color: #f59e0b;
        }
        
        .star-btn.hovered {
            color: #fbbf24;
        }
        
        /* Radio Group */
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 16px 0;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .radio-option:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .radio-option.selected {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.1);
        }
        
        .radio-input {
            margin-right: 12px;
            width: 18px;
            height: 18px;
        }
        
        .radio-label {
            flex: 1;
            cursor: pointer;
            font-weight: 500;
        }
        
        /* Textarea */
        .feedback-textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 120px;
            margin: 16px 0;
            transition: border-color 0.2s ease;
        }
        
        .feedback-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        /* Success Message */
        .success-card {
            text-align: center;
            padding: 60px 40px;
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            max-width: 500px;
            margin: 40px auto;
        }
        
        .success-icon {
            font-size: 64px;
            color: #10b981;
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        
        .success-message {
            color: var(--text-secondary);
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        /* Rating Labels */
        .rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .required {
            color: #ef4444;
            margin-left: 4px;
        }
        
        .error-message {
            color: #ef4444;
            font-size: 14px;
            margin-top: 8px;
            display: none;
        }
    </style>
</head>
<body>

<div class="app-layout">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOP BAR -->
<header class="topbar"></header>

        <!-- PAGE CONTENT -->
        <section class="content">
            <?php if ($submitted): ?>
            <!-- Success State -->
            <div class="feedback-container">
                <div class="success-card">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="success-title">Terima Kasih!</h2>
                    <p class="success-message">
                        Maklum balas anda telah berjaya dihantar dan akan digunakan untuk penambahbaikan program.
                    </p>
                    <a href="rekod-penyertaan.php" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 32px;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Rekod Penyertaan
                    </a>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Feedback Form -->
            <div class="feedback-container">
                <!-- Header -->
                <div class="welcome-section">
                    <h1 class="page-title">Maklum Balas Program</h1>
                    <p class="page-subtitle">Beri penilaian anda terhadap program yang telah dihadiri</p>
                </div>

                <!-- Program Info -->
                <div class="program-info">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars($programName) ?></h3>
                    <div style="display: flex; gap: 24px; font-size: 14px; color: var(--text-secondary);">
                        <span><i class="fas fa-calendar"></i> <?= htmlspecialchars($programDate) ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($programLocation) ?></span>
                    </div>
                </div>

                <!-- Feedback Form -->
                <form method="POST" class="form-section">
                    <!-- Question 1 -->
                    <div class="question-group">
                        <label class="question-label">
                            1. Bagaimanakah penilaian keseluruhan anda terhadap program ini?<span class="required">*</span>
                        </label>
                        <div class="star-rating" id="rating1">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="star-btn" data-value="<?= $i ?>">
                                    <i class="fas fa-star"></i>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-labels">
                            <span>Sangat Tidak Memuaskan</span>
                            <span>Sangat Memuaskan</span>
                        </div>
                        <input type="hidden" name="penilaian_keseluruhan" id="penilaian_keseluruhan" value="0">
                        <div class="error-message" id="error1">Sila berikan penilaian</div>
                    </div>

                    <!-- Question 2 -->
                    <div class="question-group">
                        <label class="question-label">
                            2. Adakah objektif program ini berjaya dicapai?<span class="required">*</span>
                        </label>
                        <div class="radio-group" id="rating2">
                            <?php
                            $options2 = [
                                'sangat-tidak-setuju' => 'Sangat Tidak Setuju',
                                'tidak-setuju' => 'Tidak Setuju',
                                'neutral' => 'Neutral',
                                'setuju' => 'Setuju',
                                'sangat-setuju' => 'Sangat Setuju'
                            ];
                            
                            foreach ($options2 as $value => $label):
                            ?>
                            <div class="radio-option" data-value="<?= $value ?>">
                                <input type="radio" name="pencapaian_objektif" value="<?= $value ?>" 
                                       id="obj_<?= $value ?>" class="radio-input">
                                <label for="obj_<?= $value ?>" class="radio-label"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="error-message" id="error2">Sila pilih satu pilihan</div>
                    </div>

                    <!-- Question 3 -->
                    <div class="question-group">
                        <label class="question-label">
                            3. Bagaimanakah tahap pengurusan program oleh pihak penganjur?<span class="required">*</span>
                        </label>
                        <div class="star-rating" id="rating3">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="star-btn" data-value="<?= $i ?>">
                                    <i class="fas fa-star"></i>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-labels">
                            <span>Sangat Lemah</span>
                            <span>Sangat Baik</span>
                        </div>
                        <input type="hidden" name="pengurusan_program" id="pengurusan_program" value="0">
                        <div class="error-message" id="error3">Sila berikan penilaian</div>
                    </div>

                    <!-- Question 4 -->
                    <div class="question-group">
                        <label class="question-label">
                            4. Adakah pengisian atau aktiviti yang dijalankan bermanfaat kepada anda?<span class="required">*</span>
                        </label>
                        <div class="radio-group" id="rating4">
                            <?php
                            $options4 = [
                                'sangat-tidak-bermanfaat' => 'Sangat Tidak Bermanfaat',
                                'tidak-bermanfaat' => 'Tidak Bermanfaat',
                                'neutral' => 'Neutral',
                                'bermanfaat' => 'Bermanfaat',
                                'sangat-bermanfaat' => 'Sangat Bermanfaat'
                            ];
                            
                            foreach ($options4 as $value => $label):
                            ?>
                            <div class="radio-option" data-value="<?= $value ?>">
                                <input type="radio" name="kualiti_aktiviti" value="<?= $value ?>" 
                                       id="akt_<?= $value ?>" class="radio-input">
                                <label for="akt_<?= $value ?>" class="radio-label"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="error-message" id="error4">Sila pilih satu pilihan</div>
                    </div>

                    <!-- Question 5 -->
                    <div class="question-group">
                        <label class="question-label">
                            5. Bagaimanakah pengurusan masa dan atur cara program?<span class="required">*</span>
                        </label>
                        <div class="star-rating" id="rating5">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="star-btn" data-value="<?= $i ?>">
                                    <i class="fas fa-star"></i>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-labels">
                            <span>Sangat Tidak Teratur</span>
                            <span>Sangat Teratur</span>
                        </div>
                        <input type="hidden" name="pengurusan_masa" id="pengurusan_masa" value="0">
                        <div class="error-message" id="error5">Sila berikan penilaian</div>
                    </div>

                    <!-- Question 6 -->
                    <div class="question-group">
                        <label class="question-label">
                            6. Sila nyatakan komen atau cadangan penambahbaikan untuk program ini
                        </label>
                        <textarea 
                            name="komen" 
                            class="feedback-textarea" 
                            placeholder="Contoh: Pengurusan masa sangat baik, tetapi aktiviti boleh dipelbagaikan...">
                        </textarea>
                        <p style="font-size: 12px; color: var(--text-secondary); margin-top: -8px;">
                            Komen adalah pilihan tetapi sangat dihargai
                        </p>
                    </div>

                    <div style="padding-top: 24px; border-top: 1px solid var(--border);">
                        <button type="submit" name="submit_feedback" class="btn-primary" style="width: 100%; padding: 16px; font-size: 16px;">
                            <i class="fas fa-paper-plane"></i> Hantar Maklum Balas
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
    // Initialize star ratings
    document.addEventListener('DOMContentLoaded', function() {
        // Setup star ratings
        const starContainers = document.querySelectorAll('.star-rating');
        starContainers.forEach(container => {
            const stars = container.querySelectorAll('.star-btn');
            const hiddenInput = container.nextElementSibling.nextElementSibling;
            
            stars.forEach(star => {
                // Click event
                star.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    
                    // Update star colors
                    stars.forEach(s => {
                        const starValue = parseInt(s.getAttribute('data-value'));
                        if (starValue <= value) {
                            s.classList.add('active');
                            s.querySelector('i').style.color = '#f59e0b';
                        } else {
                            s.classList.remove('active');
                            s.querySelector('i').style.color = '#ddd';
                        }
                    });
                    
                    // Update hidden input
                    hiddenInput.value = value;
                    
                    // Clear error
                    const errorDiv = container.nextElementSibling.nextElementSibling.nextElementSibling;
                    errorDiv.style.display = 'none';
                });
                
                // Hover events
                star.addEventListener('mouseenter', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    
                    stars.forEach(s => {
                        const starValue = parseInt(s.getAttribute('data-value'));
                        if (starValue <= value) {
                            s.classList.add('hovered');
                            s.querySelector('i').style.color = '#fbbf24';
                        }
                    });
                });
                
                star.addEventListener('mouseleave', function() {
                    stars.forEach(s => {
                        s.classList.remove('hovered');
                        
                        // Restore original color
                        const starValue = parseInt(s.getAttribute('data-value'));
                        const currentValue = parseInt(hiddenInput.value);
                        
                        if (starValue <= currentValue) {
                            s.querySelector('i').style.color = '#f59e0b';
                        } else {
                            s.querySelector('i').style.color = '#ddd';
                        }
                    });
                });
            });
        });
        
        // Setup radio options
        const radioOptions = document.querySelectorAll('.radio-option');
        radioOptions.forEach(option => {
            const radioInput = option.querySelector('.radio-input');
            
            option.addEventListener('click', function() {
                // Remove selected class from siblings
                const siblings = this.parentElement.querySelectorAll('.radio-option');
                siblings.forEach(sib => {
                    sib.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Check the radio input
                radioInput.checked = true;
                
                // Clear error
                const errorDiv = this.parentElement.nextElementSibling;
                errorDiv.style.display = 'none';
            });
        });
        
        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let isValid = true;
                
                // Check star ratings
                const starInputs = [
                    { input: document.getElementById('penilaian_keseluruhan'), error: 'error1' },
                    { input: document.getElementById('pengurusan_program'), error: 'error3' },
                    { input: document.getElementById('pengurusan_masa'), error: 'error5' }
                ];
                
                starInputs.forEach(item => {
                    if (item.input.value === '0') {
                        document.getElementById(item.error).style.display = 'block';
                        isValid = false;
                    } else {
                        document.getElementById(item.error).style.display = 'none';
                    }
                });
                
                // Check radio groups
                const radioGroups = [
                    { name: 'pencapaian_objektif', error: 'error2' },
                    { name: 'kualiti_aktiviti', error: 'error4' }
                ];
                
                radioGroups.forEach(group => {
                    const checked = document.querySelector(`input[name="${group.name}"]:checked`);
                    if (!checked) {
                        document.getElementById(group.error).style.display = 'block';
                        isValid = false;
                    } else {
                        document.getElementById(group.error).style.display = 'none';
                    }
                });
                
                if (isValid) {
                    // Submit form
                    this.submit();
                } else {
                    // Scroll to first error
                    const firstError = document.querySelector('.error-message[style*="display: block"]');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        }
    });
</script>

</body>
</html>