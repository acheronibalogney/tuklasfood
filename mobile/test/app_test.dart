import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:tuklasfood_mobile/api.dart';
import 'package:tuklasfood_mobile/main.dart';
import 'package:tuklasfood_mobile/home_screen.dart';
import 'package:tuklasfood_mobile/chat_screen.dart';

class FakeApi extends KitchenApi {
  bool chatUnavailable = false;
  final calls = <String>[];
  @override
  Future<Map<String, dynamic>> request(
    String method,
    String path, [
    Map<String, dynamic>? body,
  ]) async {
    calls.add('$method $path');
    if (path == 'auth/login') return {'token': 'test-token'};
    if (path == 'cooking-chat') {
      if (chatUnavailable) {
        throw const ApiException(
          'The cooking assistant is not connected yet.',
          503,
        );
      }
      return {'reply': 'Try adobo with rice.'};
    }
    return {
      'user': {
        'name': 'Test Cook',
        'email': 'cook@example.test',
        'role': 'user',
      },
      'recipes': [],
      'ingredients': [
        {'id': 1, 'name': 'Rice', 'base_unit': 'g'},
      ],
      'pantry': [],
      'equipment': [
        {'id': 1, 'name': 'Stove'},
      ],
      'owned_equipment': [],
    };
  }
}

void main() {
  testWidgets('small phone sign-in scrolls and opens the real kitchen flow', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(360, 740);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final api = FakeApi();
    await tester.pumpWidget(TuklasFoodApp(api: api));
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Email'),
      'cook@example.test',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Password'),
      'password',
    );
    await tester.scrollUntilVisible(
      find.text('Open my kitchen'),
      200,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.tap(find.text('Open my kitchen'));
    await tester.pumpAndSettle();
    expect(find.byType(HomeScreen), findsOneWidget);
    expect(api.token, 'test-token');
    expect(find.text('What’s cooking\ntoday?'), findsOneWidget);
    await tester.tap(find.text('Pantry').last);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Add an ingredient'));
    await tester.pumpAndSettle();
    expect(find.text('Expiry date (optional)'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets('chat keeps failed messages editable and shows setup status', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(320, 640);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    addTearDown(tester.view.resetViewInsets);
    final api = FakeApi()..chatUnavailable = true;
    await tester.pumpWidget(
      MaterialApp(
        home: HomeScreen(api: api, onLogout: () {}),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Chat').last);
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(
      find.text('How do I make chicken adobo?'),
      100,
      scrollable: find
          .descendant(
            of: find.byType(ChatScreen),
            matching: find.byType(Scrollable),
          )
          .first,
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('How do I make chicken adobo?'));
    await tester.pumpAndSettle();
    expect(
      find.text('The cooking assistant is not connected yet.'),
      findsOneWidget,
    );
    expect(
      find.widgetWithText(TextField, 'How do I make chicken adobo?'),
      findsOneWidget,
    );
    tester.view.viewInsets = const FakeViewPadding(bottom: 280);
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
    api.chatUnavailable = false;
    await tester.tap(find.byTooltip('Send message'));
    await tester.pumpAndSettle();
    expect(find.text('Try adobo with rice.'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
