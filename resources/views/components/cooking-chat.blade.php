<button type="button" class="chat-launcher" data-chat-launch aria-controls="cooking-chat" aria-expanded="false">✳ Ask the kitchen</button>
<dialog id="cooking-chat" class="cooking-chat" aria-labelledby="chat-title" data-chat-endpoint="{{ route('cooking-chat') }}">
    <header class="chat-header"><div><span class="eyebrow">A LITTLE HELP IN THE KITCHEN</span><h2 id="chat-title">Your cooking companion</h2></div><button type="button" data-chat-close aria-label="Close cooking assistant">×</button></header>
    <p class="chat-disclosure">AI cooking advice. Messages are sent to the AI provider when connected. Check ingredients for allergies.</p>
    <div class="chat-messages" role="log" aria-live="polite" aria-label="Cooking conversation"><p class="chat-message">Hi! Ask me about meal ideas, ingredient swaps, or a cooking technique.</p></div>
    @if(! \App\Support\CookingAssistant::configured())<p class="chat-status" role="status">AI setup is pending. The assistant will be available once connected.</p>@endif
    <div class="chat-prompts"><button type="button" data-chat-prompt="What can I cook with eggs and tomatoes?">Eggs + tomatoes?</button><button type="button" data-chat-prompt="Suggest an easy Filipino meal for tonight.">An easy dinner</button></div>
    <form class="chat-form"><label class="sr-only" for="chat-message">Your cooking question</label><textarea id="chat-message" rows="2" maxlength="2000" placeholder="What’s cooking on your mind?" required></textarea><button class="button" type="submit">Send ↑</button></form>
    <p class="chat-status" data-chat-status role="status"></p><button class="chat-clear" type="button" data-chat-clear>Clear conversation</button>
</dialog>
