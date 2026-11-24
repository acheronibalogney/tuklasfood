// ignore_for_file: avoid_web_libraries_in_flutter, deprecated_member_use

import 'dart:async';
import 'dart:convert';
import 'dart:html' as html;
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

class ImageDropZone extends StatefulWidget {
  const ImageDropZone({
    super.key,
    required this.onImagesDropped,
    required this.onMessage,
  });

  final Future<void> Function(List<XFile> images) onImagesDropped;
  final ValueChanged<String> onMessage;

  @override
  State<ImageDropZone> createState() => _ImageDropZoneState();
}

class _ImageDropZoneState extends State<ImageDropZone> {
  final subscriptions = <StreamSubscription<dynamic>>[];
  html.DivElement? element;
  bool hovering = false;

  @override
  void dispose() {
    for (final subscription in subscriptions) {
      unawaited(subscription.cancel());
    }
    super.dispose();
  }

  void configureElement(Object element) {
    final dropElement = element as html.DivElement;
    this.element = dropElement;
    dropElement
      ..setAttribute('role', 'group')
      ..setAttribute('aria-label', 'Drop ingredient photos here')
      ..style.width = '100%'
      ..style.height = '100%'
      ..style.boxSizing = 'border-box'
      ..style.border = '2px dashed #A9B9AE'
      ..style.borderRadius = '14px'
      ..style.backgroundColor = '#F8F8F0'
      ..style.color = '#36554A'
      ..style.cursor = 'copy'
      ..style.display = 'flex'
      ..style.flexDirection = 'column'
      ..style.alignItems = 'center'
      ..style.justifyContent = 'center'
      ..style.fontFamily = 'Arial, sans-serif'
      ..style.textAlign = 'center'
      ..style.userSelect = 'none';

    final title = html.DivElement()
      ..text = 'Drag ingredient photos here'
      ..style.fontSize = '15px'
      ..style.fontWeight = '600';
    final subtitle = html.DivElement()
      ..text = 'JPG, PNG, or WebP · up to four photos'
      ..style.marginTop = '5px'
      ..style.fontSize = '12px'
      ..style.color = '#66746A';
    dropElement.children
      ..clear()
      ..addAll([title, subtitle]);

    subscriptions.add(
      dropElement.onDragEnter.listen((event) {
        event.preventDefault();
        setHovering(true);
      }),
    );
    subscriptions.add(
      dropElement.onDragOver.listen((event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        setHovering(true);
      }),
    );
    subscriptions.add(
      dropElement.onDragLeave.listen((event) {
        event.preventDefault();
        setHovering(false);
      }),
    );
    subscriptions.add(
      dropElement.onDrop.listen((event) {
        event.preventDefault();
        setHovering(false);
        unawaited(handleDrop(event));
      }),
    );
  }

  void setHovering(bool value) {
    if (!mounted || hovering == value) {
      return;
    }
    setState(() => hovering = value);
    element
      ?..style.borderColor = value ? '#F07836' : '#A9B9AE'
      ..style.backgroundColor = value ? '#FFF1E3' : '#F8F8F0';
  }

  Future<void> handleDrop(html.MouseEvent event) async {
    final files = event.dataTransfer.files;
    if (files == null || files.isEmpty) {
      widget.onMessage('Drop image files here to add them to the scan.');
      return;
    }

    final images = <XFile>[];
    var skippedFiles = 0;
    for (final file in files) {
      if (!_isSupportedImage(file)) {
        skippedFiles++;
        continue;
      }
      if (file.size > 25 * 1024 * 1024) {
        skippedFiles++;
        continue;
      }
      try {
        images.add(await optimizeImage(file));
      } catch (_) {
        skippedFiles++;
      }
    }

    if (!mounted) {
      return;
    }
    if (images.isNotEmpty) {
      await widget.onImagesDropped(images);
    }
    if (skippedFiles > 0) {
      widget.onMessage(
        'Some files were skipped. Drop JPG, PNG, or WebP images under 25 MB.',
      );
    } else if (images.isEmpty) {
      widget.onMessage('No supported image files were found.');
    }
  }

  bool _isSupportedImage(html.File file) {
    final imageMimeType = file.type.toLowerCase().startsWith('image/');
    final imageFileName = RegExp(
      r'\.(jpe?g|png|webp)$',
      caseSensitive: false,
    ).hasMatch(file.name);

    return imageMimeType || imageFileName;
  }

  Future<XFile> optimizeImage(html.File file) async {
    final objectUrl = html.Url.createObjectUrlFromBlob(file);
    try {
      final image = html.ImageElement()..src = objectUrl;
      await image.onLoad.first.timeout(const Duration(seconds: 15));

      final originalWidth = image.naturalWidth;
      final originalHeight = image.naturalHeight;
      if (originalWidth == 0 || originalHeight == 0) {
        throw const FormatException(
          'The dropped file is not a readable image.',
        );
      }

      final scale = math.min(
        1.0,
        1280 / math.max(originalWidth, originalHeight),
      );
      final width = (originalWidth * scale).round();
      final height = (originalHeight * scale).round();
      final canvas = html.CanvasElement(width: width, height: height);
      canvas.context2D.drawImageScaled(image, 0, 0, width, height);
      final encodedImage = canvas.toDataUrl('image/jpeg', 0.8).split(',').last;
      final nameWithoutExtension = file.name.replaceFirst(
        RegExp(r'\.[^.]+$'),
        '',
      );

      return XFile.fromData(
        base64Decode(encodedImage),
        name: '$nameWithoutExtension.jpg',
        mimeType: 'image/jpeg',
      );
    } finally {
      html.Url.revokeObjectUrl(objectUrl);
    }
  }

  @override
  Widget build(BuildContext context) => SizedBox(
    height: 96,
    width: double.infinity,
    child: HtmlElementView.fromTagName(
      tagName: 'div',
      onElementCreated: configureElement,
    ),
  );
}
