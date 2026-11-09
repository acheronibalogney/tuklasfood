const chat = document.querySelector('#cooking-chat');

if (chat) {
    const form = chat.querySelector('form');
    const input = chat.querySelector('textarea');
    const messages = chat.querySelector('.chat-messages');
    const status = chat.querySelector('[data-chat-status]');
    const submit = form.querySelector('button');
    let history = [];
    let pending = false;

    const append = (text, role) => {
        const bubble = document.createElement('p');
        bubble.className = `chat-message chat-${role}`;
        bubble.textContent = text;
        messages.append(bubble);
        messages.scrollTop = messages.scrollHeight;
    };

    document.querySelectorAll('[data-chat-launch]').forEach(button => button.addEventListener('click', () => {
        chat.showModal();
        document.querySelector('.chat-launcher').setAttribute('aria-expanded', 'true');
        input.focus();
    }));
    chat.querySelector('[data-chat-close]').addEventListener('click', () => chat.close());
    chat.addEventListener('close', () => document.querySelector('.chat-launcher').setAttribute('aria-expanded', 'false'));
    chat.querySelectorAll('[data-chat-prompt]').forEach(button => button.addEventListener('click', () => {
        input.value = button.dataset.chatPrompt;
        input.focus();
    }));
    chat.querySelector('[data-chat-clear]').addEventListener('click', () => {
        if (pending) return;
        history = [];
        messages.replaceChildren();
        append('A fresh start! What would you like to cook?', 'assistant');
        status.textContent = '';
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || pending) return;
        pending = true;
        submit.disabled = true;
        status.textContent = 'Thinking about your question…';
        append(message, 'user');
        input.value = '';
        try {
            const response = await fetch(chat.dataset.chatEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ message, history: history.slice(-10) }),
                signal: AbortSignal.timeout(40000),
            });
            const data = await response.json();
            if (!response.ok) {
                status.textContent = response.status === 429 ? 'Too many questions at once. Please wait a minute and try again.' : response.status === 419 ? 'Your session expired. Refresh the page and try again.' : data.message || 'Unable to respond. Please try again.';
                input.value = message;
                return;
            }
            append(data.reply, 'assistant');
            history.push({ role: 'user', content: message }, { role: 'assistant', content: data.reply.slice(0, 4000) });
            history = history.slice(-10);
            status.textContent = '';
        } catch {
            status.textContent = 'Could not reach the assistant. Please try again.';
            input.value = message;
        } finally {
            pending = false;
            submit.disabled = false;
            input.focus();
        }
    });
}
