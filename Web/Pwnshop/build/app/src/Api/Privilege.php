<?php
namespace App\Api;

use Attribute;

#[Attribute]
class Privilege {
    public function __construct(
        public array $permissions = []
    ) {}
} 