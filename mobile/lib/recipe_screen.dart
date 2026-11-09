import 'dart:async';

import 'package:flutter/material.dart';

import 'kitchen.dart';
import 'main.dart';

class RecipeScreen extends StatelessWidget {
  const RecipeScreen({
    super.key,
    required this.recipe,
    required this.availability,
  });
  final Json recipe;
  final RecipeAvailability availability;
  @override
  Widget build(BuildContext context) {
    final groups = <int, List<String>>{};
    for (final tool in rows(recipe['equipment'])) {
      groups
          .putIfAbsent(tool['pivot']['group_no'] as int, () => [])
          .add(tool['name'] as String);
    }
    return Scaffold(
      appBar: AppBar(title: const Text('From your recipe book')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const Icon(Icons.ramen_dining, color: orange, size: 80),
            const SizedBox(height: 24),
            Text(
              recipe['title'] as String,
              style: const TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.w800,
                color: forest,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              '${recipe['total_minutes']} min · ${recipe['servings']} servings · ${recipe['difficulty']}',
            ),
            const SizedBox(height: 16),
            Text(
              recipe['description'] as String? ?? '',
              style: const TextStyle(height: 1.6),
            ),
            const SizedBox(height: 24),
            Card(
              color: const Color(0xFFEAF0DF),
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      availability.ready
                          ? 'Your kitchen is ready'
                          : 'Before you begin',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 20,
                      ),
                    ),
                    const SizedBox(height: 10),
                    if (availability.ready)
                      const Text(
                        'You have the quantities and tools for this recipe.',
                      ),
                    ...availability.missingIngredients.map((s) => Text('• $s')),
                    ...availability.missingTools.map((s) => Text('• Tool: $s')),
                  ],
                ),
              ),
            ),
            const Text(
              'Ingredients',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 14),
            ...rows(recipe['ingredients']).map(
              (i) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Text(
                  '${amount(i['quantity'])} ${i['unit']}  ${i['ingredient']['name']}${i['is_optional'] == true ? ' (optional)' : ''}${i['note'] == null ? '' : '\n${i['note']}'}',
                  style: const TextStyle(fontSize: 16, height: 1.4),
                ),
              ),
            ),
            const SizedBox(height: 12),
            const Text(
              'Tools',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            ...groups.values.map(
              (g) => Padding(
                padding: const EdgeInsets.only(top: 10),
                child: Text('• ${g.join(' or ')}'),
              ),
            ),
            const SizedBox(height: 26),
            FilledButton.icon(
              onPressed: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => CookingScreen(recipe: recipe),
                ),
              ),
              icon: const Icon(Icons.play_arrow),
              label: const Text('Start cooking'),
            ),
            const SizedBox(height: 24),
            ...rows(recipe['steps']).map(
              (s) => ListTile(
                contentPadding: EdgeInsets.zero,
                leading: CircleAvatar(
                  backgroundColor: forest,
                  foregroundColor: Colors.white,
                  child: Text('${s['position']}'),
                ),
                title: Text(s['instruction'] as String),
                subtitle: s['timer_seconds'] == null
                    ? null
                    : Text(
                        '${amount(number(s['timer_seconds']) / 60)} minute timer',
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class CookingScreen extends StatefulWidget {
  const CookingScreen({super.key, required this.recipe});
  final Json recipe;
  @override
  State<CookingScreen> createState() => _CookingScreenState();
}

class _CookingScreenState extends State<CookingScreen> {
  int step = 0, remaining = 0;
  Timer? timer;
  DateTime? deadline;
  bool finished = false;
  void start(int seconds) {
    timer?.cancel();
    deadline = DateTime.now().add(Duration(seconds: seconds));
    setState(() => remaining = seconds);
    timer = Timer.periodic(const Duration(milliseconds: 250), (_) {
      final left = (deadline!.difference(DateTime.now()).inMilliseconds / 1000)
          .ceil()
          .clamp(0, seconds);
      if (!mounted) return;
      setState(() => remaining = left);
      if (left == 0) {
        timer?.cancel();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Timer finished. Check your food before continuing.'),
          ),
        );
      }
    });
  }

  void move(int next) {
    timer?.cancel();
    setState(() {
      step = next;
      remaining = 0;
      deadline = null;
    });
  }

  @override
  void dispose() {
    timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final steps = rows(widget.recipe['steps']);
    if (steps.isEmpty) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(
          child: Text('No instructions are available for this recipe.'),
        ),
      );
    }
    final current = steps[step];
    final seconds = number(current['timer_seconds']).toInt();
    return Scaffold(
      appBar: AppBar(title: Text(widget.recipe['title'] as String)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(28),
          children: [
            LinearProgressIndicator(
              value: finished ? 1 : (step + 1) / steps.length,
            ),
            const SizedBox(height: 36),
            Text(
              finished
                  ? 'Made with care.'
                  : 'STEP ${step + 1} OF ${steps.length}',
              style: const TextStyle(
                color: forest,
                fontWeight: FontWeight.bold,
                letterSpacing: 2,
              ),
            ),
            const SizedBox(height: 24),
            Text(
              finished ? 'Enjoy your meal!' : current['instruction'] as String,
              style: const TextStyle(
                fontSize: 28,
                height: 1.4,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 32),
            if (finished)
              const Text(
                'Your pantry has not been deducted automatically. Update the quantities you used in My pantry.',
              ),
            if (!finished && seconds > 0) ...[
              Text(
                '${remaining ~/ 60}:${(remaining % 60).toString().padLeft(2, '0')}',
                style: const TextStyle(fontSize: 54, color: forest),
              ),
              OutlinedButton.icon(
                onPressed: () => start(seconds),
                icon: const Icon(Icons.timer_outlined),
                label: Text(
                  deadline == null
                      ? 'Start ${amount(seconds / 60)} minute timer'
                      : 'Restart timer',
                ),
              ),
              const Text(
                'Keep the app open to see the timer. No background alarm is scheduled.',
              ),
              const SizedBox(height: 24),
            ],
            if (!finished)
              FilledButton(
                onPressed: () {
                  if (step + 1 == steps.length) {
                    timer?.cancel();
                    setState(() => finished = true);
                  } else {
                    move(step + 1);
                  }
                },
                child: Text(
                  step + 1 == steps.length ? 'Finish cooking' : 'Next step',
                ),
              ),
            if (!finished && step > 0)
              TextButton(
                onPressed: () => move(step - 1),
                child: const Text('Previous step'),
              ),
            if (finished)
              FilledButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Back to recipe'),
              ),
          ],
        ),
      ),
    );
  }
}
