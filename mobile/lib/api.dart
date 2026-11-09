import 'dart:async';
import 'dart:convert';
import 'dart:io';

class ApiException implements Exception {
  final String message;
  final int status;
  const ApiException(this.message, [this.status = 0]);
  @override
  String toString() => message;
}

class KitchenApi {
  KitchenApi({String? baseUrl})
    : baseUrl =
          baseUrl ??
          const String.fromEnvironment(
            'API_BASE',
            defaultValue: 'http://10.0.2.2:8000/api/v1',
          );
  final String baseUrl;
  String? token;
  void Function()? onUnauthorized;

  Future<Map<String, dynamic>> request(
    String method,
    String path, [
    Map<String, dynamic>? body,
  ]) async {
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 10);
    try {
      return await (() async {
        final request = await client.openUrl(
          method,
          Uri.parse('$baseUrl/$path'),
        );
        request.followRedirects = false;
        request.headers.set(HttpHeaders.acceptHeader, 'application/json');
        if (token != null) {
          request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
        }
        if (body != null) {
          request.headers.contentType = ContentType.json;
          request.write(jsonEncode(body));
        }
        final response = await request.close();
        final text = await utf8.decoder.bind(response).join();
        Map<String, dynamic> data;
        try {
          data = jsonDecode(text) as Map<String, dynamic>;
        } catch (_) {
          throw ApiException(
            'Unexpected server response. Check the API address.',
            response.statusCode,
          );
        }
        if (response.statusCode >= 400) {
          if (response.statusCode == 401) {
            token = null;
            onUnauthorized?.call();
          }
          final errors = data['errors'] as Map<String, dynamic>?;
          throw ApiException(
            errors != null
                ? errors.values.expand((v) => v as List).join('\n')
                : data['message']?.toString() ??
                      'Unable to complete this request.',
            response.statusCode,
          );
        }
        return data;
      })().timeout(const Duration(seconds: 40));
    } on SocketException {
      throw const ApiException(
        'Cannot reach your kitchen. Check your connection and server address.',
      );
    } on TimeoutException {
      throw const ApiException('The server took too long. Please try again.');
    } on HandshakeException {
      throw const ApiException('A secure connection could not be established.');
    } finally {
      client.close(force: true);
    }
  }
}
