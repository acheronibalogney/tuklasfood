import 'package:flutter/material.dart';

import 'api.dart';
import 'main.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key, required this.api, this.compact = false});
  final KitchenApi api;
  final bool compact;
  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final input = TextEditingController();
  final scroll = ScrollController();
  final messages = <Map<String, String>>[];
  bool busy = false;
  String? error;
  void bottom() => WidgetsBinding.instance.addPostFrameCallback((_) {
    if (mounted && scroll.hasClients) {
      scroll.animateTo(
        scroll.position.maxScrollExtent,
        duration: const Duration(milliseconds: 200),
        curve: Curves.easeOut,
      );
    }
  });
  Future<void> send() async {
    final text = input.text.trim();
    if (text.isEmpty || busy) return;
    final history = messages
        .skip(messages.length > 10 ? messages.length - 10 : 0)
        .map(
          (message) => {
            'role': message['role']!,
            'content': String.fromCharCodes(
              message['content']!.runes.take(4000),
            ),
          },
        )
        .toList();
    setState(() {
      busy = true;
      error = null;
      messages.add({'role': 'user', 'content': text});
      input.clear();
    });
    bottom();
    try {
      final result = await widget.api.request('POST', 'cooking-chat', {
        'message': text,
        'history': history,
      });
      if (mounted) {
        setState(
          () => messages.add({
            'role': 'assistant',
            'content': result['reply'] as String,
          }),
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          error = e.toString();
          messages.removeLast();
          input.text = text;
        });
      }
    } finally {
      if (mounted) {
        setState(() => busy = false);
        bottom();
      }
    }
  }

  @override
  void dispose() {
    input.dispose();
    scroll.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Column(
    children: [
      if (!widget.compact)
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 14, 12, 10),
          child: Row(
            children: [
              const Expanded(
                child: Text(
                  'Your cooking companion',
                  style: TextStyle(
                    fontSize: 23,
                    fontWeight: FontWeight.bold,
                    color: forest,
                  ),
                ),
              ),
              IconButton(
                tooltip: 'Clear conversation',
                onPressed: busy
                    ? null
                    : () => setState(() {
                        messages.clear();
                        error = null;
                      }),
                icon: const Icon(Icons.delete_sweep_outlined),
              ),
            ],
          ),
        ),
      if (!widget.compact)
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 20),
          child: Text(
            'Ask about recipes, substitutions, or techniques. AI can make mistakes; check cooking times and allergens.',
          ),
        ),
      Expanded(
        child: ListView(
          controller: scroll,
          padding: const EdgeInsets.all(20),
          children: [
            if (messages.isEmpty) ...[
              const SizedBox(height: 28),
              const Icon(Icons.soup_kitchen, size: 64, color: orange),
              const SizedBox(height: 18),
              const Text(
                'A little help for your next meal.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 24, color: forest),
              ),
              const SizedBox(height: 18),
              ...[
                'What can I cook for a quick dinner?',
                'How do I make chicken adobo?',
                'Help me substitute an ingredient.',
              ].map(
                (prompt) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: OutlinedButton(
                    onPressed: () {
                      input.text = prompt;
                      send();
                    },
                    child: Text(prompt),
                  ),
                ),
              ),
            ],
            ...messages.map(
              (m) => Align(
                alignment: m['role'] == 'user'
                    ? Alignment.centerRight
                    : Alignment.centerLeft,
                child: Container(
                  constraints: const BoxConstraints(maxWidth: 420),
                  margin: const EdgeInsets.only(bottom: 14),
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: m['role'] == 'user' ? forest : Colors.white,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: SelectableText(
                    m['content']!,
                    style: TextStyle(
                      color: m['role'] == 'user' ? Colors.white : forest,
                      height: 1.5,
                    ),
                  ),
                ),
              ),
            ),
            if (busy) const LinearProgressIndicator(),
          ],
        ),
      ),
      if (error != null)
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
          child: Text(
            error!,
            maxLines: 3,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ),
      Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(
              child: TextField(
                controller: input,
                enabled: !busy,
                minLines: 1,
                maxLines: widget.compact ? 2 : 4,
                maxLength: 2000,
                decoration: const InputDecoration(
                  hintText: 'Ask your cooking question…',
                  counterText: '',
                ),
              ),
            ),
            const SizedBox(width: 10),
            IconButton.filled(
              tooltip: 'Send message',
              onPressed: busy ? null : send,
              icon: const Icon(Icons.arrow_upward),
            ),
          ],
        ),
      ),
    ],
  );
}
