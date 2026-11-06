<?php

namespace PHPMailer\PHPMailer;

class Exception extends \Exception
{
}

class SMTP
{
    public const DEFAULT_PORT = 25;
}

class PHPMailer
{
    public const ENCRYPTION_STARTTLS = 'tls';
    public const ENCRYPTION_SMTPS = 'ssl';

    public string $Host = '';
    public bool $SMTPAuth = false;
    public string $Username = '';
    public string $Password = '';
    public string $SMTPSecure = '';
    public int $Port = 25;
    public string $CharSet = 'UTF-8';
    public string $Subject = '';
    public string $Body = '';
    public string $ErrorInfo = '';

    private string $fromAddress = '';
    private string $fromName = '';
    private array $addresses = [];
    private bool $html = false;

    public function __construct(bool $exceptions = false)
    {
    }

    public function isSMTP(): void
    {
    }

    public function setFrom(string $address, string $name = ''): bool
    {
        $this->fromAddress = $address;
        $this->fromName = $name;
        return true;
    }

    public function addAddress(string $address, string $name = ''): bool
    {
        $this->addresses[] = [$address, $name];
        return true;
    }

    public function clearAddresses(): void
    {
        $this->addresses = [];
    }

    public function isHTML(bool $isHtml = true): void
    {
        $this->html = $isHtml;
    }

    public function send(): bool
    {
        $to = $this->addresses[0][0] ?? '';
        if ($to === '') {
            $this->ErrorInfo = 'Aucun destinataire défini.';
            return false;
        }

        $headers = [];
        if ($this->fromAddress !== '') {
            $fromName = $this->fromName !== '' ? sprintf('"%s" ', $this->fromName) : '';
            $headers[] = 'From: ' . $fromName . '<' . $this->fromAddress . '>';
        }

        if ($this->html) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=' . $this->CharSet;
        } else {
            $headers[] = 'Content-type: text/plain; charset=' . $this->CharSet;
        }

        $subject = $this->Subject;
        $message = $this->Body;
        $result = @mail($to, $subject, $message, implode("\r\n", $headers));

        if (!$result) {
            $this->ErrorInfo = 'Envoi impossible sans PHPMailer installé via Composer.';
        }

        return $result;
    }
}
