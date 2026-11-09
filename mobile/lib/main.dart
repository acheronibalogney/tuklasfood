import 'package:flutter/material.dart';

import 'api.dart';
import 'auth_screen.dart';
import 'home_screen.dart';

void main() => runApp(TuklasFoodApp());
const forest = Color(0xFF173F34),
    cream = Color(0xFFF7F5EB),
    orange = Color(0xFFE87939);

class TuklasFoodApp extends StatefulWidget {
  TuklasFoodApp({super.key, KitchenApi? api}) : api = api ?? KitchenApi();
  final KitchenApi api;
  @override
  State<TuklasFoodApp> createState() => _TuklasFoodAppState();
}

class _TuklasFoodAppState extends State<TuklasFoodApp> {
  bool signedIn = false;
  final navigator = GlobalKey<NavigatorState>();
  @override
  void initState() {
    super.initState();
    widget.api.onUnauthorized = () {
      if (mounted) {
        navigator.currentState?.popUntil((route) => route.isFirst);
        setState(() => signedIn = false);
      }
    };
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'TuklasFood',
    debugShowCheckedModeBanner: false,
    navigatorKey: navigator,
    theme: ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: forest,
        primary: forest,
        secondary: orange,
        surface: cream,
      ),
      scaffoldBackgroundColor: cream,
      appBarTheme: const AppBarTheme(
        backgroundColor: cream,
        foregroundColor: forest,
        centerTitle: false,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
        contentPadding: const EdgeInsets.all(18),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        margin: const EdgeInsets.only(bottom: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(48, 52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
        ),
      ),
      navigationBarTheme: const NavigationBarThemeData(
        backgroundColor: Colors.white,
        indicatorColor: Color(0xFFFBE5C6),
      ),
    ),
    home: signedIn
        ? HomeScreen(
            api: widget.api,
            onLogout: () {
              widget.api.token = null;
              setState(() => signedIn = false);
            },
          )
        : AuthScreen(
            api: widget.api,
            onSignedIn: () => setState(() => signedIn = true),
          ),
  );
}
