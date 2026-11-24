import 'package:flutter/widgets.dart';
import 'package:image_picker/image_picker.dart';

class ImageDropZone extends StatelessWidget {
  const ImageDropZone({
    super.key,
    required this.onImagesDropped,
    required this.onMessage,
  });

  final Future<void> Function(List<XFile> images) onImagesDropped;
  final ValueChanged<String> onMessage;

  @override
  Widget build(BuildContext context) => const SizedBox.shrink();
}
