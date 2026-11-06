<?php
/*
Plugin Name: OLX Single Sign-On (Google & Facebook)
Plugin URI: https://github.com/osclass/
Description: Complete Single Sign-On (SSO) integration for Google and Facebook login and instant 1-click registration.
Version: 1.0.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_social_login
Plugin update URI:
*/

function olx_social_login_install() {
    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    $comm->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_user_social (
        pk_i_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        fk_i_user_id INT UNSIGNED NOT NULL,
        s_provider VARCHAR(30) NOT NULL,
        s_provider_uid VARCHAR(191) NOT NULL,
        s_email VARCHAR(191) NULL,
        s_avatar_url TEXT NULL,
        dt_created_at DATETIME NOT NULL,
        dt_last_login DATETIME NOT NULL,
        PRIMARY KEY (pk_i_id),
        UNIQUE KEY uq_provider_uid (s_provider, s_provider_uid),
        KEY idx_user_id (fk_i_user_id),
        KEY idx_provider_email (s_provider, s_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    osc_set_preference('google_enabled', '1', 'olx_social_login', 'INTEGER');
    osc_set_preference('google_client_id', '', 'olx_social_login', 'STRING');
    osc_set_preference('google_client_secret', '', 'olx_social_login', 'STRING');

    osc_set_preference('facebook_enabled', '1', 'olx_social_login', 'INTEGER');
    osc_set_preference('facebook_app_id', '', 'olx_social_login', 'STRING');
    osc_set_preference('facebook_app_secret', '', 'olx_social_login', 'STRING');

    osc_set_preference('allow_registration', '1', 'olx_social_login', 'INTEGER');
    osc_set_preference('sandbox_mode', '1', 'olx_social_login', 'INTEGER');
}

function olx_social_login_uninstall() {
    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);
    $comm->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_user_social");
    Preference::newInstance()->delete(array('s_section' => 'olx_social_login'));
}

// Admin menu
function olx_social_login_admin_menu() {
    osc_add_admin_menu_page(
        __('Social Login (SSO)', 'olx_social_login'),
        osc_admin_render_plugin_url('olx_social_login/admin.php'),
        'olx_social_login_admin',
        'administrator'
    );
}

// Injected CSS for Social Buttons
function olx_social_login_header() {
    echo '<style>
    .olx-social-login-container {
        margin: 12px 0 20px 0;
        width: 100%;
    }
    .olx-social-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .olx-social-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 12px !important;
        width: 100% !important;
        box-sizing: border-box !important;
        height: 44px !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        text-decoration: none !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        line-height: normal !important;
    }
    .olx-btn-google {
        background: #ffffff !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
    }
    .olx-btn-google:hover {
        background: #f9fafb !important;
        border-color: #9ca3af !important;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12) !important;
        color: #111827 !important;
    }
    .olx-btn-facebook {
        background: #1877f2 !important;
        color: #ffffff !important;
        border: 1px solid #1877f2 !important;
        box-shadow: 0 1px 3px rgba(24, 119, 242, 0.3) !important;
    }
    .olx-btn-facebook:hover {
        background: #166fe5 !important;
        border-color: #166fe5 !important;
        box-shadow: 0 4px 12px rgba(24, 119, 242, 0.4) !important;
        color: #ffffff !important;
    }
    .olx-social-icon {
        flex-shrink: 0;
    }
    .olx-social-divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 20px 0 10px 0;
        color: #9ca3af;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.8px;
    }
    .olx-social-divider::before,
    .olx-social-divider::after {
        content: "";
        flex: 1;
        border-bottom: 1px solid #e5e7eb;
    }
    .olx-social-divider span {
        padding: 0 12px;
    }
    </style>';
}

// Render Social Buttons on Login and Registration forms
function olx_social_login_buttons() {
    $google_enabled   = (int)osc_get_preference('google_enabled', 'olx_social_login');
    $facebook_enabled = (int)osc_get_preference('facebook_enabled', 'olx_social_login');

    if (!$google_enabled && !$facebook_enabled) {
        return;
    }

    $google_url   = osc_base_url(true) . '?page=social_auth&provider=google';
    $facebook_url = osc_base_url(true) . '?page=social_auth&provider=facebook';
    ?>
    <div class="olx-social-login-container">
        <div class="olx-social-buttons">
            <?php if ($google_enabled) { ?>
                <a href="<?php echo $google_url; ?>" class="olx-social-btn olx-btn-google" title="Entrar com o Google">
                    <svg class="olx-social-icon" viewBox="0 0 24 24" width="20" height="20">
                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.99 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                    </svg>
                    <span>Continuar com Google</span>
                </a>
            <?php } ?>

            <?php if ($facebook_enabled) { ?>
                <a href="<?php echo $facebook_url; ?>" class="olx-social-btn olx-btn-facebook" title="Entrar com o Facebook">
                    <svg class="olx-social-icon" viewBox="0 0 24 24" width="20" height="20" fill="#ffffff">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span>Continuar com Facebook</span>
                </a>
            <?php } ?>
        </div>
        <div class="olx-social-divider">
            <span>OU</span>
        </div>
    </div>
    <?php
}

