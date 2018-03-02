<?php

namespace Photogabble\LaravelRememberUploads;

use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\ParameterBag;

class RememberedFileBag extends ParameterBag
{
    /**
     * @param \Illuminate\Support\MessageBag $messageBag
     */
    public function filterFailedValidation($messageBag)
    {
        foreach($messageBag->keys() as $key) {
            $this->remove($key);
        }
    }

    /**
     * @return FileBag
     */
    public function toFileBag()
    {
        return new FileBag($this->allAsUploadFile($this->parameters));
    }

    /**
     * @param array|null $items
     * @return array
     */
    public function allAsUploadFile(array $items)
    {
        $result = [];

        foreach ($items as $key => $value) {
            if (is_array($value)){
                $result[$key] = $this->allAsUploadFile($value);
            } else {
                if ($value instanceof RememberedFile) {
                    $result[$key] = $value->toUploadedFile();
                }
            }
        }

        return $result;
    }
}