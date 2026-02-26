<header class="site-header">
    <div class="header-inner" style="display: flex; align-items: center; height: 60px; padding: 0 2rem;">
        <h1 style="flex:0 0 auto;"><a href="index.html" class="site-title">E-Crime Sentinel</a></h1>
        <div style="flex:1;"></div>
        <?php
        $noNavPages = ['vicpanel.php'];
        if (empty($hideNav) && !in_array(basename($_SERVER['PHP_SELF']), $noNavPages)) {
        ?>
        <nav style="display: flex; gap: 2.5rem; justify-content: flex-end;">
            <a href="index.html" class="<?php echo basename($_SERVER['PHP_SELF'])=='index.html' ? 'active' : ''; ?>">Home</a>
            <a href="login.html" class="<?php echo basename($_SERVER['PHP_SELF'])=='login.html' ? 'active' : ''; ?>">Login</a>
            <a href="register.html" class="<?php echo basename($_SERVER['PHP_SELF'])=='register.html' ? 'active' : ''; ?>">Register</a>
        </nav>
        <?php } ?>
    </div>
</header>
<div style="height:60px;"></div> <!-- Spacer for fixed header -->
