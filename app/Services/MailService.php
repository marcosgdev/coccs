<?php

namespace GestContratos\Services;

/**
 * Envio de e-mail via SMTP autenticado (sem dependências externas).
 * Configuração via variáveis de ambiente MAIL_*.
 */
final class MailService
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $encryption; // 'tls', 'ssl' ou ''
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->host       = env('MAIL_HOST', 'smtp.gmail.com');
        $this->port       = (int) env('MAIL_PORT', 587);
        $this->username   = env('MAIL_USERNAME', '');
        $this->password   = env('MAIL_PASSWORD', '');
        $this->encryption = strtolower(env('MAIL_ENCRYPTION', 'tls'));
        $this->fromEmail  = env('MAIL_FROM_ADDRESS', 'gestcontratos@tjpa.jus.br');
        $this->fromName   = env('MAIL_FROM_NAME', 'GestContratos TJPA');
    }

    /**
     * Envia e-mail para um ou mais destinatários.
     *
     * @param string|array $to      Um e-mail ou array de e-mails
     * @param string       $subject Assunto
     * @param string       $body    Corpo em texto plano
     * @return bool
     */
    public function send(string|array $to, string $subject, string $body): bool
    {
        $recipients = is_array($to) ? $to : [$to];
        $recipients = array_filter($recipients, fn($e) => filter_var(trim($e), FILTER_VALIDATE_EMAIL));

        if (empty($recipients)) {
            return false;
        }

        // Se não houver SMTP configurado, tenta mail() como fallback
        if (!$this->username) {
            return $this->fallbackMail($recipients, $subject, $body);
        }

        try {
            return $this->sendViaSmtp($recipients, $subject, $body);
        } catch (\Throwable $e) {
            error_log('[MailService] SMTP error: ' . $e->getMessage());
            return false;
        }
    }

    private function sendViaSmtp(array $recipients, string $subject, string $body): bool
    {
        $socket = $this->connect();

        $this->expect($socket, '220');

        $this->cmd($socket, "EHLO gestcontratos.tjpa.jus.br");
        $this->read($socket);

        if ($this->encryption === 'tls') {
            $this->cmd($socket, "STARTTLS");
            $this->expect($socket, '220');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd($socket, "EHLO gestcontratos.tjpa.jus.br");
            $this->read($socket);
        }

        $this->cmd($socket, "AUTH LOGIN");
        $this->expect($socket, '334');
        $this->cmd($socket, base64_encode($this->username));
        $this->expect($socket, '334');
        $this->cmd($socket, base64_encode($this->password));
        $this->expect($socket, '235');

        $this->cmd($socket, "MAIL FROM:<{$this->fromEmail}>");
        $this->expect($socket, '250');

        foreach ($recipients as $email) {
            $this->cmd($socket, "RCPT TO:<" . trim($email) . ">");
            $this->expect($socket, '25');
        }

        $this->cmd($socket, "DATA");
        $this->expect($socket, '354');

        $toHeader  = implode(', ', array_map('trim', $recipients));
        $subjectB64 = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $nameB64    = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';
        $date       = date('r');
        $msgId      = '<' . uniqid('gc', true) . '@tjpa.jus.br>';

        $message  = "Date: {$date}\r\n";
        $message .= "Message-ID: {$msgId}\r\n";
        $message .= "From: {$nameB64} <{$this->fromEmail}>\r\n";
        $message .= "To: {$toHeader}\r\n";
        $message .= "Subject: {$subjectB64}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "\r\n";
        $message .= chunk_split(base64_encode($body));
        $message .= "\r\n.";

        fwrite($socket, $message . "\r\n");
        $this->expect($socket, '250');

        $this->cmd($socket, "QUIT");
        fclose($socket);

        return true;
    }

    private function connect(): mixed
    {
        $prefix  = $this->encryption === 'ssl' ? 'ssl://' : '';
        $address = $prefix . $this->host;

        $ctx = stream_context_create(['ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
        ]]);

        $socket = stream_socket_client(
            "{$address}:{$this->port}",
            $errno, $errstr, 15,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if (!$socket) {
            throw new \RuntimeException("Não foi possível conectar ao SMTP {$this->host}:{$this->port} — {$errstr}");
        }

        stream_set_timeout($socket, 15);
        return $socket;
    }

    private function cmd(mixed $socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    private function read(mixed $socket): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    }

    private function expect(mixed $socket, string $code): string
    {
        $response = $this->read($socket);
        if (!str_starts_with($response, $code)) {
            throw new \RuntimeException("SMTP esperou {$code}, recebeu: {$response}");
        }
        return $response;
    }

    private function fallbackMail(array $recipients, string $subject, string $body): bool
    {
        $headers = implode("\r\n", [
            'Content-Type: text/plain; charset=UTF-8',
            "From: {$this->fromEmail}",
        ]);
        $ok = true;
        foreach ($recipients as $email) {
            $sent = mail(trim($email), '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
            if (!$sent) $ok = false;
        }
        return $ok;
    }
}
