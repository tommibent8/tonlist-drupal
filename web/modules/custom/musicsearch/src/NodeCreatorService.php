<?php

namespace Drupal\musicsearch;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles creation of Artist, Album, Track, and Útgefandi nodes.
 */
class NodeCreatorService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected FileSystemInterface $fileSystem;
  protected LoggerInterface $logger;

  protected ?int $lastArtistNid = NULL;
  protected ?int $lastAlbumNid = NULL;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FileSystemInterface $fileSystem,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->logger = $logger;
  }

  /* ===============================================================
     SAFE FILE ITEM BUILDER (Required by Drupal)
     =============================================================== */
  private function buildImageItem(\Drupal\file\Entity\File $file, string $alt): array {
    $metadata = @getimagesize($file->getFileUri());

    return [
      'target_id' => $file->id(),
      'alt'       => $alt ?: 'Image',
      'title'     => '',
      'width'     => $metadata[0] ?? NULL,
      'height'    => $metadata[1] ?? NULL,
    ];
  }

  /* ===============================================================
     IMAGE SAVING (Now returns FULL File entity)
     =============================================================== */
  protected function saveImageFromUrl(string $url): ?\Drupal\file\Entity\File {
    try {
      $data = @file_get_contents($url);
      if (!$data) {
        $this->logger->warning("Could not download image: $url");
        return NULL;
      }

      // Validate image type
      $info = @getimagesizefromstring($data);
      if (!$info) {
        $this->logger->warning("Invalid image data from $url");
        return NULL;
      }

      $directory = 'public://music_images';
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

      $extension = image_type_to_extension($info[2], false); // jpg/png/webp
      $filename = uniqid('img_') . '.' . $extension;

      $uri = "$directory/$filename";

      $saved_path = $this->fileSystem->saveData($data, $uri, FileSystemInterface::EXISTS_RENAME);
      if (!$saved_path) {
        return NULL;
      }

      $file = \Drupal\file\Entity\File::create([
        'uri' => $saved_path,
      ]);
      $file->save();

      return $file;

    } catch (\Exception $e) {
      $this->logger->error("Image save failed for $url — " . $e->getMessage());
      return NULL;
    }
  }

  /* ===============================================================
     GENRE TERM
     =============================================================== */
  protected function getOrCreateGenre(string $name): int {
    $name = trim($name);
    if ($name === '') return 0;

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $existing = $storage->loadByProperties([
      'name' => $name,
      'vid' => 'tegund_tonlistar',
    ]);

    if ($existing) return reset($existing)->id();

    $term = $storage->create([
      'name' => $name,
      'vid'  => 'tegund_tonlistar',
    ]);
    $term->save();

    return $term->id();
  }

  /* ===============================================================
     ÚTGEFANDI NODE
     =============================================================== */
  protected function getOrCreateUtgefandi(string $name): ?int {
    if (!$name) return NULL;

    $storage = $this->entityTypeManager->getStorage('node');

    $existing = $storage->loadByProperties([
      'type' => 'utgefandi',
      'title' => $name,
    ]);

    if ($existing) return reset($existing)->id();

    $node = $storage->create([
      'type' => 'utgefandi',
      'title' => $name,
      'field_utgefandi_nafn' => ['value' => $name],
    ]);

    $node->save();
    return $node->id();
  }

  /* ===============================================================
     ARTIST NODE
     =============================================================== */
  public function createArtist(array $data, array $fields) {
    $values = [
      'type'  => 'listamadur',
      'title' => $data['name'] ?? 'Unknown Artist',
    ];

    if (in_array('name', $fields)) {
      $values['field_nafn'] = ['value' => $data['name']];
    }

    if (in_array('spotify_id', $fields)) {
      $values['field_listamadur_spotify_id'] = $data['id'] ?? '';
    }

    if (in_array('genres', $fields)) {
      $terms = [];
      foreach ($data['genres'] ?? [] as $g) {
        $tid = $this->getOrCreateGenre($g);
        if ($tid) $terms[] = $tid;
      }
      $values['field_tegund_tonlistar'] = $terms;
    }

    if (in_array('website', $fields)) {
      $values['field_website'] = ['uri' => $data['website'] ?? ''];
    }

    if (in_array('description', $fields)) {
      $values['field_lysing_listamadur'] = [
        'value'  => $data['description'] ?? '',
        'format' => 'basic_html',
      ];
    }

    if (in_array('birth', $fields)) {
      $values['field_faedingardagur'] = $data['birth'] ?? '';
    }

    if (in_array('death', $fields)) {
      $values['field_danardagur'] = $data['death'] ?? '';
    }

    /* ---------- SAFE MULTIPLE IMAGE FIELD ---------- */
    if (in_array('images', $fields) && !empty($data['images'])) {
      $items = [];

      foreach ($data['images'] as $url) {
        $file = $this->saveImageFromUrl($url);
        if ($file) {
          $items[] = $this->buildImageItem($file, $data['name'] ?? 'Artist image');
        }
      }

      if ($items) {
        $values['field_myndir'] = $items;
      }
    }

    $node = $this->entityTypeManager->getStorage('node')->create($values);
    $node->save();
    $this->lastArtistNid = $node->id();

    return $node;
  }

  /* ===============================================================
     ALBUM NODE
     =============================================================== */
  public function createAlbum(array $data, $artistNode, array $fields = []) {
    $values = [
      'type'  => 'plata',
      'title' => $data['title'] ?? 'Untitled Album',
    ];

    if (in_array('title', $fields)) {
      $values['field_plata_titill'] = $data['title'] ?? '';
    }

    if (in_array('label', $fields)) {
      $uid = $this->getOrCreateUtgefandi($data['label'] ?? '');
      if ($uid) $values['field_plata_utgefandi'] = ['target_id' => $uid];
    }

    if (in_array('description', $fields)) {
      $values['field_lysing'] = [
        'value'  => $data['description'] ?? '',
        'format' => 'basic_html',
      ];
    }

    if (in_array('release_date', $fields)) {
      $year = substr($data['release_date'] ?? '', 0, 4);
      $values['field_plata_utgafuar'] = $year ?: NULL;
    }

    if (in_array('genres', $fields)) {
      $terms = [];
      foreach ($data['genres'] ?? [] as $g) {
        $tid = $this->getOrCreateGenre($g);
        if ($tid) $terms[] = $tid;
      }
      $values['field_tegund_tonlistar'] = $terms;
    }

    /* ---------- SAFE SINGLE IMAGE FIELD ---------- */
    if (in_array('image', $fields) && !empty($data['image'])) {
      $file = $this->saveImageFromUrl($data['image']);
      if ($file) {
        $values['field_umslagsmynd'] = [
          $this->buildImageItem($file, $data['title'] ?? 'Album image'),
        ];
      }
    }

    $node = $this->entityTypeManager->getStorage('node')->create($values);
    $node->save();
    $this->lastAlbumNid = $node->id();

    return $node;
  }

  /* ===============================================================
     TRACK NODE
     =============================================================== */
  public function createTrack(array $data, $albumNode, array $fields = []) {
    $values = [
      'type'  => 'lag',
      'title' => $data['title'] ?? 'Unnamed Track',
    ];

    if (in_array('title', $fields)) {
      $values['field_lag_titill'] = [
        'value'  => $data['title'] ?? '',
        'format' => 'basic_html',
      ];
    }

    if (in_array('spotify_id', $fields)) {
      $values['field_lag_spotify_id'] = $data['id'] ?? '';
    }

    if (in_array('duration_ms', $fields)) {
      $values['field_lag_lengd'] = ($data['duration_ms'] ?? 0) / 1000 / 60;
    }

    if (in_array('genres', $fields)) {
      $terms = [];
      foreach ($data['genres'] ?? [] as $g) {
        $tid = $this->getOrCreateGenre($g);
        if ($tid) $terms[] = $tid;
      }
      $values['field_tegund_tonlistar_lags'] = $terms;
    }

    $node = $this->entityTypeManager->getStorage('node')->create($values);
    $node->save();

    return $node;
  }

  /* ===============================================================
     HELPERS
     =============================================================== */
  public function getLastCreatedArtist() {
    return $this->lastArtistNid
      ? $this->entityTypeManager->getStorage('node')->load($this->lastArtistNid)
      : NULL;
  }

  public function getLastCreatedAlbum() {
    return $this->lastAlbumNid
      ? $this->entityTypeManager->getStorage('node')->load($this->lastAlbumNid)
      : NULL;
  }
}
