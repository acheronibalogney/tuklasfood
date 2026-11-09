typedef Json = Map<String, dynamic>;
List<Json> rows(dynamic value) => (value as List? ?? [])
    .map((e) => Map<String, dynamic>.from(e as Map))
    .toList();
double number(dynamic value) => double.tryParse('$value') ?? 0;
String amount(dynamic value) {
  final n = number(value);
  return n == n.roundToDouble() ? n.toInt().toString() : n.toStringAsFixed(2);
}

String dateOnly(dynamic value) =>
    value == null ? '' : value.toString().split('T').first;
bool expired(Json item, DateTime now) {
  final expiry = DateTime.tryParse(dateOnly(item['expiration_date']));
  return expiry != null &&
      expiry.isBefore(DateTime(now.year, now.month, now.day));
}

class RecipeAvailability {
  final List<String> missingIngredients;
  final List<String> missingTools;
  const RecipeAvailability(this.missingIngredients, this.missingTools);
  bool get ready => missingIngredients.isEmpty && missingTools.isEmpty;
}

RecipeAvailability availability(
  Json recipe,
  List<Json> pantry,
  List<int> owned, {
  DateTime? now,
}) {
  final stock = <int, double>{};
  for (final item in pantry) {
    if (!expired(item, now ?? DateTime.now())) {
      final id = item['ingredient_id'] as int;
      stock[id] = (stock[id] ?? 0) + number(item['quantity_base']);
    }
  }
  final missing = <String>[];
  for (final item in rows(recipe['ingredients'])) {
    if (item['is_optional'] == true) continue;
    if ((stock[item['ingredient_id']] ?? 0) + 0.00001 <
        number(item['quantity_base'])) {
      missing.add(
        '${item['ingredient']['name']} (${amount(item['quantity'])} ${item['unit']} needed)',
      );
    }
  }
  final groups = <int, List<Json>>{};
  for (final tool in rows(recipe['equipment'])) {
    groups.putIfAbsent(tool['pivot']['group_no'] as int, () => []).add(tool);
  }
  final tools = groups.values
      .where((group) => !group.any((tool) => owned.contains(tool['id'])))
      .map((group) => group.map((tool) => tool['name']).join(' or '))
      .toList();
  return RecipeAvailability(missing, tools);
}
