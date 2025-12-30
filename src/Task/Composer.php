<?php

namespace Abstract\Task;

use Composer\Script\Event;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Composer {
  /**
   * @param Event $event Composer event passed in for any script method
   * @param Filesystem $filesystem Optional. Used for testing.
   */
  public static function cleanup(
    Event $event,
    Filesystem $filesystem = null
  ) {
    $composer = $event->getComposer();
    $extra = $composer->getPackage()->getExtra();
    $servicesToKeep = isset($extra['manifestation'])
      ? $extra['manifestation']
      : [];
    if ($servicesToKeep) {
      $vendorDir = $composer->getConfig()->get('vendor-dir');
      $serviceDir = sprintf(
        '%s/abstract/src/Mapper',
        $vendorDir
      );
      self::verifyServicesToKeep($serviceDir, $servicesToKeep);
      $finder = self::getServicesToRemove($serviceDir, $servicesToKeep);
      $filesystem = $filesystem ?: new Filesystem();
      if (0 !== $count = count($finder)) {
        $event->getIO()->write(
          sprintf('Removing %s google services', $count)
        );
        foreach ($finder as $file) {
          $realpath = $file->getRealPath();
          $filesystem->remove($realpath);
          $filesystem->remove($realpath . '.php');
        }
      }
    }
  }

  /**
   * @throws InvalidArgumentException when the service doesn't exist
   */
  private static function verifyServicesToKeep(
    $serviceDir,
    array $servicesToKeep
  ) {
    $finder = (new Finder())
      ->directories()
      ->depth('== 0');

    foreach ($servicesToKeep as $service) {
      if (!preg_match('/^[a-zA-Z0-9]*$/', $service)) {
        throw new InvalidArgumentException(
          sprintf(
            'Invalid Mapper name "%s"',
            $service
          )
        );
      }
      try {
        $finder->in($serviceDir . '/' . $service);
      } catch (InvalidArgumentException $e) {
        throw new InvalidArgumentException(
          sprintf(
            'Mapper "%s" does not exist or was removed previously',
            $service
          )
        );
      }
    }
  }

  private static function getServicesToRemove(
    $serviceDir,
    array $servicesToKeep
  ) {
    // find all files in the current directory
    return (new Finder())
      ->directories()
      ->depth('== 0')
      ->in($serviceDir)
      ->exclude($servicesToKeep);
  }
}
