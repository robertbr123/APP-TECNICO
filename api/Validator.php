<?php
/**
 * Validator - Validações Centralizadas
 * Ondeline Tech - App do Técnico
 */

class Validator {
    
    /**
     * Valida CPF usando algoritmo matemático
     */
    public static function validateCPF($cpf) {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/\D/', '', $cpf);
        
        // Verifica tamanho
        if (strlen($cpf) !== 11) {
            return [
                'valid' => false,
                'message' => 'CPF deve ter 11 dígitos'
            ];
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1+$/', $cpf)) {
            return [
                'valid' => false,
                'message' => 'CPF inválido (todos os dígitos iguais)'
            ];
        }
        
        // Calcula primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = ($sum * 10) % 11;
        $digit1 = ($remainder === 10) ? 0 : $remainder;
        
        if ($digit1 !== intval($cpf[9])) {
            return [
                'valid' => false,
                'message' => 'CPF inválido (primeiro dígito verificador)'
            ];
        }
        
        // Calcula segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = ($sum * 10) % 11;
        $digit2 = ($remainder === 10) ? 0 : $remainder;
        
        if ($digit2 !== intval($cpf[10])) {
            return [
                'valid' => false,
                'message' => 'CPF inválido (segundo dígito verificador)'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'CPF válido',
            'clean' => $cpf,
            'formatted' => self::formatCPF($cpf)
        ];
    }
    
    /**
     * Formata CPF
     */
    public static function formatCPF($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return $cpf;
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    
    /**
     * Valida telefone
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/\D/', '', $phone);
        
        if (strlen($phone) < 10 || strlen($phone) > 11) {
            return [
                'valid' => false,
                'message' => 'Telefone deve ter 10 ou 11 dígitos'
            ];
        }
        
        // Verifica DDD válido
        $ddd = intval(substr($phone, 0, 2));
        if ($ddd < 11 || $ddd > 99) {
            return [
                'valid' => false,
                'message' => 'DDD inválido'
            ];
        }
        
        // Validação de celular (se tiver 11 dígitos)
        if (strlen($phone) === 11) {
            $ninthDigit = intval($phone[2]);
            if ($ninthDigit !== 9) {
                return [
                    'valid' => false,
                    'message' => 'Celular inválido (deve começar com 9)'
                ];
            }
        }
        
        return [
            'valid' => true,
            'message' => 'Telefone válido',
            'clean' => $phone,
            'formatted' => self::formatPhone($phone)
        ];
    }
    
    /**
     * Formata telefone
     */
    public static function formatPhone($phone) {
        $phone = preg_replace('/\D/', '', $phone);
        
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
        } elseif (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
        }
        
        return $phone;
    }
    
    /**
     * Valida CEP
     */
    public static function validateCEP($cep) {
        $cep = preg_replace('/\D/', '', $cep);
        
        if (strlen($cep) !== 8) {
            return [
                'valid' => false,
                'message' => 'CEP deve ter 8 dígitos'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'CEP válido',
            'clean' => $cep,
            'formatted' => substr($cep, 0, 5) . '-' . substr($cep, 5, 3)
        ];
    }
    
    /**
     * Valida email
     */
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => 'Email inválido'
            ];
        }
        
        // Verifica domínio
        $domain = substr(strrchr($email, '@'), 1);
        if (!checkdnsrr($domain, 'MX')) {
            return [
                'valid' => false,
                'message' => 'Domínio de email inválido'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Email válido'
        ];
    }
    
    /**
     * Valida arquivo de upload
     */
    public static function validateUpload($file, $options = []) {
        $defaults = [
            'maxSize' => 10 * 1024 * 1024, // 10MB
            'allowedTypes' => ['image/jpeg', 'image/png', 'image/webp'],
            'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp']
        ];
        $options = array_merge($defaults, $options);
        
