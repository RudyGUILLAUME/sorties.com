document.addEventListener('DOMContentLoaded', () => {
    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const chatSend = document.getElementById('chat-send');

    if (!chatMessages || !chatInput || !chatSend) return;

    const sendUrl = chatMessages.dataset.sendUrl;
    const fetchUrl = chatMessages.dataset.fetchUrl;
    let isSending = false;

    // 🔁 Récupération initiale
    fetchMessages();

    // ⏳ Rafraîchissement auto toutes les 5 secondes
    setInterval(fetchMessages, 5000);

    // 📨 Envoi d’un message
    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message || isSending) return;

        isSending = true;

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });

            if (!res.ok) {
                console.error('Erreur lors de l’envoi du message.');
                return;
            }

            chatInput.value = '';
            await fetchMessages(); // Recharge les messages confirmés par le backend
        } catch (error) {
            console.error('Erreur réseau :', error);
        } finally {
            isSending = false;
        }
    }

    async function fetchMessages() {
        try {
            const res = await fetch(fetchUrl);
            if (!res.ok) {
                console.error('Erreur lors de la récupération des messages.');
                return;
            }

            const messages = await res.json();
            renderMessages(messages);
        } catch (error) {
            console.error('Erreur réseau :', error);
        }
    }

    function renderMessages(messages) {
        chatMessages.innerHTML = '';

        messages.forEach(msg => {
            const div = document.createElement('div');
            div.className = msg.isMine ? 'flex justify-end' : 'flex justify-start';
            div.innerHTML = `
                <div class="${msg.isMine
                ? 'bg-primary text-text-light'
                : 'bg-background-light dark:bg-background-dark'} p-3 rounded-lg max-w-xs">
                    
                    <p class="text-xs text-subtle-light dark:text-subtle-dark mb-1">
                        ${escapeHtml(msg.participant)} ${msg.isMine ? '(vous)' : ''}
                    </p>
                    
                    <p class="text-sm mb-1">${escapeHtml(msg.content)}</p>
                    
                    <p class="text-xs text-subtle-light dark:text-subtle-dark text-right opacity-70">
                        ${escapeHtml(msg.createdAt || '')}
                    </p>
                </div>
            `;
            chatMessages.appendChild(div);
        });

        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
