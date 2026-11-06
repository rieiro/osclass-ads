<?php
if (!defined('OC_ADMIN') || OC_ADMIN !== true) exit('Access denied');

if (Params::getParam('plugin_action') == 'done') {
    osc_set_preference('google_enabled', Params::getParam('google_enabled') ? '1' : '0', 'olx_social_login', 'INTEGER');
    osc_set_preference('google_client_id', trim(Params::getParam('google_client_id')), 'olx_social_login', 'STRING');
    osc_set_preference('google_client_secret', trim(Params::getParam('google_client_secret')), 'olx_social_login', 'STRING');

    osc_set_preference('facebook_enabled', Params::getParam('facebook_enabled') ? '1' : '0', 'olx_social_login', 'INTEGER');
    osc_set_preference('facebook_app_id', trim(Params::getParam('facebook_app_id')), 'olx_social_login', 'STRING');
    osc_set_preference('facebook_app_secret', trim(Params::getParam('facebook_app_secret')), 'olx_social_login', 'STRING');

    osc_set_preference('allow_registration', Params::getParam('allow_registration') ? '1' : '0', 'olx_social_login', 'INTEGER');
    osc_set_preference('sandbox_mode', Params::getParam('sandbox_mode') ? '1' : '0', 'olx_social_login', 'INTEGER');

    osc_add_flash_ok_message(__('Configurações de Single Sign-On (SSO) salvas com sucesso!', 'olx_social_login'), 'admin');
    header('Location: ' . osc_admin_render_plugin_url('olx_social_login/admin.php'));
    exit;
}

$google_enabled       = (int)osc_get_preference('google_enabled', 'olx_social_login');
$google_client_id     = osc_get_preference('google_client_id', 'olx_social_login');
$google_client_secret = osc_get_preference('google_client_secret', 'olx_social_login');

$facebook_enabled     = (int)osc_get_preference('facebook_enabled', 'olx_social_login');
$facebook_app_id      = osc_get_preference('facebook_app_id', 'olx_social_login');
$facebook_app_secret  = osc_get_preference('facebook_app_secret', 'olx_social_login');

$allow_registration   = (int)osc_get_preference('allow_registration', 'olx_social_login');
$sandbox_mode         = (int)osc_get_preference('sandbox_mode', 'olx_social_login');

$google_callback_url   = osc_base_url(true) . '?page=social_auth&action=callback&provider=google';
$facebook_callback_url = osc_base_url(true) . '?page=social_auth&action=callback&provider=facebook';

// Fetch recent social logins
$conn = DBConnectionClass::newInstance();
$db   = $conn->getOsclassDb();
$comm = new DBCommandClass($db);
$socialUsers = array();
$res = $comm->query("SELECT s.*, u.s_name as user_name FROM " . DB_TABLE_PREFIX . "t_user_social s LEFT JOIN " . DB_TABLE_PREFIX . "t_user u ON u.pk_i_id = s.fk_i_user_id ORDER BY s.dt_last_login DESC LIMIT 10");
if ($res && $res->numRows() > 0) {
    $socialUsers = $res->result();
}
?>

<style>
.social-admin-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.social-admin-card h3 {
    margin-top: 0;
    margin-bottom: 16px;
    color: #0f172a;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.social-field {
    margin-bottom: 18px;
}
.social-field label {
    display: block;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
    font-size: 13px;
}
.social-field input[type="text"],
.social-field input[type="password"] {
    width: 100%;
    max-width: 550px;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    color: #1e293b;
    box-sizing: border-box;
}
.social-field .help-text {
    font-size: 12px;
    color: #64748b;
    margin-top: 5px;
    line-height: 1.4;
}
.social-uri-box {
    display: flex;
    align-items: center;
    gap: 8px;
    max-width: 550px;
}
.social-uri-box input {
    flex: 1;
    background: #f8fafc;
    font-family: monospace;
    font-size: 12px !important;
}
.social-copy-btn {
    padding: 9px 14px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    white-space: nowrap;
}
.social-copy-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.badge-status {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 9999px;
    text-transform: uppercase;
}
.badge-active { background: #dcfce7; color: #15803d; }
.badge-pending { background: #fef3c7; color: #b45309; }
.guide-box {
    background: #f8fafc;
    border-left: 4px solid #6366f1;
    padding: 14px 18px;
    border-radius: 0 8px 8px 0;
    margin-top: 14px;
    font-size: 13px;
    color: #334155;
    line-height: 1.5;
}
.guide-box ol {
    margin: 6px 0 0 18px;
    padding: 0;
}
.guide-box li {
    margin-bottom: 4px;
}
.social-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
}
.social-table th, .social-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    font-size: 13px;
}
.social-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #475569;
}
</style>

