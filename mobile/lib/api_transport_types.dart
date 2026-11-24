class ApiTransportResponse {
  const ApiTransportResponse({required this.statusCode, required this.body});

  final int statusCode;
  final String body;
}

class ApiTransportException implements Exception {
  const ApiTransportException(this.kind);

  final String kind;
}