// Controller logic for initiating or returning from OAuth flow
function olx_social_login_controller() {
    if (Params::getParam('page') !== 'social_auth') {
        return;
    }

    $action   = Params::getParam('action') ?: 'redirect';
    $provider = strtolower(Params::getParam('provider'));

    if (!in_array($provider, array('google', 'facebook'), true)) {
        osc_add_flash_error_message(__('Provedor de autenticação inválido.', 'olx_social_login'));
        header('Location: ' . osc_user_login_url());
        exit;
    }

    if ($action === 'redirect') {
        olx_social_login_start($provider);
    } elseif ($action === 'callback') {
        olx_social_login_callback($provider);
    }
}

// Initiate OAuth flow
function olx_social_login_start($provider) {
    if (osc_is_web_user_logged_in()) {
        header('Location: ' . osc_user_dashboard_url());
        exit;
    }

    $sandbox = (int)osc_get_preference('sandbox_mode', 'olx_social_login');

    if ($provider === 'google') {
        $enabled   = (int)osc_get_preference('google_enabled', 'olx_social_login');
        $client_id = trim(osc_get_preference('google_client_id', 'olx_social_login'));

        if (!$enabled) {
            osc_add_flash_error_message(__('O login com Google está temporariamente desativado.', 'olx_social_login'));
            if (defined('OC_TEST_MODE') && OC_TEST_MODE) { return; }
            header('Location: ' . osc_user_login_url());
            exit;
        }

        if (empty($client_id) && !$sandbox) {
            osc_add_flash_info_message(__('As credenciais do Google SSO ainda não foram configuradas. Acesse o Painel de Administração (Plugins > Social Login) para inserir o Client ID.', 'olx_social_login'));
            if (defined('OC_TEST_MODE') && OC_TEST_MODE) { return; }
            header('Location: ' . osc_user_login_url());
            exit;
        }

        if ($sandbox && (empty($client_id) || Params::getParam('sandbox') == '1')) {
            olx_social_login_simulate('google');
            exit;
        }

        // Live Google OAuth 2.0
        $state = bin2hex(random_bytes(16));
        Session::newInstance()->_set('oauth_state', $state);
        Session::newInstance()->_set('oauth_provider', 'google');

        $redirect_uri = osc_base_url(true) . '?page=social_auth&action=callback&provider=google';
        $params = array(
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account'
        );

        $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $auth_url);
        exit;

    } elseif ($provider === 'facebook') {
        $enabled = (int)osc_get_preference('facebook_enabled', 'olx_social_login');
        $app_id  = trim(osc_get_preference('facebook_app_id', 'olx_social_login'));

        if (!$enabled) {
            osc_add_flash_error_message(__('O login com Facebook está temporariamente desativado.', 'olx_social_login'));
            if (defined('OC_TEST_MODE') && OC_TEST_MODE) { return; }
            header('Location: ' . osc_user_login_url());
            exit;
        }

        if (empty($app_id) && !$sandbox) {
            osc_add_flash_info_message(__('As credenciais do Facebook SSO ainda não foram configuradas. Acesse o Painel de Administração (Plugins > Social Login) para inserir o App ID.', 'olx_social_login'));
            if (defined('OC_TEST_MODE') && OC_TEST_MODE) { return; }
            header('Location: ' . osc_user_login_url());
            exit;
        }

        if ($sandbox && (empty($app_id) || Params::getParam('sandbox') == '1')) {
            olx_social_login_simulate('facebook');
            exit;
        }

        // Live Facebook OAuth 2.0
        $state = bin2hex(random_bytes(16));
        Session::newInstance()->_set('oauth_state', $state);
        Session::newInstance()->_set('oauth_provider', 'facebook');

        $redirect_uri = osc_base_url(true) . '?page=social_auth&action=callback&provider=facebook';
        $params = array(
            'client_id'     => $app_id,
            'redirect_uri'  => $redirect_uri,
            'state'         => $state,
            'scope'         => 'email,public_profile'
        );

        $auth_url = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
        header('Location: ' . $auth_url);
        exit;
    }
}