<div style="padding: 24px; max-width: 900px;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0;">Single Sign-On (SSO) - Google & Facebook</h1>
        <p style="color: #64748b; margin: 0; font-size: 14px;">Gerencie a integração de autenticação e registro automático com um clique via Google e Facebook.</p>
    </div>

    <form action="<?php echo osc_admin_render_plugin_url('olx_social_login/admin.php'); ?>" method="post">
        <input type="hidden" name="plugin_action" value="done" />

        <!-- Google Configuration Card -->
        <div class="social-admin-card">
            <h3>
                <svg viewBox="0 0 24 24" width="22" height="22">
                    <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.99 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                    <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                </svg>
                Google Sign-In
                <?php if ($google_enabled && !empty($google_client_id)) { ?>
                    <span class="badge-status badge-active">Ativo</span>
                <?php } else { ?>
                    <span class="badge-status badge-pending">Pendente</span>
                <?php } ?>
            </h3>

            <div class="social-field">
                <label>
                    <input type="checkbox" name="google_enabled" value="1" <?php echo ($google_enabled ? 'checked="checked"' : ''); ?> />
                    Habilitar botão "Continuar com Google" no Login e Registro
                </label>
            </div>

            <div class="social-field">
                <label for="google_client_id">Google Client ID</label>
                <input type="text" id="google_client_id" name="google_client_id" value="<?php echo osc_esc_html($google_client_id); ?>" placeholder="ex: 123456789-xxxx.apps.googleusercontent.com" />
            </div>

            <div class="social-field">
                <label for="google_client_secret">Google Client Secret</label>
                <input type="password" id="google_client_secret" name="google_client_secret" value="<?php echo osc_esc_html($google_client_secret); ?>" placeholder="ex: GOCSPX-xxxxxxxxxxxx" />
            </div>

            <div class="social-field">
                <label>URI de Redirecionamento Autorizada (OAuth Redirect URI)</label>
                <div class="social-uri-box">
                    <input type="text" id="google_redirect_uri" value="<?php echo osc_esc_html($google_callback_url); ?>" readonly />
                    <button type="button" class="social-copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('google_redirect_uri').value); alert('URI copiada!');">Copiar</button>
                </div>
                <div class="help-text">Cole exatamente esta URL no Google Cloud Console em: <strong>APIs e Serviços &gt; Credenciais &gt; URIs de redirecionamento autorizados</strong>.</div>
            </div>

            <div class="guide-box">
                <strong>Como configurar o Google Cloud Console:</strong>
                <ol>
                    <li>Acesse o <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a>.</li>
                    <li>Crie um projeto ou selecione um existente.</li>
                    <li>Em <strong>Tela de permissão OAuth</strong>, selecione <em>Externo</em>, preencha o nome do app e salve.</li>
                    <li>Em <strong>Credenciais</strong>, clique em <em>Criar Credenciais &gt; ID do cliente OAuth</em>.</li>
                    <li>Selecione <strong>Aplicativo da Web</strong>, cole a URL acima em <em>URIs de redirecionamento autorizados</em> e clique em <em>Criar</em>.</li>
                    <li>Copie o Client ID e Client Secret gerados para os campos acima.</li>
                </ol>
            </div>
        </div>

        <!-- Facebook Configuration Card -->
        <div class="social-admin-card">
            <h3>
                <svg viewBox="0 0 24 24" width="22" height="22" fill="#1877f2">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Facebook Login
                <?php if ($facebook_enabled && !empty($facebook_app_id)) { ?>
                    <span class="badge-status badge-active">Ativo</span>
                <?php } else { ?>
                    <span class="badge-status badge-pending">Pendente</span>
                <?php } ?>
            </h3>

            <div class="social-field">
                <label>
                    <input type="checkbox" name="facebook_enabled" value="1" <?php echo ($facebook_enabled ? 'checked="checked"' : ''); ?> />
                    Habilitar botão "Continuar com Facebook" no Login e Registro
                </label>
            </div>

            <div class="social-field">
                <label for="facebook_app_id">Facebook App ID</label>
                <input type="text" id="facebook_app_id" name="facebook_app_id" value="<?php echo osc_esc_html($facebook_app_id); ?>" placeholder="ex: 123456789012345" />
            </div>

            <div class="social-field">
                <label for="facebook_app_secret">Facebook App Secret</label>
                <input type="password" id="facebook_app_secret" name="facebook_app_secret" value="<?php echo osc_esc_html($facebook_app_secret); ?>" placeholder="ex: 1a2b3c4d5e6f7g8h9i0j..." />
            </div>

            <div class="social-field">
                <label>URI de Redirecionamento OAuth Válida (Facebook Valid OAuth Redirect URI)</label>
                <div class="social-uri-box">
                    <input type="text" id="fb_redirect_uri" value="<?php echo osc_esc_html($facebook_callback_url); ?>" readonly />
                    <button type="button" class="social-copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('fb_redirect_uri').value); alert('URI copiada!');">Copiar</button>
                </div>
                <div class="help-text">Cole exatamente esta URL no painel do Meta for Developers em: <strong>Facebook Login &gt; Configurações &gt; URIs de redirecionamento do OAuth válidos</strong>.</div>
            </div>

            <div class="guide-box">
                <strong>Como configurar o Meta for Developers:</strong>
                <ol>
                    <li>Acesse o <a href="https://developers.facebook.com/" target="_blank" rel="noopener">Meta for Developers</a> e clique em <em>Meus Aplicativos &gt; Criar Aplicativo</em>.</li>
                    <li>Selecione o tipo de caso de uso <strong>Autenticar e solicitar dados de usuários com o Login do Facebook</strong>.</li>
                    <li>No menu lateral, vá em <strong>Casos de uso &gt; Login do Facebook &gt; Configurações</strong>.</li>
                    <li>Cole a URL acima no campo <strong>URIs de redirecionamento do OAuth válidos</strong> e salve.</li>
                    <li>Vá em <strong>Configurações do aplicativo &gt; Básico</strong>, copie o ID do aplicativo e Chave Secreta para os campos acima.</li>
                </ol>
            </div>
        </div>

        <!-- General & Sandbox Settings Card -->
        <div class="social-admin-card">
            <h3>Opções Gerais & Modo Sandbox</h3>

            <div class="social-field">
                <label>
                    <input type="checkbox" name="allow_registration" value="1" <?php echo ($allow_registration ? 'checked="checked"' : ''); ?> />
                    Permitir registro automático de novos usuários ao autenticar via Google/Facebook
                </label>
                <div class="help-text">Quando ativado, caso o usuário não possua conta no site, sua conta será criada automaticamente com o nome e e-mail verificados pelo provedor social.</div>
            </div>

            <div class="social-field">
                <label>
                    <input type="checkbox" name="sandbox_mode" value="1" <?php echo ($sandbox_mode ? 'checked="checked"' : ''); ?> />
                    <strong>Modo de Teste / Sandbox (Simulação Instantânea)</strong>
                </label>
                <div class="help-text">Permite testar o fluxo completo de Single Sign-On (criação de conta, login automático e sessão) mesmo sem credenciais do Google/Facebook cadastradas. Desative em ambiente de produção com chaves ativas.</div>
            </div>
        </div>

        <div style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px; font-weight: 600; background: #6366f1; border-color: #6366f1; color: #fff; border-radius: 6px; cursor: pointer;">
                Salvar Configurações
            </button>
        </div>
    </form>

    <?php if (!empty($socialUsers)) { ?>
        <div class="social-admin-card" style="margin-top: 30px;">
            <h3>Últimos Usuários Conectados via SSO</h3>
            <table class="social-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Provedor</th>
                        <th>ID Social</th>
                        <th>Primeiro Acesso</th>
                        <th>Último Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($socialUsers as $u) { ?>
                        <tr>
                            <td><strong><?php echo osc_esc_html($u['user_name'] ?: 'Usuário #' . $u['fk_i_user_id']); ?></strong></td>
                            <td><?php echo osc_esc_html($u['s_email']); ?></td>
                            <td>
                                <?php if ($u['s_provider'] === 'google') { ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 600; color: #ea4335;">
                                        Google
                                    </span>
                                <?php } else { ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 600; color: #1877f2;">
                                        Facebook
                                    </span>
                                <?php } ?>
                            </td>
                            <td><code><?php echo osc_esc_html($u['s_provider_uid']); ?></code></td>
                            <td><?php echo osc_esc_html($u['dt_created_at']); ?></td>
                            <td><?php echo osc_esc_html($u['dt_last_login']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</div>
