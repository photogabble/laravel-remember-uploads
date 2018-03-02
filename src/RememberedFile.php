<?php

namespace Photogabble\LaravelRememberUploads;

class RememberedFile
{
    /**
     * Current pathname for the remembered file.
     *
     * @var string
     */
    public $tmpPathname;

    /**
     * Original name of uploaded file.
     *
     * @var string
     */
    public $originalName;

    /**
     * Remembered file mime type.
     *
     * @var string
     */
    public $mimeType;

    /**
     * Remembered file size in bytes.
     *
     * @var int
     */
    public $size;

    /**
     * RememberedFile constructor.
     * @param string $tmpPathname
     * @param string $originalName
     * @param string $mimeType
     * @param int $size
     */
    public function __construct($tmpPathname, $originalName, $mimeType, $size)
    {
        $this->tmpPathname = $tmpPathname;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
    }
}