<?php
/**
 * Sidebar Admin - Componente Reutilizável
 * Padrão MVP - Camada de Apresentação
 */
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-danger sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center"
        href="<?php echo DIR_ADMIN; ?>/dashboard-admin.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Admin</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/dashboard-admin.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Gerenciamento</div>

    <!-- Nav Item - Parceiros -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'parceiros-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/parceiros-admin.php">
            <i class="fas fa-fw fa-handshake"></i>
            <span>Parceiros</span>
        </a>
    </li>

    <!-- Nav Item - Planos -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'planos-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/planos-admin.php">
            <i class="fas fa-fw fa-list"></i>
            <span>Planos</span>
        </a>
    </li>

    <!-- Nav Item - Assinaturas -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'assinaturas-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/assinaturas-admin.php">
            <i class="fas fa-fw fa-credit-card"></i>
            <span>Assinaturas</span>
        </a>
    </li>

    <!-- Nav Item - Solicitações de Planos -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'solicitacoes-planos.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/solicitacoes-planos.php">
            <i class="fas fa-fw fa-exchange-alt"></i>
            <span>Solicitações de Planos</span>
        </a>
    </li>

    <!-- Nav Item - Contratos -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'contratos-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/contratos-admin.php">
            <i class="fas fa-fw fa-file-contract"></i>
            <span>Gerenciar Contratos</span>
        </a>
    </li>

    <!-- Nav Item - Termos -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'termos-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/termos-admin.php">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Gerenciar Termos</span>
        </a>
    </li>

    <!-- Nav Item - Templates do Sistema -->
    <li
        class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'templates-sistema.php' || basename($_SERVER['PHP_SELF']) === 'editor-template-sistema.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/templates-sistema.php">
            <i class="fas fa-fw fa-layer-group"></i>
            <span>Templates do Sistema</span>
        </a>
    </li>



    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Financeiro</div>

    <!-- Nav Item - Faturas -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'faturas-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/faturas-admin.php">
            <i class="fas fa-fw fa-file-invoice-dollar"></i>
            <span>Faturas</span>
        </a>
    </li>

    <!-- Nav Item - Pagamentos -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'pagamentos-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/pagamentos-admin.php">
            <i class="fas fa-fw fa-money-bill-wave"></i>
            <span>Pagamentos</span>
        </a>
    </li>

    <!-- Nav Item - Receitas -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'receitas-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/receitas-admin.php">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Receitas</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Integrações</div>

    <!-- Nav Item - Boletos Asaas -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'boletos-asaas.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/boletos-asaas.php">
            <i class="fas fa-fw fa-barcode"></i>
            <span>Boletos Asaas</span>
        </a>
    </li>

    <!-- Nav Item - Asaas Config -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'asaas-config.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/asaas-config.php">
            <i class="fas fa-fw fa-cog"></i>
            <span>Configurar Asaas</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Ajuda</div>

    <!-- Nav Item - Tutoriais -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'tutoriais.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/tutoriais.php">
            <i class="fas fa-fw fa-question-circle"></i>
            <span>Tutoriais</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Conta</div>

    <!-- Nav Item - Perfil -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'perfil-admin.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo DIR_ADMIN; ?>/perfil-admin.php">
            <i class="fas fa-fw fa-user"></i>
            <span>Meu Perfil</span>
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