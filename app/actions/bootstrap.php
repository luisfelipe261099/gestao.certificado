<?php
/**
 * Bootstrap para app/actions
 * Carrega o arquivo de configuração de forma robusta
 * Funciona em qualquer estrutura de diretórios
 */

// Usar __DIR__ para obter o diretório correto do arquivo atual
// __DIR__ = C:\xampp\htdocs\gestao_certificado_murilo\app\actions
// Precisamos ir para: C:\xampp\htdocs\gestao_certificado_murilo\app\config

$config_path = realpath(__DIR__ . '/../config/config.php');

if (!$config_path || !file_exists($config_path)) {
    // Se não encontrou, tentar com dirname(__DIR__)
    $config_path = realpath(dirname(__DIR__) . '/config/config.php');
}

if (!$config_path || !file_exists($config_path)) {
    die('Erro: Arquivo de configuração não encontrado em ' . __DIR__);
}

require_once $config_path;
