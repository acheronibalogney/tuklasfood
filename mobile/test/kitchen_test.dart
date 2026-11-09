import 'package:flutter_test/flutter_test.dart';
import 'package:tuklasfood_mobile/kitchen.dart';

void main() {
  final recipe = <String, dynamic>{
    'ingredients': [
      {
        'ingredient_id': 1,
        'ingredient': {'name': 'Chicken wings'},
        'quantity': 1,
        'unit': 'kg',
        'quantity_base': '1000',
        'is_optional': false,
      },
      {
        'ingredient_id': 2,
        'ingredient': {'name': 'Garnish'},
        'quantity': 1,
        'unit': 'pc',
        'quantity_base': 1,
        'is_optional': true,
      },
    ],
    'equipment': [
      {
        'id': 1,
        'name': 'Oven',
        'pivot': {'group_no': 1},
      },
      {
        'id': 2,
        'name': 'Air fryer',
        'pivot': {'group_no': 1},
      },
    ],
  };
  test('combines batches, ignores optional ingredients and accepts alternative tools', () {
    final result = availability(
      recipe,
      [
        {'ingredient_id': 1, 'quantity_base': '600'},
        {'ingredient_id': 1, 'quantity_base': '400'},
      ],
      [2],
    );
    expect(result.ready, isTrue);
  });
  test('expired stock and different cuts do not count as available', () {
    final result = availability(
      recipe,
      [
        {
          'ingredient_id': 1,
          'quantity_base': 1000,
          'expiration_date': '2026-10-20',
        },
        {'ingredient_id': 3, 'quantity_base': 2000},
      ],
      [],
      now: DateTime(2026, 10, 21),
    );
    expect(result.ready, isFalse);
    expect(result.missingIngredients, ['Chicken wings (1 kg needed)']);
    expect(result.missingTools, ['Oven or Air fryer']);
  });
  test('expiry day is usable and insufficient amounts remain missing', () {
    final item = {
      'ingredient_id': 1,
      'quantity_base': 999,
      'expiration_date': '2026-10-21T00:00:00.000Z',
    };
    expect(expired(item, DateTime(2026, 10, 21, 23)), isFalse);
    expect(
      availability(recipe, [item], [1], now: DateTime(2026, 10, 21)).ready,
      isFalse,
    );
  });
}
