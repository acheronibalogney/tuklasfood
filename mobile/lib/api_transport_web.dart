// ignore_for_file: avoid_web_libraries_in_flutter, deprecated_member_use

import 'dart:async';
import 'dart:html' as html;

import 'api_transport_types.dart';

Future<ApiTransportResponse> sendApiRequest(
  String method,
  Uri uri,
  Map<String, String> headers,
  String? body,
) async {
  final request = html.HttpRequest();
  final completer = Completer<ApiTransportResponse>();

  try {
    request.open(method, uri.toString());
    headers.forEach(request.setRequestHeader);

    request.onLoad.listen((_) {
      if (!completer.isCompleted) {
        completer.complete(
          ApiTransportResponse(
            statusCode: request.status ?? 0,
            body: request.responseText ?? '',
          ),
        );
      }
    });

    request.onError.listen((_) {
      if (!completer.isCompleted) {
        completer.completeError(const ApiTransportException('network'));
      }
    });

    request.onAbort.listen((_) {
      if (!completer.isCompleted) {
        completer.completeError(const ApiTransportException('network'));
      }
    });

    request.send(body);

    return await completer.future;
  } catch (_) {
    throw const ApiTransportException('network');
  }
}
