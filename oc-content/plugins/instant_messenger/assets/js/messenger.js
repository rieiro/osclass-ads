/* Instant Messenger Live Engine */
(function() {
    window.InstantMessenger = {
        activeThreadId: 0,
        lastMessageId: 0,
        pollTimer: null,
        pollInterval: 4000,
        ajaxUrl: "",
        eventsBound: false,
        isSending: false,
        isFetching: false,

        init: function(opts) {
            this.activeThreadId = opts.threadId || 0;
            this.lastMessageId = opts.lastMessageId || 0;
            this.pollInterval = opts.pollInterval || 4000;
            this.ajaxUrl = opts.ajaxUrl || "";

            this.bindEvents();
            if (this.activeThreadId > 0) {
                this.scrollToBottom();
                this.startPolling();
            }
        },

        bindEvents: function() {
            if (this.eventsBound) return;
            this.eventsBound = true;
            var self = this;

            // Textarea Enter key handling
            var textarea = document.getElementById("imMessageInput");
            if (textarea) {
                textarea.addEventListener("keydown", function(e) {
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        self.sendMessage();
                    }
                });
            }

            // Send Button
            var sendBtn = document.getElementById("imSendBtn");
            if (sendBtn) {
                sendBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    self.sendMessage();
                });
            }


        },

        scrollToBottom: function() {
            var box = document.getElementById("imMessagesBox");
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
            if (!this.activeThreadId || this.isFetching) return;
            this.isFetching = true;

            var url = this.ajaxUrl + "&action=im_get_messages&thread_id=" + this.activeThreadId + "&after_id=" + this.lastMessageId;
            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    self.isFetching = false;
                    if (data.status === "ok" && data.messages && data.messages.length > 0) {
                        var box = document.getElementById("imMessagesBox");
                        var shouldScroll = box ? (box.scrollHeight - box.scrollTop <= box.clientHeight + 100) : false;

                        data.messages.forEach(function(msg) {
                            if (msg.id > self.lastMessageId) {
                                self.lastMessageId = msg.id;
                            }
                            if (document.getElementById("imMsg_" + msg.id)) {
                                return;
                            }
                            var row = document.createElement("div");
                            row.id = "imMsg_" + msg.id;
                            row.setAttribute("data-msg-id", msg.id);
                            row.className = "im-msg-row " + (msg.is_me ? "me" : "other");

                            var attachHtml = "";
                            if (msg.attachment_url) {
                                if (msg.is_image) {
                                    attachHtml = '<div class="im-attachment-preview"><a href="' + msg.attachment_url + '" target="_blank"><img src="' + msg.attachment_url + '" alt="attachment" /></a></div>';
                                } else {
                                    attachHtml = '<div><a href="' + msg.attachment_url + '" target="_blank" class="im-attachment-doc"><i class="fa fa-file"></i> ' + msg.attachment_name + '</a></div>';
                                }
                            }

                            row.innerHTML = '<div class="im-msg-bubble">' + 
                                (msg.message ? '<div>' + msg.message + '</div>' : "") + 
                                attachHtml + 
                            '</div>' + 
                            '<div class="im-msg-meta">' + 
                                '<span>' + msg.time + '</span>' + 
                                (msg.is_me ? (msg.is_read ? '<span class="im-seen-check"><i class="fa fa-check-double"></i> ' + (window.imSeenText || "Seen") + '</span>' : '<span class="im-seen-check"><i class="fa fa-check"></i></span>') : "") + 
                            '</div>';

                            if (box) box.appendChild(row);
                        });

                        if (shouldScroll) {
                            self.scrollToBottom();
                        }
                    }
                })
                .catch(function(err) {
                    self.isFetching = false;
                    console.error("IM fetch error:", err);
                });
        },

        sendMessage: function() {
            var self = this;
            if (this.isSending) return;

            var textarea = document.getElementById("imMessageInput");
            var sendBtn = document.getElementById("imSendBtn");

            var text = textarea ? textarea.value.trim() : "";
            if (!text) return;

            this.isSending = true;
            if (sendBtn) sendBtn.disabled = true;
            if (textarea) textarea.value = "";

            // Optimistic UI: append bubble immediately
            var tempMsgId = "temp_" + Date.now();
            var nowTime = new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", hour12: false });
            var box = document.getElementById("imMessagesBox");
            if (box && text) {
                var pendingRow = document.createElement("div");
                pendingRow.id = "imMsg_" + tempMsgId;
                pendingRow.setAttribute("data-msg-id", tempMsgId);
                pendingRow.className = "im-msg-row me im-msg-pending";
                pendingRow.innerHTML = '<div class="im-msg-bubble">' + 
                    '<div>' + text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>") + '</div>' + 
                '</div>' + 
                '<div class="im-msg-meta">' + 
                    '<span>' + nowTime + '</span>' + 
                    '<span class="im-seen-check"><i class="fa fa-clock" style="opacity:0.6;"></i></span>' + 
                '</div>';
                box.appendChild(pendingRow);
                self.scrollToBottom();
            }

            this.startPolling();

            var formData = new FormData();
            formData.append("action", "im_send_message");
            formData.append("thread_id", this.activeThreadId);
            formData.append("message", text);

            fetch(this.ajaxUrl, {
                method: "POST",
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                self.isSending = false;
                if (sendBtn) sendBtn.disabled = false;
                if (data.status === "ok") {
                    if (data.msg_id) {
                        if (data.msg_id > self.lastMessageId) {
                            self.lastMessageId = data.msg_id;
                        }
                        var pRow = document.getElementById("imMsg_" + tempMsgId);
                        if (pRow) {
                            pRow.id = "imMsg_" + data.msg_id;
                            pRow.setAttribute("data-msg-id", data.msg_id);
                            pRow.classList.remove("im-msg-pending");
                            var checkSpan = pRow.querySelector(".im-seen-check");
                            if (checkSpan) {
                                checkSpan.innerHTML = '<i class="fa fa-check"></i>';
                            }
                        }
                    }
                    self.fetchNewMessages();
                } else {
                    var pRow = document.getElementById("imMsg_" + tempMsgId);
                    if (pRow) pRow.remove();
                    if (textarea && text) textarea.value = text;
                    alert(data.error || "Failed to send message.");
                }
            })
            .catch(function(err) {
                self.isSending = false;
                if (sendBtn) sendBtn.disabled = false;
                var pRow = document.getElementById("imMsg_" + tempMsgId);
                if (pRow) pRow.remove();
                if (textarea && text) textarea.value = text;
                alert("Network error while sending message.");
            });
        }
    };
})();
