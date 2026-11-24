// ignore_for_file: avoid_web_libraries_in_flutter, deprecated_member_use

import 'dart:html' as html;

const _tokenKey = 'tuklasfood.session-token';

String? readToken() {
  try {
    return html.window.sessionStorage[_tokenKey];
  } catch (_) {
    return null;
  }
}

void saveToken(String? token) {
  try {
    final storage = html.window.sessionStorage;
    if (token == null || token.isEmpty) {
      storage.remove(_tokenKey);
      return;
    }
    storage[_tokenKey] = token;
  } catch (_) {
    // Keep the in-memory session usable when browser storage is unavailable.
  }
}
