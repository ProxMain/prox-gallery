<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\DTO;

/**
 * Data transfer object for tracked uploaded images.
 */
final class TrackedImageDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $mimeType,
        public readonly string $uploadedAt,
        public readonly string $uploadedBy,
        public readonly string $url,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ?int $fileSize,
        public readonly string $file,
        public readonly ?int $createdTimestamp,
        public readonly string $camera,
        public readonly string $aperture,
        public readonly string $focalLength,
        public readonly string $iso,
        public readonly string $shutterSpeed
    ) {
    }

    /**
     * @return array{
     *     id:int,
     *     title:string,
     *     mime_type:string,
     *     uploaded_at:string,
     *     uploaded_by:string,
     *     url:string,
     *     width:int|null,
     *     height:int|null,
     *     file_size:int|null,
     *     file:string,
     *     created_timestamp:int|null,
     *     camera:string,
     *     aperture:string,
     *     focal_length:string,
     *     iso:string,
     *     shutter_speed:string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'mime_type' => $this->mimeType,
            'uploaded_at' => $this->uploadedAt,
            'uploaded_by' => $this->uploadedBy,
            'url' => $this->url,
            'width' => $this->width,
            'height' => $this->height,
            'file_size' => $this->fileSize,
            'file' => $this->file,
            'created_timestamp' => $this->createdTimestamp,
            'camera' => $this->camera,
            'aperture' => $this->aperture,
            'focal_length' => $this->focalLength,
            'iso' => $this->iso,
            'shutter_speed' => $this->shutterSpeed,
        ];
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public static function fromAttachmentId(int $attachmentId, ?array $metadata = null): ?self
    {
        $post = \get_post($attachmentId);

        if (! $post instanceof \WP_Post) {
            return null;
        }

        $resolvedMetadata = $metadata;

        if ($resolvedMetadata === null) {
            $storedMetadata = \wp_get_attachment_metadata($attachmentId);
            $resolvedMetadata = is_array($storedMetadata) ? $storedMetadata : [];
        }

        $imageMeta = [];

        if (isset($resolvedMetadata['image_meta']) && is_array($resolvedMetadata['image_meta'])) {
            $imageMeta = $resolvedMetadata['image_meta'];
        }

        $width = isset($resolvedMetadata['width']) ? (int) $resolvedMetadata['width'] : null;
        $height = isset($resolvedMetadata['height']) ? (int) $resolvedMetadata['height'] : null;
        $fileSize = isset($resolvedMetadata['filesize']) ? (int) $resolvedMetadata['filesize'] : null;
        $file = isset($resolvedMetadata['file']) ? (string) $resolvedMetadata['file'] : '';
        $createdTimestamp = isset($imageMeta['created_timestamp']) ? (int) $imageMeta['created_timestamp'] : null;
        $uploadedBy = '';
        $authorId = (int) $post->post_author;

        if ($authorId > 0) {
            $author = \get_userdata($authorId);

            if ($author instanceof \WP_User) {
                $uploadedBy = $author->display_name !== ''
                    ? (string) $author->display_name
                    : (string) $author->user_login;
            }
        }

        return new self(
            id: $attachmentId,
            title: (string) \get_the_title($attachmentId),
            mimeType: (string) \get_post_mime_type($attachmentId),
            uploadedAt: (string) $post->post_date_gmt,
            uploadedBy: $uploadedBy,
            url: (string) \wp_get_attachment_url($attachmentId),
            width: $width,
            height: $height,
            fileSize: $fileSize,
            file: $file,
            createdTimestamp: $createdTimestamp,
            camera: isset($imageMeta['camera']) ? (string) $imageMeta['camera'] : '',
            aperture: isset($imageMeta['aperture']) ? (string) $imageMeta['aperture'] : '',
            focalLength: isset($imageMeta['focal_length']) ? (string) $imageMeta['focal_length'] : '',
            iso: isset($imageMeta['iso']) ? (string) $imageMeta['iso'] : '',
            shutterSpeed: isset($imageMeta['shutter_speed']) ? (string) $imageMeta['shutter_speed'] : ''
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): ?self
    {
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;

        if ($id <= 0) {
            return null;
        }

        return new self(
            id: $id,
            title: isset($payload['title']) ? (string) $payload['title'] : '',
            mimeType: isset($payload['mime_type']) ? (string) $payload['mime_type'] : '',
            uploadedAt: isset($payload['uploaded_at']) ? (string) $payload['uploaded_at'] : '',
            uploadedBy: isset($payload['uploaded_by']) ? (string) $payload['uploaded_by'] : '',
            url: isset($payload['url']) ? (string) $payload['url'] : '',
            width: isset($payload['width']) ? (int) $payload['width'] : null,
            height: isset($payload['height']) ? (int) $payload['height'] : null,
            fileSize: isset($payload['file_size']) ? (int) $payload['file_size'] : null,
            file: isset($payload['file']) ? (string) $payload['file'] : '',
            createdTimestamp: isset($payload['created_timestamp']) ? (int) $payload['created_timestamp'] : null,
            camera: isset($payload['camera']) ? (string) $payload['camera'] : '',
            aperture: isset($payload['aperture']) ? (string) $payload['aperture'] : '',
            focalLength: isset($payload['focal_length']) ? (string) $payload['focal_length'] : '',
            iso: isset($payload['iso']) ? (string) $payload['iso'] : '',
            shutterSpeed: isset($payload['shutter_speed']) ? (string) $payload['shutter_speed'] : ''
        );
    }
}