// OAuth Callback handler
function olx_social_login_callback($provider) {
    $code  = Params::getParam('code');
    $state = Params::getParam('state');
    $error = Params::getParam('error') ?: Params::getParam('error_description');

    if (!empty($error)) {
        osc_add_flash_error_message(sprintf(__('Autenticação cancelada ou recusada: %s', 'olx_social_login'), osc_esc_html($error)));
        header('Location: ' . osc_user_login_url());
        exit;
    }

    if (empty($code)) {
        osc_add_flash_error_message(__('Código de autorização não recebido do provedor.', 'olx_social_login'));
        header('Location: ' . osc_user_login_url());
        exit;
    }

    $saved_state = Session::newInstance()->_get('oauth_state');
    if (empty($state) || $state !== $saved_state) {
        osc_add_flash_error_message(__('Falha de validação de segurança (estado CSRF inválido). Por favor, tente novamente.', 'olx_social_login'));
        header('Location: ' . osc_user_login_url());
        exit;
    }

    Session::newInstance()->_drop('oauth_state');

    $userData = null;
    if ($provider === 'google') {
        $userData = olx_social_login_fetch_google($code);
    } elseif ($provider === 'facebook') {
        $userData = olx_social_login_fetch_facebook($code);
    }

    if (!$userData || empty($userData['email'])) {
        osc_add_flash_error_message(__('Não foi possível obter o perfil ou e-mail a partir do provedor.', 'olx_social_login'));
        header('Location: ' . osc_user_login_url());
        exit;
    }

    olx_social_login_authenticate_user($provider, $userData);
}

// Exchange code & fetch Google profile
function olx_social_login_fetch_google($code) {
    $client_id     = osc_get_preference('google_client_id', 'olx_social_login');
    $client_secret = osc_get_preference('google_client_secret', 'olx_social_login');
    $redirect_uri  = osc_base_url(true) . '?page=social_auth&action=callback&provider=google';

    // 1. Token Exchange
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
        'grant_type'    => 'authorization_code'
    )));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200 || !$response) {
        return false;
    }

    $tokenData = json_decode($response, true);
    if (empty($tokenData['access_token'])) {
        return false;
    }

    // 2. Fetch User Profile
    $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $tokenData['access_token']
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $userProfile = curl_exec($ch);
    $status      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200 || !$userProfile) {
        return false;
    }

    $profile = json_decode($userProfile, true);
    return array(
        'uid'     => $profile['sub'] ?? '',
        'email'   => strtolower(trim($profile['email'] ?? '')),
        'name'    => $profile['name'] ?? '',
        'picture' => $profile['picture'] ?? ''
    );
}

// Exchange code & fetch Facebook profile
function olx_social_login_fetch_facebook($code) {
    $app_id       = osc_get_preference('facebook_app_id', 'olx_social_login');
    $app_secret   = osc_get_preference('facebook_app_secret', 'olx_social_login');
    $redirect_uri = osc_base_url(true) . '?page=social_auth&action=callback&provider=facebook';

    // 1. Token Exchange
    $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query(array(
        'client_id'     => $app_id,
        'client_secret' => $app_secret,
        'redirect_uri'  => $redirect_uri,
        'code'          => $code
    ));

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200 || !$response) {
        return false;
    }

    $tokenData = json_decode($response, true);
    if (empty($tokenData['access_token'])) {
        return false;
    }

    // 2. Fetch User Profile
    $info_url = 'https://graph.facebook.com/me?fields=id,name,email,picture.width(250).height(250)&access_token=' . urlencode($tokenData['access_token']);
    $ch = curl_init($info_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $userProfile = curl_exec($ch);
    $status      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200 || !$userProfile) {
        return false;
    }

    $profile = json_decode($userProfile, true);
    $picture = '';
    if (isset($profile['picture']['data']['url'])) {
        $picture = $profile['picture']['data']['url'];
    }

    return array(
        'uid'     => $profile['id'] ?? '',
        'email'   => strtolower(trim($profile['email'] ?? '')),
        'name'    => $profile['name'] ?? '',
        'picture' => $picture
    );
}

