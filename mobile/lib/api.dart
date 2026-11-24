import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:image_picker/image_picker.dart';

import 'api_transport_stub.dart'
    if (dart.library.io) 'api_transport_io.dart'
    if (dart.library.html) 'api_transport_web.dart'
    as transport;
import 'api_transport_types.dart';
import 'session_token_store_stub.dart'
    if (dart.library.html) 'session_token_store_web.dart'
    as session_token_store;

String _defaultApiBaseUrl() {
  const configuredBaseUrl = String.fromEnvironment('API_BASE');
  if (configuredBaseUrl.isNotEmpty) {
    return configuredBaseUrl;
  }
  if (kIsWeb) {
    final hostname = Uri.base.host;
    if (hostname == 'localhost' ||
        hostname == '127.0.0.1' ||
        hostname == '::1') {
      return 'http://127.0.0.1:8000/api/v1';
    }
    return 'https://3.24.169.79/api/v1';
  }
  if (defaultTargetPlatform == TargetPlatform.android) {
    return 'http://10.0.2.2:8000/api/v1';
  }
  return 'http://127.0.0.1:8000/api/v1';
}

class ApiException implements Exception {
  final String message;
  final int status;
  const ApiException(this.message, [this.status = 0]);
  @override
  String toString() => message;
}

class KitchenApi {
  KitchenApi({String? baseUrl}) : baseUrl = baseUrl ?? _defaultApiBaseUrl();
  final String baseUrl;
  String? _token;
  String? get token => _token;
  set token(String? value) {
    _token = value;
    session_token_store.saveToken(value);
  }

  void restoreSession() {
    _token = session_token_store.readToken();
  }

  void Function()? onUnauthorized;

  Future<Map<String, dynamic>> scanIngredients(
    List<XFile> images, {
    String? context,
  }) async {
    final encodedImages = <Map<String, String>>[];
    var totalBytes = 0;

    for (final image in images) {
      final bytes = await image.readAsBytes();
      totalBytes += bytes.length;
      if (totalBytes > 3000000) {
        throw const ApiException(
          'Choose fewer or smaller photos. The combined upload limit is 3 MB.',
        );
      }
      encodedImages.add({
        'mime_type': _imageMimeType(bytes),
        'data': base64Encode(bytes),
      });
    }

    return request('POST', 'ingredient-scan', {
      'images': encodedImages,
      if (context?.trim().isNotEmpty ?? false) 'context': context!.trim(),
    });
  }

  Future<List<Map<String, dynamic>>> savedRecommendations() async {
    final response = await request('GET', 'recommendations');
    return (response['recommendations'] as List? ?? [])
        .map((item) => Map<String, dynamic>.from(item as Map))
        .toList();
  }

  String _imageMimeType(List<int> bytes) {
    if (bytes.length >= 3 &&
        bytes[0] == 0xff &&
        bytes[1] == 0xd8 &&
        bytes[2] == 0xff) {
      return 'image/jpeg';
    }
    if (bytes.length >= 8 &&
        bytes[0] == 0x89 &&
        bytes[1] == 0x50 &&
        bytes[2] == 0x4e &&
        bytes[3] == 0x47 &&
        bytes[4] == 0x0d &&
        bytes[5] == 0x0a &&
        bytes[6] == 0x1a &&
        bytes[7] == 0x0a) {
      return 'image/png';
    }
    if (bytes.length >= 12 &&
        String.fromCharCodes(bytes.sublist(0, 4)) == 'RIFF' &&
        String.fromCharCodes(bytes.sublist(8, 12)) == 'WEBP') {
      return 'image/webp';
    }
    throw const ApiException(
      'This photo format is unsupported. Choose a JPEG, PNG, or WebP image.',
    );
  }

  Future<Map<String, dynamic>> request(
    String method,
    String path, [
    Map<String, dynamic>? body,
    Duration timeout = const Duration(seconds: 100),
  ]) async {
    try {
      final headers = <String, String>{
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
        if (body != null) 'Content-Type': 'application/json',
      };
      final response = await transport
          .sendApiRequest(
            method,
            Uri.parse('${baseUrl.replaceFirst(RegExp(r'/+$'), '')}/$path'),
            headers,
            body == null ? null : jsonEncode(body),
          )
          .timeout(timeout);
      Map<String, dynamic> data;
      try {
        data = jsonDecode(response.body) as Map<String, dynamic>;
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
        final validationMessages = errors?.values
            .expand((value) => value is List ? value : [value])
            .map((value) => value.toString())
            .where((message) => message.isNotEmpty)
            .join('\n');
        throw ApiException(
          validationMessages?.isNotEmpty == true
              ? validationMessages!
              : data['message']?.toString() ??
                    'Unable to complete this request.',
          response.statusCode,
        );
      }
      return data;
    } on ApiTransportException catch (exception) {
      if (exception.kind == 'secure') {
        throw const ApiException(
          'A secure connection could not be established.',
        );
      }
      if (exception.kind == 'unsupported') {
        throw const ApiException(
          'This platform does not support API requests.',
        );
      }
      throw const ApiException(
        'Cannot reach the API. Check the server, HTTPS certificate, and web CORS settings.',
      );
    } on TimeoutException {
      throw const ApiException('The server took too long. Please try again.');
    }
  }
}
