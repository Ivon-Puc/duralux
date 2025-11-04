<?php

/**
 * Notification Center v6.0 - Sistema Avançado de Notificações Multi-Canal
 * Suporte a Push Notifications, Email, SMS e Integração com Workflows
 */

class NotificationCenter {
    private $db;
    private $config;
    private $templates = [];
    
    public function __construct() {
        $this->config = [
            'channels' => ['database', 'email', 'sms', 'push', 'webhook'],
            'priority_levels' => ['baixa', 'normal', 'alta', 'critica', 'urgente'],
            'templates_path' => __DIR__ . '/../templates/notifications/',
            'max_retries' => 3,
            'batch_size' => 100,
            'rate_limit' => 50 // por minuto
        ];
        $this->initDatabase();
        $this->loadTemplates();
    }
    
    private function initDatabase() {
        try {
            $this->db = new PDO('sqlite:' . __DIR__ . '/../data/duralux_notifications.db');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
        }
    }
    
    private function createTables() {
        // Tabela de notificações
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo VARCHAR(255) NOT NULL,
                mensagem TEXT NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                prioridade VARCHAR(20) DEFAULT 'normal',
                canal VARCHAR(50) NOT NULL,
                usuario_id INTEGER,
                grupo_id INTEGER,
                dados_meta TEXT,
                status VARCHAR(20) DEFAULT 'pendente',
                tentativas INTEGER DEFAULT 0,
                agendado_para DATETIME,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                enviado_em DATETIME,
                lido_em DATETIME,
                template_id VARCHAR(100)
            )
        ");
        
        // Tabela de templates
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notification_templates (
                id VARCHAR(100) PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                canal VARCHAR(50) NOT NULL,
                assunto VARCHAR(255),
                corpo TEXT NOT NULL,
                variaveis TEXT,
                ativo BOOLEAN DEFAULT 1,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de configurações de usuário
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notification_settings (
                usuario_id INTEGER PRIMARY KEY,
                email_enabled BOOLEAN DEFAULT 1,
                sms_enabled BOOLEAN DEFAULT 0,
                push_enabled BOOLEAN DEFAULT 1,
                webhook_enabled BOOLEAN DEFAULT 0,
                quiet_hours_start TIME DEFAULT '22:00',
                quiet_hours_end TIME DEFAULT '07:00',
                timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
                configuracoes TEXT
            )
        ");
        
        // Tabela de analytics
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notification_analytics (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                notification_id INTEGER,
                evento VARCHAR(50) NOT NULL,
                canal VARCHAR(50),
                usuario_id INTEGER,
                dados TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (notification_id) REFERENCES notifications(id)
            )
        ");
        
        $this->insertDefaultTemplates();
    }
    
