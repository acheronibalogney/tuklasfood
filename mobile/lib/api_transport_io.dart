import 'dart:convert';
import 'dart:io';

import 'api_transport_types.dart';

Future<ApiTransportResponse> sendApiRequest(
  String method,
  Uri uri,
  Map<String, String> headers,
  String? body,
) async {
  final client = HttpClient()..connectionTimeout = const Duration(seconds: 10);

  try {
    final request = await client.openUrl(method, uri);
    request.followRedirects = false;
    headers.forEach(request.headers.set);
    if (body != null) request.write(body);

    final response = await request.close();
    return ApiTransportResponse(
      statusCode: response.statusCode,
      body: await utf8.decoder.bind(response).join(),
    );
  } on SocketException {
    throw const ApiTransportException('network');
  } on HandshakeException {
    throw const ApiTransportException('secure');
  } finally {
    client.close(force: true);
  }
}
