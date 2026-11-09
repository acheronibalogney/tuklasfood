import 'package:flutter/material.dart';

import 'api.dart';
import 'kitchen.dart';

class PantryEditor extends StatefulWidget {
  const PantryEditor({
    super.key,
    required this.api,
    required this.ingredients,
    this.item,
  });
  final KitchenApi api;
  final List<Json> ingredients;
  final Json? item;
  @override
  State<PantryEditor> createState() => _PantryEditorState();
}

class _PantryEditorState extends State<PantryEditor> {
  final form = GlobalKey<FormState>();
  final quantity = TextEditingController(),
      location = TextEditingController(),
      purchase = TextEditingController(),
      expiry = TextEditingController();
  int? ingredient;
  String unit = 'g';
  bool saving = false;
  String? error;
  @override
  void initState() {
    super.initState();
    final item = widget.item;
    if (item != null) {
      ingredient = item['ingredient_id'] as int;
      unit = item['unit'] as String;
      quantity.text = amount(item['quantity']);
      location.text = item['storage_location'] as String? ?? '';
      purchase.text = dateOnly(item['purchase_date']);
      expiry.text = dateOnly(item['expiration_date']);
    }
  }

  @override
  void dispose() {
    for (final c in [quantity, location, purchase, expiry]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> pickDate(TextEditingController controller) async {
    final date = await showDatePicker(
      context: context,
      initialDate: DateTime.tryParse(controller.text) ?? DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (date != null && mounted) {
      setState(() => controller.text = date.toIso8601String().split('T').first);
    }
  }

  Future<void> save() async {
    if (!form.currentState!.validate()) return;
    setState(() {
      saving = true;
      error = null;
    });
    try {
      await widget.api.request(
        widget.item == null ? 'POST' : 'PUT',
        widget.item == null ? 'pantry' : 'pantry/${widget.item!['id']}',
        {
          'ingredient_id': ingredient,
          'quantity': double.parse(quantity.text),
          'unit': unit,
          'storage_location': location.text.trim().isEmpty
              ? null
              : location.text.trim(),
          'purchase_date': purchase.text.isEmpty ? null : purchase.text,
          'expiration_date': expiry.text.isEmpty ? null : expiry.text,
        },
      );
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  Widget dateField(String label, TextEditingController controller) =>
      TextFormField(
        controller: controller,
        readOnly: true,
        onTap: saving ? null : () => pickDate(controller),
        decoration: InputDecoration(
          labelText: label,
          suffixIcon: IconButton(
            onPressed: saving ? null : () => setState(controller.clear),
            icon: const Icon(Icons.clear),
          ),
        ),
      );
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(widget.item == null ? 'Add ingredient' : 'Edit ingredient'),
    ),
    body: SafeArea(
      child: Form(
        key: form,
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            DropdownButtonFormField<int>(
              initialValue: ingredient,
              isExpanded: true,
              decoration: const InputDecoration(labelText: 'Ingredient'),
              items: widget.ingredients
                  .map(
                    (i) => DropdownMenuItem(
                      value: i['id'] as int,
                      child: Text(
                        i['name'] as String,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  )
                  .toList(),
              onChanged: saving
                  ? null
                  : (v) => setState(() {
                      ingredient = v;
                      unit =
                          widget.ingredients.firstWhere(
                                (i) => i['id'] == v,
                              )['base_unit']
                              as String;
                    }),
              validator: (v) => v == null ? 'Choose an ingredient' : null,
            ),
            const SizedBox(height: 18),
            TextFormField(
              controller: quantity,
              enabled: !saving,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              decoration: const InputDecoration(labelText: 'Quantity'),
              validator: (v) => (double.tryParse(v ?? '') ?? 0) <= 0
                  ? 'Enter a positive quantity'
                  : null,
            ),
            const SizedBox(height: 18),
            DropdownButtonFormField<String>(
              key: ValueKey(unit),
              initialValue: unit,
              decoration: const InputDecoration(labelText: 'Unit'),
              items: [
                'g',
                'kg',
                'ml',
                'l',
                'tsp',
                'tbsp',
                'cup',
                'pc',
              ].map((u) => DropdownMenuItem(value: u, child: Text(u))).toList(),
              onChanged: saving ? null : (v) => setState(() => unit = v!),
            ),
            const SizedBox(height: 18),
            TextFormField(
              controller: location,
              enabled: !saving,
              decoration: const InputDecoration(
                labelText: 'Storage location (optional)',
                hintText: 'Fridge, freezer, cupboard…',
              ),
            ),
            const SizedBox(height: 18),
            dateField('Purchase date (optional)', purchase),
            const SizedBox(height: 18),
            dateField('Expiry date (optional)', expiry),
            const SizedBox(height: 24),
            if (error != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: Text(
                  error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            FilledButton(
              onPressed: saving ? null : save,
              child: Text(saving ? 'Saving…' : 'Save ingredient'),
            ),
          ],
        ),
      ),
    ),
  );
}
