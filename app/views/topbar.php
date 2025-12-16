<?php
/**
 * Topbar - Componente Reutilizável
 * Padrão MVP - Camada de Apresentação
 * Versão Melhorada com Design Moderno
 */

$user = getCurrentUser();
$user_role = $user['role'] ?? '';
$is_admin = hasRole(ROLE_ADMIN);
$is_parceiro = hasRole(ROLE_PARCEIRO);
?>
<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm" style="border-bottom: 1px solid #e5e7eb;">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" style="color: #6b7280;">
        <i class="fa fa-bars fa-lg"></i>
    </button>



    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        <!-- Nav Item - Notifications (Parceiro) -->
        <?php if ($is_parceiro): ?>
        <li class="nav-item dropdown no-arrow mx-2">
            <a class="nav-link dropdown-toggle text-secondary" href="#" id="alertsDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Notificações">
                <i class="fas fa-bell fa-lg"></i>
                <span class="badge badge-light badge-counter" style="position: absolute; top: 5px; right: 5px;">2</span>
            </a>
            <!-- Dropdown - Alerts -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown" style="min-width: 300px;">
                <h6 class="dropdown-header bg-light text-dark font-weight-bold">
                    <i class="fas fa-bell mr-2"></i>Notificações
                </h6>
                <a class="dropdown-item d-flex align-items-center border-bottom" href="#">
                    <div class="mr-3">
                        <div class="icon-circle bg-light border">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">Contrato pendente de assinatura</div>
                        <span class="font-weight-bold">Renovação de Plano</span>
                    </div>
                </a>
                <a class="dropdown-item d-flex align-items-center" href="#">
                    <div class="mr-3">
                        <div class="icon-circle bg-light border">
                            <i class="fas fa-check text-white"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">Fatura gerada</div>
                        <span class="font-weight-bold">Vencimento em 7 dias</span>
                    </div>
                </a>
                <a class="dropdown-item text-center small text-secondary py-2" href="#">
                    Ver todas as notificações
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- Topbar Divider -->
        <div class="topbar-divider d-none d-sm-block" style="border-right: 1px solid rgba(0,0,0,0.1); height: 2rem; margin: 0 1rem;"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle text-secondary d-flex align-items-center" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="avatar-circle bg-light text-dark font-weight-bold" style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; border: 1px solid #e5e7eb;">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown" style="min-width: 250px;">
                <h6 class="dropdown-header bg-light text-dark font-weight-bold">
                    <i class="fas fa-user-circle mr-2"></i>Minha Conta
                </h6>
                <?php if ($is_admin): ?>
                    <a class="dropdown-item" href="<?php echo DIR_ADMIN; ?>/perfil-admin.php">
                        <i class="fas fa-user fa-sm fa-fw mr-2 text-secondary"></i>
                        <span>Meu Perfil</span>
                    </a>
                    <a class="dropdown-item" href="<?php echo DIR_ADMIN; ?>/dashboard-admin.php">
                        <i class="fas fa-tachometer-alt fa-sm fa-fw mr-2 text-secondary"></i>
                        <span>Dashboard</span>
                    </a>
                <?php else: ?>
                    <a class="dropdown-item" href="<?php echo DIR_PARCEIRO; ?>/perfil-parceiro.php">
                        <i class="fas fa-user fa-sm fa-fw mr-2 text-secondary"></i>
                        <span>Meu Perfil</span>
                    </a>
                    <a class="dropdown-item" href="<?php echo DIR_PARCEIRO; ?>/meu-plano.php">
                        <i class="fas fa-credit-card fa-sm fa-fw mr-2 text-secondary"></i>
                        <span>Meu Plano</span>
                    </a>
                    <a class="dropdown-item" href="<?php echo DIR_PARCEIRO; ?>/contratos.php">
                        <i class="fas fa-file-contract fa-sm fa-fw mr-2 text-secondary"></i>
                        <span>Contratos</span>
                    </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i>
                    <span>Sair</span>
                </a>
            </div>
        </li>

    </ul>

</nav>
<!-- End of Topbar -->

