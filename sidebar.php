<?php
$userRole = $_SESSION['role'] ?? 'pelajar';
$activePage = $activePage ?? '';
?>

<aside class="sidebar">

    <!-- Logo -->
    <div class="sidebar-header">
        <img src="UKM.png" alt="UKM Logo" class="sidebar-logo">
        <h3 class="sidebar-title">UKMInvolve</h3>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

        <?php if ($userRole === 'pelajar'): ?>
            <?php
            $menu = [
                'dashboard_pelajar' => '🏠 Dashboard',
                'pilihan-minat' => '🎯 Pilihan Minat',
                'cari-program' => '🔍 Cari Program',
                'recommended' => '✨ Recommended',
                'rekod-penyertaan' => '📊 Rekod Penyertaan',
                'maklum-balas' => '💭 Maklum Balas'
            ];
            ?>

        <?php elseif ($userRole === 'penganjur'): ?>
            <?php
            $menu = [
                'dashboard_penganjur' => '🏠 Dashboard',
                'hebahan-program' => '📢 Hebahan Program',
                'urus-program' => '⚙️ Urus Program',
                'peserta-kehadiran' => '👥 Peserta',
                'laporan-statistik' => '📈 Laporan'
            ];
            ?>

        <?php else: ?>
            <?php
            $menu = [
                'dashboard-pentadbir' => '🏠 Dashboard',
                'pemantauan-aktiviti' => '👁️ Pemantauan',
                'pengurusan-pengguna' => '👤 Pengguna',
                'pengurusan-kategori' => '🏷️ Kategori',
                'statistik-sistem' => '📊 Statistik'
            ];
            ?>
        <?php endif; ?>

        <?php foreach ($menu as $page => $label): ?>
            <a href="<?= $page ?>.php"
               class="sidebar-link <?= ($activePage === $page) ? 'active' : '' ?>">
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>

    </nav>

    <!-- User Profile -->
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <?= strtoupper(substr($userRole, 0, 1)) ?>
            </div>

            <div class="user-info">
                <h4><?= ucfirst($userRole) ?></h4>
                <p>UKM Account</p>
            </div>
        </div>
    </div>

</aside>
