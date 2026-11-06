<?php
if (!osc_is_web_user_logged_in()) {
    osc_add_flash_error_message(__('You must be logged in to view your chat messages.', 'olx_chat'));
    header('Location: ' . osc_user_login_url());
    exit;
}

$current_user_id = osc_logged_user_id();
$dao = new DAO();
$dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
$dao->dao = new DBCommandClass($dao->udb);

$req_item_id = (int)Params::getParam('item_id');
$req_seller_id = (int)Params::getParam('seller_id');
$active_thread_id = (int)Params::getParam('thread_id');

// If directed from listing page, get or create thread
if ($req_item_id > 0 && $req_seller_id > 0 && $req_seller_id != $current_user_id) {
    $existing = $dao->dao->query("SELECT pk_i_id FROM " . DB_TABLE_PREFIX . "t_olx_chat_thread WHERE fk_i_item_id = {$req_item_id} AND fk_i_buyer_id = {$current_user_id} AND fk_i_seller_id = {$req_seller_id}");
    if ($existing && $existing->numRows() > 0) {
        $row = $existing->row();
        $active_thread_id = (int)$row['pk_i_id'];
    } else {
        $now = date('Y-m-d H:i:s');
        $dao->dao->insert(DB_TABLE_PREFIX . 't_olx_chat_thread', array(
            'fk_i_item_id' => $req_item_id,
            'fk_i_buyer_id' => $current_user_id,
            'fk_i_seller_id' => $req_seller_id,
            'dt_created_at' => $now,
            'dt_updated_at' => $now
        ));
        $active_thread_id = (int)$dao->dao->insertedId();
    }
}

// Fetch all threads for current user
$threads_sql = "SELECT t.*, id.s_title, u_buyer.s_name as buyer_name, u_seller.s_name as seller_name,
    (SELECT s_message FROM " . DB_TABLE_PREFIX . "t_olx_chat_message WHERE fk_i_thread_id = t.pk_i_id ORDER BY pk_i_id DESC LIMIT 1) as last_msg,
    (SELECT COUNT(*) FROM " . DB_TABLE_PREFIX . "t_olx_chat_message WHERE fk_i_thread_id = t.pk_i_id AND fk_i_sender_id != {$current_user_id} AND b_read = 0) as unread_count
    FROM " . DB_TABLE_PREFIX . "t_olx_chat_thread t
    LEFT JOIN " . DB_TABLE_PREFIX . "t_item_description id ON t.fk_i_item_id = id.fk_i_item_id
    LEFT JOIN " . DB_TABLE_PREFIX . "t_user u_buyer ON t.fk_i_buyer_id = u_buyer.pk_i_id
    LEFT JOIN " . DB_TABLE_PREFIX . "t_user u_seller ON t.fk_i_seller_id = u_seller.pk_i_id
    WHERE t.fk_i_buyer_id = {$current_user_id} OR t.fk_i_seller_id = {$current_user_id}
    ORDER BY t.dt_updated_at DESC";

$threads_res = $dao->dao->query($threads_sql);
$threads = ($threads_res && $threads_res->numRows() > 0) ? $threads_res->result() : array();

if ($active_thread_id <= 0 && count($threads) > 0) {
    $active_thread_id = (int)$threads[0]['pk_i_id'];
}

// Active thread details
$active_thread = null;
foreach ($threads as $t) {
    if ((int)$t['pk_i_id'] === $active_thread_id) {
        $active_thread = $t;
        break;
    }
}
?>