// Match or create user and login
function olx_social_login_authenticate_user($provider, $userData) {
    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    $email       = $userData['email'];
    $name        = $userData['name'] ?: substr($email, 0, strpos($email, '@'));
    $providerUid = $userData['uid'];
    $pictureUrl  = $userData['picture'] ?? '';
    $now         = date('Y-m-d H:i:s');

    // 1. Check if linked in oc_t_user_social
    $userId = 0;
    $res = $comm->query("SELECT fk_i_user_id FROM " . DB_TABLE_PREFIX . "t_user_social WHERE s_provider = '{$provider}' AND s_provider_uid = '{$providerUid}' LIMIT 1");
    if ($res && $res->numRows() > 0) {
        $row = $res->row();
        $userId = (int)$row['fk_i_user_id'];
    }

    // 2. If not found by UID, check by email
    if ($userId === 0) {
        $existing = User::newInstance()->findByEmail($email);
        if ($existing && !empty($existing['pk_i_id'])) {
            $userId = (int)$existing['pk_i_id'];
        }
    }

    // 3. If not found, create new user account
    if ($userId === 0) {
        $allowReg = (int)osc_get_preference('allow_registration', 'olx_social_login');
        if (!$allowReg) {
            osc_add_flash_error_message(__('Nenhuma conta existente com este e-mail e o registro social está desativado.', 'olx_social_login'));
            if (defined('OC_TEST_MODE') && OC_TEST_MODE) { return $userId; }
            header('Location: ' . osc_user_login_url());
            exit;
        }

        // Username generation
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(substr($email, 0, strpos($email, '@'))));
        if (strlen($baseUsername) < 3) {
            $baseUsername = 'user' . rand(100, 999);
        }
        $username = $baseUsername;
        $counter = 1;
        while (User::newInstance()->findByUsername($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        $randomPassword = osc_genRandomPassword();
        $userDataToInsert = array(
            's_name'         => $name,
            's_username'     => $username,
            's_password'     => osc_hash_password($randomPassword),
            's_email'        => $email,
            's_secret'       => osc_genRandomPassword(),
            'b_active'       => 1, // Pre-verified by provider
            'b_enabled'      => 1,
            'dt_reg_date'    => $now,
            'dt_mod_date'    => $now,
            'dt_access_date' => $now,
            's_access_ip'    => osc_get_ip()
        );

        $comm->insert(DB_TABLE_PREFIX . 't_user', $userDataToInsert);
        $userId = (int)$comm->insertedId();

        // Download and attach avatar if provided
        if (!empty($pictureUrl) && $userId > 0) {
            olx_social_save_avatar($userId, $pictureUrl);
        }
    }

    if ($userId > 0) {
        // Link or update social row
        $escapedPic = addslashes($pictureUrl);
        $comm->query("INSERT INTO " . DB_TABLE_PREFIX . "t_user_social (fk_i_user_id, s_provider, s_provider_uid, s_email, s_avatar_url, dt_created_at, dt_last_login)
            VALUES ({$userId}, '{$provider}', '{$providerUid}', '{$email}', '{$escapedPic}', '{$now}', '{$now}')
            ON DUPLICATE KEY UPDATE fk_i_user_id = {$userId}, s_email = '{$email}', s_avatar_url = '{$escapedPic}', dt_last_login = '{$now}'");

        // Log into Osclass
        require_once LIB_PATH . 'osclass/UserActions.php';
        $uActions = new UserActions(false);
        $loginResult = $uActions->bootstrap_login($userId);

        if ($loginResult === 3) {
            $providerTitle = ($provider === 'google') ? 'Google' : 'Facebook';
            osc_add_flash_ok_message(sprintf(__('Bem-vindo, %s! Conectado com sucesso via %s.'), osc_esc_html($name), $providerTitle));
            if (defined('OC_TEST_MODE') && OC_TEST_MODE) { return $userId; }
            header('Location: ' . osc_user_dashboard_url());
            exit;
        } else {
            osc_add_flash_error_message(__('Sua conta de usuário está inativa ou bloqueada. Contate o suporte.', 'olx_social_login'));
            if (defined('OC_TEST_MODE') && OC_TEST_MODE) { return $userId; }
            header('Location: ' . osc_user_login_url());
            exit;
        }
    }
}

// Download & store profile avatar
function olx_social_save_avatar($userId, $pictureUrl) {
    try {
        $ch = curl_init($pictureUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && !empty($data)) {
            $dir = osc_base_path() . OC_CONTENT_FOLDER . '/uploads/user-images/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $filename = 'user_' . $userId . '_' . time() . '.jpg';
            file_put_contents($dir . $filename, $data);
            User::newInstance()->update(array('s_profile_img' => $filename), array('pk_i_id' => $userId));
        }
    } catch (Exception $e) {}
}

// Development Sandbox Simulation Mode
function olx_social_login_simulate($provider) {
    if ($provider === 'google') {
        $mockUser = array(
            'uid'     => 'google_demo_' . time(),
            'email'   => 'demo.google@example.com',
            'name'    => 'Demo Google User',
            'picture' => 'https://lh3.googleusercontent.com/a/default-user'
        );
    } else {
        $mockUser = array(
            'uid'     => 'facebook_demo_' . time(),
            'email'   => 'demo.facebook@example.com',
            'name'    => 'Demo Facebook User',
            'picture' => 'https://graph.facebook.com/4/picture?type=large'
        );
    }

    olx_social_login_authenticate_user($provider, $mockUser);
}

// Register plugin hooks
osc_register_plugin(osc_plugin_path(__FILE__), 'olx_social_login_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'olx_social_login_uninstall');

osc_add_hook('admin_menu_init', 'olx_social_login_admin_menu');
osc_add_hook('header', 'olx_social_login_header');
osc_add_hook('user_pre_login_form', 'olx_social_login_buttons');
osc_add_hook('user_pre_register_form', 'olx_social_login_buttons');
osc_add_hook('init', 'olx_social_login_controller');
