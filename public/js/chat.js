document.addEventListener('DOMContentLoaded', () => {
    const chatBox = document.getElementById('chat-messages');
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');

    // ⚠️ Ces valeurs seront injectées depuis Twig (voir étape 3)
    const sendUrl = chatBox.dataset.sendUrl;
    const fetchUrl = chatBox.dataset.fetchUrl;

    let lastMessageId = 0;
    let isSending = false;
    let isFetching = false;
    const displayedIds = new Set();

    async function sendMessage() {
        const content = input.value.trim();
        if (!content || isSending) return;
        isSending = true;

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content })
            });

            if (!res.ok) {
                console.error('Erreur envoi message :', await res.text());
                return;
            }

            const data = await res.json();
            addMessageToChat(data, true);

            if (data.id) {
                displayedIds.add(data.id);
                lastMessageId = Math.max(lastMessageId, data.id);
            }

            input.value = '';
        } catch (e) {
            console.error('Erreur réseau :', e);
        } finally {
            isSending = false;
        }
    }

    function addMessageToChat(msg, isMine = false) {
        if (!msg.id) return;
        if (displayedIds.has(msg.id)) return;

        const div = document.createElement('div');
        div.classList.add('mb-2', 'chat-message');
        if (isMine) div.classList.add('my-message');

        div.innerHTML = `<strong>${msg.participant}</strong> : ${msg.content}
                         <small class="text-muted">${msg.createdAt}</small>`;

        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
        displayedIds.add(msg.id);
    }

    async function fetchMessages(playAnimation = false) {
        if (isFetching) return;
        isFetching = true;

        try {
            const res = await fetch(`${fetchUrl}?lastId=${lastMessageId}`);
            if (!res.ok) return;

            const newMessages = await res.json();

            if (newMessages.length > 0) {
                newMessages.forEach(msg => addMessageToChat(msg));
                const last = newMessages[newMessages.length - 1];
                lastMessageId = Math.max(lastMessageId, last.id);

                if (playAnimation) {
                    chatBox.classList.add('highlight');
                    setTimeout(() => chatBox.classList.remove('highlight'), 800);
                }
            }
        } catch (e) {
            console.error('Erreur fetch messages :', e);
        } finally {
            isFetching = false;
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    fetchMessages(false);
    setInterval(() => fetchMessages(true), 3000);
});
