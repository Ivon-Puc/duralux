<?php
/**
 * API REST para Gerenciamento de Clientes - Duralux CRM
 * Endpoint: /duralux/api/customers.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class CustomerAPI {
    private $dataFile;
    
    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/customers.json';
        $this->ensureDataDirectory();
    }
    
    private function ensureDataDirectory() {
        $dataDir = dirname($this->dataFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([]));
        }
    }
    
    private function loadCustomers() {
        $content = file_get_contents($this->dataFile);
        return json_decode($content, true) ?: [];
    }
    
    private function saveCustomers($customers) {
        return file_put_contents($this->dataFile, json_encode($customers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function generateId() {
        return 'CLT' . strtoupper(base_convert(time(), 10, 36)) . strtoupper(substr(md5(uniqid()), 0, 5));
    }
    
    private function validateCustomerData($data) {
        $errors = [];
        
        // Campo obrigatório: nome
        if (empty($data['nome']) || strlen(trim($data['nome'])) < 2) {
            $errors[] = 'Nome é obrigatório e deve ter pelo menos 2 caracteres';
        }
        
        // Campo obrigatório: email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email válido é obrigatório';
        }
        
        // Validar CPF/CNPJ se fornecido
        if (!empty($data['cpf_cnpj'])) {
            $cpfCnpj = preg_replace('/\D/', '', $data['cpf_cnpj']);
            
            if (strlen($cpfCnpj) === 11) {
                if (!$this->validateCPF($cpfCnpj)) {
                    $errors[] = 'CPF inválido';
                }
            } elseif (strlen($cpfCnpj) === 14) {
                if (!$this->validateCNPJ($cpfCnpj)) {
                    $errors[] = 'CNPJ inválido';
                }
            } else {
                $errors[] = 'CPF deve ter 11 dígitos ou CNPJ deve ter 14 dígitos';
            }
        }
        
        // Validar telefone se fornecido
        if (!empty($data['telefone'])) {
            $telefone = preg_replace('/\D/', '', $data['telefone']);
            if (strlen($telefone) < 10 || strlen($telefone) > 11) {
                $errors[] = 'Telefone deve ter 10 ou 11 dígitos';
            }
        }
        
        return $errors;
    }
    
    private function validateCPF($cpf) {
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    private function validateCNPJ($cnpj) {
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        // Primeiro dígito
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights1[$i];
        }
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        
        // Segundo dígito
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights2[$i];
        }
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        
        return $cnpj[12] == $digit1 && $cnpj[13] == $digit2;
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    return $this->getCustomers();
                
                case 'POST':
                    return $this->createCustomer();
                
                case 'PUT':
                    return $this->updateCustomer();
                
                case 'DELETE':
                    return $this->deleteCustomer();
                
                default:
                    http_response_code(405);
                    return ['success' => false, 'message' => 'Método não permitido'];
            }
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getCustomers() {
        $customers = $this->loadCustomers();
        
        // Filtros opcionais
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        // Aplicar filtros
        if ($status) {
            $customers = array_filter($customers, function($customer) use ($status) {
                return $customer['status'] === $status;
            });
        }
        
        if ($search) {
            $customers = array_filter($customers, function($customer) use ($search) {
                return stripos($customer['nome'], $search) !== false ||
                       stripos($customer['email'], $search) !== false ||
                       stripos($customer['empresa'], $search) !== false;
            });
        }
        
        // Paginação
        $total = count($customers);
        $customers = array_slice($customers, $offset, $limit);
        
        return [
            'success' => true,
            'data' => array_values($customers),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    private function createCustomer() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Dados inválidos'];
        }
        
        // Validar dados
        $errors = $this->validateCustomerData($input);
        if (!empty($errors)) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => 'Dados de entrada inválidos',
                'errors' => $errors
            ];
        }
        
        $customers = $this->loadCustomers();
        
        // Verificar se email já existe
        foreach ($customers as $customer) {
            if ($customer['email'] === $input['email']) {
                http_response_code(409);
                return [
                    'success' => false,
                    'message' => 'Cliente com este email já existe'
                ];
            }
        }
        
        // Preparar dados do cliente
        $customer = [
            'id' => $input['id'] ?? $this->generateId(),
            'nome' => trim($input['nome']),
            'email' => strtolower(trim($input['email'])),
            'usuario' => $input['usuario'] ?? '',
            'telefone' => $input['telefone'] ?? '',
            'empresa' => $input['empresa'] ?? '',
            'cargo' => $input['cargo'] ?? '',
            'website' => $input['website'] ?? '',
            'cpf_cnpj' => $input['cpf_cnpj'] ?? '',
            'endereco' => $input['endereco'] ?? '',
            'sobre' => $input['sobre'] ?? '',
            'data_nascimento' => $input['data_nascimento'] ?? '',
            'pais' => $input['pais'] ?? 'br',
            'estado' => $input['estado'] ?? '',
            'cidade' => $input['cidade'] ?? '',
            'fuso_horario' => $input['fuso_horario'] ?? '',
            'idiomas' => $input['idiomas'] ?? [],
            'moeda' => $input['moeda'] ?? 'BRL',
            'grupos' => $input['grupos'] ?? [],
            'status' => $input['status'] ?? 'success',
            'privacidade' => $input['privacidade'] ?? 'everyone',
            'criado_em' => $input['criado_em'] ?? date('c'),
            'atualizado_em' => date('c')
        ];
        
        // Adicionar cliente
        $customers[] = $customer;
        
        if ($this->saveCustomers($customers)) {
            http_response_code(201);
            return [
                'success' => true,
                'message' => 'Cliente criado com sucesso',
                'data' => $customer
            ];
        } else {
            http_response_code(500);
            return [
                'success' => false,
                'message' => 'Erro ao salvar cliente'
            ];
        }
    }
    
    private function updateCustomer() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            return ['success' => false, 'message' => 'ID do cliente é obrigatório'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Dados inválidos'];
        }
        
        $customers = $this->loadCustomers();
        $customerIndex = null;
        
        // Encontrar cliente
        foreach ($customers as $index => $customer) {
            if ($customer['id'] === $id) {
                $customerIndex = $index;
                break;
            }
        }
        
        if ($customerIndex === null) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Cliente não encontrado'];
        }
        
        // Validar dados
        $errors = $this->validateCustomerData($input);
        if (!empty($errors)) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => 'Dados de entrada inválidos',
                'errors' => $errors
            ];
        }
        
        // Atualizar cliente
        $customers[$customerIndex] = array_merge($customers[$customerIndex], $input);
        $customers[$customerIndex]['atualizado_em'] = date('c');
        
        if ($this->saveCustomers($customers)) {
            return [
                'success' => true,
                'message' => 'Cliente atualizado com sucesso',
                'data' => $customers[$customerIndex]
            ];
        } else {
            http_response_code(500);
            return [
                'success' => false,
                'message' => 'Erro ao atualizar cliente'
            ];
        }
    }
    
    private function deleteCustomer() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            return ['success' => false, 'message' => 'ID do cliente é obrigatório'];
        }
        
        $customers = $this->loadCustomers();
        $originalCount = count($customers);
        
        // Filtrar para remover o cliente
        $customers = array_filter($customers, function($customer) use ($id) {
            return $customer['id'] !== $id;
        });
        
        if (count($customers) === $originalCount) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Cliente não encontrado'];
        }
        
        if ($this->saveCustomers(array_values($customers))) {
            return [
                'success' => true,
                'message' => 'Cliente removido com sucesso'
            ];
        } else {
            http_response_code(500);
            return [
                'success' => false,
                'message' => 'Erro ao remover cliente'
            ];
        }
    }
}

// Executar API
$api = new CustomerAPI();
$result = $api->handleRequest();

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>