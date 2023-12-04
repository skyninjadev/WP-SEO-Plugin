<?php
namespace NitroPack\SDK;

/**
 * Class Revision
 * @package NitroPack\WordPress
 */
class ElementRevision
{
    private $siteId;
    private $revisionFile;

    private $revisions;

    /**
     * Revision constructor.
     * @param $siteId
     * @param $dataDir
     */
    public function __construct($siteId, $revisionFile) {
        $this->siteId = $siteId;
        $this->revisionFile = $revisionFile;
        $this->revisions = [];
    }

    /**
     * @return string
     */
    public function get() {
        if (empty($this->revisions)) {
            $this->load();
        }

        if (!empty($this->revisions[$this->siteId])) {
            return $this->revisions[$this->siteId];
        }

        $this->save("nitro-" . substr(md5(microtime(true)), 0, 7));

        return $this->revisions[$this->siteId];
    }

    public function refresh() {
        $this->save('');
    }

    private function load() {
        try {
            if (Filesystem::fileExists($this->revisionFile)) {
                $this->revisions = json_decode(Filesystem::fileGetContents($this->revisionFile), true);
            } else {
                $this->revisions = [];
            }
        } catch (\Exception $e) {
            $this->revisions = [];
        }
    }

    /**
     * @param string $revision
     */
    private function save($revision) {
        $this->revisions[$this->siteId] = $revision;
        try {
            Filesystem::filePutContents($this->revisionFile, json_encode($this->revisions));
        } catch (\Exception $e) {
        }
    }
}