    private function insertDefaultTemplates() {
        $templates = [
            [
                'id' => 'lead_novo',
                'nome' => 'Novo Lead Recebido',
                'tipo' => 'lead',
                'canal' => 'email,push',
                'assunto' => '🔔 Novo Lead: {{lead_nome}}',
                'corpo' => 'Um novo lead foi recebido de {{lead_nome}} ({{lead_email}}). Empresa: {{lead_empresa}}. Mensagem: {{lead_mensagem}}',
                'variaveis' => 'lead_nome,lead_email,lead_empresa,lead_mensagem'
            ],
            [
                'id' => 'proposta_aprovada',
                'nome' => 'Proposta Aprovada',
                'tipo' => 'proposta',
                'canal' => 'email,sms',
                'assunto' => '✅ Proposta #{{proposta_id}} Aprovada',
                'corpo' => 'Parabéns! Sua proposta #{{proposta_id}} para {{cliente_nome}} foi aprovada no valor de R$ {{valor}}.',
                'variaveis' => 'proposta_id,cliente_nome,valor'
            ],
            [
                'id' => 'projeto_prazo',
                'nome' => 'Projeto com Prazo Próximo',
                'tipo' => 'projeto',
                'canal' => 'push,webhook',
                'assunto' => '⏰ Projeto {{projeto_nome}} - Prazo em {{dias}} dias',
                'corpo' => 'O projeto "{{projeto_nome}}" tem prazo de entrega em {{dias}} dias ({{data_entrega}}). Status atual: {{status}}.',
                'variaveis' => 'projeto_nome,dias,data_entrega,status'
            ],
            [
                'id' => 'sistema_manutencao',
                'nome' => 'Manutenção Programada',
                'tipo' => 'sistema',
                'canal' => 'database,email',
                'assunto' => '🔧 Manutenção do Sistema - {{data}}',
                'corpo' => 'Manutenção programada do sistema será realizada em {{data}} das {{hora_inicio}} às {{hora_fim}}. Duração estimada: {{duracao}}.',
                'variaveis' => 'data,hora_inicio,hora_fim,duracao'
            ],
            [
                'id' => 'backup_sucesso',
                'nome' => 'Backup Concluído',
                'tipo' => 'sistema',
                'canal' => 'database',
                'assunto' => '💾 Backup Realizado com Sucesso',
                'corpo' => 'Backup automático concluído em {{data_backup}}. Tamanho: {{tamanho}}. Localização: {{local}}.',
                'variaveis' => 'data_backup,tamanho,local'
            ]
        ];
        
        foreach ($templates as $template) {
            $this->db->prepare("
                INSERT OR REPLACE INTO notification_templates 
                (id, nome, tipo, canal, assunto, corpo, variaveis) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $template['id'], $template['nome'], $template['tipo'], 
                $template['canal'], $template['assunto'], $template['corpo'], 
                $template['variaveis']
            ]);
        }
    }
    
    /**
     * Criar nova notificação
     */
    public function criar($dados) {
        $required = ['titulo', 'mensagem', 'tipo', 'canal'];
        foreach ($required as $field) {
            if (!isset($dados[$field])) {
                throw new Exception("Campo obrigatório: $field");
            }
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO notifications 
            (titulo, mensagem, tipo, prioridade, canal, usuario_id, grupo_id, dados_meta, agendado_para, template_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $agendado = isset($dados['agendado_para']) ? $dados['agendado_para'] : null;
        $meta = isset($dados['dados_meta']) ? json_encode($dados['dados_meta']) : null;
        
        $stmt->execute([
            $dados['titulo'],
            $dados['mensagem'],
            $dados['tipo'],
            $dados['prioridade'] ?? 'normal',
            $dados['canal'],
            $dados['usuario_id'] ?? null,
            $dados['grupo_id'] ?? null,
            $meta,
            $agendado,
            $dados['template_id'] ?? null
        ]);
        
        $notificationId = $this->db->lastInsertId();
        
        // Enviar imediatamente se não agendado
        if (!$agendado) {
            $this->processar($notificationId);
        }
        
        return $notificationId;
    }
    
    /**
     * Criar notificação usando template
     */
    public function criarComTemplate($templateId, $variaveis, $dados = []) {
        $template = $this->getTemplate($templateId);
        if (!$template) {
            throw new Exception("Template não encontrado: $templateId");
        }
        
        $titulo = $this->processarTemplate($template['assunto'], $variaveis);
        $mensagem = $this->processarTemplate($template['corpo'], $variaveis);
        
        $notificacao = array_merge($dados, [
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'tipo' => $template['tipo'],
            'canal' => $template['canal'],
            'template_id' => $templateId
        ]);
        
        return $this->criar($notificacao);
    }
    
    /**
     * Processar template com variáveis
     */
    private function processarTemplate($template, $variaveis) {
        foreach ($variaveis as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }
    
    /**
     * Processar fila de notificações
     */
    public function processarFila() {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE status = 'pendente' 
            AND (agendado_para IS NULL OR agendado_para <= datetime('now'))
            AND tentativas < ?
            ORDER BY prioridade DESC, criado_em ASC
            LIMIT ?
        ");
        
        $stmt->execute([$this->config['max_retries'], $this->config['batch_size']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notifications as $notification) {
            $this->processar($notification['id']);
        }
        
        return count($notifications);
    }
    
    /**
     * Processar notificação individual
     */
    private function processar($notificationId) {
        $notification = $this->getNotification($notificationId);
        if (!$notification) return false;
        
        $canais = explode(',', $notification['canal']);
        $sucesso = true;
        
        foreach ($canais as $canal) {
            $canal = trim($canal);
            try {
                switch ($canal) {
                    case 'database':
                        $this->enviarDatabase($notification);
                        break;
                    case 'email':
                        $this->enviarEmail($notification);
                        break;
                    case 'sms':
                        $this->enviarSMS($notification);
                        break;
                    case 'push':
                        $this->enviarPush($notification);
                        break;
                    case 'webhook':
                        $this->enviarWebhook($notification);
                        break;
                }
                $this->logAnalytics($notificationId, 'enviado', $canal);
            } catch (Exception $e) {
                $sucesso = false;
                error_log("Erro ao enviar notificação $notificationId via $canal: " . $e->getMessage());
                $this->logAnalytics($notificationId, 'erro', $canal, ['erro' => $e->getMessage()]);
            }
        }
        
        // Atualizar status
        $status = $sucesso ? 'enviado' : 'erro';
        $tentativas = $notification['tentativas'] + 1;
        
        $this->db->prepare("
            UPDATE notifications 
            SET status = ?, tentativas = ?, enviado_em = CASE WHEN ? = 'enviado' THEN datetime('now') ELSE enviado_em END
            WHERE id = ?
        ")->execute([$status, $tentativas, $status, $notificationId]);
        
        return $sucesso;
    }
    
    /**
     * Enviar para banco de dados (notificação interna)
     */
    private function enviarDatabase($notification) {
        // Já está no banco, apenas marca como enviado
        return true;
    }
    
    /**
     * Enviar por email
     */
    private function enviarEmail($notification) {
        // Simulação de envio de email
        // Aqui integraria com serviços como SendGrid, Mailgun, etc.
        $to = $this->getUserEmail($notification['usuario_id']);
        if (!$to) throw new Exception("Email do usuário não encontrado");
        
        $headers = [
            'From: noreply@duralux.com',
            'Content-Type: text/html; charset=UTF-8',
            'X-Notification-ID: ' . $notification['id']
        ];
        
        $subject = $notification['titulo'];
        $body = $this->formatEmailBody($notification);
        
        // mail($to, $subject, $body, implode("\r\n", $headers));
        
        return true;
    }
    
    /**
     * Enviar SMS
     */
    private function enviarSMS($notification) {
        // Integração com serviços SMS (Twilio, etc.)
        $telefone = $this->getUserPhone($notification['usuario_id']);
        if (!$telefone) throw new Exception("Telefone do usuário não encontrado");
        
        $mensagem = substr($notification['titulo'] . ': ' . $notification['mensagem'], 0, 160);
        
        // Simulação de envio
        return true;
    }
    
    /**
     * Enviar push notification
     */
    private function enviarPush($notification) {
        // Integração com Firebase, OneSignal, etc.
        $deviceTokens = $this->getUserDeviceTokens($notification['usuario_id']);
        if (empty($deviceTokens)) throw new Exception("Tokens de dispositivo não encontrados");
        
        $payload = [
            'title' => $notification['titulo'],
            'body' => $notification['mensagem'],
            'data' => json_decode($notification['dados_meta'] ?? '{}', true)
        ];
        
        // Envio para FCM/OneSignal
        return true;
    }
    
    /**
     * Enviar webhook
     */
    private function enviarWebhook($notification) {
        $webhookUrl = $this->getWebhookUrl($notification['usuario_id']);
        if (!$webhookUrl) throw new Exception("URL do webhook não configurada");
        
        $payload = [
            'id' => $notification['id'],
            'titulo' => $notification['titulo'],
            'mensagem' => $notification['mensagem'],
            'tipo' => $notification['tipo'],
            'timestamp' => $notification['criado_em']
        ];
        
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Webhook retornou código $httpCode");
        }
        
        return true;
    }
    
    /**
     * Marcar notificação como lida
     */
    public function marcarLida($notificationId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET lido_em = datetime('now') 
            WHERE id = ? AND (usuario_id = ? OR usuario_id IS NULL)
        ");
        
        $resultado = $stmt->execute([$notificationId, $userId]);
        
        if ($resultado) {
            $this->logAnalytics($notificationId, 'lido', null, ['usuario_id' => $userId]);
        }
        
        return $resultado;
    }
    
    /**
     * Listar notificações do usuário
     */
    public function listarUsuario($userId, $filtros = []) {
        $where = ["(usuario_id = ? OR usuario_id IS NULL)"];
        $params = [$userId];
        
        if (isset($filtros['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filtros['tipo'];
        }
        
        if (isset($filtros['lidas'])) {
            if ($filtros['lidas']) {
                $where[] = "lido_em IS NOT NULL";
            } else {
                $where[] = "lido_em IS NULL";
            }
        }
        
        $limit = $filtros['limit'] ?? 50;
        $offset = $filtros['offset'] ?? 0;
        
        $sql = "
            SELECT *, 
            CASE WHEN lido_em IS NULL THEN 1 ELSE 0 END as nao_lida
            FROM notifications 
            WHERE " . implode(' AND ', $where) . "
            ORDER BY nao_lida DESC, prioridade DESC, criado_em DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obter estatísticas
     */
    public function getStats($periodo = '7 days') {
        $sql = "
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'enviado' THEN 1 END) as enviadas,
                COUNT(CASE WHEN status = 'erro' THEN 1 END) as erros,
                COUNT(CASE WHEN lido_em IS NOT NULL THEN 1 END) as lidas,
                AVG(CASE WHEN enviado_em IS NOT NULL THEN 
                    (julianday(enviado_em) - julianday(criado_em)) * 24 * 60 
                END) as tempo_medio_envio_minutos
            FROM notifications 
            WHERE criado_em >= datetime('now', '-$periodo')
        ";
        
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Configurar preferências do usuário
     */
    public function configurarUsuario($userId, $configuracoes) {
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO notification_settings 
            (usuario_id, email_enabled, sms_enabled, push_enabled, webhook_enabled, 
             quiet_hours_start, quiet_hours_end, timezone, configuracoes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId,
            $configuracoes['email_enabled'] ?? 1,
            $configuracoes['sms_enabled'] ?? 0,
            $configuracoes['push_enabled'] ?? 1,
            $configuracoes['webhook_enabled'] ?? 0,
            $configuracoes['quiet_hours_start'] ?? '22:00',
            $configuracoes['quiet_hours_end'] ?? '07:00',
            $configuracoes['timezone'] ?? 'America/Sao_Paulo',
            json_encode($configuracoes)
        ]);
    }
    
    // Métodos auxiliares
    private function getNotification($id) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getTemplate($id) {
        $stmt = $this->db->prepare("SELECT * FROM notification_templates WHERE id = ? AND ativo = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getUserEmail($userId) {
        // Integrar com sistema de usuários existente
        return "usuario@exemplo.com";
    }
    
    private function getUserPhone($userId) {
        return "+5511999999999";
    }
    
    private function getUserDeviceTokens($userId) {
        return ["token_dispositivo_exemplo"];
    }
    
    private function getWebhookUrl($userId) {
        return null; // Configurar conforme necessário
    }
    
    private function formatEmailBody($notification) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>{$notification['titulo']}</h2>
            <p>{$notification['mensagem']}</p>
            <hr>
            <small>Enviado pelo Sistema Duralux CRM</small>
        </body>
        </html>
        ";
    }
    
    private function logAnalytics($notificationId, $evento, $canal = null, $dados = []) {
        $this->db->prepare("
            INSERT INTO notification_analytics (notification_id, evento, canal, dados)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $notificationId, $evento, $canal, json_encode($dados)
        ]);
    }
    
    private function loadTemplates() {
        // Carregar templates customizados do diretório
        // Implementar conforme necessário
    }
}