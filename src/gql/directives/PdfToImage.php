<?php

namespace bymayo\pdftransform\gql\directives;

use bymayo\pdftransform\PdfTransform;

use Craft;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Directive as GqlDirective;

class PdfToImage extends Directive
{

    public static function create(): GqlDirective
    {
        if ($type = GqlEntityRegistry::getEntity(self::name())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(static::name(), new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'description' => 'Transform a PDF page to an image (JPEG, PNG)',
            'args' => [],
        ]));

        return $type;
    }

    public static function name(): string
    {
        return 'pdfToImage';
    }

    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo): string
    {
        return PdfTransform::getInstance()->pdfTransformServices->url($value);
    }
}