<?php
/**
 * Footer Comum - Componente Reutilizável
 * Padrão MVP - Camada de Apresentação
 */
?>
</div>
</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Deseja sair?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Clique em "Sair" abaixo se deseja encerrar sua sessão.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                <a class="btn btn-primary" href="<?php echo APP_URL; ?>/app/actions/logout.php">Sair</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="<?php echo DIR_VENDOR; ?>/jquery/jquery.min.js"></script>
<script src="<?php echo DIR_VENDOR; ?>/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="<?php echo DIR_VENDOR; ?>/jquery-easing/jquery.easing.min.js"></script>

<!-- DataTables JavaScript -->
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="<?php echo DIR_JS; ?>/sb-admin-2.min.js"></script>

<!-- Intro.js -->
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/introjs.min.css">
<script src="<?php echo APP_URL; ?>/assets/js/intro.min.js"></script>
<script src="<?php echo APP_URL; ?>/assets/js/admin-tour.js"></script>
</body>

</html>