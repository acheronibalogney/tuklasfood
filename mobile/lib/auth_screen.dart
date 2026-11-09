import 'package:flutter/material.dart';

import 'api.dart';
import 'main.dart';

class AuthScreen extends StatefulWidget {
  const AuthScreen({super.key, required this.api, required this.onSignedIn});
  final KitchenApi api;
  final VoidCallback onSignedIn;
  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final form = GlobalKey<FormState>();
  final first = TextEditingController(),
      last = TextEditingController(),
      email = TextEditingController(),
      password = TextEditingController(),
      confirmation = TextEditingController(),
      code = TextEditingController();
  bool register = false, busy = false, hidden = true;
  String? error;
  @override
  void dispose() {
    for (final c in [first, last, email, password, confirmation, code]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> submit() async {
    if (!form.currentState!.validate()) return;
    setState(() {
      busy = true;
      error = null;
    });
    try {
      final result = await widget.api.request(
        'POST',
        'auth/${register ? 'register' : 'login'}',
        {
          'email': email.text.trim(),
          'password': password.text,
          if (register) ...{
            'name': '${first.text.trim()} ${last.text.trim()}',
            'password_confirmation': confirmation.text,
          },
          if (!register && code.text.isNotEmpty) 'code': code.text.trim(),
        },
      );
      if (!mounted) return;
      widget.api.token = result['token'] as String;
      widget.onSignedIn();
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> resetPassword() async {
    if (!email.text.contains('@')) {
      setState(() => error = 'Enter your email address first.');
      return;
    }
    setState(() => busy = true);
    try {
      final result = await widget.api.request('POST', 'auth/forgot-password', {
        'email': email.text.trim(),
      });
      if (mounted) setState(() => error = result['message'] as String);
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Widget field(
    String label,
    TextEditingController controller, {
    bool secret = false,
    bool optional = false,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 14),
    child: TextFormField(
      controller: controller,
      obscureText: secret && hidden,
      enabled: !busy,
      keyboardType: label == 'Email'
          ? TextInputType.emailAddress
          : TextInputType.text,
      autocorrect: !secret,
      enableSuggestions: !secret,
      decoration: InputDecoration(
        labelText: label,
        suffixIcon: secret
            ? IconButton(
                onPressed: () => setState(() => hidden = !hidden),
                icon: Icon(
                  hidden
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined,
                ),
              )
            : null,
      ),
      validator: (value) =>
          !optional && (value ?? '').trim().isEmpty ? 'Enter $label' : null,
    ),
  );
  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 480),
          child: ListView(
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 22),
              const Row(
                children: [
                  Icon(Icons.restaurant_rounded, color: orange, size: 32),
                  SizedBox(width: 10),
                  Text(
                    'TuklasFood',
                    style: TextStyle(
                      color: forest,
                      fontSize: 26,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 36),
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: forest,
                  borderRadius: BorderRadius.circular(28),
                ),
                child: const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'YOUR EVERYDAY KITCHEN',
                      style: TextStyle(
                        color: Color(0xFFF9D45C),
                        letterSpacing: 2,
                        fontSize: 11,
                      ),
                    ),
                    SizedBox(height: 14),
                    Text(
                      'Good food starts\nwith what you have.',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 32,
                        fontWeight: FontWeight.w700,
                        height: 1.12,
                      ),
                    ),
                    SizedBox(height: 14),
                    Text(
                      'Discover Filipino recipes. Keep your pantry in order. Cook with confidence.',
                      style: TextStyle(color: Color(0xFFD4E0D6), height: 1.5),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 28),
              Text(
                register ? 'Create your kitchen' : 'Welcome back',
                style: const TextStyle(
                  fontSize: 26,
                  fontWeight: FontWeight.bold,
                  color: forest,
                ),
              ),
              const SizedBox(height: 18),
              Form(
                key: form,
                child: Column(
                  children: [
                    if (register) ...[
                      field('First name', first),
                      field('Last name', last),
                    ],
                    field('Email', email),
                    field('Password', password, secret: true),
                    if (register)
                      field('Confirm password', confirmation, secret: true),
                    if (!register)
                      field(
                        'Authenticator code (if enabled)',
                        code,
                        optional: true,
                      ),
                  ],
                ),
              ),
              if (error != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: Text(
                    error!,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                ),
              FilledButton(
                onPressed: busy ? null : submit,
                child: Text(
                  busy
                      ? 'Please wait…'
                      : register
                      ? 'Create account'
                      : 'Open my kitchen',
                ),
              ),
              if (!register)
                TextButton(
                  onPressed: busy ? null : resetPassword,
                  child: const Text('Forgot password?'),
                ),
              TextButton(
                onPressed: busy
                    ? null
                    : () => setState(() {
                        register = !register;
                        error = null;
                      }),
                child: Text(
                  register
                      ? 'Already have an account? Sign in'
                      : 'New here? Create an account',
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}