        // Verifica erro de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'Arquivo excede o tamanho máximo do servidor',
                UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo do formulário',
                UPLOAD_ERR_PARTIAL => 'Upload foi interrompido',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
                UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
                UPLOAD_ERR_CANT_WRITE => 'Erro ao salvar arquivo',
                UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
            ];
            
            return [
                'valid' => false,
                'message' => $errors[$file['error']] ?? 'Erro desconhecido no upload'
            ];
        }
        
        // Verifica tamanho
        if ($file['size'] > $options['maxSize']) {
            $maxSizeMB = $options['maxSize'] / (1024 * 1024);
            return [
                'valid' => false,
                'message' => "Arquivo muito grande (máx: {$maxSizeMB}MB)"
            ];
        }
        
        // Verifica MIME type real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $options['allowedTypes'])) {
            return [
                'valid' => false,
                'message' => 'Tipo de arquivo não permitido. Permitidos: ' . implode(', ', $options['allowedTypes'])
            ];
        }
        
        // Verifica extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $options['allowedExtensions'])) {
            return [
                'valid' => false,
                'message' => 'Extensão de arquivo não permitida'
            ];
        }
        
        // Verifica se é imagem válida (para imagens)
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return [
                    'valid' => false,
                    'message' => 'Arquivo de imagem inválido ou corrompido'
                ];
            }
        }
        
        return [
            'valid' => true,
            'message' => 'Arquivo válido',
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
    
    /**
     * Valida base64 de imagem
     */
    public static function validateBase64Image($base64, $options = []) {
        $defaults = [
            'maxSize' => 10 * 1024 * 1024,
            'allowedTypes' => ['jpeg', 'jpg', 'png', 'webp']
        ];
        $options = array_merge($defaults, $options);
        
        // Verifica formato
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            return [
                'valid' => false,
                'message' => 'Formato base64 inválido'
            ];
        }
        
        $imageType = strtolower($matches[1]);
        
        if (!in_array($imageType, $options['allowedTypes'])) {
            return [
                'valid' => false,
                'message' => 'Tipo de imagem não permitido. Permitidos: ' . implode(', ', $options['allowedTypes'])
            ];
        }
        
        // Extrai e decodifica
        $data = substr($base64, strpos($base64, ',') + 1);
        $decoded = base64_decode($data, true);
        
        if ($decoded === false) {
            return [
                'valid' => false,
                'message' => 'Falha ao decodificar base64'
            ];
        }
        
        // Verifica tamanho
        if (strlen($decoded) > $options['maxSize']) {
            $maxSizeMB = $options['maxSize'] / (1024 * 1024);
            return [
                'valid' => false,
                'message' => "Imagem muito grande (máx: {$maxSizeMB}MB)"
            ];
        }
        
        // Verifica se é imagem válida
        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($tempFile, $decoded);
        
        $imageInfo = getimagesize($tempFile);
        unlink($tempFile);
        
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'message' => 'Dados não representam uma imagem válida'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Imagem válida',
            'type' => $imageType,
            'size' => strlen($decoded),
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ];
    }
    
    /**
     * Sanitiza string
     */
    public static function sanitizeString($string, $options = []) {
        $defaults = [
            'stripTags' => true,
            'encodeSpecial' => true,
            'maxLength' => null
        ];
        $options = array_merge($defaults, $options);
        
        if ($options['stripTags']) {
            $string = strip_tags($string);
        }
        
        if ($options['encodeSpecial']) {
            $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        }
        
        $string = trim($string);
        
        if ($options['maxLength'] && strlen($string) > $options['maxLength']) {
            $string = substr($string, 0, $options['maxLength']);
        }
        
        return $string;
    }
    
    /**
     * Valida campos obrigatórios
     */
    public static function validateRequired($data, $fields) {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Campo obrigatório: {$field}";
            }
        }
        
        if (!empty($errors)) {
            return [
                'valid' => false,
                'message' => implode(', ', $errors),
                'errors' => $errors
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Todos os campos preenchidos'
        ];
    }
}
