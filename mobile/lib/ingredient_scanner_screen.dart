import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';

import 'api.dart';
import 'kitchen.dart';
import 'main.dart';
import 'image_drop_zone_stub.dart'
    if (dart.library.html) 'image_drop_zone_web.dart'
    as drop_zone;

class IngredientScannerScreen extends StatefulWidget {
  const IngredientScannerScreen({super.key, required this.api});

  final KitchenApi api;

  @override
  State<IngredientScannerScreen> createState() =>
      _IngredientScannerScreenState();
}

class _IngredientScannerScreenState extends State<IngredientScannerScreen> {
  final picker = ImagePicker();
  final contextController = TextEditingController();
  final images = <XFile>[];
  final imagePreviews = <Uint8List>[];
  final savedRecommendations = <Json>[];
  Json? result;
  String? error;
  bool scanning = false;
  bool loadingSaved = true;

  @override
  void initState() {
    super.initState();
    loadSavedRecommendations();
  }

  Future<void> loadSavedRecommendations() async {
    try {
      final saved = await widget.api.savedRecommendations();
      if (mounted) {
        setState(() {
          savedRecommendations
            ..clear()
            ..addAll(saved);
          loadingSaved = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => loadingSaved = false);
    }
  }

  Future<void> takePhoto() async {
    if (images.length >= 4) {
      showMessage('You can scan up to four photos at a time.');
      return;
    }

    XFile? image;
    try {
      image = await picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 80,
        maxWidth: 1280,
        maxHeight: 1280,
      );
    } on PlatformException {
      if (mounted) showMessage('Could not open the camera. Please try again.');
      return;
    }
    final capturedImage = image;
    if (capturedImage == null || !mounted) {
      return;
    }
    final previewBytes = await capturedImage.readAsBytes();
    if (!mounted) {
      return;
    }

    setState(() {
      images.add(capturedImage);
      imagePreviews.add(previewBytes);
      result = null;
      error = null;
    });
  }

  Future<void> choosePhotos() async {
    final remaining = 4 - images.length;
    if (remaining <= 0) {
      showMessage('You can scan up to four photos at a time.');
      return;
    }
    List<XFile> selected;
    try {
      selected = await picker.pickMultiImage(
        imageQuality: 80,
        maxWidth: 1280,
        maxHeight: 1280,
      );
    } on PlatformException {
      if (mounted) {
        showMessage('Could not open your photo library. Please try again.');
      }
      return;
    }
    await addPhotos(selected);
  }

  Future<void> addPhotos(List<XFile> selected) async {
    if (!mounted || selected.isEmpty) {
      return;
    }
    final remaining = 4 - images.length;
    if (remaining <= 0) {
      showMessage('You can scan up to four photos at a time.');
      return;
    }
    final acceptedImages = selected.take(remaining).toList();
    final previewBytes = await Future.wait(
      acceptedImages.map((image) => image.readAsBytes()),
    );
    if (!mounted) {
      return;
    }

    setState(() {
      images.addAll(acceptedImages);
      imagePreviews.addAll(previewBytes);
      result = null;
      error = null;
    });
    if (selected.length > remaining) {
      showMessage('Only four photos can be scanned at once.');
    }
  }

  Future<void> scan() async {
    if (images.isEmpty || scanning) return;
    setState(() {
      scanning = true;
      error = null;
      result = null;
    });
    try {
      final response = await widget.api.scanIngredients(
        images,
        context: contextController.text,
      );
      if (mounted) {
        setState(() => result = response);
        await loadSavedRecommendations();
      }
    } catch (exception) {
      if (mounted) setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => scanning = false);
    }
  }

