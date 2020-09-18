<?php
/**
 * Copyright 2019-2020 AlexaCRM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 * associated documentation files (the "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
 * OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace AlexaCRM\StrongSerializer;

/**
 * Converts generically-typed objects to strongly-typed objects using the provided map.
 */
class Deserializer {

    /**
     * FQCN => strongly-typed properties map.
     */
    protected array $map;

    /**
     * Deserializer constructor.
     *
     * @param array $map
     */
    public function __construct( array $map ) {
        $this->map = $map;
    }

    /**
     * Creates a strongly-typed object or a collection or map of such objects.
     *
     * It further casts certain fields to referenced types as described in the type definition.
     *
     * @param object|object[] $data Generic object with properties.
     * @param Reference $typeDefinition Type definition.
     *
     * @return array|object
     */
    public function deserialize( $data, Reference $typeDefinition ) {
        switch ( true ) {
            case $typeDefinition->isMap():
                $obj = $this->toStrongMap( $data, $typeDefinition );
                break;
            case $typeDefinition->isCollection():
                $obj = $this->toStrongCollection( $data, $typeDefinition );
                break;
            default:
                $obj = $this->toStrong( $data, $typeDefinition );
        }

        foreach ( $typeDefinition->getCastMap() as $fieldName => $ref ) {
            if ( !property_exists( $obj, $fieldName ) ) {
                continue;
            }

            $obj->{$fieldName} = $this->deserialize( $obj->{$fieldName}, $ref );
        }

        return $obj;
    }

    /**
     * Creates a strongly-typed object. $type holds the FQCN of the type.
     *
     * @param object
     * @param Reference $type
     *
     * @return object
     */
    protected function toStrong( $data, Reference $type ) {
        if ( $data === null ) {
            return null;
        }

        $className = $type->getClassName( $data );

        $obj = new $className();

        $typedProperties = $this->getTypedPropertyRefs( $className );

        foreach ( $data as $key => $value ) {
            if ( strpos( $key, '@' ) !== false ) {
                continue;
            }

            if ( !array_key_exists( $key, $typedProperties ) ) {
                $obj->{$key} = $value;
                continue;
            }

            $obj->{$key} = $this->deserialize( $value, $typedProperties[$key] );
        }

        return $obj;
    }

    /**
     * Creates a collection of strongly-typed objects.
     *
     * @param object[] $data
     * @param Reference $type
     *
     * @return array
     */
    protected function toStrongCollection( array $data, Reference $type ): array {
        $collection = [];

        foreach ( $data as $value ) {
            $collection[] = $this->toStrong( $value, $type );
        }

        return $collection;
    }

    /**
     * Creates a collection of strongly-typed objects enumerated by object key value.
     *
     * @param object[] $data
     * @param Reference $type
     *
     * @return array
     */
    protected function toStrongMap( array $data, Reference $type ): array {
        $collection = $this->toStrongCollection( $data, $type );

        $map = [];
        $keyName = $type->getMapKey();
        foreach ( $collection as $obj ) {
            $map[ $obj->{$keyName} ] = $obj;
        }

        return $map;
    }

    /**
     * Returns a map of type references for the given class including class hierarchy.
     *
     * @param string $className
     *
     * @return Reference[]
     */
    protected function getTypedPropertyRefs( string $className ): array {
        $classChain = class_parents( $className );
        if ( $classChain === false ) {
            $classChain = [];
        }

        $map = [];
        $classChain = array_reverse( array_values( $classChain ) );
        array_push( $classChain, $className );

        foreach ( $classChain as $typeName ) {
            if ( !array_key_exists( $typeName, $this->map ) ) {
                continue;
            }

            $map = array_merge( $map, $this->map[$typeName] );
        }

        return $map;
    }

}
