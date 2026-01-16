# Vision & Description Services

## VisionService (BLIP-2, local)
- Loads `Salesforce/blip-image-captioning-base` lazily; CPU/GPU; ~990MB download; zero API cost.
- `analyze_image(image_url, context="medical", questions=None)`: downloads image, generates caption, detailed description (context-specific question list), extracts tags, optionally answers custom questions (VQA).
- Helper methods: `_download_image` (httpx + temp file), `_generate_caption`, `_generate_detailed_description`, `_answer_question` (conditional generation), `_extract_tags` (keyword-based health tags).

## DescriptionService
- `generate_description(message_id, use_vision=True)`: fetches message via `MessageRepository`; for images with URL and `use_vision`, calls BLIP analysis; videos: TODO frame extraction; others fallback to metadata.
- `analyze_image_with_blip(image_url, caption)`: uses `get_vision_service().analyze_image_sync` (sync wrapper) to build `generated_title`, `generated_description`, `suggested_tags`, combining user caption when present. Falls back to basic metadata on error.
- `generate_file_metadata(filename, caption, file_type)`: heuristic metadata from filename/caption/extension (title/description/tags) without external calls.

## Gaps / Considerations
- Video visual analysis is TODO (only metadata fallback).
- BLIP model download is heavy; ensure cache/warmup for production containers.
- Tags are heuristic; consider controlled vocabulary for analytics.
