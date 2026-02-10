<?php
session_start();
$_SESSION['role'] = 'penganjur';
$activePage = 'hebahan-program';

// Check if form is submitted
$submitted = isset($_POST['submit_program']);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hebahan Program | UKMInvolve</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Form Styling */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-section {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin: 32px 0;
        }
        
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-full-width {
                grid-column: span 2;
            }
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .required {
            color: #ef4444;
            font-weight: bold;
        }
        
        .form-input {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-textarea {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            resize: vertical;
            min-height: 150px;
            transition: border-color 0.2s ease;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 48px;
        }
        
        .form-actions {
            display: flex;
            gap: 16px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        
        /* Success State */
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
        
        /* Form Tips */
        .form-tips {
            background: var(--background);
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 24px;
            border-left: 4px solid var(--primary);
        }
        
        .form-tips h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-tips ul {
            margin: 0;
            padding-left: 20px;
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .form-tips li {
            margin-bottom: 6px;
        }
        
        /* Button Styles */
        .btn-outline {
            padding: 14px 24px;
            background: transparent;
            border: 2px solid var(--border);
            color: var(--text-primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }
        
        .btn-outline:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .btn-primary {
            padding: 14px 24px;
            background: var(--primary);
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
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
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="success-title">Program Berjaya Diterbitkan!</h2>
                <p class="success-message">
                    Program anda telah diterbitkan dan boleh dilihat oleh pelajar. 
                    Anda boleh urus program ini di halaman "Urus Program".
                </p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <a href="urus-program.php" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 24px;">
                        <i class="fas fa-cog"></i> Urus Program
                    </a>
                    <a href="hebahan-program.php" class="btn-outline" style="display: inline-block; width: auto; padding: 12px 24px;">
                        <i class="fas fa-plus"></i> Program Baru
                    </a>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Form State -->
            <div class="form-container">
                <!-- Header -->
                <div class="welcome-section">
                    <h1 class="page-title">Hebahan Program</h1>
                    <p class="page-subtitle">Cipta dan terbitkan program baharu untuk pelajar</p>
                </div>

                <!-- Form -->
                <form method="POST" class="form-section">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                        Maklumat Program
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 24px;">
                        Sila isi semua maklumat yang diperlukan
                    </p>

                    <div class="form-grid">
                        <!-- Nama Program -->
                        <div class="form-group form-full-width">
                            <label class="form-label">
                                Nama Program <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="nama_program" 
                                class="form-input" 
                                placeholder="Contoh: Workshop Kepimpinan Mahasiswa"
                                required
                            >
                        </div>

                        <!-- Kategori -->
                        <div class="form-group">
                            <label class="form-label">
                                Kategori <span class="required">*</span>
                            </label>
                            <select name="kategori" class="form-input form-select" required>
                                <option value="">Pilih kategori</option>
                                <option value="kepimpinan">Kepimpinan</option>
                                <option value="teknologi">Teknologi & IT</option>
                                <option value="komuniti">Khidmat Komuniti</option>
                                <option value="sukan">Sukan & Kesihatan</option>
                                <option value="seni">Seni & Budaya</option>
                                <option value="akademik">Akademik</option>
                                <option value="kerjaya">Kerjaya</option>
                                <option value="antarabangsa">Antarabangsa</option>
                            </select>
                        </div>

                        <!-- Kapasiti -->
                        <div class="form-group">
                            <label class="form-label">
                                Kapasiti Peserta <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="kapasiti" 
                                class="form-input" 
                                placeholder="100"
                                min="1"
                                required
                            >
                        </div>

                        <!-- Tarikh -->
                        <div class="form-group">
                            <label class="form-label">
                                Tarikh Program <span class="required">*</span>
                            </label>
                            <input 
                                type="date" 
                                name="tarikh" 
                                class="form-input" 
                                required
                            >
                        </div>

                        <!-- Masa -->
                        <div class="form-group">
                            <label class="form-label">
                                Masa <span class="required">*</span>
                            </label>
                            <input 
                                type="time" 
                                name="masa" 
                                class="form-input" 
                                required
                            >
                        </div>

                        <!-- Lokasi -->
                        <div class="form-group form-full-width">
                            <label class="form-label">
                                Lokasi <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="lokasi" 
                                class="form-input" 
                                placeholder="Contoh: Dewan Tun Canselor, UKM"
                                required
                            >
                        </div>

                        <!-- Penerangan -->
                        <div class="form-group form-full-width">
                            <label class="form-label">
                                Penerangan Program <span class="required">*</span>
                            </label>
                            <textarea 
                                name="penerangan" 
                                class="form-textarea" 
                                placeholder="Berikan penerangan terperinci mengenai program, objektif, aktiviti, dan apa yang pelajar akan pelajari..."
                                required
                            ></textarea>
                        </div>
                    </div>

                    <!-- Form Tips -->
                    <div class="form-tips">
                        <h4><i class="fas fa-lightbulb"></i> Tips untuk Program yang Baik</h4>
                        <ul>
                            <li>Berikan penerangan yang jelas dan menarik</li>
                            <li>Pilih kategori yang paling relevan</li>
                            <li>Pastikan maklumat tarikh dan masa tepat</li>
                            <li>Sediakan kapasiti yang realistik</li>
                            <li>Nyatakan lokasi dengan jelas termasuk sebarang arahan tambahan</li>
                        </ul>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn-outline" onclick="saveDraft()">
                            <i class="fas fa-save"></i> Simpan Draf
                        </button>
                        <button type="submit" name="submit_program" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> Terbitkan Program
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
    // Set default date to tomorrow
    document.addEventListener('DOMContentLoaded', function() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const formattedDate = tomorrow.toISOString().split('T')[0];
        
        const dateInput = document.querySelector('input[name="tarikh"]');
        if (dateInput && !dateInput.value) {
            dateInput.value = formattedDate;
        }
        
        // Set default time to 10:00 AM
        const timeInput = document.querySelector('input[name="masa"]');
        if (timeInput && !timeInput.value) {
            timeInput.value = '10:00';
        }
        
        // Add form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Check if date is not in the past
                const dateInput = document.querySelector('input[name="tarikh"]');
                const selectedDate = new Date(dateInput.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    e.preventDefault();
                    alert('Tarikh program tidak boleh pada masa lalu. Sila pilih tarikh yang akan datang.');
                    dateInput.focus();
                    return false;
                }
                
                // Check capacity
                const capacityInput = document.querySelector('input[name="kapasiti"]');
                if (parseInt(capacityInput.value) < 1) {
                    e.preventDefault();
                    alert('Kapasiti mestilah sekurang-kurangnya 1 peserta.');
                    capacityInput.focus();
                    return false;
                }
                
                // All good, proceed
                return true;
            });
        }
    });
    
    // Save draft functionality
    function saveDraft() {
        // Collect form data
        const formData = {
            nama_program: document.querySelector('input[name="nama_program"]').value,
            kategori: document.querySelector('select[name="kategori"]').value,
            kapasiti: document.querySelector('input[name="kapasiti"]').value,
            tarikh: document.querySelector('input[name="tarikh"]').value,
            masa: document.querySelector('input[name="masa"]').value,
            lokasi: document.querySelector('input[name="lokasi"]').value,
            penerangan: document.querySelector('textarea[name="penerangan"]').value,
        };
        
        // Save to localStorage (or send to server in real app)
        localStorage.setItem('program_draft', JSON.stringify(formData));
        
        // Show success message
        alert('Draf program telah disimpan. Anda boleh teruskan kemudian.');
        
        // In real app, you would send to server via AJAX
        /*
        fetch('save_draft.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Draf program telah disimpan.');
            }
        });
        */
    }
    
    // Load draft if exists
    window.onload = function() {
        const savedDraft = localStorage.getItem('program_draft');
        if (savedDraft) {
            const draft = JSON.parse(savedDraft);
            
            // Fill form fields
            document.querySelector('input[name="nama_program"]').value = draft.nama_program || '';
            document.querySelector('select[name="kategori"]').value = draft.kategori || '';
            document.querySelector('input[name="kapasiti"]').value = draft.kapasiti || '';
            document.querySelector('input[name="tarikh"]').value = draft.tarikh || '';
            document.querySelector('input[name="masa"]').value = draft.masa || '';
            document.querySelector('input[name="lokasi"]').value = draft.lokasi || '';
            document.querySelector('textarea[name="penerangan"]').value = draft.penerangan || '';
            
            // Show notification
            const shouldLoadDraft = confirm('Anda mempunyai draf program yang disimpan. Adakah anda ingin memuatkannya?');
            if (!shouldLoadDraft) {
                localStorage.removeItem('program_draft');
                // Clear form
                document.querySelector('form').reset();
            }
        }
    };
</script>

</body>
</html>