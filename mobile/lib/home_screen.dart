import 'package:flutter/material.dart';

import 'api.dart';
import 'kitchen.dart';
import 'main.dart';
import 'pantry_editor.dart';
import 'recipe_screen.dart';
import 'chat_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.api, required this.onLogout});
  final KitchenApi api;
  final VoidCallback onLogout;
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Json? data;
  String? error;
  bool loading = true, saving = false, readyOnly = false;
  String search = '', difficulty = 'All';
  int tab = 0;
  List<Json> get pantry => rows(data?['pantry']);
  List<int> get owned => (data?['owned_equipment'] as List? ?? []).cast<int>();
  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    try {
      final result = await widget.api.request('GET', 'kitchen');
      if (mounted) {
        setState(() {
          data = result;
          error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  void notify(String message) {
    if (mounted) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(message)));
    }
  }

  Future<void> edit([Json? item]) async {
    final changed = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => PantryEditor(
          api: widget.api,
          ingredients: rows(data?['ingredients']),
          item: item,
        ),
      ),
    );
    if (changed == true) await load();
  }

  Future<void> remove(Json item) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Remove pantry item?'),
        content: Text(item['ingredient']['name'] as String),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Keep'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Remove'),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    setState(() => saving = true);
    try {
      await widget.api.request('DELETE', 'pantry/${item['id']}');
      await load();
    } catch (e) {
      notify(e.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  Future<void> changeTool(int id, bool selected) async {
    setState(() => saving = true);
    final next = [...owned];
    if (selected) {
      next.add(id);
    } else {
      next.remove(id);
    }
    try {
      await widget.api.request('PUT', 'equipment', {'equipment': next});
      if (mounted) setState(() => data!['owned_equipment'] = next);
    } catch (e) {
      notify(e.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  Widget heading(String title, String subtitle) => Padding(
    padding: const EdgeInsets.only(bottom: 22),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 30,
            fontWeight: FontWeight.w800,
            color: forest,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          subtitle,
          style: const TextStyle(color: Color(0xFF66746A), height: 1.5),
        ),
      ],
    ),
  );
  Widget list(List<Widget> children) => RefreshIndicator(
    onRefresh: load,
    child: ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(20),
      children: children,
    ),
  );
  Widget recipes() {
    final recipes = rows(data!['recipes'])
        .where(
          (r) =>
              '${r['title']} ${r['cuisine']} ${rows(r['ingredients']).map((i) => i['ingredient']['name']).join(' ')}'
                  .toLowerCase()
                  .contains(search.toLowerCase()) &&
              (difficulty == 'All' || r['difficulty'] == difficulty) &&
              (!readyOnly || availability(r, pantry, owned).ready),
        )
        .toList();
    return list([
      Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: forest,
          borderRadius: BorderRadius.circular(28),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'LET’S MAKE SOMETHING GOOD',
              style: TextStyle(
                color: Color(0xFFF9D45C),
                letterSpacing: 1.5,
                fontSize: 11,
              ),
            ),
            const SizedBox(height: 14),
            const Text(
              'What’s cooking\ntoday?',
              style: TextStyle(
                color: Colors.white,
                fontSize: 36,
                fontWeight: FontWeight.bold,
                height: 1.1,
              ),
            ),
            const SizedBox(height: 18),
            Text(
              '${pantry.length} pantry items  ·  ${owned.length} kitchen tools',
              style: const TextStyle(color: Colors.white70),
            ),
          ],
        ),
      ),
      const SizedBox(height: 22),
      TextField(
        decoration: const InputDecoration(
          hintText: 'Find a recipe or ingredient',
          prefixIcon: Icon(Icons.search),
        ),
        onChanged: (v) => setState(() => search = v),
      ),
      const SizedBox(height: 12),
      Wrap(
        spacing: 8,
        children: ['All', 'easy', 'medium', 'hard']
            .map(
              (d) => ChoiceChip(
                label: Text(d),
                selected: difficulty == d,
                onSelected: (_) => setState(() => difficulty = d),
              ),
            )
            .toList(),
      ),
      SwitchListTile(
        contentPadding: EdgeInsets.zero,
        title: const Text('Ready to cook'),
        subtitle: const Text('Enough ingredients and the right tools'),
        value: readyOnly,
        onChanged: (v) => setState(() => readyOnly = v),
      ),
      Text(
        '${recipes.length} recipes to discover',
        style: const TextStyle(fontWeight: FontWeight.bold, color: forest),
      ),
      const SizedBox(height: 16),
      if (recipes.isEmpty)
        const Padding(
          padding: EdgeInsets.all(24),
          child: Text(
            'No recipes match yet. Try another search or add ingredients and tools.',
          ),
        ),
      ...recipes.map((recipe) {
        final match = availability(recipe, pantry, owned);
        return Card(
          child: InkWell(
            borderRadius: BorderRadius.circular(24),
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) =>
                    RecipeScreen(recipe: recipe, availability: match),
              ),
            ),
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFBE5C6),
                          borderRadius: BorderRadius.circular(18),
                        ),
                        child: const Icon(
                          Icons.ramen_dining,
                          color: forest,
                          size: 36,
                        ),
                      ),
                      const Spacer(),
                      Text(
                        '${recipe['total_minutes']} min',
                        style: const TextStyle(
                          color: forest,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  Text(
                    recipe['title'] as String,
                    style: const TextStyle(
                      fontSize: 23,
                      fontWeight: FontWeight.w700,
                      color: forest,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${recipe['cuisine']} · ${recipe['difficulty']} · ${recipe['servings']} servings',
                  ),
                  const SizedBox(height: 14),
                  Text(
                    match.ready
                        ? '✓ Your kitchen is ready'
                        : '${match.missingIngredients.length} ingredients · ${match.missingTools.length} tool groups to check',
                    style: const TextStyle(color: forest),
                  ),
                ],
              ),
            ),
          ),
        );
      }),
    ]);
  }

  Widget pantryPage() => list([
    heading(
      'My pantry',
      'Keep track of what you have, where it lives, and when to use it.',
    ),
    FilledButton.icon(
      onPressed: () => edit(),
      icon: const Icon(Icons.add),
      label: const Text('Add an ingredient'),
    ),
    const SizedBox(height: 20),
    if (pantry.isEmpty)
      const Text(
        'Your pantry is empty. Add your first ingredient to find meals you can cook.',
      ),
    ...pantry.map(
      (item) => Card(
        child: ListTile(
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 18,
            vertical: 10,
          ),
          onTap: saving ? null : () => edit(item),
          title: Text(
            item['ingredient']['name'] as String,
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
          subtitle: Text(
            '${amount(item['quantity'])} ${item['unit']} · ${item['storage_location'] ?? 'No location'}\n${item['expiration_date'] == null ? 'No expiry set' : '${expired(item, DateTime.now()) ? 'Expired' : 'Use by'} ${dateOnly(item['expiration_date'])}'}',
          ),
          trailing: IconButton(
            tooltip: 'Remove ingredient',
            onPressed: saving ? null : () => remove(item),
            icon: const Icon(Icons.delete_outline),
          ),
        ),
      ),
    ),
  ]);
  Widget toolsPage() => list([
    heading(
      'Kitchen tools',
      'Select the tools you own. Recipes may offer alternatives such as an oven or air fryer.',
    ),
    if (saving) const LinearProgressIndicator(),
    ...rows(data!['equipment']).map(
      (tool) => Card(
        child: CheckboxListTile(
          value: owned.contains(tool['id']),
          onChanged: saving ? null : (v) => changeTool(tool['id'] as int, v!),
          title: Text(tool['name'] as String),
          secondary: const Icon(Icons.kitchen_outlined),
        ),
      ),
    ),
  ]);
  Widget account() => list([
    heading(
      'Your kitchen',
      'Your account is shared with TuklasFood on the web.',
    ),
    Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const CircleAvatar(
              radius: 30,
              backgroundColor: forest,
              child: Icon(Icons.person_outline, color: Colors.white, size: 34),
            ),
            const SizedBox(height: 18),
            Text(
              data!['user']['name'] as String,
              style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            Text(data!['user']['email'] as String),
            const SizedBox(height: 10),
            Text('Account: ${data!['user']['role']}'),
          ],
        ),
      ),
    ),
    const Text(
      'Pantry and tools are saved to your account. For now, sign in again after closing the app.',
    ),
    const SizedBox(height: 20),
    OutlinedButton.icon(
      onPressed: saving
          ? null
          : () async {
              setState(() => saving = true);
              try {
                await widget.api.request('DELETE', 'auth/token');
                if (mounted) widget.onLogout();
              } catch (e) {
                notify(e.toString());
              } finally {
                if (mounted) setState(() => saving = false);
              }
            },
      icon: const Icon(Icons.logout),
      label: const Text('Sign out'),
    ),
  ]);
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Text(
        'TuklasFood',
        style: TextStyle(fontWeight: FontWeight.w800),
      ),
      actions: [
        IconButton(
          tooltip: 'Refresh kitchen',
          onPressed: loading ? null : load,
          icon: const Icon(Icons.refresh),
        ),
      ],
    ),
    body: SafeArea(
      child: loading
          ? const Center(child: CircularProgressIndicator())
          : data == null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(error ?? 'Unable to load your kitchen.'),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: load,
                      child: const Text('Try again'),
                    ),
                  ],
                ),
              ),
            )
          : Column(
              children: [
                if (error != null)
                  MaterialBanner(
                    content: Text(error!),
                    actions: [
                      TextButton(onPressed: load, child: const Text('Retry')),
                    ],
                  ),
                Expanded(
                  child: IndexedStack(
                    index: tab,
                    children: [
                      recipes(),
                      pantryPage(),
                      toolsPage(),
                      ChatScreen(
                        api: widget.api,
                        compact: MediaQuery.viewInsetsOf(context).bottom > 0,
                      ),
                      account(),
                    ],
                  ),
                ),
              ],
            ),
    ),
    bottomNavigationBar: MediaQuery.viewInsetsOf(context).bottom > 0
        ? null
        : NavigationBar(
            selectedIndex: tab,
            onDestinationSelected: (v) => setState(() => tab = v),
            destinations: const [
              NavigationDestination(
                icon: Icon(Icons.restaurant_menu),
                label: 'Recipes',
              ),
              NavigationDestination(
                icon: Icon(Icons.inventory_2_outlined),
                label: 'Pantry',
              ),
              NavigationDestination(
                icon: Icon(Icons.kitchen_outlined),
                label: 'Tools',
              ),
              NavigationDestination(
                icon: Icon(Icons.chat_bubble_outline),
                label: 'Chat',
              ),
              NavigationDestination(
                icon: Icon(Icons.person_outline),
                label: 'Account',
              ),
            ],
          ),
  );
}