<div class="wrapper" style="margin-top: 20px;">
    <!-- Web Push Notification Banner -->
    <div id="olx-push-banner" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 8px; padding: 12px 18px; margin-bottom: 15px; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 20px;">🔔</span>
            <div style="font-size: 13px; color: #166534;">
                <strong>Receba alertas em tempo real:</strong> Ative as notificações no navegador para não perder respostas de compradores e vendedores.
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" id="olx-push-enable-btn" style="background: #16a34a; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer;">
                Ativar Notificações
            </button>
            <button type="button" onclick="document.getElementById('olx-push-banner').style.display='none';" style="background: transparent; border: none; color: #64748b; font-size: 16px; cursor: pointer; padding: 0 6px;">&times;</button>
        </div>
    </div>

    <div style="display: flex; gap: 20px; min-height: 550px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
        <!-- Sidebar Conversation List -->
        <div style="width: 320px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #f8fafc;">
            <div style="padding: 16px; border-bottom: 1px solid #e2e8f0; background: #6b21a8; color: #fff;">
                <h3 style="margin: 0; font-size: 18px; color: #fff; display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Messages
                </h3>
            </div>
            <div style="flex: 1; overflow-y: auto;">
                <?php if (empty($threads)): ?>
                    <p style="padding: 20px; color: #64748b; text-align: center;">No active chats yet.</p>
                <?php else: ?>
                    <?php foreach ($threads as $th): 
                        $is_active = ((int)$th['pk_i_id'] === $active_thread_id);
                        $partner_name = ((int)$th['fk_i_buyer_id'] === $current_user_id) ? $th['seller_name'] : $th['buyer_name'];
                    ?>
                        <a href="<?php echo osc_render_file_url('olx_chat/user_chat.php') . '&thread_id=' . $th['pk_i_id']; ?>" style="display: block; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; background: <?php echo $is_active ? '#ede9fe' : 'transparent'; ?>; transition: background 0.15s;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="font-weight: 600; font-size: 14px; color: #1e293b;"><?php echo osc_esc_html($partner_name ?: 'User'); ?></span>
                                <?php if (!empty($th['unread_count'])): ?>
                                    <span style="background: #ef4444; color: #fff; font-size: 11px; font-weight: bold; padding: 1px 6px; border-radius: 10px;"><?php echo $th['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 13px; font-weight: 500; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px;">
                                <?php echo osc_esc_html($th['s_title'] ?: 'Ad listing'); ?>
                            </div>
                            <div style="font-size: 12px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo osc_esc_html($th['last_msg'] ?: 'No messages yet'); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Conversation Window -->
        <div style="flex: 1; display: flex; flex-direction: column;">
            <?php if ($active_thread): 
                $partner_name = ((int)$active_thread['fk_i_buyer_id'] === $current_user_id) ? $active_thread['seller_name'] : $active_thread['buyer_name'];
            ?>
                <!-- Header -->
                <div style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #fff;">
                    <div>
                        <div style="font-size: 16px; font-weight: 700; color: #1e293b;"><?php echo osc_esc_html($partner_name ?: 'User'); ?></div>
                        <a href="<?php echo osc_item_url_ns($active_thread['fk_i_item_id']); ?>" target="_blank" style="font-size: 13px; color: #2563eb; text-decoration: none;">
                            Item: <?php echo osc_esc_html($active_thread['s_title'] ?: 'Listing'); ?> &rarr;
                        </a>
                    </div>
                </div>

                <!-- Messages Area -->
                <div id="olx-chat-box" style="flex: 1; padding: 20px; overflow-y: auto; background: #fdfdfd; display: flex; flex-direction: column; gap: 12px;"></div>

                <!-- Input Box -->
                <form id="olx-chat-form" style="padding: 15px 20px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; background: #fff;">
                    <input type="text" id="olx-chat-input" placeholder="Type a message (e.g. Is this still available?)" style="flex: 1; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;" autocomplete="off" required />
                    <button type="submit" id="olx-chat-submit" style="background: #6b21a8; color: #fff; border: none; padding: 0 24px; border-radius: 6px; font-weight: 600; cursor: pointer;">Send</button>
                </form>

                <script>
                (function() {
                    var threadId = <?php echo $active_thread_id; ?>;
                    var lastMessageId = 0;
                    var chatBox = document.getElementById('olx-chat-box');
                    var chatForm = document.getElementById('olx-chat-form');
                    var chatInput = document.getElementById('olx-chat-input');
                    var ajaxUrl = "<?php echo osc_base_url(true); ?>?page=ajax";

                    function appendMessage(msg) {
                        var div = document.createElement('div');
                        div.style.maxWidth = '70%';
                        div.style.padding = '10px 14px';
                        div.style.borderRadius = '12px';
                        div.style.fontSize = '14px';
                        div.style.lineHeight = '1.4';

                        if (msg.is_me) {
                            div.style.alignSelf = 'flex-end';
                            div.style.background = '#6b21a8';
                            div.style.color = '#ffffff';
                            div.style.borderBottomRightRadius = '2px';
                        } else {
                            div.style.alignSelf = 'flex-start';
                            div.style.background = '#f1f5f9';
                            div.style.color = '#1e293b';
                            div.style.borderBottomLeftRadius = '2px';
                        }

                        div.innerHTML = '<div>' + msg.message + '</div><div style="font-size: 11px; opacity: 0.75; text-align: right; margin-top: 4px;">' + msg.time + '</div>';
                        chatBox.appendChild(div);
                        chatBox.scrollTop = chatBox.scrollHeight;
                        if (msg.id > lastMessageId) {
                            lastMessageId = msg.id;
                        }
                    }

                    function fetchMessages() {
                        fetch(ajaxUrl + "&olx_chat_action=get_messages&thread_id=" + threadId + "&after_id=" + lastMessageId)
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success && data.messages) {
                                data.messages.forEach(appendMessage);
                            }
                        });
                    }

                    chatForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        var text = chatInput.value.trim();
                        if (!text) return;
                        chatInput.value = '';

                        var fd = new FormData();
                        fd.append('olx_chat_action', 'send_message');
                        fd.append('thread_id', threadId);
                        fd.append('message', text);

                        fetch(ajaxUrl, { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(d) {
                            if (d.success) {
                                fetchMessages();
                            }
                        });
                    });

                    fetchMessages();
                    setInterval(fetchMessages, 3000);

                    // Service Worker & Web Push Registration
                    if ('serviceWorker' in navigator && 'PushManager' in window) {
                        navigator.serviceWorker.register('/sw.js').then(function(reg) {
                            if (Notification.permission === 'default') {
                                document.getElementById('olx-push-banner').style.display = 'flex';
                            }
                            
                            document.getElementById('olx-push-enable-btn').addEventListener('click', function() {
                                Notification.requestPermission().then(function(perm) {
                                    if (perm === 'granted') {
                                        document.getElementById('olx-push-banner').style.display = 'none';
                                        // Save subscription endpoint
                                        reg.pushManager.subscribe({
                                            userVisibleOnly: true,
                                            applicationServerKey: null
                                        }).then(function(sub) {
                                            var key = sub.getKey ? sub.getKey('p256dh') : null;
                                            var auth = sub.getKey ? sub.getKey('auth') : null;
                                            var p256dh = key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : 'mock_p256dh';
                                            var authStr = auth ? btoa(String.fromCharCode.apply(null, new Uint8Array(auth))) : 'mock_auth';
                                            
                                            var sfd = new FormData();
                                            sfd.append('olx_chat_action', 'save_push_subscription');
                                            sfd.append('endpoint', sub.endpoint);
                                            sfd.append('p256dh', p256dh);
                                            sfd.append('auth', authStr);
                                            fetch(ajaxUrl, { method: 'POST', body: sfd });
                                        }).catch(function(err) {
                                            // Fallback local notification
                                            new Notification("Notificações Ativadas!", {
                                                body: "Você receberá alertas quando novas mensagens chegarem.",
                                                icon: "/favicon.ico"
                                            });
                                        });
                                    }
                                });
                            }
                        );
                    }
                })();
                </script>
            <?php else: ?>
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 15px;">
                    Select or start a chat from the left panel.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
