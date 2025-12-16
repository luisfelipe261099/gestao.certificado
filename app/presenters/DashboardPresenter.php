<?php
/**
 * Dashboard Presenter - Padrão MVP
 * Responsável por processar dados e preparar para a view
 */

require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardPresenter {
    private $model;
    private $data = [];
    
    public function __construct($connection) {
        $this->model = new DashboardModel($connection);
    }
    
    /**
     * Preparar dados para dashboard do admin
     */
    public function prepareAdminDashboard() {
        $this->data['stats'] = $this->model->getAdminStats();
        $this->data['recent_parceiros'] = $this->model->getRecentParceiros(3);
        $this->data['expiring_subscriptions'] = $this->model->getExpiringSubscriptions(30);
        
        return $this->data;
    }
    
    /**
     * Preparar dados para dashboard do parceiro
     */
    public function prepareParceiroDashboard($parceiro_id) {
        $this->data['stats'] = $this->model->getParceiroStats($parceiro_id);

        return $this->data;
    }
    
    /**
     * Formatar moeda
     */
    public function formatCurrency($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
    
    /**
     * Formatar data
     */
    public function formatDate($date) {
        if (empty($date)) return '-';
        return date('d/m/Y', strtotime($date));
    }
    
    /**
     * Calcular dias restantes
     */
    public function getDaysRemaining($date) {
        $now = new DateTime();
        $expiry = new DateTime($date);
        $interval = $now->diff($expiry);
        return $interval->days;
    }
    
    /**
     * Obter dados preparados
     */
    public function getData() {
        return $this->data;
    }
}
?>

