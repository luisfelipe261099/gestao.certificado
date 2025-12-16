<?php
/**
 * Dashboard Model - Padrão MVP
 * Responsável por buscar dados do banco de dados
 */

class DashboardModel {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Obter estatísticas do admin
     */
    public function getAdminStats() {
        $stats = [
            'total_parceiros' => 0,
            'assinaturas_ativas' => 0,
            'certificados_gerados' => 0,
            'receita_total' => 0
        ];
        
        try {
            // Total de parceiros
            $result = $this->conn->query("SELECT COUNT(*) as total FROM parceiros WHERE ativo = 1");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_parceiros'] = $row['total'] ?? 0;
            }
            
            // Assinaturas ativas
            $result = $this->conn->query("SELECT COUNT(*) as total FROM assinaturas WHERE status = 'ativa'");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['assinaturas_ativas'] = $row['total'] ?? 0;
            }
            
            // Certificados gerados
            $result = $this->conn->query("SELECT COUNT(*) as total FROM certificados");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['certificados_gerados'] = $row['total'] ?? 0;
            }
            
            // Receita total - Soma do valor dos planos das assinaturas ativas
            $result = $this->conn->query("SELECT SUM(pl.valor) as total FROM assinaturas a LEFT JOIN planos pl ON a.plano_id = pl.id WHERE a.status = 'ativa'");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['receita_total'] = floatval($row['total'] ?? 0);
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Obter parceiros recentes
     */
    public function getRecentParceiros($limit = 3) {
        $parceiros = [];
        
        try {
            $stmt = $this->conn->prepare("
                SELECT id, nome, plano, status, data_criacao 
                FROM parceiros 
                WHERE ativo = 1 
                ORDER BY data_criacao DESC 
                LIMIT ?
            ");
            
            if ($stmt) {
                $stmt->bind_param("i", $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $parceiros[] = $row;
                }
                
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar parceiros recentes: " . $e->getMessage());
        }
        
        return $parceiros;
    }
    
    /**
     * Obter assinaturas vencendo em breve
     */
    public function getExpiringSubscriptions($days = 30) {
        $subscriptions = [];
        
        try {
            $stmt = $this->conn->prepare("
                SELECT a.id, p.nome, a.data_vencimento, 
                       DATEDIFF(a.data_vencimento, CURDATE()) as dias_restantes
                FROM assinaturas a
                JOIN parceiros p ON a.parceiro_id = p.id
                WHERE a.status = 'ativa' 
                AND DATEDIFF(a.data_vencimento, CURDATE()) <= ?
                AND DATEDIFF(a.data_vencimento, CURDATE()) > 0
                ORDER BY a.data_vencimento ASC
            ");
            
            if ($stmt) {
                $stmt->bind_param("i", $days);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $subscriptions[] = $row;
                }
                
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar assinaturas vencendo: " . $e->getMessage());
        }
        
        return $subscriptions;
    }
    
    /**
     * Obter estatísticas do parceiro
     */
    public function getParceiroStats($parceiro_id) {
        $stats = [
            'certificados_disponiveis' => 0,
            'certificados_usados' => 0,
            'alunos_registrados' => 0,
            'cursos_ativos' => 0,
            'certificados_gerados' => 0,
            'certificados_totais' => 0
        ];

        try {
            // Certificados disponíveis
            $stmt = $this->conn->prepare("
                SELECT a.id, a.data_inicio, a.data_vencimento, pl.quantidade_certificados AS certificados_totais
                FROM assinaturas a
                JOIN planos pl ON a.plano_id = pl.id
                WHERE a.parceiro_id = ? AND a.status = 'ativa'
                LIMIT 1
            ");

            if ($stmt) {
                $stmt->bind_param("i", $parceiro_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $assinatura_id = $row['id'];
                    $totais = $row['certificados_totais'] ?? 0;
                    $usados = $row['certificados_usados'] ?? 0;
                    $disponiveis = $row['certificados_disponiveis'] ?? 0;



                    $stats['certificados_totais'] = (int)$totais;
                }

                $stmt->close();
            }
            
            // Alunos registrados
            $result = $this->conn->query("SELECT COUNT(*) as total FROM alunos WHERE parceiro_id = $parceiro_id");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['alunos_registrados'] = $row['total'] ?? 0;
            }
            
            // Cursos ativos
            $result = $this->conn->query("SELECT COUNT(*) as total FROM cursos WHERE parceiro_id = $parceiro_id AND ativo = 1");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['cursos_ativos'] = $row['total'] ?? 0;
            }
            
            // Certificados gerados (apenas os que não foram cancelados)
            // Conta apenas certificados com status != 'cancelado' para refletir certificados realmente gerados
            $result = $this->conn->query("SELECT COUNT(*) as total FROM certificados WHERE parceiro_id = $parceiro_id AND status != 'cancelado'");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['certificados_gerados'] = (int)($row['total'] ?? 0);
            }
            $stats['certificados_usados'] = (int)$stats['certificados_gerados'];
            $stats['certificados_disponiveis'] = max(0, (int)$stats['certificados_totais'] - (int)$stats['certificados_usados']);
        } catch (Exception $e) {
            error_log("Erro ao buscar estatísticas do parceiro: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Recalcular certificados inconsistentes
     * Baseado em certificados realmente gerados na tabela certificados
     */
    private function recalcularCertificados($parceiro_id, $assinatura_id, $certificados_totais) {
        try {
            // Contar certificados realmente gerados (não cancelados)
            $result = $this->conn->query("
                SELECT COUNT(*) as total
                FROM certificados
                WHERE parceiro_id = $parceiro_id AND status != 'cancelado'
            ");

            if (!$result) {
                error_log("Erro ao contar certificados: " . $this->conn->error);
                return false;
            }

            $row = $result->fetch_assoc();
            $certificados_gerados = $row['total'] ?? 0;
            $certificados_disponiveis = $certificados_totais - $certificados_gerados;

            // Garantir que não fique negativo
            if ($certificados_disponiveis < 0) {
                $certificados_disponiveis = 0;
            }

            // Atualizar assinatura
            $stmt = $this->conn->prepare("
                UPDATE assinaturas
                SET
                    certificados_usados = ?,
                    certificados_disponiveis = ?,
                    atualizado_em = NOW()
                WHERE id = ? AND parceiro_id = ?
                LIMIT 1
            ");

            if ($stmt) {
                $stmt->bind_param("iiii", $certificados_gerados, $certificados_disponiveis, $assinatura_id, $parceiro_id);
                if ($stmt->execute()) {
                    error_log("✅ Certificados recalculados para parceiro $parceiro_id: Gerados=$certificados_gerados, Disponíveis=$certificados_disponiveis");
                    $stmt->close();
                    return true;
                } else {
                    error_log("Erro ao atualizar assinatura: " . $stmt->error);
                    $stmt->close();
                    return false;
                }
            }

            return false;
        } catch (Exception $e) {
            error_log("Erro ao recalcular certificados: " . $e->getMessage());
            return false;
        }
    }
}
?>

