<?php
/*
Plugin Name: OLX Self-Service Monetization & Payments
Plugin URI: https://github.com/osclass/
Description: Automated payment checkout for Bumps and Featured Highlights with instant activation, PIX QR Copia e Cola generation, and HMAC-verified webhook listener.
Version: 1.3.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_payments
Plugin update URI:
*/

function olx_payments_install() {
    $conn = DBConnectionClass::newInstance();
    $data = $conn->getOsclassDb();
    $comm = new DBCommandClass($data);

    $comm->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_olx_orders (
        pk_i_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        fk_i_user_id INT UNSIGNED NOT NULL,
        fk_i_item_id INT UNSIGNED NOT NULL,
        s_package_type VARCHAR(50) NOT NULL,
        f_amount DECIMAL(10,2) NOT NULL,
        s_currency VARCHAR(3) NOT NULL DEFAULT 'BRL',
        s_status VARCHAR(20) NOT NULL DEFAULT 'completed',
        s_gateway VARCHAR(50) NOT NULL DEFAULT 'mock_instant',
        s_payment_method VARCHAR(20) NOT NULL DEFAULT 'instant',
        s_txid VARCHAR(100) NULL,
        s_pix_copy_paste TEXT NULL,
        s_pix_qr_url VARCHAR(255) NULL,
        dt_expires_at DATETIME NULL,
        dt_paid_at DATETIME NULL,
        dt_created_at DATETIME NOT NULL,
        PRIMARY KEY (pk_i_id),
        KEY fk_user (fk_i_user_id),
        KEY fk_item (fk_i_item_id),
        KEY idx_txid (s_txid)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    osc_set_preference('price_bump', '5.00', 'olx_payments', 'STRING');
    osc_set_preference('price_highlight', '15.00', 'olx_payments', 'STRING');
    osc_set_preference('currency', 'BRL', 'olx_payments', 'STRING');
    osc_set_preference('pix_key', 'financeiro@marketplace.local', 'olx_payments', 'STRING');
    osc_set_preference('merchant_name', 'OSCLASS CLASSIFIEDS', 'olx_payments', 'STRING');
    osc_set_preference('merchant_city', 'SAO PAULO', 'olx_payments', 'STRING');
    osc_set_preference('webhook_secret', 'whsec_' . bin2hex(random_bytes(16)), 'olx_payments', 'STRING');
    osc_set_preference('sandbox_mode', '1', 'olx_payments', 'STRING');
}

function olx_payments_uninstall() {
    $conn = DBConnectionClass::newInstance();
    $data = $conn->getOsclassDb();
    $comm = new DBCommandClass($data);
    $comm->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_olx_orders");
    Preference::newInstance()->delete(array('s_section' => 'olx_payments'));
}

function olx_payments_get_secret() {
    $secret = osc_get_preference('webhook_secret', 'olx_payments');
    if (empty($secret)) {
        $secret = 'whsec_olx_' . hash('sha256', osc_base_url() . 'olx_secret_key_salt_2026');
        osc_set_preference('webhook_secret', $secret, 'olx_payments', 'STRING');
    }
    return $secret;
}

// Generates Brazilian Central Bank EMV BR Code (PIX Copia e Cola)
function olx_generate_pix_brcode($key, $name, $city, $amount, $txid) {
    $formatField = function($id, $val) {
        return sprintf("%02d%02d%s", $id, strlen($val), $val);
    };

    $name = strtoupper(substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $name), 0, 25));
    $city = strtoupper(substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $city), 0, 15));
    $amountStr = number_format($amount, 2, '.', '');
    $txid = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $txid), 0, 25)) ?: '***';

    $accountInfo = $formatField(0, 'BR.GOV.BCB.PIX') . $formatField(1, $key);

    $payload = $formatField(0, '01')                  // Payload Format Indicator
             . $formatField(1, '12')                  // Dynamic Point of Initiation
             . $formatField(26, $accountInfo)         // Merchant Account Information (Pix)
             . $formatField(52, '0000')               // Merchant Category Code
             . $formatField(53, '986')                // Transaction Currency (BRL)
             . $formatField(54, $amountStr)           // Transaction Amount
             . $formatField(58, 'BR')                 // Country Code
             . $formatField(59, $name)                // Merchant Name
             . $formatField(60, $city)                // Merchant City
             . $formatField(62, $formatField(5, $txid)) // Additional Data Field (TxID)
             . '6304';                                // CRC16 Header

    // CRC16-CCITT calculation (poly 0x1021, init 0xFFFF)
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $crc ^= (ord($payload[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    return $payload . strtoupper(sprintf('%04X', $crc));
}

function olx_payments_header() {
    if (osc_get_osclass_location() == 'user' && osc_get_osclass_section() == 'items') {
        echo '<style>
            .olx-pay-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); align-items: center; justify-content: center; }
            .olx-pay-card { background: #fff; width: 92%; max-width: 460px; border-radius: 12px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.25); max-height: 90vh; overflow-y: auto; }
            .olx-pay-option { border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.2s; }
            .olx-pay-option.selected { border-color: #2563eb; background: #eff6ff; }
            .olx-pay-submit { width: 100%; padding: 12px; background: #16a34a; color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 15px; cursor: pointer; margin-top: 10px; }
            .olx-pay-submit:hover { background: #15803d; }
            .olx-btn-promote { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #6b21a8; color: #fff !important; font-size: 12px; font-weight: 600; border-radius: 4px; text-decoration: none !important; cursor: pointer; margin-left: 6px; }
            .olx-pix-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; text-align: center; margin-top: 15px; }
            .olx-pix-qr { width: 180px; height: 180px; margin: 0 auto 10px; display: block; border-radius: 6px; border: 1px solid #e2e8f0; }
            .olx-pix-code { font-family: monospace; font-size: 11px; word-break: break-all; background: #fff; padding: 8px; border: 1px dashed #94a3b8; border-radius: 4px; max-height: 60px; overflow-y: auto; text-align: left; margin-bottom: 8px; }
            .olx-btn-copy { background: #2563eb; color: #fff; border: none; padding: 7px 14px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; }
            .olx-btn-copy:hover { background: #1d4ed8; }
        </style>';
    }
}

// Promotes CTA button on user items list
function olx_payments_user_items($item_id = null) {
    if (!$item_id) $item_id = osc_item_id();
    if ($item_id <= 0) return;
    $bump_price = osc_get_preference('price_bump', 'olx_payments') ?: '5.00';
    $highlight_price = osc_get_preference('price_highlight', 'olx_payments') ?: '15.00';
    $curr = osc_get_preference('currency', 'olx_payments') ?: 'BRL';

    echo '<a href="javascript:void(0);" class="olx-btn-promote" onclick="olxOpenCheckout(' . $item_id . ', \'' . $bump_price . '\', \'' . $highlight_price . '\', \'' . $curr . '\')">';
    echo '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Impulsionar (Pay)';
    echo '</a>';
}

function olx_payments_footer() {
    if (osc_get_osclass_location() == 'user' && osc_get_osclass_section() == 'items') {
        $sandbox_active = (osc_get_preference('sandbox_mode', 'olx_payments') === '1');
        $webhook_secret = olx_payments_get_secret();
        ?>
        <div id="olx-checkout-modal" class="olx-pay-modal">
            <div class="olx-pay-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #1e293b;">Turbinar Anúncio (Promote)</h3>
                    <span style="cursor: pointer; font-size: 20px; font-weight: bold; color: #64748b;" onclick="olxCloseCheckout()">&times;</span>
                </div>

                <div id="olx-checkout-step-choose">
                    <form id="olx-pay-form" method="post" action="<?php echo osc_base_url(true); ?>">
                        <?php echo osc_csrf_token_form(); ?>
                        <input type="hidden" name="page" value="custom" />
                        <input type="hidden" name="action" value="olx_do_payment" />
                        <input type="hidden" name="item_id" id="olx-pay-item-id" value="" />
                        <input type="hidden" name="plan" id="olx-pay-plan" value="bump" />
                        <input type="hidden" name="method" id="olx-pay-method" value="pix" />

                        <label style="font-size: 13px; font-weight: 700; color: #475569; display: block; margin-bottom: 8px;">1. Escolha o Pacote:</label>
                        <div class="olx-pay-option selected" id="opt-bump" onclick="olxSelectPlan('bump')">
                            <div>
                                <div style="font-weight: 700; color: #0f172a;">Subida Instantânea (Bump)</div>
                                <small style="color: #64748b;">Retorna o anúncio ao topo da página 1</small>
                            </div>
                            <div style="font-weight: 700; color: #2563eb;" id="lbl-bump-price">R$ 5,00</div>
                        </div>

                        <div class="olx-pay-option" id="opt-highlight" onclick="olxSelectPlan('highlight')">
                            <div>
                                <div style="font-weight: 700; color: #0f172a;">Destaque Ouro (7 Dias)</div>
                                <small style="color: #64748b;">Borda âmbar, badge Featured e topo de busca</small>
                            </div>
                            <div style="font-weight: 700; color: #2563eb;" id="lbl-highlight-price">R$ 15,00</div>
                        </div>

                        <?php if ($sandbox_active): ?>
                            <label style="font-size: 13px; font-weight: 700; color: #475569; display: block; margin: 15px 0 8px 0;">2. Forma de Pagamento (Ambiente de Teste Ativo):</label>
                            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <label style="flex: 1; border: 1.5px solid #22c55e; background: #f0fdf4; border-radius: 6px; padding: 10px; text-align: center; cursor: pointer; font-size: 13px; font-weight: 700; color: #166534;">
                                    <input type="radio" name="pay_mode" value="pix" checked onchange="document.getElementById('olx-pay-method').value = 'pix';" /> PIX Instantâneo
                                </label>
                                <label style="flex: 1; border: 1.5px solid #94a3b8; background: #f8fafc; border-radius: 6px; padding: 10px; text-align: center; cursor: pointer; font-size: 13px; font-weight: 700; color: #334155;">
                                    <input type="radio" name="pay_mode" value="instant" onchange="document.getElementById('olx-pay-method').value = 'instant';" /> Teste Rápido (Sandbox)
                                </label>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="pay_mode" value="pix" />
                        <?php endif; ?>

                        <button type="submit" class="olx-pay-submit">Gerar Pagamento &rarr;</button>
                    </form>
                </div>

                <?php 
                $pix_data = Session::newInstance()->_get('olx_active_pix');
                if (!empty($pix_data)): 
                    $sim_sig = hash_hmac('sha256', (string)$pix_data['order_id'], $webhook_secret);
                ?>
                <div class="olx-pix-box" id="olx-pix-active-card">
                    <h4 style="margin: 0 0 6px 0; color: #15803d; font-size: 16px;">Pagamento PIX Gerado!</h4>
                    <p style="font-size: 12px; color: #64748b; margin: 0 0 10px 0;">
                        Escaneie o QR Code abaixo ou copie o código PIX. O anúncio será ativado automaticamente após o pagamento.
                    </p>
                    <img src="<?php echo osc_esc_html($pix_data['qr_url']); ?>" alt="PIX QR Code" class="olx-pix-qr" />
                    
                    <div class="olx-pix-code" id="olx-pix-copy-text"><?php echo osc_esc_html($pix_data['payload']); ?></div>
                    
                    <div style="display: flex; gap: 8px; justify-content: center; margin-bottom: 10px;">
                        <button type="button" class="olx-btn-copy" onclick="olxCopyPix()">📋 Copiar Código PIX</button>
                        <?php if ($sandbox_active): ?>
                            <form method="post" action="<?php echo osc_base_url(true); ?>?page=ajax&action=olx_payment_webhook" target="_blank" style="margin: 0;">
                                <input type="hidden" name="order_id" value="<?php echo (int)$pix_data['order_id']; ?>" />
                                <input type="hidden" name="signature" value="<?php echo osc_esc_html($sim_sig); ?>" />
                                <input type="hidden" name="simulated" value="1" />
                                <button type="submit" style="background: #16a34a; color: #fff; padding: 7px 12px; border: none; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer;">
                                    ✔ Simular Webhook (Assinado)
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <small style="color: #94a3b8; font-size: 11px;">Válido por 30 minutos &bull; Pedido #<?php echo $pix_data['order_id']; ?></small>
                </div>
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    document.getElementById('olx-checkout-modal').style.display = 'flex';
                });
                </script>
                <?php Session::newInstance()->_drop('olx_active_pix'); endif; ?>
            </div>
        </div>

        <script>
        function olxOpenCheckout(itemId, pBump, pHighlight, curr) {
            document.getElementById('olx-pay-item-id').value = itemId;
            document.getElementById('lbl-bump-price').textContent = (curr === 'BRL' ? 'R$ ' : '$ ') + pBump;
            document.getElementById('lbl-highlight-price').textContent = (curr === 'BRL' ? 'R$ ' : '$ ') + pHighlight;
            document.getElementById('olx-checkout-modal').style.display = 'flex';
        }
        function olxCloseCheckout() {
            document.getElementById('olx-checkout-modal').style.display = 'none';
        }
        function olxSelectPlan(plan) {
            document.getElementById('olx-pay-plan').value = plan;
            if (plan === 'bump') {
                document.getElementById('opt-bump').classList.add('selected');
                document.getElementById('opt-highlight').classList.remove('selected');
            } else {
                document.getElementById('opt-highlight').classList.add('selected');
                document.getElementById('opt-bump').classList.remove('selected');
            }
        }
        function olxCopyPix() {
            var text = document.getElementById('olx-pix-copy-text').textContent;
            navigator.clipboard.writeText(text).then(function() {
                alert('Código PIX Copiado com Sucesso!');
            }).catch(function() {
                alert('Copie o código manualmente do campo.');
            });
        }
        </script>
        <?php
    }
}

// Payment initiation & controller
function olx_payments_controller() {
    if (Params::getParam('action') !== 'olx_do_payment') return;
    if (!osc_is_web_user_logged_in()) {
        header('Location: ' . osc_user_login_url());
        exit;
    }

    if (!osc_csrf_check()) {
        osc_add_flash_error_message(__('Security token validation failed. Please try again.', 'olx_payments'));
        header('Location: ' . osc_user_list_items_url());
        exit;
    }

    $item_id = (int)Params::getParam('item_id');
    $plan    = Params::getParam('plan');
    $method  = Params::getParam('method') ?: 'pix';
    $user_id = osc_logged_user_id();

    // Whitelist plan packages strictly
    if (!in_array($plan, array('bump', 'highlight'), true)) {
        osc_add_flash_error_message(__('Invalid promotion package selected.', 'olx_payments'));
        header('Location: ' . osc_user_list_items_url());
        exit;
    }

    // Ownership check (BOLA / IDOR defense)
    $item = Item::newInstance()->findByPrimaryKey($item_id);
    if (!$item || (int)$item['fk_i_user_id'] !== $user_id) {
        osc_add_flash_error_message(__('Invalid listing.', 'olx_payments'));
        header('Location: ' . osc_user_list_items_url());
        exit;
    }

    $bump_price      = (float)(osc_get_preference('price_bump', 'olx_payments') ?: 5.00);
    $highlight_price = (float)(osc_get_preference('price_highlight', 'olx_payments') ?: 15.00);
    $curr            = osc_get_preference('currency', 'olx_payments') ?: 'BRL';
    $amount          = ($plan === 'highlight') ? $highlight_price : $bump_price;
    $now             = date('Y-m-d H:i:s');
    $txid            = 'OLX' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 15));

    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    if ($method === 'pix') {
        $pix_key  = osc_get_preference('pix_key', 'olx_payments') ?: 'financeiro@marketplace.local';
        $m_name   = osc_get_preference('merchant_name', 'olx_payments') ?: 'OSCLASS';
        $m_city   = osc_get_preference('merchant_city', 'olx_payments') ?: 'SAO PAULO';
        $expires  = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        $payload  = olx_generate_pix_brcode($pix_key, $m_name, $m_city, $amount, $txid);
        $qr_url   = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=5&data=' . urlencode($payload);

        $comm->insert(DB_TABLE_PREFIX . 't_olx_orders', array(
            'fk_i_user_id'     => $user_id,
            'fk_i_item_id'     => $item_id,
            's_package_type'   => $plan,
            'f_amount'         => $amount,
            's_currency'       => $curr,
            's_status'         => 'pending',
            's_gateway'        => 'pix_manual',
            's_payment_method' => 'pix',
            's_txid'           => $txid,
            's_pix_copy_paste' => $payload,
            's_pix_qr_url'     => $qr_url,
            'dt_expires_at'    => $expires,
            'dt_created_at'    => $now
        ));
        $order_id = $comm->insertedId();

        // Pass active pix payload to session to display modal immediately
        Session::newInstance()->_set('olx_active_pix', array(
            'order_id' => $order_id,
            'txid'     => $txid,
            'payload'  => $payload,
            'qr_url'   => $qr_url,
            'amount'   => $amount,
            'curr'     => $curr
        ));

        header('Location: ' . osc_user_list_items_url());
        exit;
    }

    // Instant mock execution fallback - ONLY permitted if sandbox_mode is explicitly enabled
    $sandbox_enabled = (osc_get_preference('sandbox_mode', 'olx_payments') === '1');
    if (!$sandbox_enabled) {
        osc_add_flash_error_message(__('Mock checkout is disabled in production mode. Please use PIX.', 'olx_payments'));
        header('Location: ' . osc_user_list_items_url());
        exit;
    }

    $comm->insert(DB_TABLE_PREFIX . 't_olx_orders', array(
        'fk_i_user_id'     => $user_id,
        'fk_i_item_id'     => $item_id,
        's_package_type'   => $plan,
        'f_amount'         => $amount,
        's_currency'       => $curr,
        's_status'         => 'completed',
        's_gateway'        => 'mock_instant',
        's_payment_method' => 'instant',
        's_txid'           => $txid,
        'dt_paid_at'       => $now,
        'dt_created_at'    => $now
    ));

    // Activate promotion immediately
    olx_payments_activate_promotion($item_id, $plan, $now);
    osc_add_flash_ok_message(__('Sandbox payment approved! Promotion activated.', 'olx_payments'));
    header('Location: ' . osc_user_list_items_url());
    exit;
}

// Executes ad bump or highlight strictly
function olx_payments_activate_promotion($item_id, $plan, $now = null) {
    if (!$now) $now = date('Y-m-d H:i:s');
    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    if ($plan === 'bump') {
        Item::newInstance()->update(array('dt_pub_date' => $now, 'dt_mod_date' => $now), array('pk_i_id' => $item_id));
        $comm->query("INSERT INTO " . DB_TABLE_PREFIX . "t_olx_promotions (fk_i_item_id, i_bumps_count, dt_last_bump)
            VALUES ({$item_id}, 1, '{$now}')
            ON DUPLICATE KEY UPDATE i_bumps_count = i_bumps_count + 1, dt_last_bump = '{$now}'");
    } elseif ($plan === 'highlight') {
        $until = date('Y-m-d H:i:s', strtotime('+7 days', strtotime($now)));
        $comm->query("INSERT INTO " . DB_TABLE_PREFIX . "t_olx_promotions (fk_i_item_id, b_highlighted, dt_highlight_until)
            VALUES ({$item_id}, 1, '{$until}')
            ON DUPLICATE KEY UPDATE b_highlighted = 1, dt_highlight_until = '{$until}'");
        Item::newInstance()->update(array('b_premium' => 1), array('pk_i_id' => $item_id));
    }
}

// Webhook listener endpoint with HMAC signature verification & idempotency guard
function olx_payments_webhook() {
    $action = Params::getParam('action');
    if ($action !== 'olx_payment_webhook') {
        return;
    }

    // Require POST method for all financial status mutations
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'Method Not Allowed. Webhook notifications must use POST.'));
        exit;
    }

    $secret    = olx_payments_get_secret();
    $order_id  = (int)Params::getParam('order_id');
    $txid      = Params::getParam('txid');
    $simulated = (int)Params::getParam('simulated');
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? Params::getParam('signature');

    // Cryptographic HMAC Verification
    $raw_input = file_get_contents('php://input');
    // Verify against order_id/txid if form-encoded, or raw body if JSON payload
    $payload_to_verify = ($order_id > 0) ? (string)$order_id : (!empty($txid) ? (string)$txid : $raw_input);
    $expected_sig = hash_hmac('sha256', $payload_to_verify, $secret);

    if (empty($signature) || !hash_equals($expected_sig, (string)$signature)) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'Forbidden: Invalid or missing HMAC signature'));
        exit;
    }

    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    $sql = "SELECT * FROM " . DB_TABLE_PREFIX . "t_olx_orders WHERE ";
    if ($order_id > 0) {
        $sql .= "pk_i_id = " . $order_id;
    } elseif (!empty($txid)) {
        $sql .= "s_txid = '" . $comm->escapeStr($txid) . "'";
    } else {
        header('Content-Type: application/json', true, 400);
        echo json_encode(array('success' => false, 'error' => 'Missing order reference identifier'));
        exit;
    }

    $res = $comm->query($sql);
    if (!$res || $res->numRows() == 0) {
        header('Content-Type: application/json', true, 404);
        echo json_encode(array('success' => false, 'error' => 'Order not found'));
        exit;
    }

    $order = $res->row();
    $now = date('Y-m-d H:i:s');

    // Idempotency check: if order is already completed, return OK without duplicating promotions
    if ($order['s_status'] === 'completed') {
        if ($simulated) {
            echo "<html><body style='font-family: sans-serif; text-align: center; padding: 40px;'>
                  <h2 style='color: #2563eb;'>Order #{$order['pk_i_id']} is already completed!</h2>
                  <p><a href='" . osc_user_list_items_url() . "'>Return to My Listings</a></p></body></html>";
            exit;
        }
        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'status' => 'already_completed', 'order_id' => (int)$order['pk_i_id']));
        exit;
    }

    // Check expiration (30 min window for PIX)
    if (!empty($order['dt_expires_at']) && strtotime($now) > strtotime($order['dt_expires_at'])) {
        $comm->update(
            DB_TABLE_PREFIX . 't_olx_orders',
            array('s_status' => 'expired'),
            array('pk_i_id' => $order['pk_i_id'])
        );
        header('Content-Type: application/json', true, 410);
        echo json_encode(array('success' => false, 'error' => 'Payment expired'));
        exit;
    }

    // Mark order completed with payment timestamp
    $comm->update(
        DB_TABLE_PREFIX . 't_olx_orders',
        array('s_status' => 'completed', 'dt_paid_at' => $now),
        array('pk_i_id' => $order['pk_i_id'])
    );

    // Trigger promotion execution
    olx_payments_activate_promotion((int)$order['fk_i_item_id'], $order['s_package_type'], $now);

    if ($simulated) {
        echo "<html><body style='font-family: sans-serif; text-align: center; padding: 40px;'>
              <h2 style='color: #16a34a;'>✔ Webhook Successfully Processed & HMAC Verified!</h2>
              <p>Order #{$order['pk_i_id']} ({$order['s_package_type']}) was approved and activated.</p>
              <p><a href='" . osc_user_list_items_url() . "' style='display: inline-block; padding: 10px 20px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;'>Return to My Listings &rarr;</a></p></body></html>";
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(array(
        'success'  => true,
        'status'   => 'approved',
        'order_id' => (int)$order['pk_i_id'],
        'item_id'  => (int)$order['fk_i_item_id']
    ));
    exit;
}

// Hook registrations
osc_register_plugin(osc_plugin_path(__FILE__), 'olx_payments_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'olx_payments_uninstall');
osc_add_hook('header', 'olx_payments_header');
osc_add_hook('user_items_body', 'olx_payments_user_items');
osc_add_hook('footer', 'olx_payments_footer');
osc_add_hook('init', 'olx_payments_controller');
osc_add_hook('init_ajax', 'olx_payments_webhook');
osc_add_hook('init', 'olx_payments_webhook');
