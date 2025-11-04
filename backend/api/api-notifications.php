<?php
/**
 * API do Notification Center v6.0
 * Endpoints para gerenciamento de notificações
 */

require_once __DIR__ . '/../classes/NotificationCenter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $notificationCenter = new NotificationCenter();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    $userId = $_GET['user_id'] ?? 1; // Simulação - integrar com autenticação
    
    switch ($method) {
        case 'GET':
            handleGet($notificationCenter, $path, $userId);
            break;
        case 'POST':
            handlePost($notificationCenter, $path, $userId);
            break;
        case 'PUT':
            handlePut($notificationCenter, $path, $userId);
            break;
        case 'DELETE':
            handleDelete($notificationCenter, $path, $userId);
            break;
        default:
            throw new Exception('Método não suportado');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleGet($nc, $path, $userId) {
    switch ($path) {
        case 'list':
            // Listar notificações do usuário
            $filtros = [
                'tipo' => $_GET['tipo'] ?? null,
                'lidas' => isset($_GET['lidas']) ? (bool)$_GET['lidas'] : null,
                'limit' => (int)($_GET['limit'] ?? 50),
                'offset' => (int)($_GET['offset'] ?? 0)
            ];
            
            $notifications = $nc->listarUsuario($userId, array_filter($filtros));
            
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'total' => count($notifications)
            ]);
            break;
            
        case 'stats':
            // Estatísticas de notificações
            $periodo = $_GET['periodo'] ?? '7 days';
            $stats = $nc->getStats($periodo);
            
            // Stats adicionais
            $stats['taxa_leitura'] = $stats['total'] > 0 ? 
                round(($stats['lidas'] / $stats['total']) * 100, 2) : 0;
            $stats['taxa_sucesso'] = $stats['total'] > 0 ? 
                round(($stats['enviadas'] / $stats['total']) * 100, 2) : 0;
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'count-unread':
            // Contar não lidas
            $unread = $nc->listarUsuario($userId, ['lidas' => false, 'limit' => 1000]);
            
            echo json_encode([
                'success' => true,
                'count' => count($unread)
            ]);
            break;
            
        case 'templates':
            // Listar templates disponíveis
            echo json_encode([
                'success' => true,
                'data' => [
                    'lead_novo' => 'Novo Lead Recebido',
                    'proposta_aprovada' => 'Proposta Aprovada',
                    'projeto_prazo' => 'Projeto com Prazo Próximo',
                    'sistema_manutencao' => 'Manutenção Programada',
                    'backup_sucesso' => 'Backup Concluído'
                ]
            ]);
            break;
            
        default:
            throw new Exception('Endpoint não encontrado');
    }
}

function handlePost($nc, $path, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($path) {
        case 'create':
            // Criar nova notificação
            $required = ['titulo', 'mensagem', 'tipo', 'canal'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    throw new Exception("Campo obrigatório: $field");
                }
            }
            
            $input['usuario_id'] = $input['usuario_id'] ?? $userId;
            $notificationId = $nc->criar($input);
            
            echo json_encode([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Notificação criada com sucesso'
            ]);
            break;
            
        case 'create-template':
            // Criar notificação usando template
            if (!isset($input['template_id']) || !isset($input['variaveis'])) {
                throw new Exception('Template ID e variáveis são obrigatórios');
            }
            
            $dados = $input['dados'] ?? [];
            $dados['usuario_id'] = $dados['usuario_id'] ?? $userId;
            
            $notificationId = $nc->criarComTemplate(
                $input['template_id'], 
                $input['variaveis'], 
                $dados
            );
            
            echo json_encode([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Notificação criada usando template'
            ]);
            break;
            
        case 'process-queue':
            // Processar fila de notificações
            $processed = $nc->processarFila();
            
            echo json_encode([
                'success' => true,
                'processed' => $processed,
                'message' => "$processed notificações processadas"
            ]);
            break;
            
        case 'test':
            // Enviar notificação de teste
            $testData = [
                'titulo' => '🧪 Teste do Sistema de Notificações',
                'mensagem' => 'Esta é uma notificação de teste do Duralux CRM. Sistema funcionando corretamente!',
                'tipo' => 'teste',
                'canal' => 'database,email',
                'usuario_id' => $userId,
                'prioridade' => 'normal'
            ];
            
            $notificationId = $nc->criar($testData);
            
            echo json_encode([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Notificação de teste enviada'
            ]);
            break;
            
        case 'demo-lead':
            // Demo: Novo lead recebido
            $notificationId = $nc->criarComTemplate('lead_novo', [
                'lead_nome' => 'João Silva',
                'lead_email' => 'joao.silva@empresa.com',
                'lead_empresa' => 'Empresa ABC Ltda',
                'lead_mensagem' => 'Interessado em seus serviços de consultoria empresarial.'
            ], [
                'usuario_id' => $userId,
                'prioridade' => 'alta'
            ]);
            
            echo json_encode([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Demo: Notificação de novo lead criada'
            ]);
            break;
            
        case 'demo-proposta':
            // Demo: Proposta aprovada
            $notificationId = $nc->criarComTemplate('proposta_aprovada', [
                'proposta_id' => 'PROP-2024-001',
                'cliente_nome' => 'Empresa XYZ',
                'valor' => '25.000,00'
            ], [
                'usuario_id' => $userId,
                'prioridade' => 'alta'
            ]);
            
            echo json_encode([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Demo: Notificação de proposta aprovada criada'
            ]);
            break;
            
        default:
            throw new Exception('Endpoint não encontrado');
    }
}

function handlePut($nc, $path, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($path) {
        case 'mark-read':
            // Marcar como lida
            if (!isset($input['notification_id'])) {
                throw new Exception('ID da notificação é obrigatório');
            }
            
            $result = $nc->marcarLida($input['notification_id'], $userId);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notificação marcada como lida' : 'Erro ao marcar como lida'
            ]);
            break;
            
        case 'settings':
            // Configurar preferências do usuário
            $result = $nc->configurarUsuario($userId, $input);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Configurações salvas' : 'Erro ao salvar configurações'
            ]);
            break;
            
        default:
            throw new Exception('Endpoint não encontrado');
    }
}

function handleDelete($nc, $path, $userId) {
    // Implementar exclusão se necessário
    throw new Exception('Exclusão não implementada');
}

// Dados de demonstração para testes
function getDemoData() {
    return [
        'notifications' => [
            [
                'id' => 1,
                'titulo' => '🔔 Novo Lead Recebido',
                'mensagem' => 'Lead de Maria Santos interessada em consultoria',
                'tipo' => 'lead',
                'prioridade' => 'alta',
                'criado_em' => date('Y-m-d H:i:s'),
                'lido_em' => null
            ],
            [
                'id' => 2,
                'titulo' => '✅ Proposta Aprovada',
                'mensagem' => 'Proposta #PROP-001 aprovada no valor de R$ 15.000',
                'tipo' => 'proposta',
                'prioridade' => 'alta',
                'criado_em' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'lido_em' => null
            ],
            [
                'id' => 3,
                'titulo' => '⏰ Projeto com Prazo Próximo',
                'mensagem' => 'Projeto "Website E-commerce" tem entrega em 3 dias',
                'tipo' => 'projeto',
                'prioridade' => 'normal',
                'criado_em' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                'lido_em' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ]
        ],
        'stats' => [
            'total' => 15,
            'enviadas' => 14,
            'erros' => 1,
            'lidas' => 8,
            'taxa_leitura' => 53.33,
            'taxa_sucesso' => 93.33,
            'tempo_medio_envio_minutos' => 1.2
        ]
    ];
}