<?php
// Disclaimer mtimes are only accurate for the final entries. But mtimes for parent directories (more than 1 level up the hierarchy) might not be updated correctly when children have been modified
// This is also a bad design for emulating a file system performance wise. Only use this driver when you need shared storage between multiple servers. On a single server with an SSD using the Disk diver is a better idea.
namespace NitroPack\SDK\StorageDriver;

use \NitroPack\SDK\FileHandle;

class Redis {
    const LOCK_TTL = 30000;
    const LOCK_TIMEOUT = 30;
    private $redis;

    private function preparePathInput($path) {
        return $path == DIRECTORY_SEPARATOR ? $path : rtrim($path, DIRECTORY_SEPARATOR);
    }

    public function __construct($host = "127.0.0.1", $port = 6379, $password = NULL, $db = NULL) {
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);

        if ($password !== NULL) {
            $this->redis->auth($password);
        }

        if ($db !== NULL) {
            $this->redis->select($db);
        }
    }

    public function getOsPath($parts) {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    public function touch($path, $time = NULL) {
        if ($time === NULL || !is_numeric($time)) $time = time();
        $path = $this->preparePathInput($path);
        $parent = dirname($path);
        $key = basename($path);
        if ($this->isDir($parent)) {
            $this->redis->hSet($parent, "::mtime::" . $key, (int)$time);
            $this->redis->hSetNx($parent, "::content::" . $key, "");
            return true;
        } else {
            return false;
        }
    }

    public function setContent($path, $content) {
        $path = $this->preparePathInput($path);
        if ($this->isDir($path)) {
            return false;
        } else {
            try {
                //TODO: Create parent dir if it doesn't exist. This can impact performance though. Maybe make it optional
                $dir = dirname($path);
                $file = basename($path);
                $this->redis->hMSet($dir, array(
                    "::content::" . $file => $content,
                    "::mtime::" . $file => time()
                ));
            } catch (\Exception $e) {
                return false;
            }
            return true;
        }
    }

    public function createDir($dir) {
        $dir = $this->preparePathInput($dir);
        $now = time();
        $childDir = NULL;
        $numDirsCreated = 0;
        try {
            while ($childDir !== "" && !$this->exists($dir)) {
                $this->redis->hSet($dir, "::self::ctime::", $now);
                $numDirsCreated++;
                if ($childDir) {
                    $this->touch($this->getOsPath(array($dir, $childDir)));
                }
                $childDir = basename($dir);
                $dir = dirname($dir);
            }
            if ($numDirsCreated > 0 && $childDir) {
                $this->touch($this->getOsPath(array($dir, $childDir)));
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function deletePath($path) {
        $path = $this->preparePathInput($path);
        $dirKey = dirname($path);
        $fileName = basename($path);

        try {
            $deleted = $this->redis->hDel($dirKey, "::content::" . $fileName, "::mtime::" . $fileName);
            if ($deleted) {
                $this->touch($dirKey);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function deleteFile($path) {
        return !$this->isDir($path) && $this->deletePath($path);
    }

    public function deleteDir($dir) {
        $dir = $this->preparePathInput($dir);
        try {
            if (!$this->isDir($dir)) return true;
            $this->trunkDir($dir) && $this->redis->unlink($dir) && $this->deletePath($dir);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function trunkDir($dir) {
        $dir = $this->preparePathInput($dir);
        if (!$this->isDir($dir)) return false;

        if ($dir == DIRECTORY_SEPARATOR) {
            $osPath = DIRECTORY_SEPARATOR . "*";
        } else {
            $osPath = $this->getOsPath(array($dir, "*"));
        }

        $success = false;
        try {
            $this->redis->eval('
local cursor = "0";
repeat
    local t = redis.call("SCAN", cursor, "MATCH", ARGV[1]);
    cursor = t[1];
    local list = t[2];
    for i = 1, #list do
        redis.call("UNLINK", list[i]);
    end;
until cursor == "0";
            ', array($osPath), 0);
            $success = true;
        } catch (\Exception $e) {
            // TODO: Log an error
        }
        return $success;
    }

    public function isDirEmpty($dir) {
        $dir = $this->preparePathInput($dir);
        return (int)$this->redis->hLen($dir) <= 1;
    }

    private function isDir($dir) {
        $dir = $this->preparePathInput($dir);
        return !!$this->redis->hLen($dir); // if this is a non-empty sorted set then it is a dir
    }

    public function dirForeach($dir, $callback) {
        $dir = $this->preparePathInput($dir);
        if (!$this->isDir($dir)) return false;
        $result = true;
        $it = NULL;
        $prevScanMode = $this->redis->getOption(\Redis::OPT_SCAN);
        $this->redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
        try {
            while($entries = $this->redis->hScan($dir, $it, "::mtime::*")) {
                foreach($entries as $entry => $mtime) {
                    $entry = substr($entry, 9);//remove the ::mtime:: prefix
                    $path = $dir != DIRECTORY_SEPARATOR ? $this->getOsPath(array($dir, $entry)) : $dir . $entry;
                    call_user_func($callback, $path);
                }
            }
        } catch (\Exception $e) {
            // TODO: Log an error
            $result = false;
        } finally {
            $this->redis->setOption(\Redis::OPT_SCAN, $prevScanMode);
        }
        return $result;
    }

    public function mtime($path) {
        $path = $this->preparePathInput($path);
        $dir = dirname($path);
        $file = basename($path);
        return $this->redis->hGet($dir, "::mtime::" . $file);
    }

    public function exists($path) {
        $path = $this->preparePathInput($path);
        $dir = dirname($path);
        $file = basename($path);
        return $this->redis->hExists($dir, "::mtime::" . $file);
    }

    public function getContent($path) {
        $path = $this->preparePathInput($path);
        if ($this->isDir($path)) {
            return false;
        } else {
            $dir = dirname($path);
            $file = basename($path);
            return $this->redis->hGet($dir, "::content::" . $file);
        }
    }

    public function rename($oldKey, $newKey, $innerCall = false) {
        $oldKey = $this->preparePathInput($oldKey);
        $newKey = $this->preparePathInput($newKey);
        if ($this->exists($newKey)) return false;

        $success = false;

        try {
            $isDir = $this->isDir($oldKey);
            if (!$isDir) {
                $content = $this->getContent($oldKey);
                $this->deleteFile($oldKey);
                $this->setContent($newKey, $content);
            } else {
                $this->deletePath($oldKey);
                $this->createDir($newKey);
                $this->redis->rename($oldKey, $newKey);
                $this->redis->eval('
local cursor = "0";
repeat
    local t = redis.call("SCAN", cursor, "MATCH", ARGV[1]);
    cursor = t[1];
    local list = t[2];
    for i = 1, #list do
        local s = list[i];
        local changed = s:gsub(ARGV[2], ARGV[3], 1);
        redis.call("RENAME", s, changed);
    end;
until cursor == "0";
                ', array($this->getOsPath(array($oldKey, "*")), $this->prepareForLuaPattern($oldKey . DIRECTORY_SEPARATOR), $newKey . DIRECTORY_SEPARATOR), 0);
            }

            $success = true;
        } catch (\Exception $e) {
            // TODO: Log an error
        }
        return $success;
    }

    private function prepareForLuaPattern($pattern) {
        $specialPatternChars = array("%", "(", ")", ".", "+", "-", "*", "?", "[", "^", "$");
        $regex = "/(" . implode("|", array_map("preg_quote", $specialPatternChars)) . ")/";
        return preg_replace($regex, "%$1", $pattern);
        //return str_replace(array("%", "(", ")", ".", "+", "-", "*", "?", "[", "^", "$"), array("%%", "%(", "%)", "%.", "%+", "%-", "%*", "%?", "%[", "%^", "%$"), $pattern);
    }
    
    public function fopen($file, $mode) {
        $fh = new \stdClass();
        $fh->pos = 0;
        $fh->content = "";
        $fh->canRead = in_array($mode, array("r", "r+", "w+", "a+", "x+", "c+"));
        $fh->canWrite = in_array($mode, array("r+", "w", "w+", "a", "a+", "x", "x+", "c", "c+"));
        $fh->mode = $mode;
        $fh->writeOccurred = false;
        $fh->isOpen = true;
        $fh->path = $file;

        switch ($mode) {
        case "r":
        case "r+":
            if (!$this->exists($file)) return false;
            $fh->content = $this->getContent($file);
            break;
        case "w":
        case "w+":
            // Do nothing
            break;
        case "a":
        case "a+":
            if (!$this->exists($file)) return false;
            $fh->content = $this->getContent($file);
            break;
        case "x":
        case "x+":
            if ($this->exists($file)) return false;
            break;
        case "c":
        case "c+":
            if ($this->exists($file)) {
                $fh->content = $this->getContent($file);
            }
            break;
        }

        return new RedisFileHandle($fh);
    }

    public function fclose($handle) {
        if (!($handle instanceof FileHandle)) return false;
        $fh = $handle->getHandle();
        if (!$fh->isOpen) return true;

        if ($fh->canWrite && $fh->writeOccurred) {
            $status = $this->fflush($handle);
            if ($status) {
                $fh->isOpen = false;
                $fh->canRead = false;
                $fh->canWrite = false;
            }
            return $status;
        }

        return true;
    }

    public function fflush($fh) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        if ($fh->canWrite && $fh->writeOccurred && $fh->isOpen) {
            if ($this->setContent($fh->path, $fh->content)) {
                $fh->writeOccurred = false; // Reset the counter
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function fseek($fh, $offset, $whence = SEEK_SET) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        switch ($whence) {
        case SEEK_CUR:
            $fh->pos += $offset;
            break;
        case SEEK_END:
            $fh->pos = strlen($fh->content) + $offset;
            break;
        default:
            $fh->pos = $offset;
            break;
        }

        return 0;
    }

    public function ftell($fh) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        return $fh->pos;
    }

    public function fwrite($fh, $string, $length = NULL) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        if (!$fh->canWrite) return false;

        if ($length === NULL || $length > strlen($string)) {
            $length = strlen($string);
        }

        if ($fh->mode[0] == "a" || $fh->pos > strlen($fh->content)) {
            $fh->content .= substr($string, 0, $length);
            $fh->pos = strlen($fh->content);
        } else {
            $head = substr($fh->content, 0, $fh->pos);
            $tail = substr($fh->content, $fh->pos + $length);
            $fh->content = $head . substr($string, 0, $length) . $tail;
            $fh->pos = strlen($head) + $length;
        }

        $fh->writeOccurred = true;

        return $length;
    }

    public function fread($fh, $length) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        if (!$fh->canRead) return false;

        $result = substr($fh->content, $fh->pos, $length);
        $fh->pos += strlen($result);//$length;

        return $result;
    }

    public function fgetc($fh) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        if (!$fh->canRead) return false;

        return $fh->content[$fh->pos++];
    }

    public function fgets($fh, $length = NULL) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        if (!$fh->canRead) return false;

        if ($length === NULL) {
            $length = strlen($fh->content);
        }

        $pos = strpos($fh->content, "\n", $fh->pos);
        if ($pos === false) {
            if ($fh->pos >= strlen($fh->content)) return false;
            $result = substr($fh->content, $fh->pos, $length);
        } else {
            $result = substr($fh->content, $fh->pos, $pos - $fh->pos+1);
        }
        $fh->pos += strlen($result);
        return $result;
    }

    public function flock($fh, $operation, $wouldblock = NULL) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        if (!$fh->isOpen) return false;
        switch ($operation) {
        case LOCK_SH:
            // In this case we acquire a lock in order to wait for any other process that has currently locked the file
            // And then release the lock immediately.
            // The sole purpose of this is to block until the writer process is complete.
            return $this->acquireLock($fh->path, self::LOCK_TTL) && $this->releaseLock($fh->path);
        case LOCK_EX:
            return $this->acquireLock($fh->path, self::LOCK_TTL);
        case LOCK_UN:
            return $this->releaseLock($fh->path);
        default:
            return true;
        }
        return true;
    }

    public function feof($fh) {
        if (!($fh instanceof FileHandle)) return false;
        $fh = $fh->getHandle();
        if (!$fh->isOpen) return false;
        return $fh->pos >= strlen($fh->content);
    }

    private function acquireLock($path, $ttl) {
        $startTime = microtime(true);
        while (false === ($result = $this->redis->set('lock:' . $path, time(), ['nx', 'px' => self::LOCK_TTL])) && microtime(true) - $startTime < self::LOCK_TIMEOUT) {
            usleep(50000);
        }
        return $result;
    }

    private function releaseLock($path) {
        return $this->redis->del('lock:' . $path);
    }
}
