<?php
require_once __DIR__ . '/db.php';

function sendAdminInviteEmail($recipient_email, $username, $temp_password) {
    global $pdo;

    // Fetch site title & support email from system_settings
    $site_name = 'RateMyPalika';
    $support_email = 'no-reply@ratemypalika.np';

    $cfg_stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $cfg_stmt->fetch()) {
        if ($row['setting_key'] === 'site_name') $site_name = $row['setting_value'];
        if ($row['setting_key'] === 'support_email') $support_email = $row['setting_value'];
    }

    $login_url = "http://" . $_SERVER['HTTP_HOST'] . "/ratemypalika/admin/login.php";

    $subject = "Official Invitation: Join $site_name Admin Panel";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 20px; }
            .card { background: #ffffff; max-width: 520px; margin: 0 auto; border-radius: 10px; border: 1px solid #e2e8f0; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
            .header { border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
            .brand { font-size: 22px; font-weight: 800; color: #0f172a; }
            .sub { font-size: 12px; color: #2563eb; font-weight: 600; }
            .cred-box { background: #f1f5f9; border-radius: 6px; padding: 16px; margin: 20px 0; font-size: 14px; }
            .btn { display: inline-block; background: #0f172a; color: #ffffff !important; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; margin-top: 10px; }
            .footer { font-size: 12px; color: #64748b; margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <div class='header'>
                <div class='brand'>$site_name</div>
                <div class='sub'>An AcademiX Digital Initiative</div>
            </div>
            
            <h3>You've Been Invited as an Administrator</h3>
            <p>Hello,</p>
            <p>An administrator account has been created for you on the <strong>$site_name</strong> Admin Control Console. You can log in using the credentials below:</p>
            
            <div class='cred-box'>
                <strong>Login Credentials:</strong><br><br>
                <b>Username:</b> " . htmlspecialchars($username) . "<br>
                <b>Email:</b> " . htmlspecialchars($recipient_email) . "<br>
                <b>Temporary Password:</b> " . htmlspecialchars($temp_password) . "
            </div>

            <a href='$login_url' class='btn' target='_blank'>Access Admin Console ↗</a>

            <p style='margin-top: 20px; font-size: 13px; color: #64748b;'><em>Note: We recommend updating your password in the <b>Platform Settings</b> tab after your first login.</em></p>

            <div class='footer'>
                This is an automated administrative invitation from $site_name.<br>
                For support, contact: <a href='mailto:$support_email'>$support_email</a>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $site_name <$support_email>" . "\r\n";
    $headers .= "Reply-To: $support_email" . "\r\n";

    return @mail($recipient_email, $subject, $message, $headers);
}