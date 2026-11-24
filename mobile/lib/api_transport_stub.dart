import 'api_transport_types.dart';

Future<ApiTransportResponse> sendApiRequest(
  String method,
  Uri uri,
  Map<String, String> headers,
  String? body,
) {
  throw const ApiTransportException('unsupported');
}
