<?php
/**
 * Classe para Upload e Processamento de Imagens
 * 
 * Funcionalidades:
 * - Upload de imagens (JPEG, JPG, PNG, WEBP, GIF)
 * - Crop automático para 500x500px
 * - Compressão para reduzir tamanho
 * - Organização por tenant
 * - Validação de segurança
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

class ImageUploader {
    
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private $maxSize = 5 * 1024 * 1024; // 5MB
    private $targetSize = 500; // 500x500px
    private $quality = 85; // Qualidade de compressão
    private $uploadDir = 'uploads/produtos/';
    
    /**
     * Fazer upload de imagem a partir de base64 (já cropada)
     * 
     * @param string $base64 String base64 da imagem
     * @param int $tenantId ID do tenant
     * @return array ['success' => bool, 'filename' => string, 'message' => string]
     */
    public function uploadFromBase64($base64, $tenantId) {
        try {
            // Extrair dados do base64
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif, webp
                
                // Validar tipo
                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    return [
                        'success' => false,
                        'message' => 'Tipo de imagem não permitido'
                    ];
                }
                
                // Decodificar base64
                $imageData = base64_decode($base64);
                
                if ($imageData === false) {
                    return [
                        'success' => false,
                        'message' => 'Erro ao decodificar imagem'
                    ];
                }
                
                // Criar diretório do tenant se não existir
                $tenantDir = $this->uploadDir . $tenantId . '/';
                if (!file_exists($tenantDir)) {
                    mkdir($tenantDir, 0755, true);
                }
                
                // Gerar nome único
                $filename = uniqid('prod_') . '_' . time() . '.' . ($type === 'jpg' ? 'jpeg' : $type);
                $filepath = $tenantDir . $filename;
                
                // Salvar arquivo
                if (file_put_contents($filepath, $imageData)) {
                    return [
                        'success' => true,
                        'filename' => $tenantId . '/' . $filename,
                        'message' => 'Imagem enviada com sucesso'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Erro ao salvar imagem'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Formato base64 inválido'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Fazer upload de imagem com crop automático
     * 
     * @param array $file Array $_FILES['campo']
     * @param int $tenantId ID do tenant
     * @return array ['success' => bool, 'filename' => string, 'message' => string]
     */
    public function upload($file, $tenantId) {
        try {
            // Validar arquivo
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            // Criar diretório do tenant se não existir
            $tenantDir = $this->uploadDir . $tenantId . '/';
            if (!file_exists($tenantDir)) {
                mkdir($tenantDir, 0755, true);
            }
            
            // Gerar nome único
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid('prod_') . '_' . time() . '.' . $extension;
            $filepath = $tenantDir . $filename;
            
            // Processar imagem (crop e compressão)
            $processed = $this->processImage($file['tmp_name'], $filepath, $extension);
            
            if (!$processed) {
                return [
                    'success' => false,
                    'message' => 'Erro ao processar imagem'
                ];
            }
            
            return [
                'success' => true,
                'filename' => $tenantId . '/' . $filename,
                'message' => 'Imagem enviada com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar arquivo de upload
     */
    private function validateFile($file) {
        // Verificar se houve erro no upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Erro no upload do arquivo'
            ];
        }
        
        // Verificar tamanho
        if ($file['size'] > $this->maxSize) {
            return [
                'success' => false,
                'message' => 'Arquivo muito grande. Máximo: 5MB'
            ];
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Tipo de arquivo não permitido. Use: JPEG, PNG, WEBP ou GIF'
            ];
        }
        
        // Verificar extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'success' => false,
                'message' => 'Extensão não permitida'
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Processar imagem: crop 500x500 e compressão
     */
    private function processImage($source, $destination, $extension) {
        // Criar imagem a partir do arquivo
        $sourceImage = $this->createImageFromFile($source, $extension);
        
        if (!$sourceImage) {
            return false;
        }
        
        // Obter dimensões originais
        list($width, $height) = getimagesize($source);
        
        // Calcular crop para quadrado (centro da imagem)
        $size = min($width, $height);
        $x = ($width - $size) / 2;
        $y = ($height - $size) / 2;
        
        // Criar imagem quadrada cropada
        $croppedImage = imagecreatetruecolor($size, $size);
        
        // Preservar transparência para PNG e GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($croppedImage, false);
            imagesavealpha($croppedImage, true);
            $transparent = imagecolorallocatealpha($croppedImage, 255, 255, 255, 127);
            imagefilledrectangle($croppedImage, 0, 0, $size, $size, $transparent);
        }
        
        // Copiar região central
        imagecopyresampled($croppedImage, $sourceImage, 0, 0, $x, $y, $size, $size, $size, $size);
        
        // Redimensionar para 500x500
        $finalImage = imagecreatetruecolor($this->targetSize, $this->targetSize);
        
        // Preservar transparência
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($finalImage, false);
            imagesavealpha($finalImage, true);
            $transparent = imagecolorallocatealpha($finalImage, 255, 255, 255, 127);
            imagefilledrectangle($finalImage, 0, 0, $this->targetSize, $this->targetSize, $transparent);
        }
        
        imagecopyresampled($finalImage, $croppedImage, 0, 0, 0, 0, $this->targetSize, $this->targetSize, $size, $size);
        
        // Salvar imagem com compressão
        $saved = $this->saveImage($finalImage, $destination, $extension);
        
        // Liberar memória
        imagedestroy($sourceImage);
        imagedestroy($croppedImage);
        imagedestroy($finalImage);
        
        return $saved;
    }
    
    /**
     * Criar imagem GD a partir de arquivo
     */
    private function createImageFromFile($filepath, $extension) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($filepath);
            case 'png':
                return imagecreatefrompng($filepath);
            case 'gif':
                return imagecreatefromgif($filepath);
            case 'webp':
                return imagecreatefromwebp($filepath);
            default:
                return false;
        }
    }
    
    /**
     * Salvar imagem processada
     */
    private function saveImage($image, $filepath, $extension) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $filepath, $this->quality);
            case 'png':
                // PNG: qualidade 0-9 (0 = sem compressão, 9 = máxima)
                $pngQuality = 9 - round(($this->quality / 100) * 9);
                return imagepng($image, $filepath, $pngQuality);
            case 'gif':
                return imagegif($image, $filepath);
            case 'webp':
                return imagewebp($image, $filepath, $this->quality);
            default:
                return false;
        }
    }
    
    /**
     * Deletar imagem antiga
     */
    public function delete($filename) {
        if (empty($filename)) {
            return true;
        }
        
        $filepath = $this->uploadDir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return true;
    }
    
    /**
     * Obter URL da imagem
     */
    public static function getImageUrl($filename) {
        if (empty($filename)) {
            return '/assets/images/no-image.png'; // Imagem placeholder
        }
        
        return '/uploads/produtos/' . $filename;
    }
}
