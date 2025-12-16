<?php
/**
 * Sidebar Parceiro - Componente Reutilizável
 * Padrão MVP - Camada de Apresentação
 */
?>
<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo DIR_PARCEIRO; ?>/dashboard-parceiro.php">
        <div class="sidebar-brand-text mx-3" style="font-size: 0.9rem;">Gestão Educacional</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard-parceiro.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_PARCEIRO; ?>/dashboard-parceiro.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Gerenciamento</div>

    <!-- Nav Item - Cursos -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'cursos-parceiro.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_PARCEIRO; ?>/cursos-parceiro.php">
            <i class="fas fa-fw fa-book"></i>
            <span>Cursos</span>
        </a>
    </li>

    <!-- Nav Item - Alunos -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'alunos-parceiro.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_PARCEIRO; ?>/alunos-parceiro.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Alunos</span>
        </a>
    </li>

    <!-- Nav Item - Templates -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'templates-parceiro.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_PARCEIRO; ?>/templates-parceiro.php">
            <i class="fas fa-fw fa-file-pdf"></i>
            <span title="Templates de Certificados">Templates</span>
        </a>
    </li>

    <!-- Nav Item - Gerar Certificados -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'gerar-certificados.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_PARCEIRO; ?>/gerar-certificados.php">
            <i class="fas fa-fw fa-certificate"></i>
            <span>Gerar Certificados</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Conta</div>

    <!-- Nav Item - Perfil -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'perfil-parceiro.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_PARCEIRO; ?>/perfil-parceiro.php">
            <i class="fas fa-fw fa-user"></i>
            <span>Meu Perfil</span>
        </a>
    </li>

    <!-- Nav Item - Plano -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'meu-plano.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_PARCEIRO; ?>/meu-plano.php">
            <i class="fas fa-fw fa-credit-card"></i>
            <span>Meu Plano</span>
        </a>
    </li>

    <!-- Nav Item - Financeiro -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'financeiro.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_PARCEIRO; ?>/financeiro.php">
            <i class="fas fa-fw fa-wallet"></i>
            <span>Financeiro</span>
        </a>
    </li>


    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->

