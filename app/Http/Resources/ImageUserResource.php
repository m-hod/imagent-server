<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class ImageUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $baseUrl = config('filesystems.disks.digitalocean.endpoint');

        return [
            'id' => $this->id,
            'url' => "{$baseUrl}/imagent/{$this->hash}.{$this->ext}",
            'user_tags' => $this->getUserTags(),
            'tags' => $this->tags,
        ];
    }
}
