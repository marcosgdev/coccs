<?php

namespace GestContratos\Services;

final class UploadService
{
    public function store(array $file, string $folder, array $allowedExtensions): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorMessage((int) $file['error']));
        }

        $maxBytes = (int) config('app.upload_max_mb', 20) * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new \RuntimeException('Arquivo acima do limite permitido.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (! in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('Tipo de arquivo nao permitido.');
        }

        $safeName = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($file['name']));
        $relative = 'storage/' . trim($folder, '/') . '/' . $safeName;
        $target = base_path($relative);
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }
        if (! move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('Nao foi possivel salvar o arquivo.');
        }
        return $relative;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo acima do limite permitido pelo PHP. Reinicie o sistema local e tente novamente.',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto. Tente enviar a planilha novamente.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporaria de upload nao configurada no PHP.',
            UPLOAD_ERR_CANT_WRITE => 'Nao foi possivel gravar o arquivo temporario do upload.',
            UPLOAD_ERR_EXTENSION => 'Uma extensao do PHP bloqueou o upload.',
            default => 'Falha no upload do arquivo.',
        };
    }
}
