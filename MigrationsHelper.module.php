<?php

namespace ProcessWire;

class MigrationsHelper extends WireData implements Module {
  public static function getModuleInfo() {
    return array(
      'title' => 'Migrations Helper',
      'version' => 200,
      'summary' => 'Helper module for managing migrations via RockMigrations.',
      'icon' => 'refresh',
      'author' => "Ivan Milincic",
      "href" => "https://kreativan.dev",
      'singular' => true,
      'autoload' => false,
      'requires' => ['ProcessWire>=3.0.0', 'RockMigrations'],
    );
  }

  public function __construct() {
  }

  public function init() {
  }

  public function run($moduleFolderName) {

    if (!$this->wire()->modules->isInstalled('RockMigrations')) {
      return;
    }

    if (!$this->wire()->modules->isInstalled('Autoloader')) {
      return;
    }

    $rm = $this->wire()->modules->get('RockMigrations');

    $moduleFolderPath = $this->wire()->config->paths->siteModules . $moduleFolderName . '/';

    if (!is_dir($moduleFolderPath . 'migrations')) {
      return;
    }

    $roles = [];
    $fields = [];
    $templates = [];
    $pages = [];
    $permissions = [];

    if (file_exists($moduleFolderPath . '/migrations/roles.php')) {
      $roles = require $moduleFolderPath . '/migrations/roles.php';
    }

    if (file_exists($moduleFolderPath . '/migrations/fields.php')) {
      $fields = require $moduleFolderPath . '/migrations/fields.php';
    }

    if (file_exists($moduleFolderPath . '/migrations/templates.php')) {
      $templates = require $moduleFolderPath . '/migrations/templates.php';
    }

    if (file_exists($moduleFolderPath . '/migrations/pages.php')) {
      $pages = require $moduleFolderPath . '/migrations/pages.php';
    }

    if (file_exists($moduleFolderPath . '/migrations/permissions.php')) {
      $permissions = require $moduleFolderPath . '/migrations/permissions.php';
    }

    // Fields, templates and roles
    $rm->migrate([
      'fields' => $fields,
      'templates' => $templates,
      'roles' => $roles,
    ]);

    // Pages
    $this->rmCreatePages($pages);

    // Permissions
    foreach ($permissions as $name => $title) {
      $rm->createPermission($name, $title);
    }
  }

  public function rmCreatePages($pages) {
    $rm = $this->wire()->modules->get('RockMigrations');
    if (!$rm) {
      $this->error("RockMigrations module not found.");
      return;
    }
    foreach ($pages as $key => $pageData) {
      $page = $this->wire()->pages->get($pageData['parent'] . $pageData['name'] . '/');
      $parent = $this->wire()->pages->get($pageData['parent']);
      $data = $pageData['data'] ?? [];
      if (!$page->id && $parent->id) {
        $rm->createPage(
          $pageData['template'],
          $this->wire()->pages->get($pageData['parent']),
          $pageData['name'],
          $pageData['title'],
          null, // status
          $data, // fields
          true, // All languages
        );
      }
    }
  }
}
