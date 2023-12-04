<?php
namespace NitroPack\SDK\StorageDriver;

use \NitroPack\SDK\FileHandle;

class Disk {
    public function getOsPath($parts) {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    public function deleteFile($path) {
        return @unlink($path);
    }

    public function createDir($dir) {
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }

        return true;
    }

    public function deleteDir($dir) {
        if (!is_dir($dir)) return true;
        return $this->trunkDir($dir) && rmdir($dir);
    }

    public function trunkDir($dir) {
        if (!is_dir($dir)) return true;
        $dh = opendir($dir);
        if ($dh === false) return false;

        while (false !== ($entry = readdir($dh))) {
            if ($entry == "." || $entry == "..") continue;
            $path = $this->getOsPath(array($dir, $entry));
            if (is_dir($path)) {
                if (!$this->deleteDir($path)) {
                    closedir($dh);
                    return false;
                }
            } else {
                if (!unlink($path)) {
                    closedir($dh);
                    return false;
                }
            }
        }
        closedir($dh);

        return true;
    }

    public function isDirEmpty($dir) {
        if (!is_dir($dir)) return false;
        $dh = opendir($dir);
        if ($dh === false) return false;

        $isEmpty = true;
        while (false !== ($entry = readdir($dh))) {
            if ($entry == "." || $entry == "..") continue;
            $isEmpty = false; // first entry which is not "." or ".." means the dir is not empty
            break;
        }
        closedir($dh);

        return $isEmpty;
    }

    public function dirForeach($dir, $callback) {
        if (!is_dir($dir)) return false;
        $dh = opendir($dir);
        if ($dh === false) return false;

        while (false !== ($entry = readdir($dh))) {
            if ($entry == "." || $entry == "..") continue;
            call_user_func($callback, $this->getOsPath(array($dir, $entry)));
        }
        closedir($dh);
        return true;
    }

    public function mtime($filePath) {
        return @filemtime($filePath);
    }

    public function touch($filePath, $time = NULL) {
        if ($time && is_numeric($time)) {
            return @touch($filePath, (int)$time);
        } else {
            return @touch($filePath);
        }
    }

    public function exists($filePath) {
        return file_exists($filePath);
    }

    public function getContent($filePath) {
        return file_get_contents($filePath);
    }

    public function setContent($file, $content) {
        return @file_put_contents($file, $content);
    }

    public function rename($oldName, $newName) {
        return @rename($oldName, $newName);
    }

    public function fopen($file, $mode) {
        $fh = @fopen($file, $mode);
        if ($fh) {
            return new DiskFileHandle($fh);
        } else {
            return false;
        }
    }

    public function fclose($fh) {
        if (!($fh instanceof FileHandle)) return false;
        return @fclose($fh->getHandle());
    }

    public function fflush($fh) {
        if (!($fh instanceof FileHandle)) return false;
        return @fflush($fh->getHandle());
    }

    public function fseek($fh, $offset, $whence = SEEK_SET) {
        if (!($fh instanceof FileHandle)) return false;
        return @fseek($fh->getHandle(), $offset, $whence);
    }

    public function ftell($fh) {
        if (!($fh instanceof FileHandle)) return false;
        return @ftell($fh->getHandle());
    }

    public function fwrite($fh, $string, $length = NULL) {
        if (!($fh instanceof FileHandle)) return false;
        if ($length !== NULL) {
            return @fwrite($fh->getHandle(), $string, $length);
        } else {
            return @fwrite($fh->getHandle(), $string);
        }
    }

    public function fread($fh, $length) {
        if (!($fh instanceof FileHandle)) return false;
        return @fread($fh->getHandle(), $length);
    }

    public function fgetc($fh) {
        if (!($fh instanceof FileHandle)) return false;
        return @fgetc($fh->getHandle());
    }

    public function fgets($fh, $length = NULL) {
        if (!($fh instanceof FileHandle)) return false;
        if ($length !== NULL) {
            return @fgets($fh->getHandle(), $length);
        } else {
            return @fgets($fh->getHandle());
        }
    }

    public function flock($fh, $operation, $wouldblock = NULL) {
        if (!($fh instanceof FileHandle)) return false;
        if ($wouldblock !== NULL) {
            return @flock($fh->getHandle(), $operation, $wouldblock);
        } else {
            return @flock($fh->getHandle(), $operation);
        }
    }

    public function feof($fh) {
        if (!($fh instanceof FileHandle)) return false;
        return @feof($fh->getHandle());
    }
}
