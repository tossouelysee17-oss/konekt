<?php
// ============================================================
//  Envoi d'email via la fonction mail() de PHP.
//  En local (XAMPP) sans SMTP configuré, l'email n'est pas
//  réellement envoyé mais on l'écrit dans un fichier log pour
//  la démo. Adapte cette fonction si un vrai SMTP est dispo.
// ============================================================

function send_html_email(string $to, string $subject, string $htmlBody): bool {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Konekt <no-reply@konekt.local>\r\n";

    // Log local pour la démo
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    @file_put_contents(
        $logDir . '/emails.log',
        "[" . date('Y-m-d H:i:s') . "] À: $to | Sujet: $subject\n" . $htmlBody . "\n\n----\n\n",
        FILE_APPEND
    );

    // Tentative d'envoi réel (fonctionnera si un SMTP est configuré dans php.ini)
    @mail($to, $subject, $htmlBody, $headers);
    return true;
}

function email_template(string $title, string $intro, string $ctaLabel, string $ctaUrl, string $footer = ''): string {
    return '<!DOCTYPE html><html><head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#0f1b3d;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0f1b3d;padding:40px 20px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#f5f0e0;border-radius:12px;overflow:hidden;">
        <tr><td style="background:#0f1b3d;padding:24px;text-align:center;">
          <span style="font-family:Georgia,serif;font-size:28px;color:#c9a84c;letter-spacing:2px;">KONEKT</span>
        </td></tr>
        <tr><td style="padding:36px 40px;color:#1a1a1a;">
          <h1 style="margin:0 0 16px;color:#0f1b3d;font-size:22px;">' . htmlspecialchars($title) . '</h1>
          <p style="line-height:1.6;font-size:15px;color:#333;">' . nl2br(htmlspecialchars($intro)) . '</p>
          <p style="text-align:center;margin:32px 0;">
            <a href="' . htmlspecialchars($ctaUrl) . '" style="display:inline-block;background:#c9a84c;color:#0f1b3d;font-weight:bold;padding:14px 28px;border-radius:6px;text-decoration:none;">' . htmlspecialchars($ctaLabel) . '</a>
          </p>
          <p style="font-size:12px;color:#777;">' . nl2br(htmlspecialchars($footer)) . '</p>
        </td></tr>
        <tr><td style="background:#0f1b3d;padding:16px;text-align:center;color:#c9a84c;font-size:12px;">
          &copy; ' . date('Y') . ' Konekt — Projet PHP/AJAX
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>';
}