  void showMessage(String message) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  void dispose() {
    contextController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => ListView(
    padding: const EdgeInsets.all(20),
    children: [
      const Text(
        'Scan your ingredients',
        style: TextStyle(
          fontSize: 28,
          fontWeight: FontWeight.w800,
          color: forest,
        ),
      ),
      const SizedBox(height: 8),
      const Text(
        'Take a photo or choose up to four. Photos are sent to Google Gemini for ingredient identification and meal ideas.',
        style: TextStyle(color: Color(0xFF66746A), height: 1.5),
      ),
      const SizedBox(height: 18),
      Row(
        children: [
          Expanded(
            child: FilledButton.icon(
              onPressed: scanning ? null : takePhoto,
              icon: const Icon(Icons.camera_alt_outlined),
              label: const Text('Take photo'),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: OutlinedButton.icon(
              onPressed: scanning ? null : choosePhotos,
              icon: const Icon(Icons.photo_library_outlined),
              label: const Text('Choose photos'),
            ),
          ),
        ],
      ),
      if (kIsWeb) ...[
        const SizedBox(height: 12),
        drop_zone.ImageDropZone(
          onImagesDropped: addPhotos,
          onMessage: showMessage,
        ),
      ],
      if (images.isNotEmpty) ...[
        const SizedBox(height: 14),
        SizedBox(
          height: 96,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: images.length,
            separatorBuilder: (_, _) => const SizedBox(width: 10),
            itemBuilder: (context, index) => Stack(
              clipBehavior: Clip.none,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(14),
                  child: Image.memory(
                    imagePreviews[index],
                    width: 96,
                    height: 96,
                    fit: BoxFit.cover,
                  ),
                ),
                Positioned(
                  top: -7,
                  right: -7,
                  child: IconButton.filledTonal(
                    visualDensity: VisualDensity.compact,
                    onPressed: scanning
                        ? null
                        : () => setState(() {
                            images.removeAt(index);
                            imagePreviews.removeAt(index);
                          }),
                    icon: const Icon(Icons.close, size: 16),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
      const SizedBox(height: 16),
      TextField(
        controller: contextController,
        maxLength: 500,
        maxLines: 2,
        decoration: const InputDecoration(
          labelText: 'Anything else to consider?',
          hintText: 'For example: quick dinner, two servings, mild spice',
        ),
      ),
      const SizedBox(height: 8),
      FilledButton.icon(
        onPressed: images.isEmpty || scanning ? null : scan,
        icon: scanning
            ? const SizedBox.square(
                dimension: 18,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            : const Icon(Icons.auto_awesome),
        label: Text(
          scanning ? 'Looking at your photos…' : 'Identify ingredients',
        ),
      ),
      if (error != null) ...[
        const SizedBox(height: 14),
        Text(
          error!,
          style: TextStyle(color: Theme.of(context).colorScheme.error),
        ),
      ],
      if (result != null) ...[
        const SizedBox(height: 24),
        Text(
          result!['summary']?.toString() ?? 'Here is what I can see.',
          style: const TextStyle(color: forest, height: 1.5),
        ),
        const SizedBox(height: 16),
        ...rows(result!['ingredients']).map(
          (item) => Card(
            child: ListTile(
              leading: const Icon(Icons.eco_outlined, color: forest),
              title: Text(item['name']?.toString() ?? 'Food item'),
              subtitle: Text(
                '${item['confidence'] ?? 'uncertain'} confidence · ${item['visual_notes'] ?? ''}\n${item['insights'] ?? ''}',
              ),
              isThreeLine: true,
            ),
          ),
        ),
        if (rows(result!['meal_ideas']).isNotEmpty) ...[
          const Padding(
            padding: EdgeInsets.only(top: 12, bottom: 8),
            child: Text(
              'Ideas for what to cook',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: forest,
              ),
            ),
          ),
          ...rows(result!['meal_ideas']).map(recipeCard),
        ],
        const Padding(
          padding: EdgeInsets.only(top: 10),
          child: Text(
            'Photo identification can be uncertain. Check ingredients and food safety yourself.',
            style: TextStyle(fontSize: 12, color: Color(0xFF66746A)),
          ),
        ),
      ],
      if (savedRecommendations.isNotEmpty) ...[
        const Padding(
          padding: EdgeInsets.only(top: 22, bottom: 8),
          child: Text(
            'Saved recommendations',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: forest,
            ),
          ),
        ),
        ...savedRecommendations.map(
          (saved) => Card(
            child: ExpansionTile(
              leading: const Icon(Icons.bookmark_outline, color: orange),
              title: Text(saved['summary']?.toString() ?? 'Saved meal ideas'),
              subtitle: Text(
                saved['created_at']?.toString().split('T').first ?? '',
              ),
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  child: Column(
                    children: rows(saved['meal_ideas'])
                        .map(recipeCard)
                        .toList(),
                  ),
                ),
              ],
            ),
          ),
        ),
      ] else if (loadingSaved) ...[
        const Padding(
          padding: EdgeInsets.all(16),
          child: Center(child: CircularProgressIndicator()),
        ),
      ],
    ],
  );

  Widget recipeCard(Json idea) => Card(
    margin: const EdgeInsets.only(bottom: 10),
    child: ExpansionTile(
      leading: const Icon(Icons.restaurant, color: orange),
      title: Text(idea['name']?.toString() ?? 'Meal idea'),
      subtitle: Text(
        '${idea['reason'] ?? ''}\n${idea['servings'] ?? 2} servings · '
        '${idea['prep_minutes'] ?? 0} min prep · ${idea['cook_minutes'] ?? 0} min cook',
      ),
      childrenPadding: const EdgeInsets.fromLTRB(18, 0, 18, 16),
      expandedCrossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Ingredients',
          style: TextStyle(fontWeight: FontWeight.bold, color: forest),
        ),
        const SizedBox(height: 6),
        ...rows(idea['ingredients']).map(
          (ingredient) => Padding(
            padding: const EdgeInsets.only(bottom: 4),
            child: Text(
              '• ${ingredient['quantity'] ?? ''} ${ingredient['name'] ?? ''}'
              '${ingredient['optional'] == true ? ' (optional)' : ''}',
            ),
          ),
        ),
        if ((idea['tools'] as List? ?? []).isNotEmpty) ...[
          const SizedBox(height: 10),
          Text('Tools: ${(idea['tools'] as List).join(', ')}'),
        ],
        const SizedBox(height: 12),
        const Text(
          'Steps',
          style: TextStyle(fontWeight: FontWeight.bold, color: forest),
        ),
        const SizedBox(height: 6),
        ...((idea['steps'] as List? ?? []).asMap().entries.map(
          (entry) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Text('${entry.key + 1}. ${entry.value}'),
          ),
        )),
      ],
    ),
  );
}
