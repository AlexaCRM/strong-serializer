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

use Closure;

/**
 * Describes how a certain piece of data should be transformed type-wise.
 */
class Reference {

    /**
     * Fully-qualified class name to convert the data into.
     */
    protected string $className;

    /**
     * Whether the referenced data should be constructed as a collection.
     */
    protected bool $isCollection = false;

    /**
     * Whether the referenced data should be constructed as a map.
     */
    protected bool $isMap = false;

    /**
     * The class field name to use as the map key.
     */
    protected ?string $mapKeyName = null;

    /**
     * The map of class field values to be cast.
     *
     * @var Reference[]
     */
    protected array $castMap = [];

    /**
     * Class resolver closure.
     *
     * @var Closure|null
     */
    protected ?Closure $classResolver = null;

    /**
     * Reference constructor.
     *
     * @param string|Closure $classOrResolver
     */
    public function __construct( $classOrResolver ) {
        if ( $classOrResolver instanceof Closure ) {
            $this->classResolver = $classOrResolver;
        } else {
            $this->className = $classOrResolver;
        }
    }

    /**
     * Creates a new type collection reference.
     */
    public function toCollection(): Reference {
        $ref = clone $this;

        $ref->isCollection = true;

        $ref->isMap = false;
        $ref->mapKeyName = null;

        return $ref;
    }

    /**
     * Creates a new type map with the given class field name as key.
     *
     * @param string $keyName
     *
     * @return Reference
     */
    public function toMap( string $keyName ): Reference {
        $ref = clone $this;

        $ref->isCollection = false;

        $ref->isMap = true;
        $ref->mapKeyName = $keyName;

        return $ref;
    }

    /**
     * Creates a strongly-typed object as specified during instantiation.
     *
     * This is the default state.
     */
    public function toObject(): Reference {
        $ref = clone $this;

        $ref->isCollection = false;
        $ref->isMap = false;
        $ref->mapKeyName = null;

        return $ref;
    }

    /**
     * Casts the specified field value according to reference rules.
     *
     * @param string $fieldName
     * @param Reference $targetRef
     *
     * @return Reference
     */
    public function addFieldCast( string $fieldName, Reference $targetRef ): Reference {
        $ref = clone $this;
        $ref->castMap[$fieldName] = $targetRef;

        return $ref;
    }

    /**
     * Returns the referenced class name, possibly based on provided data.
     *
     * @param $data
     *
     * @return string
     */
    public function getClassName( $data ): string {
        if ( $this->classResolver instanceof Closure ) {
            return $this->classResolver->call( $this, $data );
        }

        return $this->className;
    }

    /**
     * Returns the map of type casts for class fields.
     *
     * @return Reference[]
     */
    public function getCastMap(): array {
        return $this->castMap;
    }

    /**
     * Tells whether the referenced type should be enclosed into a collection.
     */
    public function isCollection(): bool {
        return $this->isCollection && !$this->isMap;
    }

    /**
     * Tells whether the referenced type should be enclosed into a map.
     */
    public function isMap(): bool {
        return $this->isMap && !$this->isCollection;
    }

    /**
     * Returns the class field to be used as the map key.
     */
    public function getMapKey(): ?string {
        return $this->mapKeyName;
    }

}
