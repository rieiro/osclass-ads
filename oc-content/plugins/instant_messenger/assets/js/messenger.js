/* Instant Messenger Live Engine */
(function() {
    window.InstantMessenger = {
        activeThreadId: 0,
        lastMessageId: 0,
        pollTimer: null,
        pollInterval: 4000,
        ajaxUrl: '',

        init: function(opts) {
            this.activeThreadId = opts.threadId || 0;
            this.lastMessageId = opts.lastMessageId || 0;
            this.pollInterval = opts.pollInterval || 4000;
            this.ajaxUrl = opts.ajaxUrl || '';

            this.bindEvents();
            if (this.activeThreadId > 0) {
                this.scrollToBottom();
                this.startPolling();
            }
        },

        bindEvents: function() {
            var self = this;

            // Textarea Enter key handling
            var textarea = document.getElementById('imMessageInput');
            if (textarea) {
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        self.sendMessage();
                    }
                });
            }

            // Send Button
            var sendBtn = document.getElementById('imSendBtn');
            if (sendBtn) {
                sendBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.sendMessage();
                });
            }

            // Attachment trigger
            var attachBtn = document.getElementById('imAttachBtn');
            var fileInput = document.getElementById('imFileInput');
            var attachBadge = document.getElementById('imAttachBadge');
            if (attachBtn && fileInput) {
                attachBtn.addEventListener('click', function() {
                    fileInput.click();
                });
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        var file = this.files[0];
                        attachBadge.innerHTML = '<i class="fa fa-paperclip"></i> ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB) <span class="im-remove-file" style="cursor:pointer;color:#dc2626;margin-left:4px;">&times;</span>';
                        attachBadge.style.display = 'inline-block';
                        attachBadge.querySelector('.im-remove-file').addEventListener('click', function() {
                            fileInput.value = '';
                            attachBadge.style.display = 'none';
                        });
                    }
                });
            }
        },

        scrollToBottom: function() {
            var box = document.getElementById('imMessagesBox');
            if (box) {
                box.scrollTop = box.scrollHeight;
            }
        },

        startPolling: function() {
            var self = this;
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.pollTimer = setInterval(function() {
                self.fetchNewMessages();
            }, this.pollInterval);
        },

        fetchNewMessages: function() {
            var self = this;
            if (!this.activeThreadId) return;

            var url = this.ajaxUrl + '&action=im_get_messages&thread_id=' + this.activeThreadId + '&after_id=' + this.lastMessageId;
            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'ok' && data.messages && data.messages.length > 0) {
                        var box = document.getElementById('imMessagesBox');
                        var shouldScroll = (box.scrollHeight - box.scrollTop <= box.clientHeight + 100);

                        data.messages.forEach(function(msg) {
                            if (msg.id > self.lastMessageId) {
                                self.lastMessageId = msg.id;
                            }
                            var row = document.createElement('div');
                            row.className = 'im-msg-row ' + (msg.is_me ? 'me' : 'other');

                            var attachHtml = '';
                            if (msg.attachment_url) {
                                if (msg.is_image) {
                                    attachHtml = '<div class="im-attachment-preview"><a href="' + msg.attachment_url + '" target="_blank"><img src="' + msg.attachment_url + '" alt="attachment" /></a></div>';
                                } else {
                                    attachHtml = '<div><a href="' + msg.attachment_url + '" target="_blank" class="im-attachment-doc"><i class="fa fa-file"></i> ' + msg.attachment_name + '</a></div>';
                                }
                            }

                            row.innerHTML = '<div class="im-msg-bubble">' + 
                                (msg.message ? '<div>' + msg.message + '</div>' : '') + 
                                attachHtml + 
                            '</div>' + 
                            '<div class="im-msg-meta">' + 
                                '<span>' + msg.time + '</span>' + 
                                (msg.is_me ? (msg.is_read ? '<span class="im-seen-check"><i class="fa fa-check-double"></i> ' + (window.imSeenText || 'Seen') + '</span>' : '<span class="im-seen-check"><i class="fa fa-check"></i></span>') : '') + 
                            '</div>';

                            box.appendChild(row);
                        });

                        if (shouldScroll) {
                            self.scrollToBottom();
                        }
                    }
                })
                .catch(function(err) {
                    console.error('IM fetch error:', err);
                });
        },

        sendMessage: function() {
            var self = this;
            var textarea = document.getElementById('imMessageInput');
            var fileInput = document.getElementById('imFileInput');
            var attachBadge = document.getElementById('imAttachBadge');
            var sendBtn = document.getElementById('imSendBtn');

            var text = textarea ? textarea.value.trim() : '';
            var hasFile = (fileInput && fileInput.files && fileInput.files[0]);

            if (!text && !hasFile) return;

            var formData = new FormData();
            formData.append('action', 'im_send_message');
            formData.append('thread_id', this.activeThreadId);
            formData.append('message', text);
            if (hasFile) {
                formData.append('attachment', fileInput.files[0]);
            }

            if (sendBtn) sendBtn.disabled = true;

            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (sendBtn) sendBtn.disabled = false;
                if (data.status === 'ok') {
                    if (textarea) textarea.value = '';
                    if (fileInput) fileInput.value = '';
                    if (attachBadge) attachBadge.style.display = 'none';
                    self.fetchNewMessages();
                } else {
                    alert(data.error || 'Failed to send message.');
                }
            })
            .catch(function(err) {
                if (sendBtn) sendBtn.disabled = false;
                alert('Network error while sending message.');
            });
        }
    };
})();
