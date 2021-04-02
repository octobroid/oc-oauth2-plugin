<?php namespace Octobro\OAuth2\Behaviors;

use Laravel\Passport\HasApiTokens;

class Tokenable extends \October\Rain\Extension\ExtensionBase
{
    use HasApiTokens;
}